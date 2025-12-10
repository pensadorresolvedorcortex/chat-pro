<?php

declare(strict_types=1);

namespace JuntaPlay\Chat;

use JuntaPlay\Data\Notifications as NotificationsData;
use WP_Error;
use function __;
use function add_query_arg;
use function get_user_by;
use function home_url;

defined('ABSPATH') || exit;

class ChatAjax
{
    public static function init(): void
    {
        add_action('wp_ajax_juntaplay_chat_send', [self::class, 'send']);
        add_action('wp_ajax_juntaplay_chat_list', [self::class, 'list']);
        add_action('wp_ajax_juntaplay_chat_upload', [self::class, 'upload']);
    }

    public static function send(): void
    {
        if (!self::check_nonce() || !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Acesso negado'], 403);
        }

        $group_id = absint($_POST['group_id'] ?? 0);
        if ($group_id <= 0) {
            wp_send_json_error(['message' => 'Grupo inválido'], 400);
        }
        $message  = isset($_POST['message']) ? sanitize_textarea_field((string) wp_unslash($_POST['message'])) : '';
        $image    = isset($_POST['image_url']) ? esc_url_raw((string) wp_unslash($_POST['image_url'])) : null;

        $context = self::resolve_context($group_id);
        if (is_wp_error($context)) {
            wp_send_json_error(['message' => $context->get_error_message()], 400);
        }

        [$group, $member_id, $admin_id, $sender] = $context;

        $group_id  = (int) $group->id;
        $member_id = (int) $member_id;
        $admin_id  = (int) $admin_id;

        $chat = new ChatMessage();
        $inserted = $chat->create_message([
            'group_id'  => $group_id,
            'user_id'   => $member_id,
            'admin_id'  => $admin_id,
            'sender'    => $sender,
            'message'   => $message,
            'image_url' => $image,
        ]);

        if (!$inserted) {
            wp_send_json_error(['message' => 'Não foi possível enviar a mensagem'], 500);
        }

        global $wpdb;
        $msg = [
            'id'            => (int) $wpdb->insert_id,
            'group_id'      => $group_id,
            'user_id'       => $member_id,
            'admin_id'      => $admin_id,
            'sender'        => $sender,
            'message'       => $message,
            'image_url'     => $image,
            'is_read_admin' => 0,
            'is_read_user'  => 0,
            'created_at'    => current_time('mysql'),
        ];

        if ($sender === 'user') {
            $group_name = isset($group->title) ? (string) $group->title : __('Grupo', 'juntaplay');
            $member     = get_user_by('id', $member_id);
            $member_name = $member && $member->exists()
                ? ($member->display_name ?: $member->user_login)
                : __('Participante', 'juntaplay');

            $action_url = add_query_arg(
                [
                    'section'        => 'juntaplay-chat',
                    'participant_id' => $member_id,
                    'group_id'       => $group_id,
                ],
                home_url('/perfil/')
            );

            NotificationsData::add($admin_id, [
                'type'       => 'chat',
                'title'      => sprintf(__('Nova mensagem de %s', 'juntaplay'), $member_name),
                'message'    => sprintf(
                    __('O %1$s do grupo %2$s enviou uma mensagem no chat.', 'juntaplay'),
                    $member_name,
                    $group_name
                ),
                'action_url' => $action_url,
            ]);
        }

        wp_send_json_success([
            'message' => 'Mensagem enviada',
            'data'    => ['msg' => $msg],
        ]);
    }

    public static function list(): void
    {
        if (!self::check_nonce() || !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Acesso negado'], 403);
        }

        $group_id = absint($_GET['group_id'] ?? $_POST['group_id'] ?? 0);
        if ($group_id <= 0) {
            wp_send_json_error(['message' => 'Grupo inválido'], 400);
        }
        $context  = self::resolve_context($group_id);
        if (is_wp_error($context)) {
            wp_send_json_error(['message' => $context->get_error_message()], 400);
        }

        [$group, $member_id, $admin_id, $sender] = $context;

        $group_id  = (int) $group->id;
        $member_id = (int) $member_id;
        $admin_id  = (int) $admin_id;

        $chat     = new ChatMessage();
        $messages = $chat->get_messages($group_id, $member_id, $admin_id, 100);

        if ($sender === 'admin') {
            $chat->mark_as_read_by_admin($group_id, $member_id);
        } else {
            $chat->mark_as_read_by_user($group_id, $member_id);
        }

        wp_send_json_success(['messages' => $messages]);
    }

    public static function upload(): void
    {
        if (!self::check_nonce() || !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Acesso negado'], 403);
        }

        if (empty($_FILES['file'])) {
            wp_send_json_error(['message' => 'Arquivo ausente'], 400);
        }

        $file = $_FILES['file'];
        $allowed_types = ['image/png', 'image/jpeg', 'image/webp'];

        if (!in_array($file['type'], $allowed_types, true)) {
            wp_send_json_error(['message' => 'Tipo de arquivo não permitido'], 400);
        }

        $size_limit = 5 * 1024 * 1024;
        if (!empty($file['size']) && (int) $file['size'] > $size_limit) {
            wp_send_json_error(['message' => 'Arquivo muito grande'], 400);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (isset($upload['error'])) {
            wp_send_json_error(['message' => $upload['error']], 400);
        }

        wp_send_json_success(['url' => $upload['url'] ?? '']);
    }

    private static function check_nonce(): bool
    {
        $nonce = $_REQUEST['_ajax_nonce'] ?? $_REQUEST['nonce'] ?? '';
        return is_string($nonce) && wp_verify_nonce($nonce, 'juntaplay_chat_nonce');
    }

    /**
     * @return array{object,int,int,string}|WP_Error
     */
    private static function resolve_context(int $group_id)
    {
        global $wpdb;

        if ($group_id <= 0) {
            return new WP_Error('invalid_group', 'Grupo inválido');
        }

        $groups_table = $wpdb->prefix . 'jp_groups';
        $group = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$groups_table} WHERE id = %d", $group_id));
        if (!$group) {
            return new WP_Error('invalid_group', 'Grupo não encontrado');
        }

        $current_user = get_current_user_id();
        $members_table = $wpdb->prefix . 'jp_group_members';
        $member_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$members_table} WHERE group_id = %d AND user_id = %d AND status = %s",
                $group_id,
                $current_user,
                'active'
            )
        );

        $is_admin = ((int) $group->owner_id === $current_user);
        $member_id = $current_user;
        $admin_id  = (int) $group->owner_id;

        if ($is_admin) {
            $member_id = absint($_REQUEST['user_id'] ?? $_REQUEST['participant_id'] ?? 0);
            if ($member_id <= 0) {
                return new WP_Error('invalid_member', 'Membro inválido');
            }

            $member_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$members_table} WHERE group_id = %d AND user_id = %d AND status = %s",
                    $group_id,
                    $member_id,
                    'active'
                )
            );

            if (!$member_row) {
                return new WP_Error('invalid_member', 'Membro não pertence ao grupo');
            }

            return [$group, $member_id, $admin_id, 'admin'];
        }

        if (!$member_row) {
            return new WP_Error('forbidden', 'Usuário não pertence ao grupo');
        }

        return [$group, $member_id, $admin_id, 'user'];
    }
}
