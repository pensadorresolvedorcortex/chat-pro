<?php

declare(strict_types=1);

namespace JuntaPlay\Front;

use WP_User;
use JuntaPlay\Notifications\EmailHelper;
use function __;
use function add_action;
use function add_query_arg;
use function add_shortcode;
use function apply_filters;
use function array_keys;
use function checked;
use function delete_transient;
use function esc_attr;
use function esc_attr_e;
use function esc_html;
use function esc_html_e;
use function esc_url_raw;
use function get_bloginfo;
use function get_current_user_id;
use function get_transient;
use function home_url;
use function in_array;
use function is_array;
use function is_user_logged_in;
use function is_wp_error;
use function ob_get_clean;
use function ob_start;
use function register_post_type;
use function rtrim;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function set_transient;
use function sprintf;
use function strpos;
use function trim;
use function wp_get_current_user;
use function wp_insert_post;
use function wp_kses_post;
use function wp_nonce_field;
use function wp_safe_redirect;
use function wp_specialchars_decode;
use function wp_unslash;
use function wp_validate_redirect;
use function wp_verify_nonce;
use const MINUTE_IN_SECONDS;

if (!defined('ABSPATH')) {
    exit;
}

class GroupCreation
{
    private Auth $auth;

    /** @var string[] */
    private array $errors = [];

    /** @var array<string, string> */
    private array $old_input = [];

    private ?string $success_notice = null;

    private const SUCCESS_TRANSIENT_PREFIX = 'juntaplay_group_success_';
    private const POST_TYPE = 'jp_group_submission';

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function init(): void
    {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'handle_form_submission']);
        add_action('wp', [$this, 'maybe_prepare_success_notice']);
        add_action('juntaplay/two_factor/success', [$this, 'handle_two_factor_success'], 10, 2);

        add_shortcode('juntaplay_group_create', [$this, 'render_create_form']);
        add_shortcode('juntaplay_group_relationship', [$this, 'render_relationship_shortcode']);
    }

    public function register_post_type(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'          => __('Grupos enviados', 'juntaplay'),
                'singular_name' => __('Grupo enviado', 'juntaplay'),
            ],
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'supports'            => ['title', 'editor'],
            'show_in_rest'        => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
        ]);
    }

    public function handle_form_submission(): void
    {
        if (!isset($_POST['jp_group_create_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        if (!is_user_logged_in()) {
            $this->errors[] = __('Faça login para criar um grupo.', 'juntaplay');

            return;
        }

        $nonce = isset($_POST['jp_group_create_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['jp_group_create_nonce'])) // phpcs:ignore WordPress.Security.NonceVerification
            : '';

        if ($nonce === '' || !wp_verify_nonce($nonce, 'juntaplay_group_create')) {
            $this->errors[] = __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay');

            return;
        }

        $group_name   = isset($_POST['jp_group_name']) ? sanitize_text_field(wp_unslash($_POST['jp_group_name'])) : '';
        $relationship = isset($_POST['jp_group_relationship']) ? sanitize_key(wp_unslash($_POST['jp_group_relationship'])) : '';
        $service_name = isset($_POST['jp_group_service']) ? sanitize_text_field(wp_unslash($_POST['jp_group_service'])) : '';
        $service_url  = isset($_POST['jp_group_service_url']) ? esc_url_raw(wp_unslash($_POST['jp_group_service_url'])) : '';
        $service_user = isset($_POST['jp_group_service_user']) ? sanitize_text_field(wp_unslash($_POST['jp_group_service_user'])) : '';
        $service_pass = isset($_POST['jp_group_service_pass']) ? sanitize_text_field(wp_unslash($_POST['jp_group_service_pass'])) : '';
        $notes        = isset($_POST['jp_group_notes']) ? sanitize_textarea_field(wp_unslash($_POST['jp_group_notes'])) : '';

        $this->old_input = [
            'group_name'        => $group_name,
            'group_relationship'=> $relationship,
            'service_name'      => $service_name,
            'service_user'      => $service_user,
            'service_url'       => $service_url,
            'notes'             => $notes,
        ];

        if ($group_name === '') {
            $this->errors[] = __('Informe um nome para o grupo.', 'juntaplay');
        }

        $valid_relationships = array_keys($this->get_relationship_options());
        if ($relationship === '' || !in_array($relationship, $valid_relationships, true)) {
            $this->errors[] = __('Selecione a relação com o administrador.', 'juntaplay');
        }

        if ($service_name === '') {
            $this->errors[] = __('Informe o serviço que será compartilhado.', 'juntaplay');
        }

        if ($service_user === '') {
            $this->errors[] = __('Informe o usuário ou e-mail de acesso compartilhado.', 'juntaplay');
        }

        if ($service_pass === '') {
            $this->errors[] = __('Informe a senha ou código de acesso compartilhado.', 'juntaplay');
        }

        if ($this->errors) {
            return;
        }

        $current_url = isset($_POST['jp_group_return'])
            ? wp_validate_redirect(sanitize_text_field(wp_unslash($_POST['jp_group_return'])))
            : '';
        if ($current_url === '') {
            $current_url = $this->get_current_url();
        }

        $redirect = add_query_arg('group_submitted', '1', $current_url);
        $payload  = [
            'group_name'      => $group_name,
            'relationship'    => $relationship,
            'service_name'    => $service_name,
            'service_login'   => $service_user,
            'service_password'=> $service_pass,
            'service_url'     => $service_url,
            'notes'           => $notes,
        ];

        $message = __('Recebemos sua solicitação! A equipe JuntaPlay vai avaliar se o grupo cumpre todos os requisitos e você será notificado por e-mail.', 'juntaplay');

        $challenge = $this->auth->begin_two_factor_challenge(
            wp_get_current_user(),
            $redirect,
            [
                'action'               => 'group_creation',
                'group_payload'        => $payload,
                'group_success_message'=> $message,
            ]
        );

        if ($challenge === null) {
            $this->errors[] = __('Não foi possível iniciar a verificação em dois fatores agora. Tente novamente em instantes.', 'juntaplay');

            return;
        }

        wp_safe_redirect($this->auth->get_two_factor_url($challenge));
        exit;
    }

    public function render_create_form(): string
    {
        $relationship_html = $this->render_relationship_fields($this->old_input['group_relationship'] ?? '');
        $current_url       = $this->get_current_url();

        ob_start();
        ?>
        <div class="jp-group-create">
            <?php if ($this->success_notice) : ?>
                <div class="jp-alert jp-alert--success">
                    <p><?php echo esc_html($this->success_notice); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($this->errors) : ?>
                <div class="jp-alert jp-alert--error">
                    <ul>
                        <?php foreach ($this->errors as $error) : ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="jp-group-form" novalidate>
                <input type="hidden" name="jp_group_create_action" value="1">
                <input type="hidden" name="jp_group_return" value="<?php echo esc_attr($current_url); ?>">
                <?php wp_nonce_field('juntaplay_group_create', 'jp_group_create_nonce'); ?>

                <div class="jp-field">
                    <label for="jp_group_name" class="jp-label"><?php esc_html_e('Nome do grupo', 'juntaplay'); ?></label>
                    <input type="text" id="jp_group_name" name="jp_group_name" class="jp-input" value="<?php echo esc_attr($this->old_input['group_name'] ?? ''); ?>" required>
                </div>

                <div class="jp-field">
                    <span class="jp-label"><?php esc_html_e('Qual a relação com o administrador?', 'juntaplay'); ?></span>
                    <?php echo wp_kses_post($relationship_html); ?>
                </div>

                <div class="jp-field">
                    <label for="jp_group_service" class="jp-label"><?php esc_html_e('Serviço compartilhado', 'juntaplay'); ?></label>
                    <input type="text" id="jp_group_service" name="jp_group_service" class="jp-input" value="<?php echo esc_attr($this->old_input['service_name'] ?? ''); ?>" required>
                </div>

                <div class="jp-field">
                    <label for="jp_group_service_url" class="jp-label"><?php esc_html_e('Link do serviço (opcional)', 'juntaplay'); ?></label>
                    <input type="url" id="jp_group_service_url" name="jp_group_service_url" class="jp-input" value="<?php echo esc_attr($this->old_input['service_url'] ?? ''); ?>" placeholder="https://">
                </div>

                <div class="jp-field">
                    <label for="jp_group_service_user" class="jp-label"><?php esc_html_e('Usuário ou e-mail de acesso', 'juntaplay'); ?></label>
                    <input type="text" id="jp_group_service_user" name="jp_group_service_user" class="jp-input" value="<?php echo esc_attr($this->old_input['service_user'] ?? ''); ?>" required>
                </div>

                <div class="jp-field">
                    <label for="jp_group_service_pass" class="jp-label"><?php esc_html_e('Senha ou código compartilhado', 'juntaplay'); ?></label>
                    <input type="text" id="jp_group_service_pass" name="jp_group_service_pass" class="jp-input" value="" required>
                </div>

                <div class="jp-field">
                    <label for="jp_group_notes" class="jp-label"><?php esc_html_e('Observações para nossa equipe (opcional)', 'juntaplay'); ?></label>
                    <textarea id="jp_group_notes" name="jp_group_notes" class="jp-textarea" rows="4" placeholder="<?php esc_attr_e('Compartilhe detalhes que ajudam a aprovação mais rápida.', 'juntaplay'); ?>"><?php echo esc_html($this->old_input['notes'] ?? ''); ?></textarea>
                </div>

                <div class="jp-form-footer">
                    <button type="submit" class="juntaplay-button juntaplay-button--primary">
                        <?php esc_html_e('Enviar para análise', 'juntaplay'); ?>
                    </button>
                    <p class="jp-form-hint">
                        <?php esc_html_e('Após enviar vamos validar com um código de 2 fatores para manter sua comunidade segura.', 'juntaplay'); ?>
                    </p>
                </div>
            </form>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render_relationship_shortcode($atts = []): string
    {
        $selected = '';
        if (is_array($atts) && isset($atts['selected'])) {
            $selected = sanitize_key((string) $atts['selected']);
        }

        return $this->render_relationship_fields($selected);
    }

    public function maybe_prepare_success_notice(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_GET['group_submitted'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $flag = sanitize_key((string) wp_unslash($_GET['group_submitted'])); // phpcs:ignore WordPress.Security.NonceVerification
        if ($flag === '') {
            return;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return;
        }

        $state = get_transient(self::SUCCESS_TRANSIENT_PREFIX . $user_id);
        if (!is_array($state)) {
            $this->success_notice = __('Sua solicitação foi recebida e está em análise. Em breve você será notificado por e-mail.', 'juntaplay');

            return;
        }

        $message = isset($state['message']) ? trim((string) $state['message']) : '';
        $this->success_notice = $message !== ''
            ? $message
            : __('Sua solicitação foi recebida e está em análise. Em breve você será notificado por e-mail.', 'juntaplay');

        delete_transient(self::SUCCESS_TRANSIENT_PREFIX . $user_id);
    }

    /**
     * @param array<string, mixed> $state
     */
    public function handle_two_factor_success(WP_User $user, array $state): void
    {
        if (empty($state['action']) || (string) $state['action'] !== 'group_creation') {
            return;
        }

        $payload = isset($state['group_payload']) && is_array($state['group_payload'])
            ? $this->sanitize_payload($state['group_payload'])
            : $this->sanitize_payload([]);

        if ($payload['group_name'] === '') {
            return;
        }

        $post_id = wp_insert_post([
            'post_type'   => self::POST_TYPE,
            'post_status' => 'pending',
            'post_title'  => $payload['group_name'],
            'post_content'=> $payload['notes'],
            'meta_input'  => [
                '_jp_group_relationship' => $payload['relationship'],
                '_jp_group_service'      => $payload['service_name'],
                '_jp_group_login'        => $payload['service_login'],
                '_jp_group_password'     => $payload['service_password'],
                '_jp_group_url'          => $payload['service_url'],
                '_jp_group_submitted_by' => $user->ID,
            ],
        ], true);

        if (is_wp_error($post_id)) {
            return;
        }

        $message = isset($state['group_success_message']) ? trim((string) $state['group_success_message']) : '';
        if ($message === '') {
            $message = __('Recebemos sua solicitação! Nossa equipe vai analisar os dados enviados e você receberá um e-mail com a atualização.', 'juntaplay');
        }

        set_transient(
            self::SUCCESS_TRANSIENT_PREFIX . $user->ID,
            [
                'message' => $message,
                'group'   => [
                    'id'   => (int) $post_id,
                    'name' => $payload['group_name'],
                ],
            ],
            MINUTE_IN_SECONDS * 20
        );

        $this->send_confirmation_email($user, $payload);
    }

    private function render_relationship_fields(string $selected): string
    {
        $options = $this->get_relationship_options();

        ob_start();
        ?>
        <div class="jp-relationship-options">
            <?php foreach ($options as $value => $label) : ?>
                <label class="jp-relationship-option">
                    <input type="radio" name="jp_group_relationship" value="<?php echo esc_attr($value); ?>" <?php checked($selected, $value); ?> required>
                    <span class="jp-relationship-option__label"><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @return array<string, string>
     */
    private function get_relationship_options(): array
    {
        $options = [
            'family'       => __('Família', 'juntaplay'),
            'friends'      => __('Amigos', 'juntaplay'),
            'cohabitants'  => __('Quem mora junto', 'juntaplay'),
            'coworkers'    => __('Colegas de trabalho', 'juntaplay'),
            'partners'     => __('Parceiros de negócio', 'juntaplay'),
        ];

        /** @var array<string, string> $filtered */
        $filtered = apply_filters('juntaplay/group/relationship_options', $options);

        return $filtered;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, string>
     */
    private function sanitize_payload(array $payload): array
    {
        $defaults = [
            'group_name'       => '',
            'relationship'     => '',
            'service_name'     => '',
            'service_login'    => '',
            'service_password' => '',
            'service_url'      => '',
            'notes'            => '',
        ];

        foreach ($defaults as $key => $default) {
            if (isset($payload[$key])) {
                $value = $payload[$key];
                $defaults[$key] = is_string($value) ? trim($value) : sanitize_text_field((string) $value);

                continue;
            }

            $defaults[$key] = $default;
        }

        return $defaults;
    }

    /**
     * @param array<string, string> $payload
     */
    private function send_confirmation_email(WP_User $user, array $payload): void
    {
        $email = trim((string) $user->user_email);
        if ($email === '') {
            return;
        }

        $site_name = wp_specialchars_decode(get_bloginfo('name'), \ENT_QUOTES);
        $subject   = sprintf(__('Recebemos o grupo %1$s — %2$s', 'juntaplay'), $payload['group_name'], $site_name);

        $list_items = [
            sprintf(__('Relação com o administrador: %s', 'juntaplay'), $this->get_relationship_options()[$payload['relationship']] ?? $payload['relationship']),
            sprintf(__('Serviço compartilhado: %s', 'juntaplay'), $payload['service_name']),
            sprintf(__('Usuário de acesso: %s', 'juntaplay'), $payload['service_login']),
        ];

        if ($payload['service_url'] !== '') {
            $list_items[] = sprintf(__('Link do serviço: %s', 'juntaplay'), $payload['service_url']);
        }

        $blocks = [
            [
                'type'    => 'paragraph',
                'content' => __('Recebemos os dados do seu novo grupo e já iniciamos a análise para liberar a comunidade.', 'juntaplay'),
            ],
            [
                'type'  => 'list',
                'items' => $list_items,
            ],
            [
                'type'    => 'paragraph',
                'content' => __('Nossa equipe valida se todas as regras são atendidas. Assim que o processo terminar você receberá um novo e-mail com o status.', 'juntaplay'),
            ],
            [
                'type'  => 'button',
                'label' => __('Acessar meu painel', 'juntaplay'),
                'url'   => home_url('/meus-grupos/'),
            ],
        ];

        $footer = [
            __('Equipe JuntaPlay', 'juntaplay'),
            __('Conte conosco para criar experiências incríveis em grupo.', 'juntaplay'),
            sprintf(__('Precisa de ajuda? Responda este e-mail ou visite %s.', 'juntaplay'), home_url('/ajuda/')),
        ];

        EmailHelper::send(
            $email,
            $subject,
            $blocks,
            [
                'headline'  => __('Seu grupo está em análise', 'juntaplay'),
                'preheader' => __('Avisaremos assim que a validação estiver concluída.', 'juntaplay'),
                'footer'    => $footer,
            ]
        );
    }

    private function get_current_url(): string
    {
        $request = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($request === '') {
            return home_url('/');
        }

        if (strpos($request, 'http://') === 0 || strpos($request, 'https://') === 0) {
            return $request;
        }

        $home = rtrim(home_url('/'), '/');
        $path = '/' . ltrim($request, '/');

        return $home . $path;
    }
}
