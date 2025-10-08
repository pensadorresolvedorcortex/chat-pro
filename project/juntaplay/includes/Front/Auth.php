<?php

declare(strict_types=1);

namespace JuntaPlay\Front;

use WP_Error;
use WP_User;
use JuntaPlay\Admin\Settings;
use JuntaPlay\Notifications\EmailHelper;

use function __;
use function add_action;
use function add_filter;
use function apply_filters;
use function add_query_arg;
use function delete_transient;
use function delete_user_meta;
use function email_exists;
use function get_option;
use function get_permalink;
use function get_transient;
use function get_user_by;
use function get_user_meta;
use function get_bloginfo;
use function home_url;
use function implode;
use function is_email;
use function is_user_logged_in;
use function is_wp_error;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sanitize_user;
use function set_transient;
use function username_exists;
use function update_user_meta;
use function wp_authenticate_username_password;
use function wp_check_password;
use function wp_create_user;
use function wp_generate_password;
use function wp_generate_uuid4;
use function wp_hash_password;
use function wp_json_decode;
use function wp_logout;
use function preg_replace;
use function strlen;
use function substr;
use function str_repeat;
use function trim;
use function wp_remote_get;
use function wp_remote_post;
use function wp_remote_retrieve_body;
use function wp_login_url;
use function wp_safe_redirect;
use function wp_set_auth_cookie;
use function wp_set_current_user;
use function wp_signon;
use function wp_unslash;
use function wp_update_user;
use function wp_verify_nonce;
use function wp_validate_redirect;
use function wp_rand;
use function do_action;
use function rawurlencode;
use function remove_query_arg;
use function esc_url;
use function esc_url_raw;
use function time;
use function explode;
use function _n;
use function max;
use function min;
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
        add_filter('juntaplay/login/providers', [$this, 'filter_social_providers']);
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
        if (!$redirect) {
            $redirect = $this->get_default_redirect();
        }

        if ($this->should_require_two_factor($user)) {
            $challenge = $this->start_two_factor_challenge($user, $remember, $redirect);

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

        if ($view === 'register') {
            $this->active_view = 'register';
        }

        if (isset($_GET['jp_social_error'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $message = sanitize_textarea_field(wp_unslash($_GET['jp_social_error'])); // phpcs:ignore WordPress.Security.NonceVerification
            if ($message !== '') {
                $this->login_errors[] = $message;
                $this->active_view    = 'login';
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

        if (!$redirect) {
            $redirect = $this->get_default_redirect();
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

    private function start_two_factor_challenge(WP_User $user, bool $remember, string $redirect, ?string $existing = null): ?string
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
            'redirect'    => $this->sanitize_redirect($redirect),
            'method'      => $method,
            'destination' => $destination['display'],
            'target'      => $destination['target'],
            'email'       => $destination['email'],
            'issued_at'   => time(),
            'expires'     => $expires,
        ];

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

    private function build_two_factor_url(string $challenge): string
    {
        $page_id = (int) get_option('juntaplay_page_verificar-acesso');
        $base    = $page_id ? get_permalink($page_id) : home_url('/verificar-acesso');

        if (!$base) {
            $base = home_url('/verificar-acesso');
        }

        return add_query_arg('challenge', rawurlencode($challenge), $base);
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

        $challenge = $this->start_two_factor_challenge($user, !empty($state['remember']), (string) ($state['redirect'] ?? ''), $challenge);

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

        $redirect = isset($state['redirect']) ? (string) $state['redirect'] : $this->get_default_redirect();
        wp_safe_redirect($this->sanitize_redirect($redirect));
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

    private function sanitize_redirect(string $redirect): string
    {
        $validated = wp_validate_redirect($redirect, $this->get_default_redirect());

        return is_string($validated) ? $validated : $this->get_default_redirect();
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

        $this->start_social_flow($provider, $redirect, false);
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

            if ($key === '' || !$this->is_social_enabled($key)) {
                continue;
            }

            $provider['href'] = esc_url($this->get_social_login_url($key));
            $output[]         = $provider;
        }

        return $output;
    }

    private function start_social_flow(string $provider, string $redirect, bool $remember): void
    {
        $settings = $this->get_provider_settings($provider);

        if (empty($settings['client_id']) || empty($settings['client_secret'])) {
            $this->redirect_with_error(__('Configuração do provedor ausente. Informe as credenciais no painel.', 'juntaplay'));
        }

        $callback = $this->get_social_callback_url($provider);
        $state    = wp_generate_uuid4();

        set_transient(self::SOCIAL_TRANSIENT_PREFIX . $state, [
            'provider' => $provider,
            'redirect' => $this->sanitize_redirect($redirect),
            'remember' => $remember,
        ], MINUTE_IN_SECONDS * 10);

        $authorize = $this->build_social_authorize_url($provider, $settings, $callback, $state);

        if ($authorize === '') {
            $this->redirect_with_error(__('Não foi possível iniciar o login social.', 'juntaplay'));
        }

        wp_safe_redirect($authorize);
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
        $callback = $this->get_social_callback_url($provider);
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

        $this->complete_social_login($provider, $profile, $redirect, $remember);
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function complete_social_login(string $provider, array $profile, string $redirect, bool $remember): void
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

        wp_safe_redirect($this->sanitize_redirect($redirect));
        exit;
    }

    private function get_social_login_url(string $provider): string
    {
        $base = add_query_arg('juntaplay_social', $provider, $this->get_login_page_url());

        if (isset($_REQUEST['redirect_to'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $redirect = sanitize_text_field(wp_unslash($_REQUEST['redirect_to'])); // phpcs:ignore WordPress.Security.NonceVerification
            $base     = add_query_arg('redirect_to', rawurlencode($redirect), $base);
        }

        return $base;
    }

    private function get_social_callback_url(string $provider): string
    {
        return add_query_arg([
            'juntaplay_social' => $provider,
            'callback'         => '1',
        ], home_url('/'));
    }

    /**
     * @return array<string, mixed>
     */
    private function get_social_settings(): array
    {
        $settings = get_option(Settings::OPTION_SOCIAL, []);

        return is_array($settings) ? $settings : [];
    }

    private function is_social_enabled(string $provider): bool
    {
        $settings = $this->get_provider_settings($provider);

        return !empty($settings['enabled']);
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
        $login_url = add_query_arg('jp_social_error', rawurlencode($message), $this->get_login_page_url());
        wp_safe_redirect($login_url);
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
    public function get_default_redirect(): string
    {
        $dashboard_id = (int) get_option('juntaplay_page_painel');
        if ($dashboard_id) {
            $dashboard = get_permalink($dashboard_id);
            if ($dashboard) {
                return $dashboard;
            }
        }

        $my_quotas_id = (int) get_option('juntaplay_page_minhas-cotas');
        if ($my_quotas_id) {
            $my_quotas = get_permalink($my_quotas_id);
            if ($my_quotas) {
                return $my_quotas;
            }
        }

        return home_url('/');
    }
}
