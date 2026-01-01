<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

use WP_REST_Request;
use WP_REST_Response;
use JuntaPlay\Data\Pools;
use JuntaPlay\Data\GroupChats;
use JuntaPlay\Data\GroupChatMessages;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\Notifications;
use function is_user_logged_in;
use function get_current_user_id;
use function rest_url;
use function wp_create_nonce;
use function get_permalink;
use function get_option;

defined('ABSPATH') || exit;

class Rest
{
    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('juntaplay/v1', '/pools/(?P<id>\d+)/quotas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_quotas'],
            'permission_callback' => '__return_true',
            'args'                => [
                'status' => [
                    'type'    => 'string',
                    'default' => 'available',
                ],
            ],
        ]);

        register_rest_route('juntaplay/v1', '/chats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_chats'],
            'permission_callback' => static fn (): bool => is_user_logged_in(),
        ]);

        register_rest_route('juntaplay/v1', '/chats/(?P<id>\d+)/messages', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_chat_messages'],
            'permission_callback' => static fn (): bool => is_user_logged_in(),
            'args'                => [
                'after' => [
                    'type' => 'string',
                ],
            ],
        ]);

        register_rest_route('juntaplay/v1', '/chats/(?P<id>\d+)/messages', [
            'methods'             => 'POST',
            'callback'            => [$this, 'post_chat_message'],
            'permission_callback' => static fn (): bool => is_user_logged_in(),
        ]);
    }

    public function get_quotas(WP_REST_Request $request): WP_REST_Response
    {
        global $wpdb;

        $pool_id = (int) $request['id'];
        $status  = $request->get_param('status');

        $pool = Pools::get($pool_id);

        if (!$pool) {
            return new WP_REST_Response(['message' => __('Campanha não encontrada.', 'juntaplay')], 404);
        }

        $table = "{$wpdb->prefix}jp_quotas";
        $where = $wpdb->prepare('WHERE pool_id = %d', $pool_id);

        if ($status && in_array($status, ['available', 'reserved', 'paid', 'canceled', 'expired'], true)) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }

        $results = $wpdb->get_results("SELECT number, status FROM $table $where ORDER BY number ASC");

        return new WP_REST_Response([
            'pool'   => ['id' => $pool->id, 'title' => $pool->title],
            'quotas' => $results,
        ]);
    }

    public function get_chats(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = get_current_user_id();

        $items = GroupChats::list_for_user($user_id);

        foreach ($items as &$item) {
            $group = Groups::get($item['group_id']);
            $item['cover_url'] = $group['cover_url'] ?? '';
            $item['cover_placeholder'] = $group['cover_placeholder'] ?? '';
            $item['role'] = $item['member_id'] === $user_id ? 'member' : 'admin';
        }

        return new WP_REST_Response([
            'items' => $items,
        ]);
    }

    public function get_chat_messages(WP_REST_Request $request): WP_REST_Response
    {
        $chat_id = (int) $request['id'];
        $user_id = get_current_user_id();

        if (!GroupChats::user_can_access($chat_id, $user_id)) {
            return new WP_REST_Response(['message' => __('Conversa não encontrada.', 'juntaplay')], 404);
        }

        $after    = $request->get_param('after');
        $messages = GroupChatMessages::get_for_chat($chat_id, 100, $after ? (string) $after : null);

        return new WP_REST_Response([
            'messages' => $messages,
        ]);
    }

    public function post_chat_message(WP_REST_Request $request): WP_REST_Response
    {
        $chat_id = (int) $request['id'];
        $user_id = get_current_user_id();

        if (!GroupChats::user_can_access($chat_id, $user_id)) {
            return new WP_REST_Response(['message' => __('Conversa não encontrada.', 'juntaplay')], 404);
        }

        $payload = $request->get_json_params();
        $message = is_array($payload) ? (string) ($payload['message'] ?? '') : '';
        $attachment_id = is_array($payload) ? (int) ($payload['attachment_id'] ?? 0) : 0;

        if ($message === '' && $attachment_id <= 0) {
            return new WP_REST_Response(['message' => __('Envie uma mensagem ou imagem.', 'juntaplay')], 400);
        }

        $chat = GroupChats::get($chat_id);
        if (!$chat) {
            return new WP_REST_Response(['message' => __('Conversa não encontrada.', 'juntaplay')], 404);
        }

        $type = $attachment_id > 0 ? GroupChatMessages::TYPE_IMAGE : GroupChatMessages::TYPE_TEXT;
        $inserted = GroupChatMessages::add(
            $chat_id,
            $user_id,
            $type,
            $message,
            $attachment_id > 0 ? $attachment_id : null
        );

        if (!$inserted) {
            return new WP_REST_Response(['message' => __('Não foi possível enviar.', 'juntaplay')], 500);
        }

        $target_user = $user_id === $chat['member_id'] ? $chat['admin_id'] : $chat['member_id'];
        if ($target_user > 0 && $target_user !== $user_id) {
            $group = Groups::get($chat['group_id']);
            $action_url = get_permalink((int) get_option('juntaplay_page_perfil'));
            if (!$action_url) {
                $action_url = home_url('/perfil/');
            }

            if ($action_url) {
                $separator = strpos($action_url, '?') === false ? '?' : '&';
                $action_url .= $separator . http_build_query([
                    'section' => 'juntaplay-chat',
                    'chat'    => $chat_id,
                ]);
            }

            $sender      = get_userdata($user_id);
            $sender_name = $sender && isset($sender->display_name)
                ? (string) $sender->display_name
                : __('Administrador', 'juntaplay');
            $group_title = $group['title'] ?? __('chat', 'juntaplay');

            Notifications::add($target_user, [
                'type'    => 'chat',
                'title'   => sprintf(__('Mensagem em %s', 'juntaplay'), $group_title),
                'message' => sprintf(__('Nova mensagem de %1$s em %2$s.', 'juntaplay'), $sender_name, $group_title),
                'action_url' => $action_url,
            ]);
        }

        $messages = GroupChatMessages::get_for_chat($chat_id, 1);

        return new WP_REST_Response([
            'messages' => $messages,
        ]);
    }
}
