<?php

declare(strict_types=1);

namespace JuntaPlay\Front;

use WP_Error;
use WP_User;
use JuntaPlay\Admin\Settings;
use JuntaPlay\Notifications\EmailHelper;

use function __;
use function _n;
use function add_action;
use function add_filter;
use function add_query_arg;
use function admin_url;
use function apply_filters;
use function delete_transient;
use function array_key_exists;
use function delete_user_meta;
use function do_action;
use function email_exists;
use function esc_html;
use function esc_html__;
use function esc_url;
use function esc_url_raw;
use function explode;
use function get_bloginfo;
use function get_option;
use function get_permalink;
use function get_transient;
use function get_user_by;
use function get_user_meta;
use function home_url;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_email;
use function is_user_logged_in;
use function is_wp_error;
use function max;
use function min;
use function nocache_headers;
use function preg_replace;
use function rawurlencode;
use function remove_query_arg;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sanitize_user;
use function set_transient;
use function str_repeat;
use function strlen;
use function substr;
use function time;
use function trailingslashit;
use function trim;
use function update_user_meta;
use function user_can;
use function username_exists;
use function wp_authenticate_username_password;
use function wp_check_password;
use function wp_create_user;
use function wp_generate_password;
use function wp_generate_uuid4;
use function wp_get_current_user;
use function wp_hash_password;
use function wp_json_decode;
use function wp_login_url;
use function wp_logout;
use function wp_rand;
use function wp_remote_get;
use function wp_remote_post;
use function wp_remote_retrieve_body;
use function wp_redirect;
use function wp_safe_redirect;
use function wp_set_auth_cookie;
use function wp_set_current_user;
use function wp_signon;
use function wp_strip_all_tags;
use function wp_unslash;
use function wp_update_user;
use function wp_validate_redirect;
use function wp_verify_nonce;
use const MINUTE_IN_SECONDS;

if (!defined('ABSPATH')) {
    exit;
}

class Auth
{
    /** @var string[] */
    private array $login_errors = [];

    /** @var string[] */
    private array $register_errors = [];

    /** @var string[] */
    private array $two_factor_errors = [];

    /** @var array<string, mixed>|null */
    private ?array $two_factor_context = null;

    /**
     * Active view (login|register)
     */
    private string $active_view = 'login';

    /**
     * Track whether the current social flow is handled through a popup window.
     */
    private bool $current_social_popup = false;

    /**
     * Persist the tab (login/register) that initiated the current social flow.
     */
    private string $current_social_context = '';

    private const TWO_FACTOR_TRANSIENT_PREFIX = 'juntaplay_2fa_';
    private const SOCIAL_TRANSIENT_PREFIX     = 'juntaplay_social_';

    /**
     * Initialize hooks.
     */
    public function init(): void
    {
        add_action('init', [$this, 'detect_view']);
        add_action('init', [$this, 'maybe_handle_register']);
        add_action('init', [$this, 'maybe_handle_login']);
        add_action('init', [$this, 'maybe_handle_two_factor']);
        add_action('init', [$this, 'maybe_handle_social_login']);
        add_action('wp_ajax_nopriv_juntaplay_social', [$this, 'handle_social_ajax']);
        add_action('wp_ajax_juntaplay_social', [$this, 'handle_social_ajax']);
        add_filter('juntaplay/login/providers', [$this, 'filter_social_providers']);
        add_filter('login_redirect', [$this, 'force_profile_redirect'], 999, 3);
        add_filter('gettext', [$this, 'filter_login_text'], 10, 3);
        add_filter('login_errors', [$this, 'filter_login_errors']);
    }

    /**
     * Attempt to authenticate when the login form is submitted.
     */
    public function maybe_handle_login(): void
    {
        if (!isset($_POST['jp_login_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        $this->active_view = 'login';

        if (is_user_logged_in()) {
            $redirect = $this->get_redirect_url();
            if ($redirect) {
                wp_safe_redirect($redirect);
                exit;
            }
            return;
        }

        if (!isset($_POST['jp_login_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jp_login_nonce'])), 'juntaplay_login')) { // phpcs:ignore WordPress.Security.NonceVerification
            $this->login_errors[] = __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay');
            return;
        }

        $username = '';
        $password = '';
        $remember = false;

        if (isset($_POST['jp_login_username'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $username = sanitize_text_field(wp_unslash($_POST['jp_login_username'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        if (isset($_POST['jp_login_password'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $password = wp_unslash($_POST['jp_login_password']); // phpcs:ignore WordPress.Security.NonceVerification
        }

        if (isset($_POST['jp_login_remember'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $remember = (bool) sanitize_text_field(wp_unslash($_POST['jp_login_remember'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        if ($username === '' || $password === '') {
            $this->login_errors[] = __('Informe e-mail/usuário e senha para continuar.', 'juntaplay');
            return;
        }

        $user = wp_authenticate_username_password(null, $username, $password);

        if ($user instanceof WP_Error) {
            $this->login_errors[] = $user->get_error_message();
            return;
        }

        if (!($user instanceof WP_User)) {
            $this->login_errors[] = __('Não foi possível autenticar no momento. Tente novamente.', 'juntaplay');
            return;
        }

        $redirect = $this->get_redirect_url();

        if ($this->is_site_admin($user)) {
            $redirect = admin_url();
        } elseif ($redirect !== '') {
            $redirect = $this->sanitize_redirect($redirect, $user);
        } else {
            $redirect = $this->get_default_redirect($user);
        }

        if ($this->should_require_two_factor($user)) {
            $challenge = $this->start_two_factor_challenge($user, $remember, $redirect, null, []);

            if (!$challenge) {
                $this->login_errors[] = __('Não foi possível gerar o código de verificação. Tente novamente.', 'juntaplay');
                return;
            }

            wp_safe_redirect($this->build_two_factor_url($challenge));
            exit;
        }

        $signon = wp_signon(
            [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ],
            false
        );

        if ($signon instanceof WP_Error) {
            $this->login_errors[] = $signon->get_error_message();
            return;
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function maybe_handle_two_factor(): void
    {
        $challenge = '';

        if (isset($_GET['challenge'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $challenge = sanitize_text_field(wp_unslash($_GET['challenge'])); // phpcs:ignore WordPress.Security.NonceVerification
            $this->load_two_factor_context($challenge);
        }

        if (!isset($_POST['jp_two_factor_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        $action = sanitize_key(wp_unslash($_POST['jp_two_factor_action'])); // phpcs:ignore WordPress.Security.NonceVerification
        if (isset($_POST['jp_two_factor_challenge'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $challenge = sanitize_text_field(wp_unslash($_POST['jp_two_factor_challenge'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        if (!isset($_POST['jp_two_factor_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jp_two_factor_nonce'])), 'juntaplay_two_factor')) { // phpcs:ignore WordPress.Security.NonceVerification
            $this->two_factor_errors[] = __('Sua sessão expirou. Faça login novamente.', 'juntaplay');
            return;
        }

        if ($action === 'resend') {
            $this->handle_two_factor_resend($challenge);

            return;
        }

        $code = isset($_POST['jp_two_factor_code'])
            ? sanitize_text_field(wp_unslash($_POST['jp_two_factor_code'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $this->handle_two_factor_verify($challenge, $code);
    }

    /**
     * Return any captured errors.
     *
     * @return string[]
     */
    public function get_login_errors(): array
    {
        return $this->login_errors;
    }

    /**
     * @return string[]
     */
    public function get_two_factor_errors(): array
    {
        return $this->two_factor_errors;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_two_factor_context(): array
    {
        return $this->two_factor_context ?? [];
    }

    /**
     * Retrieve register errors.
     *
     * @return string[]
     */
    public function get_register_errors(): array
    {
        return $this->register_errors;
    }

    /**
     * Current active view for the auth screen.
     */
    public function get_active_view(): string
    {
        return $this->active_view;
    }

    /**
     * Retrieve the redirect destination from the request.
     */
    public function get_redirect_url(): string
    {
        $redirect = isset($_REQUEST['redirect_to']) ? wp_unslash($_REQUEST['redirect_to']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $validated = wp_validate_redirect($redirect, '');

        return is_string($validated) ? $validated : '';
    }

    /**
     * Capture the initial desired view (login/register) from the request.
     */
    public function detect_view(): void
    {
        $view = '';
        if (isset($_REQUEST['jp_auth_view'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $view = sanitize_key(wp_unslash($_REQUEST['jp_auth_view'])); // phpcs:ignore WordPress.Security.NonceVerification
        } elseif (isset($_GET['action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $view = sanitize_key(wp_unslash($_GET['action'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        $social_view = '';
        if (isset($_GET['jp_social_view'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $social_view = $this->sanitize_auth_context((string) wp_unslash($_GET['jp_social_view'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        if ($view === 'register' || $social_view === 'register') {
            $this->active_view = 'register';
        }

        if ($social_view !== '') {
            $this->current_social_context = $social_view;
        }

        if (isset($_GET['jp_social_error'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $message = sanitize_textarea_field(wp_unslash($_GET['jp_social_error'])); // phpcs:ignore WordPress.Security.NonceVerification
            if ($message !== '') {
                if ($social_view === 'register') {
                    $this->register_errors[] = $message;
                    $this->active_view       = 'register';
                } else {
                    $this->login_errors[] = $message;
                    $this->active_view    = 'login';
                }
            }
        }
    }

    /**
     * Handle customer registration when the form is submitted.
     */
    public function maybe_handle_register(): void
    {
        if (!isset($_POST['jp_register_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        $this->active_view = 'register';

        if (!get_option('users_can_register')) {
            $this->register_errors[] = __('No momento não é possível criar novas contas.', 'juntaplay');
            return;
        }

        if (!isset($_POST['jp_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['jp_register_nonce'])), 'juntaplay_register')) { // phpcs:ignore WordPress.Security.NonceVerification
            $this->register_errors[] = __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay');
            return;
        }

        $name      = isset($_POST['jp_register_name']) ? sanitize_text_field(wp_unslash($_POST['jp_register_name'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $email     = isset($_POST['jp_register_email']) ? sanitize_email(wp_unslash($_POST['jp_register_email'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $password  = isset($_POST['jp_register_password']) ? (string) wp_unslash($_POST['jp_register_password']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $confirm   = isset($_POST['jp_register_password_confirm']) ? (string) wp_unslash($_POST['jp_register_password_confirm']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $accept    = isset($_POST['jp_register_accept']) ? (bool) sanitize_text_field(wp_unslash($_POST['jp_register_accept'])) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $redirect  = $this->get_redirect_url();

        if ($name === '') {
            $this->register_errors[] = __('Informe seu nome completo.', 'juntaplay');
        }

        if ($email === '' || !is_email($email)) {
            $this->register_errors[] = __('Informe um e-mail válido.', 'juntaplay');
        }

        if ($email && email_exists($email)) {
            $this->register_errors[] = __('Este e-mail já está cadastrado. Faça login ou utilize a recuperação de senha.', 'juntaplay');
        }

        if ($password === '' || strlen($password) < 6) {
            $this->register_errors[] = __('Defina uma senha com pelo menos 6 caracteres.', 'juntaplay');
        }

        if ($password !== $confirm) {
            $this->register_errors[] = __('As senhas informadas não coincidem.', 'juntaplay');
        }

        $acceptance_required = (bool) apply_filters('juntaplay/register/require_accept', true);
        if ($acceptance_required && !$accept) {
            $this->register_errors[] = __('Confirme que leu e aceita os termos para continuar.', 'juntaplay');
        }

        if (!empty($this->register_errors)) {
            return;
        }

        $username = sanitize_user(current(explode('@', $email)), true);
        if ($username === '') {
            $username = sanitize_user($email, true);
        }

        $original_username = $username;
        $suffix            = 1;

        while (username_exists($username)) {
            $username = $original_username . $suffix;
            $suffix++;
        }

        $user_id = wp_create_user($username, $password, $email);

        if ($user_id instanceof WP_Error) {
            $this->register_errors[] = $user_id->get_error_message();
            return;
        }

        $updated = wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name,
        ]);

        if ($updated instanceof WP_Error) {
            $this->register_errors[] = $updated->get_error_message();
            return;
        }

        do_action('juntaplay/register/success', $user_id);

        $signon = wp_signon([
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        ], false);

        if ($signon instanceof WP_Error) {
            $this->login_errors[] = $signon->get_error_message();
            $this->active_view    = 'login';
            return;
        }

        if ($redirect !== '') {
            $redirect = $this->sanitize_redirect($redirect, $signon);
        } else {
            $redirect = $this->get_default_redirect($signon);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    private function should_require_two_factor(WP_User $user): bool
    {
        $method = $this->get_two_factor_method($user);

        if (!in_array($method, ['email', 'whatsapp'], true)) {
            return false;
        }

        return (bool) apply_filters('juntaplay/login/require_two_factor', true, $user, $method);
    }

    private function get_two_factor_method(WP_User $user): string
    {
        $method = (string) get_user_meta($user->ID, 'juntaplay_two_factor_method', true);

        if (!in_array($method, ['email', 'whatsapp'], true)) {
            $method = 'email';
        }

        return $method;
    }

    private function start_two_factor_challenge(WP_User $user, bool $remember, string $redirect, ?string $existing = null, array $extra_state = []): ?string
    {
        $method      = $this->get_two_factor_method($user);
        $destination = $this->resolve_two_factor_destination($user, $method);

        if ($destination['display'] === '' && $destination['email'] === '') {
            $this->two_factor_errors[] = __('Configure um e-mail ou telefone válido para receber o código de verificação.', 'juntaplay');

            return null;
        }

        $code    = (string) wp_rand(100000, 999999);
        $hash    = wp_hash_password($code);
        $expires = time() + (int) apply_filters('juntaplay/two_factor/expiration', MINUTE_IN_SECONDS * 10, $user);

        update_user_meta($user->ID, 'juntaplay_two_factor_login_hash', $hash);
        update_user_meta($user->ID, 'juntaplay_two_factor_login_expires', $expires);
        update_user_meta($user->ID, 'juntaplay_two_factor_login_attempts', 0);

        $challenge = $existing ?: wp_generate_uuid4();

        $state = [
            'user_id'     => $user->ID,
            'remember'    => $remember,
            'redirect'    => $this->sanitize_redirect($redirect, $user),
            'method'      => $method,
            'destination' => $destination['display'],
            'target'      => $destination['target'],
            'email'       => $destination['email'],
            'issued_at'   => time(),
            'expires'     => $expires,
        ];

        if ($extra_state !== []) {
            $state = $this->append_challenge_state($state, $extra_state);
        }

        if (!$this->deliver_two_factor_code($user, $method, $code, $state)) {
            $this->two_factor_errors[] = __('Não foi possível enviar o código de verificação agora. Tente novamente em instantes.', 'juntaplay');
            delete_user_meta($user->ID, 'juntaplay_two_factor_login_hash');
            delete_user_meta($user->ID, 'juntaplay_two_factor_login_expires');
            delete_user_meta($user->ID, 'juntaplay_two_factor_login_attempts');

            return null;
        }

        set_transient(self::TWO_FACTOR_TRANSIENT_PREFIX . $challenge, $state, MINUTE_IN_SECONDS * 10);
        $this->assign_two_factor_context($state, $challenge);

        return $challenge;
    }

    public function begin_two_factor_challenge(WP_User $user, string $redirect, array $extra_state = []): ?string
    {
        return $this->start_two_factor_challenge($user, false, $redirect, null, $extra_state);
    }

    public function get_two_factor_url(string $challenge): string
    {
        return $this->build_two_factor_url($challenge);
    }

    private function build_two_factor_url(string $challenge): string
    {
        $page_id = (int) get_option('juntaplay_page_verificar-acesso');
        $base    = $page_id ? get_permalink($page_id) : home_url('/verificar-acesso');

        if (!$base) {
            $base = home_url('/verificar-acesso');
        }

        return add_query_arg('challenge', rawurlencode($challenge), $base);
    }

    private function append_challenge_state(array $state, array $extra): array
    {
        $extra = $this->sanitize_state_array($extra);

        foreach ($extra as $key => $value) {
            if ($key === '' || array_key_exists($key, $state)) {
                continue;
            }

            $state[$key] = $value;
        }

        return $state;
    }

    /**
     * @param array<string|int, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitize_state_array(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $normalized_key = is_int($key) ? (string) $key : sanitize_key((string) $key);

            if ($normalized_key === '') {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$normalized_key] = $this->sanitize_state_array($value);

                continue;
            }

            if (is_bool($value)) {
                $sanitized[$normalized_key] = $value;

                continue;
            }

            if (is_int($value) || is_float($value)) {
                $sanitized[$normalized_key] = $value + 0;

                continue;
            }

            if ($value === null) {
                $sanitized[$normalized_key] = '';

                continue;
            }

            $sanitized[$normalized_key] = sanitize_text_field((string) $value);
        }

        return $sanitized;
    }

    private function load_two_factor_context(string $challenge): void
    {
        $state = $this->get_two_factor_state($challenge);

        if (!$state) {
            $this->two_factor_context = null;

            return;
        }

        $this->assign_two_factor_context($state, $challenge);
    }

    private function handle_two_factor_resend(string $challenge): void
    {
        $state = $this->get_two_factor_state($challenge);

        if (!$state) {
            $this->two_factor_errors[] = __('Sua sessão de verificação expirou. Faça login novamente.', 'juntaplay');

            return;
        }

        $user = get_user_by('id', (int) $state['user_id']);
        if (!$user instanceof WP_User) {
            $this->two_factor_errors[] = __('Não foi possível identificar sua conta. Faça login novamente.', 'juntaplay');

            return;
        }

        $challenge = $this->start_two_factor_challenge($user, !empty($state['remember']), (string) ($state['redirect'] ?? ''), $challenge, $state);

        if ($challenge) {
            $context               = $this->two_factor_context ?? [];
            $context['resent']     = true;
            $this->two_factor_context = $context;
        }
    }

    private function handle_two_factor_verify(string $challenge, string $code): void
    {
        $state = $this->get_two_factor_state($challenge);

        if (!$state) {
            $this->two_factor_errors[] = __('Sua sessão de verificação expirou. Faça login novamente.', 'juntaplay');

            return;
        }

        $user = get_user_by('id', (int) $state['user_id']);
        if (!$user instanceof WP_User) {
            $this->two_factor_errors[] = __('Não foi possível identificar sua conta. Faça login novamente.', 'juntaplay');

            return;
        }

        $clean_code = preg_replace('/\D+/', '', $code);
        if ($clean_code === '' || strlen($clean_code) < 4) {
            $this->two_factor_errors[] = __('Informe o código recebido para continuar.', 'juntaplay');
            $this->assign_two_factor_context($state, $challenge);

            return;
        }

        $hash     = (string) get_user_meta($user->ID, 'juntaplay_two_factor_login_hash', true);
        $expires  = (int) get_user_meta($user->ID, 'juntaplay_two_factor_login_expires', true);
        $attempts = (int) get_user_meta($user->ID, 'juntaplay_two_factor_login_attempts', true);

        if ($hash === '' || $expires <= time()) {
            $this->two_factor_errors[] = __('Este código expirou. Solicitamos um novo automaticamente.', 'juntaplay');
            $this->handle_two_factor_resend($challenge);

            return;
        }

        if (!wp_check_password($clean_code, $hash)) {
            ++$attempts;
            update_user_meta($user->ID, 'juntaplay_two_factor_login_attempts', $attempts);

            $this->two_factor_errors[] = __('Código inválido. Verifique e tente novamente.', 'juntaplay');
            $this->assign_two_factor_context($state, $challenge, ['attempts' => $attempts]);

            return;
        }

        delete_user_meta($user->ID, 'juntaplay_two_factor_login_hash');
        delete_user_meta($user->ID, 'juntaplay_two_factor_login_expires');
        delete_user_meta($user->ID, 'juntaplay_two_factor_login_attempts');
        delete_transient(self::TWO_FACTOR_TRANSIENT_PREFIX . $challenge);

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, !empty($state['remember']));
        do_action('wp_login', $user->user_login, $user);

        $redirect = isset($state['redirect']) ? (string) $state['redirect'] : '';

        if ($redirect === '') {
            $redirect = $this->get_default_redirect($user);
        }

        $redirect = $this->sanitize_redirect($redirect, $user);

        /**
         * Fires after the user successfully confirms the two-factor challenge.
         *
         * @param WP_User               $user  Authenticated user instance.
         * @param array<string, mixed>  $state Challenge state data.
         */
        do_action('juntaplay/two_factor/success', $user, $state);
        do_action('juntaplay_2fa_success', $user, $state);

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function deliver_two_factor_code(WP_User $user, string $method, string $code, array $state): bool
    {
        $minutes   = (int) apply_filters('juntaplay/two_factor/expiration_minutes', 10, $user);
        $site_name = get_bloginfo('name');
        $destination_label = $state['destination'] !== '' ? $state['destination'] : __('seu contato cadastrado', 'juntaplay');
        $email     = $state['email'] !== '' ? (string) $state['email'] : (string) $user->user_email;

        $subject = sprintf(__('Código de verificação — %s', 'juntaplay'), $site_name);
        $blocks  = [
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Use o código %1$s para confirmar seu acesso ao %2$s.', 'juntaplay'), $code, $site_name),
            ],
            [
                'type'    => 'code',
                'content' => $code,
            ],
            [
                'type'    => 'paragraph',
                'content' => sprintf(_n('Ele expira em %d minuto.', 'Ele expira em %d minutos.', $minutes, 'juntaplay'), $minutes),
            ],
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Enviamos para: %s', 'juntaplay'), $destination_label),
            ],
            [
                'type'    => 'paragraph',
                'content' => __('Se não foi você, ignore esta mensagem.', 'juntaplay'),
            ],
        ];

        $sent = true;
        if ($email !== '') {
            $sent = EmailHelper::send(
                $email,
                $subject,
                $blocks,
                [
                    'headline'  => __('Confirme seu acesso', 'juntaplay'),
                    'preheader' => sprintf(__('Seu código expira em %d minutos.', 'juntaplay'), $minutes),
                ]
            );
        }

        if ($method === 'whatsapp') {
            do_action('juntaplay/two_factor/whatsapp', $user->ID, $code, $state);
        }

        return (bool) $sent;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get_two_factor_state(string $challenge): ?array
    {
        if ($challenge === '') {
            return null;
        }

        $state = get_transient(self::TWO_FACTOR_TRANSIENT_PREFIX . $challenge);

        if (!is_array($state) || empty($state['user_id'])) {
            return null;
        }

        return $state;
    }

    /**
     * @param array<string, mixed> $state
     */
    private function assign_two_factor_context(array $state, string $challenge, array $extra = []): void
    {
        $context            = array_merge($state, $extra);
        $context['challenge'] = $challenge;
        unset($context['target'], $context['email'], $context['user_id']);
        $this->two_factor_context = $context;
    }

    /**
     * @return array{display: string, target: string, email: string}
     */
    private function resolve_two_factor_destination(WP_User $user, string $method): array
    {
        $email  = (string) $user->user_email;
        $target = '';
        $display = '';

        if ($method === 'whatsapp') {
            $phone = (string) get_user_meta($user->ID, 'juntaplay_whatsapp', true);
            if ($phone === '') {
                $phone = (string) get_user_meta($user->ID, 'billing_phone', true);
            }

            if ($phone !== '') {
                $target  = $phone;
                $display = $this->mask_phone($phone);
            }
        }

        if ($display === '' && $email !== '') {
            $display = $this->mask_email($email);
        }

        return [
            'display' => $display,
            'target'  => $target,
            'email'   => $email,
        ];
    }

    private function mask_email(string $email): string
    {
        if (!is_email($email)) {
            return $email;
        }

        [$user_part, $domain] = explode('@', $email, 2);
        $user_part = trim($user_part);

        if ($user_part === '') {
            return $email;
        }

        $visible = substr($user_part, 0, min(2, strlen($user_part)));
        $mask    = str_repeat('•', max(1, strlen($user_part) - strlen($visible)));

        return $visible . $mask . '@' . $domain;
    }

    private function mask_phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return $phone;
        }

        $length = strlen($digits);
        $prefix = substr($digits, 0, min(2, $length - 4));
        $suffix = substr($digits, -4);
        $mask   = str_repeat('•', max(0, $length - strlen($prefix) - strlen($suffix)));

        return $prefix . $mask . $suffix;
    }

    private function sanitize_redirect(string $redirect, ?WP_User $user = null): string
    {
        if (!$user instanceof WP_User) {
            $user = wp_get_current_user();
        }

        $is_admin = $user instanceof WP_User && $this->is_site_admin($user);

        if ($user instanceof WP_User && !$is_admin) {
            return $this->get_profile_redirect();
        }

        $default = $is_admin ? admin_url() : $this->get_profile_redirect();
        $validated = wp_validate_redirect($redirect, $default);

        if (!is_string($validated) || $validated === '') {
            return $default;
        }

        return $validated;
    }

    public function maybe_handle_social_login(): void
    {
        if (!isset($_GET['juntaplay_social'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $provider = sanitize_key(wp_unslash($_GET['juntaplay_social'])); // phpcs:ignore WordPress.Security.NonceVerification

        if ($provider === '' || is_user_logged_in()) {
            return;
        }

        $popup = $this->is_popup_request();
        $this->current_social_popup = $popup;

        $context = '';
        if (isset($_REQUEST['context'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $context = $this->sanitize_auth_context((string) wp_unslash($_REQUEST['context'])); // phpcs:ignore WordPress.Security.NonceVerification
        }
        $this->current_social_context = $context;

        if (!$this->is_social_enabled($provider)) {
            $this->redirect_with_error(__('Login social indisponível no momento.', 'juntaplay'));
        }

        if (isset($_GET['callback'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $this->handle_social_callback($provider);

            return;
        }

        $redirect = $this->get_redirect_url();
        if (!$redirect) {
            $redirect = $this->get_default_redirect();
        }

        $this->start_social_flow($provider, $redirect, false, $popup, $context);
    }

    public function handle_social_ajax(): void
    {
        nocache_headers();

        $provider = isset($_REQUEST['provider']) ? sanitize_key((string) wp_unslash($_REQUEST['provider'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ($provider === '') {
            $this->current_social_popup   = $this->is_popup_request();
            $this->current_social_context = isset($_REQUEST['context'])
                ? $this->sanitize_auth_context((string) wp_unslash($_REQUEST['context']))
                : '';

            $this->redirect_with_error(__('Provedor de login inválido.', 'juntaplay'));
        }

        if (is_user_logged_in()) {
            wp_safe_redirect($this->get_default_redirect());
            exit;
        }

        $popup   = $this->is_popup_request();
        $context = '';

        if (isset($_REQUEST['context'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $context = $this->sanitize_auth_context((string) wp_unslash($_REQUEST['context'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        $this->current_social_popup   = $popup;
        $this->current_social_context = $context;

        if (isset($_REQUEST['callback'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $this->handle_social_callback($provider);

            return;
        }

        if (!$this->is_social_enabled($provider)) {
            $this->redirect_with_error(__('Login social indisponível no momento.', 'juntaplay'));
        }

        $redirect = '';
        if (isset($_REQUEST['redirect_to'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $redirect = esc_url_raw((string) wp_unslash($_REQUEST['redirect_to'])); // phpcs:ignore WordPress.Security.NonceVerification
        }

        if ($redirect === '') {
            $redirect = $this->get_redirect_url();
        }

        if ($redirect === '') {
            $redirect = $this->get_default_redirect();
        }

        $remember = false;
        if (isset($_REQUEST['remember'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $remember_value = strtolower((string) sanitize_text_field(wp_unslash($_REQUEST['remember']))); // phpcs:ignore WordPress.Security.NonceVerification
            $remember       = in_array($remember_value, ['1', 'true', 'yes'], true);
        }

        $this->start_social_flow($provider, $redirect, $remember, $popup, $context);
    }

    /**
     * @param array<int, array<string, mixed>> $providers
     * @return array<int, array<string, mixed>>
     */
    public function filter_social_providers(array $providers): array
    {
        $output = [];

        foreach ($providers as $provider) {
            $key = isset($provider['key']) ? sanitize_key((string) $provider['key']) : '';

            if ($key === '') {
                continue;
            }

            $enabled = $this->is_social_enabled($key);

            if ($enabled) {
                $provider['href'] = esc_url($this->get_social_login_url($key));
                $provider['disabled'] = false;
            } else {
                $provider['href'] = '#';
                $provider['disabled'] = true;
            }

            $output[] = $provider;
        }

        return $output;
    }

    private function start_social_flow(string $provider, string $redirect, bool $remember, bool $popup = false, string $context = ''): void
    {
        $settings = $this->get_provider_settings($provider);

        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            $this->redirect_with_error(__('Configuração do provedor ausente. Informe as credenciais no painel.', 'juntaplay'));
        }

        $callback = $this->get_social_callback_url($provider, $popup);
        $state    = wp_generate_uuid4();

        set_transient(self::SOCIAL_TRANSIENT_PREFIX . $state, [
            'provider' => $provider,
            'redirect' => $this->sanitize_redirect($redirect),
            'remember' => $remember,
            'popup'    => $popup ? '1' : '',
            'context'  => $context,
        ], MINUTE_IN_SECONDS * 10);

        $authorize = $this->build_social_authorize_url($provider, $settings, $callback, $state);

        if ($authorize === '') {
            $this->redirect_with_error(__('Não foi possível iniciar o login social.', 'juntaplay'));
        }

        wp_redirect($authorize);
        exit;
    }

    private function handle_social_callback(string $provider): void
    {
        $state_key = isset($_GET['state']) ? sanitize_text_field(wp_unslash($_GET['state'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ($state_key === '') {
            $this->redirect_with_error(__('Sua sessão expirou. Tente novamente.', 'juntaplay'));
        }

        $state = get_transient(self::SOCIAL_TRANSIENT_PREFIX . $state_key);
        delete_transient(self::SOCIAL_TRANSIENT_PREFIX . $state_key);

        if (!is_array($state) || ($state['provider'] ?? '') !== $provider) {
            $this->redirect_with_error(__('Solicitação inválida. Tente novamente.', 'juntaplay'));
        }

        if (isset($_GET['error'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $reason = isset($_GET['error_description']) ? sanitize_text_field(wp_unslash($_GET['error_description'])) : sanitize_text_field(wp_unslash($_GET['error'])); // phpcs:ignore WordPress.Security.NonceVerification
            $this->redirect_with_error($reason !== '' ? $reason : __('Autorização cancelada pelo provedor.', 'juntaplay'));
        }

        $code = isset($_GET['code']) ? sanitize_text_field(wp_unslash($_GET['code'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ($code === '') {
            $this->redirect_with_error(__('Não recebemos o código de autorização. Tente novamente.', 'juntaplay'));
        }

        $settings = $this->get_provider_settings($provider);
        $callback = $this->get_social_callback_url($provider, !empty($state['popup']));
        $token    = $this->exchange_social_token($provider, $settings, $code, $callback);

        if (!$token) {
            $this->redirect_with_error(__('Não foi possível validar o token de acesso.', 'juntaplay'));
        }

        $profile = $this->fetch_social_profile($provider, $settings, $token);

        if (!$profile) {
            $this->redirect_with_error(__('Não conseguimos obter seus dados de perfil.', 'juntaplay'));
        }

        $redirect = isset($state['redirect']) ? (string) $state['redirect'] : $this->get_default_redirect();
        $remember = !empty($state['remember']);
        $popup    = !empty($state['popup']);
        $context  = isset($state['context']) ? $this->sanitize_auth_context((string) $state['context']) : '';

        $this->current_social_popup = $popup;
        $this->current_social_context = $context;

        $this->complete_social_login($provider, $profile, $redirect, $remember, $popup, $context);
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function complete_social_login(string $provider, array $profile, string $redirect, bool $remember, bool $popup = false, string $context = ''): void
    {
        $email = isset($profile['email']) ? sanitize_email((string) $profile['email']) : '';

        if ($email === '') {
            $this->redirect_with_error(__('Não recebemos seu e-mail do provedor. Verifique as permissões e tente novamente.', 'juntaplay'));
        }

        $user = get_user_by('email', $email);

        if (!$user) {
            $login    = sanitize_user(current(explode('@', $email)), true);
            $original = $login !== '' ? $login : sanitize_user($email, true);

            if ($original === '') {
                $original = 'user';
            }

            $suffix = 1;
            $login  = $original;

            while (username_exists($login)) {
                $login = $original . $suffix;
                ++$suffix;
            }

            $user_id = wp_create_user($login, wp_generate_password(20, true), $email);

            if ($user_id instanceof WP_Error) {
                $this->redirect_with_error($user_id->get_error_message());
            }

            $display = isset($profile['name']) ? trim((string) $profile['name']) : '';
            if ($display !== '') {
                wp_update_user([
                    'ID'           => (int) $user_id,
                    'display_name' => $display,
                    'first_name'   => $display,
                ]);
            }

            $user = get_user_by('id', (int) $user_id);
        }

        if (!$user instanceof WP_User) {
            $this->redirect_with_error(__('Não foi possível concluir o login. Tente novamente.', 'juntaplay'));
        }

        $meta_key = 'juntaplay_social_' . $provider . '_id';
        if (!empty($profile['id'])) {
            update_user_meta($user->ID, $meta_key, (string) $profile['id']);
        }

        if (!empty($profile['avatar'])) {
            update_user_meta($user->ID, 'juntaplay_social_' . $provider . '_avatar', esc_url_raw((string) $profile['avatar']));
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        do_action('wp_login', $user->user_login, $user);

        if ($redirect === '') {
            $redirect = $this->get_default_redirect($user);
        }

        $redirect = $this->sanitize_redirect($redirect, $user);

        if ($popup) {
            $this->render_social_popup_success($redirect, $context);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    private function get_social_login_url(string $provider): string
    {
        $args = [
            'action'   => 'juntaplay_social',
            'provider' => $provider,
        ];

        if (isset($_REQUEST['redirect_to'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $redirect           = sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])); // phpcs:ignore WordPress.Security.NonceVerification
            $args['redirect_to'] = rawurlencode($redirect);
        }

        return add_query_arg($args, admin_url('admin-ajax.php'));
    }

    private function get_social_callback_url(string $provider, bool $popup = false): string
    {
        $args = [
            'action'   => 'juntaplay_social',
            'provider' => $provider,
            'callback' => '1',
        ];

        if ($popup) {
            $args['popup'] = '1';
        }

        return add_query_arg($args, admin_url('admin-ajax.php'));
    }

    /**
     * @return array<string, mixed>
     */
    private function get_social_settings(): array
    {
        $settings = get_option(Settings::OPTION_SOCIAL, []);

        return is_array($settings) ? $settings : [];
    }

    public function filter_login_text(string $translated, string $text, string $domain): string
    {
        if ($domain !== 'default') {
            return $translated;
        }

        switch ($text) {
            case 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.':
                return __('Perdeu sua senha? Digite seu nome de usuário ou e-mail. Você receberá um link por e-mail para criar uma nova senha.', 'juntaplay');
            case 'Lost your password?':
                return __('Perdeu sua senha?', 'juntaplay');
            case 'Username or Email Address':
            case 'Username or Email':
            case 'Username or email':
                return __('Usuário ou e-mail', 'juntaplay');
            default:
                return $translated;
        }
    }

    public function filter_login_errors(string $errors): string
    {
        if ($errors === '') {
            return $errors;
        }

        $normalized = str_replace(['<br />', '<br>'], "\n", $errors);
        $normalized = html_entity_decode($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = wp_strip_all_tags($normalized, false);
        $lines      = array_filter(array_map('trim', explode("\n", $normalized)), static function ($line): bool {
            return $line !== '';
        });

        $cleaned = implode('<br />', $lines);

        return $cleaned !== '' ? $cleaned : trim($normalized);
    }

    private function is_social_enabled(string $provider): bool
    {
        $settings = $this->get_provider_settings($provider);

        if (!empty($settings['enabled'])) {
            return true;
        }

        $client_id     = isset($settings['client_id']) ? trim((string) $settings['client_id']) : '';
        $client_secret = isset($settings['client_secret']) ? trim((string) $settings['client_secret']) : '';

        return $client_id !== '' && $client_secret !== '';
    }

    /**
     * @return array<string, mixed>
     */
    private function get_provider_settings(string $provider): array
    {
        $settings = $this->get_social_settings();

        return isset($settings[$provider]) && is_array($settings[$provider]) ? $settings[$provider] : [];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function build_social_authorize_url(string $provider, array $settings, string $callback, string $state): string
    {
        $client_id = isset($settings['client_id']) ? (string) $settings['client_id'] : '';

        if ($client_id === '') {
            return '';
        }

        if ($provider === 'google') {
            $scopes = rawurlencode('openid email profile');

            return sprintf(
                'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=%1$s&redirect_uri=%2$s&scope=%3$s&state=%4$s&prompt=select_account',
                rawurlencode($client_id),
                rawurlencode($callback),
                $scopes,
                rawurlencode($state)
            );
        }

        if ($provider === 'facebook') {
            return sprintf(
                'https://www.facebook.com/v16.0/dialog/oauth?client_id=%1$s&redirect_uri=%2$s&state=%3$s&scope=email',
                rawurlencode($client_id),
                rawurlencode($callback),
                rawurlencode($state)
            );
        }

        return '';
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>|null
     */
    private function exchange_social_token(string $provider, array $settings, string $code, string $callback): ?array
    {
        $client_id     = isset($settings['client_id']) ? (string) $settings['client_id'] : '';
        $client_secret = isset($settings['client_secret']) ? (string) $settings['client_secret'] : '';

        if ($client_id === '' || $client_secret === '') {
            return null;
        }

        if ($provider === 'google') {
            $response = wp_remote_post('https://oauth2.googleapis.com/token', [
                'timeout' => 15,
                'body'    => [
                    'code'          => $code,
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'redirect_uri'  => $callback,
                    'grant_type'    => 'authorization_code',
                ],
            ]);
        } else {
            $response = wp_remote_get(add_query_arg([
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $callback,
                'code'          => $code,
            ], 'https://graph.facebook.com/v16.0/oauth/access_token'), [
                'timeout' => 15,
            ]);
        }

        if (is_wp_error($response)) {
            return null;
        }

        $data = wp_json_decode((string) wp_remote_retrieve_body($response), true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $token
     * @return array<string, mixed>|null
     */
    private function fetch_social_profile(string $provider, array $settings, array $token): ?array
    {
        $access_token = isset($token['access_token']) ? (string) $token['access_token'] : '';

        if ($access_token === '') {
            return null;
        }

        if ($provider === 'google') {
            $response = wp_remote_get('https://www.googleapis.com/oauth2/v3/userinfo', [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                ],
            ]);
        } else {
            $url      = add_query_arg([
                'fields'       => 'id,name,email,picture.type(large)',
                'access_token' => $access_token,
            ], 'https://graph.facebook.com/me');
            $response = wp_remote_get($url, [
                'timeout' => 15,
            ]);
        }

        if (is_wp_error($response)) {
            return null;
        }

        $data = wp_json_decode((string) wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            return null;
        }

        if ($provider === 'facebook' && isset($data['picture']['data']['url'])) {
            $data['avatar'] = $data['picture']['data']['url'];
        }

        return $data;
    }

    private function redirect_with_error(string $message): void
    {
        if ($this->is_popup_request()) {
            $this->render_social_popup_error($message, $this->current_social_context);
        }

        $login_url = add_query_arg('jp_social_error', rawurlencode($message), $this->get_login_page_url());
        if ($this->current_social_context !== '') {
            $login_url = add_query_arg('jp_social_view', rawurlencode($this->current_social_context), $login_url);
        }
        wp_safe_redirect($login_url);
        exit;
    }

    private function is_popup_request(): bool
    {
        if (isset($_REQUEST['popup'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $value = strtolower((string) sanitize_text_field(wp_unslash($_REQUEST['popup']))); // phpcs:ignore WordPress.Security.NonceVerification

            return in_array($value, ['1', 'true', 'yes'], true);
        }

        return $this->current_social_popup;
    }

    private function sanitize_auth_context(string $context): string
    {
        $normalized = sanitize_key($context);

        return in_array($normalized, ['login', 'register'], true) ? $normalized : '';
    }

    private function render_social_popup_success(string $redirect, string $context = ''): void
    {
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');

        $payload = [
            'source'   => 'juntaplay-social',
            'status'   => 'success',
            'redirect' => $redirect,
        ];

        if ($context !== '') {
            $payload['context'] = $context;
        }

        $message = __('Login realizado com sucesso. Você pode fechar esta janela.', 'juntaplay');

        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8" />';
        echo '<title>' . esc_html__('Login social', 'juntaplay') . '</title>';
        echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;margin:0;padding:2rem;text-align:center;background:#f8fbff;color:#0f172a;}p{margin-bottom:1.5rem;font-size:1rem;}button{background:#0f8cff;color:#fff;border:0;border-radius:999px;padding:0.65rem 1.75rem;font-weight:600;cursor:pointer;box-shadow:0 10px 25px rgba(15,23,42,0.18);}button:hover{background:#0077e5;}</style>';
        echo '</head><body>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '<button type="button" onclick="window.close();">' . esc_html__('Fechar', 'juntaplay') . '</button>';
        echo '<script>(function(){var payload=' . wp_json_encode($payload) . ';if(window.opener&&!window.opener.closed){try{window.opener.postMessage(payload,"*");}catch(e){}}setTimeout(function(){try{window.close();}catch(e){}} ,800);setTimeout(function(){if(payload.redirect){window.location.href=payload.redirect;}},1200);})();</script>';
        echo '</body></html>';

        exit;
    }

    private function render_social_popup_error(string $message, string $context = ''): void
    {
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');

        $payload = [
            'source'  => 'juntaplay-social',
            'status'  => 'error',
            'message' => $message,
        ];

        if ($context !== '') {
            $payload['context'] = $context;
        }

        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8" />';
        echo '<title>' . esc_html__('Login social', 'juntaplay') . '</title>';
        echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;margin:0;padding:2rem;text-align:center;background:#fff4f4;color:#7f1d1d;}p{margin-bottom:1.5rem;font-size:1rem;}button{background:#7f1d1d;color:#fff;border:0;border-radius:999px;padding:0.65rem 1.75rem;font-weight:600;cursor:pointer;}button:hover{background:#991b1b;}</style>';
        echo '</head><body>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '<button type="button" onclick="window.close();">' . esc_html__('Fechar', 'juntaplay') . '</button>';
        echo '<script>(function(){var payload=' . wp_json_encode($payload) . ';if(window.opener&&!window.opener.closed){try{window.opener.postMessage(payload,"*");}catch(e){}}})();</script>';
        echo '</body></html>';

        exit;
    }

    private function get_login_page_url(): string
    {
        $login_page_id = (int) get_option('juntaplay_page_entrar');
        $login_url     = $login_page_id ? get_permalink($login_page_id) : '';

        if ($login_url) {
            return $login_url;
        }

        $login_url = wp_login_url();

        return $login_url ?: home_url('/entrar');
    }

    /**
     * Determine the default redirect destination after login/register.
     */
    public function get_default_redirect(?WP_User $user = null): string
    {
        if (!$user instanceof WP_User) {
            $user = wp_get_current_user();
        }

        if ($user instanceof WP_User && $this->is_site_admin($user)) {
            return admin_url();
        }

        return $this->get_profile_redirect();
    }

    private function get_profile_redirect(): string
    {
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        $url             = $profile_page_id ? (string) get_permalink($profile_page_id) : '';

        if ($url === '') {
            $url = home_url('/perfil');
        }

        if ($url === '') {
            $url = home_url('/');
        }

        return trailingslashit($url);
    }

    private function is_site_admin(WP_User $user): bool
    {
        return user_can($user, 'manage_options');
    }

    /**
     * @param string       $redirect_to
     * @param string       $requested_redirect_to
     * @param WP_User|WP_Error $user
     *
     * @return string
     */
    public function force_profile_redirect($redirect_to, $requested_redirect_to, $user): string
    {
        if ($user instanceof WP_User) {
            if ($this->is_site_admin($user)) {
                return admin_url();
            }

            return $this->get_profile_redirect();
        }

        if (is_string($requested_redirect_to) && $requested_redirect_to !== '') {
            $validated = wp_validate_redirect($requested_redirect_to, $this->get_profile_redirect());
            if (is_string($validated) && $validated !== '') {
                return $validated;
            }
        }

        if (is_string($redirect_to) && $redirect_to !== '') {
            $validated = wp_validate_redirect($redirect_to, $this->get_profile_redirect());
            if (is_string($validated) && $validated !== '') {
                return $validated;
            }
        }

        return $this->get_profile_redirect();
    }
}
