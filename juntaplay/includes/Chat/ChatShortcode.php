<?php

declare(strict_types=1);

namespace JuntaPlay\Chat;

use JuntaPlay\Data\Groups;

defined('ABSPATH') || exit;

class ChatShortcode
{
    public static function init(): void
    {
        add_shortcode('juntaplay_chat', [self::class, 'render_chat']);

        add_filter('woocommerce_account_menu_items', [self::class, 'add_account_menu']);
        add_action('woocommerce_account_juntaplay-chat_endpoint', [self::class, 'render_account_endpoint']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function render_chat(): string
    {
        if (!is_user_logged_in()) {
            return '<p>É necessário estar logado para acessar suas mensagens.</p>';
        }

        $current_user = wp_get_current_user();
        $chat_context = self::resolve_chat_state((int) $current_user->ID);

        if (($chat_context['status'] ?? '') === 'unavailable') {
            return self::render_unavailable_state();
        }

        if (($chat_context['status'] ?? '') === 'selection') {
            return self::render_group_selector($chat_context['eligible_groups']);
        }

        $selected_group = $chat_context['selected_group'] ?? [];

        $group_id = isset($selected_group['id']) ? (int) $selected_group['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($group_id <= 0) {
            return self::render_unavailable_state();
        }

        $user_name      = $current_user->display_name ?: $current_user->user_login;
        $user_avatar    = self::resolve_avatar((int) $current_user->ID);
        $group_name   = isset($selected_group['title']) ? (string) $selected_group['title'] : '';
        $group_cover  = isset($selected_group['cover_url']) ? (string) $selected_group['cover_url'] : '';
        $admin_id     = isset($selected_group['owner_id']) ? (int) $selected_group['owner_id'] : 0;
        $admin        = $admin_id > 0 ? get_user_by('id', $admin_id) : null;
        $admin_name   = $admin && $admin->exists() ? ($admin->display_name ?: $admin->user_login) : '';
        $admin_avatar = $admin_id > 0 ? self::resolve_avatar($admin_id) : '';

        $group_label = $group_name !== '' ? $group_name : __('Grupo', 'juntaplay');
        $admin_label = $admin_name !== '' ? $admin_name : __('Administrador', 'juntaplay');
        $admin_image = $admin_avatar !== ''
            ? $admin_avatar
            : ($admin_id > 0 ? get_avatar_url($admin_id, ['size' => 120]) : $user_avatar);

        ob_start();
        ?>
        <div id="jp-chat-wrapper">
            <div id="jp-chat-header">
                <div class="jp-chat-group-chip">
                    <div class="jp-chat-group-chip-avatar">
                        <?php if ($group_cover !== '') : ?>
                            <img src="<?php echo esc_url($group_cover); ?>" alt="<?php echo esc_attr($group_label); ?>">
                        <?php else : ?>
                            <span aria-hidden="true">💬</span>
                        <?php endif; ?>
                    </div>
                    <div class="jp-chat-group-chip-body">
                        <span class="jp-chat-group-chip-label"><?php esc_html_e('Conversando em', 'juntaplay'); ?></span>
                        <span class="jp-chat-group-chip-title" title="<?php echo esc_attr($group_label); ?>"><?php echo esc_html($group_label); ?></span>
                    </div>
                </div>
                <div class="jp-chat-heading">
                    <h3>Central de Mensagens</h3>
                    <p class="jp-chat-subtitle">Canal direto para sanar dúvidas e enviar mensagens sobre o grupo.</p>
                </div>
                <div class="jp-chat-header-people">
                    <div class="jp-chat-person">
                        <div class="jp-chat-avatar-frame">
                            <img src="<?php echo esc_url($user_avatar); ?>" class="jp-chat-avatar" alt="<?php echo esc_attr($user_name); ?>">
                        </div>
                        <div class="jp-chat-person-name"><?php echo esc_html($user_name); ?></div>
                        <div class="jp-chat-person-group" title="<?php echo esc_attr($group_label); ?>"><?php echo esc_html($group_label); ?></div>
                    </div>
                    <div class="jp-chat-person">
                        <div class="jp-chat-avatar-frame">
                            <img src="<?php echo esc_url($admin_image); ?>" class="jp-chat-avatar" alt="<?php echo esc_attr($admin_label); ?>">
                        </div>
                        <div class="jp-chat-person-name"><?php echo esc_html($admin_label); ?></div>
                        <div class="jp-chat-person-group" title="<?php echo esc_attr($group_label); ?>"><?php echo esc_html($group_label); ?></div>
                    </div>
                </div>
            </div>

            <div id="jp-chat-messages"></div>

            <div id="jp-chat-preview">
                <div class="jp-chat-preview-icon" aria-hidden="true">🖼️</div>
                <div class="jp-chat-preview-body">
                    <div class="jp-chat-preview-label" id="jp-chat-preview-label">Selecione uma imagem para enviar</div>
                    <div class="jp-chat-preview-thumb" id="jp-chat-preview-thumb"></div>
                </div>
            </div>

            <div id="jp-chat-input">
                <button id="jp-chat-upload-btn" type="button" aria-label="Selecionar imagem">📎</button>
                <input type="text" placeholder="Digite sua mensagem..." id="jp-chat-text">
                <button id="jp-chat-send-btn" type="button">Enviar</button>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    public static function add_account_menu(array $items): array
    {
        $items['juntaplay-chat'] = 'Mensagens';
        return $items;
    }

    public static function render_account_endpoint(): void
    {
        echo do_shortcode('[juntaplay_chat]');
    }

    public static function enqueue_assets(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_GET['section']) || $_GET['section'] !== 'juntaplay-chat') {
            return;
        }

        $chat_context = self::resolve_chat_state(get_current_user_id());
        $selected_group = $chat_context['selected_group'] ?? [];
        $group_id = isset($selected_group['id']) ? (int) $selected_group['id'] : 0;

        wp_enqueue_style(
            'juntaplay-chat-css',
            JP_URL . 'assets/css/chat.css',
            [],
            '1.0.0'
        );

        if (($chat_context['status'] ?? '') !== 'ready' || $group_id <= 0) {
            return;
        }

        wp_enqueue_script(
            'juntaplay-chat-js',
            plugins_url('/assets/js/chat.js', __FILE__),
            [],
            '1.0.0',
            true
        );

        wp_localize_script('juntaplay-chat-js', 'jpChatData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('juntaplay_chat_nonce'),
            'group_id' => $group_id,
        ]);
    }

    /**
     * @return array{status:string,eligible_groups:array<int,array<string,mixed>>,selected_group?:array<string,mixed>}
     */
    private static function resolve_chat_state(int $user_id): array
    {
        $group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $groups   = Groups::get_groups_for_user($user_id);

        $member_groups = array_values(array_filter(
            $groups['member'] ?? [],
            static function (array $group) use ($user_id): bool {
                $status    = isset($group['membership_status']) ? (string) $group['membership_status'] : '';
                $owner_id  = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;

                return $status === 'active' && $owner_id > 0 && $owner_id !== $user_id;
            }
        ));

        if (count($member_groups) === 0) {
            return [
                'status'          => 'unavailable',
                'eligible_groups' => [],
            ];
        }

        if (count($member_groups) === 1) {
            return [
                'status'          => 'ready',
                'eligible_groups' => $member_groups,
                'selected_group'  => $member_groups[0],
            ];
        }

        foreach ($member_groups as $group) {
            if (isset($group['id']) && (int) $group['id'] === $group_id) {
                return [
                    'status'          => 'ready',
                    'eligible_groups' => $member_groups,
                    'selected_group'  => $group,
                ];
            }
        }

        return [
            'status'          => 'selection',
            'eligible_groups' => $member_groups,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $groups
     */
    private static function render_group_selector(array $groups): string
    {
        ob_start();
        ?>
        <div id="jp-chat-wrapper" class="jp-chat-selection-state">
            <div class="jp-chat-heading">
                <h3><?php esc_html_e('Selecione o grupo para conversar', 'juntaplay'); ?></h3>
                <p class="jp-chat-subtitle"><?php esc_html_e('Escolha um grupo administrado por outra pessoa para iniciar o chat.', 'juntaplay'); ?></p>
            </div>
            <div class="jp-chat-selection-grid">
                <?php foreach ($groups as $group) :
                    $group_title = isset($group['title']) ? (string) $group['title'] : __('Grupo', 'juntaplay');
                    $group_cover = isset($group['cover_url']) ? (string) $group['cover_url'] : '';
                    $owner_id    = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
                    $admin       = $owner_id > 0 ? get_user_by('id', $owner_id) : null;
                    $admin_name  = $admin && $admin->exists() ? ($admin->display_name ?: $admin->user_login) : __('Administrador', 'juntaplay');
                    $admin_avatar = $owner_id > 0 ? self::resolve_avatar($owner_id) : '';
                    $selection_url = add_query_arg(
                        ['group_id' => isset($group['id']) ? (int) $group['id'] : 0],
                        esc_url_raw(remove_query_arg('group_id'))
                    );
                    ?>
                    <a class="jp-chat-selection-card" href="<?php echo esc_url($selection_url); ?>">
                        <span class="jp-chat-selection-glow" aria-hidden="true"></span>
                        <div class="jp-chat-selection-card-body">
                            <div class="jp-chat-selection-avatar-frame">
                                <?php if ($group_cover !== '') : ?>
                                    <img src="<?php echo esc_url($group_cover); ?>" alt="<?php echo esc_attr($group_title); ?>" class="jp-chat-selection-avatar">
                                <?php else : ?>
                                    <div class="jp-chat-selection-avatar jp-chat-selection-avatar--placeholder" aria-hidden="true">💬</div>
                                <?php endif; ?>
                            </div>
                            <div class="jp-chat-selection-body">
                                <div class="jp-chat-selection-title"><?php echo esc_html($group_title); ?></div>
                                <div class="jp-chat-selection-admin">
                                    <span class="jp-chat-selection-admin-avatar" aria-hidden="true">
                                        <?php if ($admin_avatar !== '') : ?>
                                            <img src="<?php echo esc_url($admin_avatar); ?>" alt="" loading="lazy">
                                        <?php else : ?>
                                            👤
                                        <?php endif; ?>
                                    </span>
                                    <div class="jp-chat-selection-admin-meta">
                                        <span class="jp-chat-selection-admin-label"><?php esc_html_e('Administrador', 'juntaplay'); ?></span>
                                        <span class="jp-chat-selection-admin-name"><?php echo esc_html($admin_name); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_unavailable_state(): string
    {
        $campaigns_page_id = (int) get_option('juntaplay_page_campanhas');
        $campaigns_url     = $campaigns_page_id ? get_permalink($campaigns_page_id) : home_url('/grupos');
        $manage_url        = add_query_arg('section', 'groups', home_url('/minha-conta/'));

        ob_start();
        ?>
        <div id="jp-chat-wrapper" class="jp-chat-unavailable">
            <div class="jp-chat-heading">
                <h3><?php esc_html_e('Nenhum administrador disponível para conversar', 'juntaplay'); ?></h3>
                <p class="jp-chat-subtitle">
                    <?php esc_html_e('Você é o administrador deste grupo, portanto não há um administrador externo para abrir uma conversa.', 'juntaplay'); ?><br>
                    <?php esc_html_e('Este canal é destinado somente à comunicação entre participantes e administradores.', 'juntaplay'); ?><br>
                    <?php esc_html_e('Quando você entrar em um grupo administrado por outra pessoa, o chat ficará disponível automaticamente.', 'juntaplay'); ?>
                </p>
                <div class="jp-chat-unavailable-actions">
                    <a class="button button-primary" href="<?php echo esc_url($campaigns_url); ?>"><?php esc_html_e('Explorar grupos', 'juntaplay'); ?></a>
                    <a class="button" href="<?php echo esc_url($manage_url); ?>"><?php esc_html_e('Gerenciar meu grupo', 'juntaplay'); ?></a>
                </div>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function resolve_avatar(int $user_id): string
    {
        if ($user_id <= 0) {
            return '';
        }

        $avatar_custom_url = (string) get_user_meta($user_id, 'juntaplay_avatar_url', true);
        $avatar_attachment = (int) get_user_meta($user_id, 'juntaplay_avatar_id', true);

        if ($avatar_custom_url !== '') {
            return esc_url_raw($avatar_custom_url);
        }

        if ($avatar_attachment > 0) {
            $maybe_url = wp_get_attachment_image_url($avatar_attachment, 'thumbnail');
            if ($maybe_url) {
                return $maybe_url;
            }
        }

        $fallback = get_avatar_url($user_id, ['size' => 120]);

        return is_string($fallback) ? $fallback : '';
    }
}
