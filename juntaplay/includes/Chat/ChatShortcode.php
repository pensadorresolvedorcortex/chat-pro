<?php

declare(strict_types=1);

namespace JuntaPlay\Chat;

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

        $group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $current_user   = wp_get_current_user();
        $user_name      = $current_user->display_name ?: $current_user->user_login;
        $user_avatar    = self::resolve_avatar((int) $current_user->ID);
        $group_name     = '';
        $admin_name     = '';
        $admin_avatar   = '';
        $admin_id       = 0;

        if ($group_id > 0) {
            global $wpdb;
            $groups_table = $wpdb->prefix . 'jp_groups';
            $group        = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, title, owner_id FROM {$groups_table} WHERE id = %d",
                    $group_id
                )
            );

            if ($group) {
                $group_name = (string) ($group->title ?? '');
                $admin_id   = isset($group->owner_id) ? (int) $group->owner_id : 0;

                if ($admin_id > 0) {
                    $admin = get_user_by('id', $admin_id);
                    if ($admin) {
                        $admin_name = $admin->display_name ?: $admin->user_login;
                        $admin_avatar = self::resolve_avatar($admin_id);
                    }
                }
            }
        }

        $group_label = $group_name !== '' ? $group_name : __('Grupo', 'juntaplay');
        $admin_label = $admin_name !== '' ? $admin_name : __('Administrador', 'juntaplay');
        $admin_image = $admin_avatar !== ''
            ? $admin_avatar
            : ($admin_id > 0 ? get_avatar_url($admin_id, ['size' => 120]) : $user_avatar);

        ob_start();
        ?>
        <div id="jp-chat-wrapper">
            <div id="jp-chat-header">
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

        $group_id = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0;

        wp_enqueue_style(
            'juntaplay-chat-css',
            JP_URL . 'assets/css/chat.css',
            [],
            '1.0.0'
        );

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
