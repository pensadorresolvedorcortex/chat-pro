<?php
/**
 * JuntaPlay Chat core (carregado via juntaplay.php).
 */

declare(strict_types=1);

if (!defined('JUNTAPLAY_CHAT_FILE')) {
    define('JUNTAPLAY_CHAT_FILE', __FILE__);
}

use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\Groups;

if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(JUNTAPLAY_CHAT_FILE, 'juntaplay_chat_install');
add_action('init', 'juntaplay_chat_register_endpoint');
add_action('rest_api_init', 'juntaplay_chat_register_routes');
add_action('wp_ajax_juntaplay_chat_send', 'juntaplay_chat_ajax_send');
add_action('wp_ajax_nopriv_juntaplay_chat_send', 'juntaplay_chat_ajax_forbidden');
add_action('wp_ajax_juntaplay_chat_messages', 'juntaplay_chat_ajax_messages');
add_action('wp_ajax_nopriv_juntaplay_chat_messages', 'juntaplay_chat_ajax_forbidden');

/**
 * Get the chat table name with prefix.
 */
function juntaplay_chat_table(): string
{
    global $wpdb;

    return $wpdb->prefix . 'juntaplay_chat_messages';
}

/**
 * Create the chat messages table.
 */
function juntaplay_chat_install(): void
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table           = juntaplay_chat_table();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        admin_id BIGINT UNSIGNED NOT NULL,
        subscriber_id BIGINT UNSIGNED NOT NULL,
        group_id BIGINT UNSIGNED DEFAULT 0,
        sender_id BIGINT UNSIGNED NOT NULL,
        recipient_id BIGINT UNSIGNED NOT NULL,
        message LONGTEXT NOT NULL,
        attachment_url TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        PRIMARY KEY  (id),
        KEY admin_id (admin_id),
        KEY subscriber_id (subscriber_id),
        KEY group_id (group_id),
        KEY recipient_id (recipient_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

/**
 * Register query vars and endpoint for juntaplay-chat section.
 */
function juntaplay_chat_register_endpoint(): void
{
    add_rewrite_endpoint('juntaplay-chat', EP_PAGES | EP_ROOT);
    add_filter('query_vars', static function (array $vars): array {
        if (!in_array('juntaplay-chat', $vars, true)) {
            $vars[] = 'juntaplay-chat';
        }
        return $vars;
    });
}

/**
 * Validate relation between admin and subscriber.
 */
function juntaplay_validar_relacao_entre_admin_assinante(int $admin_id, int $subscriber_id, ?int $group_id = null): bool
{
    $admin_id      = max(0, $admin_id);
    $subscriber_id = max(0, $subscriber_id);
    $group_id      = $group_id !== null ? max(0, $group_id) : null;

    if ($admin_id === 0 || $subscriber_id === 0 || $admin_id === $subscriber_id) {
        return false;
    }

    $is_valid = apply_filters(
        'juntaplay_chat_validate_relation',
        true,
        $admin_id,
        $subscriber_id,
        $group_id
    );

    return (bool) $is_valid;
}

/**
 * Simple guard to ensure the logged user is a participant.
 */
function juntaplay_chat_current_user_can_access(int $admin_id, int $subscriber_id): bool
{
    $current_user = get_current_user_id();

    if ($current_user === 0) {
        return false;
    }

    return $current_user === $admin_id || $current_user === $subscriber_id;
}

/**
 * Insert a chat message when validation passes.
 */
function juntaplay_enviar_mensagem(array $args)
{
    global $wpdb;

    $defaults = [
        'admin_id'      => 0,
        'subscriber_id' => 0,
        'group_id'      => 0,
        'sender_id'     => 0,
        'message'       => '',
        'attachment'    => '',
    ];

    $data = wp_parse_args($args, $defaults);

    $admin_id      = (int) $data['admin_id'];
    $subscriber_id = (int) $data['subscriber_id'];
    $group_id      = (int) $data['group_id'];
    $sender_id     = (int) $data['sender_id'];
    $message       = wp_strip_all_tags((string) $data['message'], true);
    $attachment    = esc_url_raw((string) $data['attachment']);

    $recipient_id = $sender_id === $admin_id ? $subscriber_id : $admin_id;

    if (!juntaplay_chat_current_user_can_access($admin_id, $subscriber_id)) {
        return new WP_Error('juntaplay_forbidden', __('Você não pode enviar mensagens nesta conversa.', 'juntaplay'));
    }

    if (!juntaplay_validar_relacao_entre_admin_assinante($admin_id, $subscriber_id, $group_id)) {
        return new WP_Error('juntaplay_invalid_relation', __('Relação administrador/assinante inválida.', 'juntaplay'));
    }

    if ($message === '' && $attachment === '') {
        return new WP_Error('juntaplay_empty_message', __('A mensagem está vazia.', 'juntaplay'));
    }

    $table = juntaplay_chat_table();

    $inserted = $wpdb->insert(
        $table,
        [
            'admin_id'       => $admin_id,
            'subscriber_id'  => $subscriber_id,
            'group_id'       => $group_id,
            'sender_id'      => $sender_id,
            'recipient_id'   => $recipient_id,
            'message'        => $message,
            'attachment_url' => $attachment,
        ],
        ['%d', '%d', '%d', '%d', '%d', '%s', '%s']
    );

    if (!$inserted) {
        return new WP_Error('juntaplay_insert_failed', __('Não foi possível enviar a mensagem.', 'juntaplay'));
    }

    $message_id = (int) $wpdb->insert_id;

    juntaplay_chat_increment_unread($recipient_id, $sender_id, $message_id, $admin_id, $subscriber_id);

    return $message_id;
}

/**
 * Fetch messages between admin and subscriber.
 */
function juntaplay_obter_mensagens(int $admin_id, int $subscriber_id, ?int $group_id = null): array
{
    global $wpdb;

    $admin_id      = max(0, $admin_id);
    $subscriber_id = max(0, $subscriber_id);
    $group_id      = $group_id !== null ? max(0, $group_id) : null;

    if ($admin_id === 0 || $subscriber_id === 0) {
        return [];
    }

    $table = juntaplay_chat_table();

    if ($group_id !== null) {
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE admin_id = %d AND subscriber_id = %d AND group_id = %d ORDER BY created_at ASC",
            $admin_id,
            $subscriber_id,
            $group_id
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE admin_id = %d AND subscriber_id = %d ORDER BY created_at ASC",
            $admin_id,
            $subscriber_id
        );
    }

    $results = $wpdb->get_results($query, ARRAY_A);

    return is_array($results) ? $results : [];
}

/**
 * Increment unread counter for a recipient and store sender context.
 */
function juntaplay_chat_increment_unread(int $recipient_id, int $sender_id, int $message_id, ?int $admin_id = null, ?int $subscriber_id = null): void
{
    if ($recipient_id <= 0) {
        return;
    }

    $sender      = get_userdata($sender_id);
    $sender_name = $sender ? trim($sender->display_name) : __('Administrador', 'juntaplay');

    $unread = get_user_meta($recipient_id, 'juntaplay_mensagens_nao_lidas', true);
    if (!is_array($unread)) {
        $unread = [];
    }

    $unread[] = [
        'id'            => $message_id,
        'sender'        => $sender_name,
        'admin_id'      => $admin_id,
        'subscriber_id' => $subscriber_id,
        'time'          => current_time('mysql'),
    ];

    update_user_meta($recipient_id, 'juntaplay_mensagens_nao_lidas', $unread);
}

/**
 * Mark messages read for the current user in the conversation.
 */
function juntaplay_chat_marcar_como_lidas(int $user_id, int $admin_id, int $subscriber_id, ?int $group_id = null): void
{
    global $wpdb;

    if ($user_id <= 0) {
        return;
    }

    $table = juntaplay_chat_table();

    $where = [
        'recipient_id'  => $user_id,
        'admin_id'      => $admin_id,
        'subscriber_id' => $subscriber_id,
    ];

    $formats = ['%d', '%d', '%d'];

    if ($group_id !== null) {
        $where['group_id'] = $group_id;
        $formats[]         = '%d';
    }

    $wpdb->update(
        $table,
        ['read_at' => current_time('mysql')],
        $where,
        ['%s'],
        $formats
    );

    $unread = get_user_meta($user_id, 'juntaplay_mensagens_nao_lidas', true);
    if (!is_array($unread)) {
        $unread = [];
    }

    $unread = array_values(array_filter($unread, static function ($entry) use ($admin_id, $subscriber_id) {
        return !isset($entry['admin_id'], $entry['subscriber_id'])
            || (int) $entry['admin_id'] !== $admin_id
            || (int) $entry['subscriber_id'] !== $subscriber_id;
    }));

    update_user_meta($user_id, 'juntaplay_mensagens_nao_lidas', $unread);
}

/**
 * Return a sanitized user snapshot.
 */
function juntaplay_chat_user_snapshot(int $user_id): array
{
    $user = get_userdata($user_id);

    if (!$user) {
        return [];
    }

    return [
        'id'     => $user_id,
        'name'   => trim($user->display_name),
        'avatar' => get_avatar_url($user_id, ['size' => 96]),
    ];
}

/**
 * Resolve group owner via filter, allowing the host plugin to define memberships.
 */
function juntaplay_chat_group_owner(int $group_id): int
{
    if (!class_exists(Groups::class) || $group_id <= 0) {
        return 0;
    }

    $current_user = get_current_user_id();
    $groups       = Groups::get_groups_for_user($current_user);

    foreach (['owned', 'member'] as $bucket) {
        $items = isset($groups[$bucket]) && is_array($groups[$bucket]) ? $groups[$bucket] : [];

        foreach ($items as $group) {
            if (!is_array($group)) {
                continue;
            }

            if ((int) ($group['id'] ?? 0) === $group_id) {
                return isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
            }
        }
    }

    return 0;
}

/**
 * Resolve group subscribers via filter.
 */
function juntaplay_chat_group_subscribers(int $group_id, int $current_user_id): array
{
    if (!class_exists(GroupMembers::class) || $group_id <= 0) {
        return [];
    }

    $owner_id = juntaplay_chat_group_owner($group_id);
    $members  = GroupMembers::get($group_id, 'active');

    if (!is_array($members)) {
        return [];
    }

    $normalized = [];

    foreach ($members as $member) {
        if (!is_array($member)) {
            continue;
        }

        $id = (int) ($member['user_id'] ?? $member['id'] ?? 0);

        if ($id <= 0 || $id === $owner_id || $id === $current_user_id) {
            continue;
        }

        $user = get_userdata($id);

        $normalized[] = [
            'id'     => $id,
            'name'   => $user ? (string) $user->display_name : '',
            'group'  => '',
            'avatar' => get_avatar_url($id, ['size' => 96]),
        ];
    }

    return $normalized;
}

/**
 * Load groups directly from the core JuntaPlay plugin when available.
 *
 * @return array{owned: array<int, array<string, mixed>>, member: array<int, array<string, mixed>>}
 */
function juntaplay_chat_groups_for_user(int $user_id): array
{
    $owned  = [];
    $member = [];

    if (class_exists(Groups::class)) {
        $raw = Groups::get_groups_for_user($user_id);

        $owned_rows  = isset($raw['owned']) && is_array($raw['owned']) ? $raw['owned'] : [];
        $member_rows = isset($raw['member']) && is_array($raw['member']) ? $raw['member'] : [];

        foreach ($owned_rows as $group) {
            if (!is_array($group)) {
                continue;
            }

            $owned[] = [
                'id'       => isset($group['id']) ? (int) $group['id'] : 0,
                'title'    => isset($group['title']) ? (string) $group['title'] : '',
                'subtitle' => isset($group['pool_title']) ? (string) $group['pool_title'] : '',
                'avatar'   => isset($group['icon_url']) ? (string) $group['icon_url'] : '',
                'owner_id' => isset($group['owner_id']) ? (int) $group['owner_id'] : 0,
                'is_admin' => true,
            ];
        }

        foreach ($member_rows as $group) {
            if (!is_array($group)) {
                continue;
            }

            $member[] = [
                'id'       => isset($group['id']) ? (int) $group['id'] : 0,
                'title'    => isset($group['title']) ? (string) $group['title'] : '',
                'subtitle' => isset($group['pool_title']) ? (string) $group['pool_title'] : '',
                'avatar'   => isset($group['icon_url']) ? (string) $group['icon_url'] : '',
                'owner_id' => isset($group['owner_id']) ? (int) $group['owner_id'] : 0,
                'is_admin' => false,
            ];
        }
    }

    return [
        'owned'  => $owned,
        'member' => $member,
    ];
}

/**
 * Register REST routes for chat operations.
 */
function juntaplay_chat_register_routes(): void
{
    register_rest_route('juntaplay/v1', '/chat/messages', [
        'methods'             => WP_REST_Server::READABLE,
        'permission_callback' => 'is_user_logged_in',
        'args'                => [
            'admin_id'      => ['required' => true, 'type' => 'integer'],
            'subscriber_id' => ['required' => true, 'type' => 'integer'],
            'group_id'      => ['required' => false, 'type' => 'integer'],
            'mark_read'     => ['required' => false, 'type' => 'boolean'],
        ],
        'callback'            => 'juntaplay_chat_rest_get_messages',
    ]);

    register_rest_route('juntaplay/v1', '/chat/send', [
        'methods'             => WP_REST_Server::CREATABLE,
        'permission_callback' => 'is_user_logged_in',
        'args'                => [
            'admin_id'      => ['required' => true, 'type' => 'integer'],
            'subscriber_id' => ['required' => true, 'type' => 'integer'],
            'group_id'      => ['required' => false, 'type' => 'integer', 'default' => 0],
            'message'       => ['required' => false, 'type' => 'string'],
            'attachment'    => ['required' => false, 'type' => 'string'],
        ],
        'callback'            => 'juntaplay_chat_rest_send',
    ]);

    register_rest_route('juntaplay/v1', '/chat/context', [
        'methods'             => WP_REST_Server::READABLE,
        'permission_callback' => 'is_user_logged_in',
        'args'                => [
            'group_id' => ['required' => false, 'type' => 'integer'],
        ],
        'callback'            => 'juntaplay_chat_rest_context',
    ]);
}

function juntaplay_chat_rest_get_messages(WP_REST_Request $request)
{
    $admin_id      = (int) $request->get_param('admin_id');
    $subscriber_id = (int) $request->get_param('subscriber_id');
    $group_id      = $request->get_param('group_id');
    $group_id      = $group_id !== null ? (int) $group_id : null;
    $mark_read     = $request->get_param('mark_read');
    $mark_read     = $mark_read === null ? true : (bool) $mark_read;

    if (!juntaplay_chat_current_user_can_access($admin_id, $subscriber_id)) {
        return new WP_Error('juntaplay_forbidden', __('Você não pode visualizar esta conversa.', 'juntaplay'));
    }

    $messages = juntaplay_obter_mensagens($admin_id, $subscriber_id, $group_id);

    if ($mark_read) {
        juntaplay_chat_marcar_como_lidas(get_current_user_id(), $admin_id, $subscriber_id, $group_id);
    }

    $mapped = array_map(static function ($message) {
        return [
            'id'         => (int) $message['id'],
            'sender_id'  => (int) $message['sender_id'],
            'recipient_id' => (int) $message['recipient_id'],
            'message'    => (string) $message['message'],
            'attachment' => isset($message['attachment_url']) ? (string) $message['attachment_url'] : '',
            'created_at' => (string) $message['created_at'],
            'read_at'    => isset($message['read_at']) ? (string) $message['read_at'] : null,
        ];
    }, $messages);

    return rest_ensure_response([
        'messages' => $mapped,
    ]);
}

function juntaplay_chat_rest_send(WP_REST_Request $request)
{
    $admin_id      = (int) $request->get_param('admin_id');
    $subscriber_id = (int) $request->get_param('subscriber_id');
    $group_id      = (int) $request->get_param('group_id');
    $message       = (string) ($request->get_param('message') ?? '');
    $attachment    = (string) ($request->get_param('attachment') ?? '');

    if (!juntaplay_chat_current_user_can_access($admin_id, $subscriber_id)) {
        return new WP_Error('juntaplay_forbidden', __('Você não pode enviar mensagens nesta conversa.', 'juntaplay'));
    }

    $result = juntaplay_enviar_mensagem([
        'admin_id'      => $admin_id,
        'subscriber_id' => $subscriber_id,
        'group_id'      => $group_id,
        'sender_id'     => get_current_user_id(),
        'message'       => $message,
        'attachment'    => $attachment,
    ]);

    if (is_wp_error($result)) {
        return $result;
    }

    $created = juntaplay_obter_mensagens($admin_id, $subscriber_id, $group_id);
    $last    = end($created);

    return rest_ensure_response([
        'message_id' => (int) $result,
        'message'    => $last ? [
            'id'         => (int) $last['id'],
            'sender_id'  => (int) $last['sender_id'],
            'recipient_id' => (int) $last['recipient_id'],
            'message'    => (string) $last['message'],
            'attachment' => isset($last['attachment_url']) ? (string) $last['attachment_url'] : '',
            'created_at' => (string) $last['created_at'],
            'read_at'    => isset($last['read_at']) ? (string) $last['read_at'] : null,
        ] : null,
    ]);
}

function juntaplay_chat_rest_context(WP_REST_Request $request)
{
    $current_user = get_current_user_id();
    $group_id     = (int) ($request->get_param('group_id') ?? 0);

    $groups = juntaplay_chat_groups_for_user($current_user);

    $normalized_groups = array_merge($groups['owned'], $groups['member']);

    $subscribers = [];

    if ($group_id > 0) {
        $subscribers = juntaplay_chat_group_subscribers($group_id, $current_user);
    }

    return rest_ensure_response([
        'groups'      => $normalized_groups,
        'subscribers' => $subscribers,
        'user'        => juntaplay_chat_user_snapshot($current_user),
    ]);
}

function juntaplay_chat_ajax_forbidden(): void
{
    wp_send_json_error(['message' => __('É necessário estar logado para usar o chat.', 'juntaplay')], 403);
}

function juntaplay_chat_ajax_send(): void
{
    if (!is_user_logged_in()) {
        juntaplay_chat_ajax_forbidden();
    }

    check_ajax_referer('wp_rest');

    $request = new WP_REST_Request('POST', '/juntaplay/v1/chat/send');
    $request->set_param('admin_id', (int) ($_POST['admin_id'] ?? 0));
    $request->set_param('subscriber_id', (int) ($_POST['subscriber_id'] ?? 0));
    $request->set_param('group_id', (int) ($_POST['group_id'] ?? 0));
    $request->set_param('message', (string) ($_POST['message'] ?? ''));
    $request->set_param('attachment', (string) ($_POST['attachment'] ?? ''));

    $response = juntaplay_chat_rest_send($request);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message(), 400);
    }

    wp_send_json_success($response);
}

function juntaplay_chat_ajax_messages(): void
{
    if (!is_user_logged_in()) {
        juntaplay_chat_ajax_forbidden();
    }

    check_ajax_referer('wp_rest');

    $request = new WP_REST_Request('GET', '/juntaplay/v1/chat/messages');
    $request->set_param('admin_id', (int) ($_GET['admin_id'] ?? 0));
    $request->set_param('subscriber_id', (int) ($_GET['subscriber_id'] ?? 0));
    $request->set_param('group_id', (int) ($_GET['group_id'] ?? 0));
    $request->set_param('mark_read', true);

    $response = juntaplay_chat_rest_get_messages($request);

    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message(), 400);
    }

    wp_send_json_success($response);
}

/**
 * Placeholder shortcode for the chat center.
 */
function juntaplay_chat_center_shortcode(): string
{
    $current_user_id = get_current_user_id();

    if ($current_user_id <= 0) {
        return '';
    }

    $groups_data        = juntaplay_chat_groups_for_user($current_user_id);
    $chat_groups        = $groups_data['owned'];
    $chat_member_groups = $groups_data['member'];

    $chat_is_admin        = !empty($chat_groups);
    $chat_admin_id        = 0;
    $chat_admin_name      = '';
    $chat_admin_avatar    = '';
    $chat_subscriber_id   = 0;
    $chat_recipient_name  = '';
    $chat_recipient_group = '';
    $chat_recipient_avatar = '';
    $initial_group_id     = 0;
    $initial_group_name   = '';

    if ($chat_is_admin) {
        $initial_group_id   = isset($chat_groups[0]['id']) ? (int) $chat_groups[0]['id'] : 0;
        $initial_group_name = isset($chat_groups[0]['title']) ? (string) $chat_groups[0]['title'] : '';
        $chat_admin_id      = $current_user_id;
        $chat_admin_name    = (string) wp_get_current_user()->display_name;
        $chat_admin_avatar  = get_avatar_url($chat_admin_id, ['size' => 96]);
    } elseif (!empty($chat_member_groups)) {
        $initial_group_id   = isset($chat_member_groups[0]['id']) ? (int) $chat_member_groups[0]['id'] : 0;
        $initial_group_name = isset($chat_member_groups[0]['title']) ? (string) $chat_member_groups[0]['title'] : '';
        $chat_admin_id      = isset($chat_member_groups[0]['owner_id']) ? (int) $chat_member_groups[0]['owner_id'] : 0;
        $chat_subscriber_id = $current_user_id;
        $chat_recipient_group = $initial_group_name;

        if ($chat_admin_id > 0) {
            $admin_user = get_userdata($chat_admin_id);
            if ($admin_user) {
                $chat_admin_name  = (string) $admin_user->display_name;
                $chat_admin_avatar = (string) get_avatar_url($chat_admin_id, ['size' => 96]);
            }
        }

        $current_user = wp_get_current_user();
        if ($current_user && $current_user->exists()) {
            $chat_recipient_name   = (string) $current_user->display_name;
            $chat_recipient_avatar = (string) get_avatar_url($current_user_id, ['size' => 96]);
        }
    }

    $chat_subscribers = $initial_group_id > 0 ? juntaplay_chat_group_subscribers($initial_group_id, $current_user_id) : [];

    $chat_config = [
        'rest' => [
            'send'     => rest_url('juntaplay/v1/chat/send'),
            'messages' => rest_url('juntaplay/v1/chat/messages'),
            'context'  => rest_url('juntaplay/v1/chat/context'),
        ],
        'nonce'            => wp_create_nonce('wp_rest'),
        'currentUserId'    => $current_user_id,
        'isAdmin'          => $chat_is_admin,
        'groups'           => $chat_groups,
        'memberGroups'     => $chat_member_groups,
        'initialGroupId'   => $initial_group_id,
        'initialGroupName' => $initial_group_name,
        'userName'         => $chat_recipient_name,
        'adminName'        => $chat_admin_name,
        'adminAvatar'      => $chat_admin_avatar,
        'recipientName'    => $chat_recipient_name,
        'recipientAvatar'  => $chat_recipient_avatar,
        'adminId'          => $chat_admin_id,
        'subscriberId'     => $chat_subscriber_id,
    ];

    ob_start();
    ?>
    <style>
        .juntaplay-chat-shell__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0 20px;
        }

        .juntaplay-chat-shell__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
            font-weight: 600;
            color: #0f172a;
        }

        .juntaplay-chat-shell__subtitle {
            margin: 0;
            color: #334155;
            font-size: 15px;
            font-weight: 500;
        }

        .juntaplay-chat-surface {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 16px;
            align-items: stretch;
        }

        .juntaplay-chat-shell {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.5));
            border-radius: 24px;
            padding: 24px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
        }

        .juntaplay-chat-panel {
            display: grid;
            gap: 12px;
        }

        .juntaplay-chat-panel__section {
            padding: 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(10px);
        }

        .juntaplay-chat-panel__section.is-muted {
            opacity: 0.75;
        }

        .juntaplay-chat-card-list {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .juntaplay-chat-card {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(14px);
            cursor: pointer;
            transition: transform 120ms ease, box-shadow 120ms ease, background 120ms ease;
        }

        .juntaplay-chat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .juntaplay-chat-card.is-active {
            outline: 2px solid #0ea5e9;
            background: rgba(14, 165, 233, 0.08);
        }

        .juntaplay-chat-card__avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(14, 165, 233, 0.05));
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            overflow: hidden;
        }

        .juntaplay-chat-card__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .juntaplay-chat-card__meta {
            display: grid;
            gap: 4px;
        }

        .juntaplay-chat-card__name {
            font-size: 18px;
            margin: 0;
            color: #0f172a;
        }

        .juntaplay-chat-card__group {
            font-size: 14px;
            color: #475569;
        }

        .juntaplay-chat-window {
            min-height: 480px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(14px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6), 0 20px 50px rgba(15, 23, 42, 0.12);
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        .juntaplay-chat-window__header {
            padding: 18px 20px 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        }

        .juntaplay-chat-window__participants {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .juntaplay-chat-window__avatars {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .juntaplay-chat-window__avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.16), rgba(14, 165, 233, 0.06));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #0f172a;
            overflow: hidden;
        }

        .juntaplay-chat-window__avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .juntaplay-chat-window__meta h3 {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
        }

        .juntaplay-chat-window__meta p {
            margin: 2px 0 0;
            color: #475569;
        }

        .juntaplay-chat-window__messages {
            padding: 16px 20px;
            overflow-y: auto;
            display: grid;
            gap: 10px;
        }

        .juntaplay-chat-message {
            display: grid;
            justify-items: start;
            gap: 4px;
        }

        .juntaplay-chat-message.is-mine {
            justify-items: end;
        }

        .juntaplay-chat-message__bubble {
            max-width: 85%;
            background: #0ea5e9;
            color: #fff;
            padding: 10px 14px;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.2);
        }

        .juntaplay-chat-message.is-mine .juntaplay-chat-message__bubble {
            background: #0f172a;
        }

        .juntaplay-chat-message__time {
            font-size: 12px;
            color: #475569;
        }

        .juntaplay-chat-window__composer {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
        }

        .juntaplay-chat-window__input {
            flex: 1;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 999px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.9);
        }

        .juntaplay-chat-window__action {
            border: none;
            border-radius: 14px;
            padding: 12px 16px;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .juntaplay-chat-window__upload {
            border: 1px dashed rgba(15, 23, 42, 0.3);
            border-radius: 12px;
            padding: 10px;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .juntaplay-chat-status {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            .juntaplay-chat-surface {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="juntaplay-chat-shell__header">
        <span class="juntaplay-chat-shell__eyebrow"><?php esc_html_e('Fale com Assinantes', 'juntaplay'); ?></span>
        <p class="juntaplay-chat-shell__subtitle"><?php esc_html_e('Selecione um assinante para iniciar o chat.', 'juntaplay'); ?></p>
    </div>
    <div class="juntaplay-chat-shell" data-juntaplay-chat data-chat-config="<?php echo esc_attr(wp_json_encode($chat_config)); ?>">
        <div class="juntaplay-chat-surface">
            <aside class="juntaplay-chat-panel">
                <div class="juntaplay-chat-panel__section" data-chat-panel="groups">
                    <h3 class="juntaplay-chat-section__title"><?php esc_html_e('Grupos', 'juntaplay'); ?></h3>
                    <ul class="juntaplay-chat-card-list" data-chat-group-list>
                        <?php foreach ($chat_groups as $chat_group) :
                            $group_title   = isset($chat_group['title']) ? (string) $chat_group['title'] : __('Grupo', 'juntaplay');
                            $group_meta    = isset($chat_group['subtitle']) ? (string) $chat_group['subtitle'] : __('Assinaturas ativas', 'juntaplay');
                            $group_avatar  = isset($chat_group['avatar']) ? (string) $chat_group['avatar'] : '';
                            $group_initial = $group_title !== '' ? mb_substr($group_title, 0, 1) : 'G';
                            ?>
                            <li class="juntaplay-chat-card" data-chat-select="group">
                                <span class="juntaplay-chat-card__avatar" aria-hidden="true">
                                    <?php if ($group_avatar !== '') : ?>
                                        <img src="<?php echo esc_url($group_avatar); ?>" alt="" loading="lazy" />
                                    <?php else : ?>
                                        <?php echo esc_html($group_initial); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="juntaplay-chat-card__meta">
                                    <strong class="juntaplay-chat-card__name"><?php echo esc_html($group_title); ?></strong>
                                    <span class="juntaplay-chat-card__group"><?php echo esc_html($group_meta); ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="juntaplay-chat-panel__section is-muted" data-chat-panel="subscribers">
                    <h3 class="juntaplay-chat-section__title"><?php esc_html_e('Assinantes', 'juntaplay'); ?></h3>
                    <ul class="juntaplay-chat-card-list" data-chat-subscriber-list>
                        <?php foreach ($chat_subscribers as $chat_subscriber) :
                            $subscriber_name   = isset($chat_subscriber['name']) ? (string) $chat_subscriber['name'] : __('Assinante', 'juntaplay');
                            $subscriber_group  = isset($chat_subscriber['group']) ? (string) $chat_subscriber['group'] : __('Grupo', 'juntaplay');
                            $subscriber_avatar = isset($chat_subscriber['avatar']) ? (string) $chat_subscriber['avatar'] : '';
                            $subscriber_initial = $subscriber_name !== '' ? mb_substr($subscriber_name, 0, 1) : 'A';
                            ?>
                            <li class="juntaplay-chat-card" data-chat-select="subscriber">
                                <span class="juntaplay-chat-card__avatar" aria-hidden="true">
                                    <?php if ($subscriber_avatar !== '') : ?>
                                        <img src="<?php echo esc_url($subscriber_avatar); ?>" alt="" loading="lazy" />
                                    <?php else : ?>
                                        <?php echo esc_html($subscriber_initial); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="juntaplay-chat-card__meta">
                                    <strong class="juntaplay-chat-card__name"><?php echo esc_html($subscriber_name); ?></strong>
                                    <span class="juntaplay-chat-card__group"><?php echo esc_html($subscriber_group); ?></span>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

            <section class="juntaplay-chat-window" data-chat-window>
                <header class="juntaplay-chat-window__header">
                    <div class="juntaplay-chat-window__participants">
                        <div class="juntaplay-chat-window__avatars" aria-hidden="true">
                            <span class="juntaplay-chat-window__avatar juntaplay-chat-window__avatar--admin" data-chat-avatar="admin">
                                <?php if ($chat_admin_avatar !== '') : ?>
                                    <img src="<?php echo esc_url($chat_admin_avatar); ?>" alt="" loading="lazy" />
                                <?php else : ?>
                                    <?php echo esc_html($chat_admin_name !== '' ? mb_substr($chat_admin_name, 0, 1) : 'A'); ?>
                                <?php endif; ?>
                            </span>
                            <span class="juntaplay-chat-window__avatar" data-chat-avatar="recipient">
                                <?php if ($chat_recipient_avatar !== '') : ?>
                                    <img src="<?php echo esc_url($chat_recipient_avatar); ?>" alt="" loading="lazy" />
                                <?php elseif ($chat_recipient_name !== '') : ?>
                                    <?php echo esc_html(mb_substr($chat_recipient_name, 0, 1)); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="juntaplay-chat-window__meta">
                            <h3><?php echo esc_html($chat_recipient_name !== '' ? $chat_recipient_name : __('Selecione um assinante', 'juntaplay')); ?></h3>
                            <p><?php echo esc_html($chat_recipient_group !== '' ? $chat_recipient_group : __('Escolha um participante para começar', 'juntaplay')); ?></p>
                        </div>
                    </div>
                </header>

                <div class="juntaplay-chat-window__messages" data-chat-thread aria-live="polite"></div>

                <div class="juntaplay-chat-window__composer">
                    <button type="button" class="juntaplay-chat-window__upload" aria-label="<?php esc_attr_e('Enviar arquivo', 'juntaplay'); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14m0 0 5-5m-5 5-5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 12V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <input type="text" class="juntaplay-chat-window__input" placeholder="<?php echo esc_attr__('Digite sua mensagem...', 'juntaplay'); ?>" aria-label="<?php echo esc_attr__('Digite sua mensagem...', 'juntaplay'); ?>" />
                    <button type="button" class="juntaplay-chat-window__action">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="m22 2-7.5 20-3.5-8L2 10Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php esc_html_e('Enviar', 'juntaplay'); ?>
                    </button>
                </div>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-juntaplay-chat]');
            if (!root) return;

            const config = (() => {
                try { return JSON.parse(root.dataset.chatConfig || '{}'); } catch (e) { return {}; }
            })();

            const thread = root.querySelector('[data-chat-thread]');
            const groupList = root.querySelector('[data-chat-group-list]');
            const subscriberList = root.querySelector('[data-chat-subscriber-list]');
            const headerTitle = root.querySelector('.juntaplay-chat-window__meta h3');
            const headerSubtitle = root.querySelector('.juntaplay-chat-window__meta p');
            const input = root.querySelector('.juntaplay-chat-window__input');
            const sendButton = root.querySelector('.juntaplay-chat-window__action');
            const sidePanel = root.querySelector('.juntaplay-chat-panel');

            let activeGroupId = config.initialGroupId || 0;
            let activeAdminId = config.adminId || 0;
            let activeSubscriberId = config.subscriberId || 0;
            let groups = Array.isArray(config.groups) ? config.groups : [];
            const memberGroups = Array.isArray(config.memberGroups) ? config.memberGroups : [];

            const api = (url, options = {}) => {
                if (!config.nonce) return Promise.reject();
                const headers = Object.assign({ 'X-WP-Nonce': config.nonce }, options.headers || {});
                return fetch(url, Object.assign({}, options, { headers, credentials: 'include' }))
                    .then((res) => res.ok ? res.json() : Promise.reject(res));
            };

            const setStatus = (text) => {
                if (!thread) return;
                thread.innerHTML = `<p class="juntaplay-chat-status">${text}</p>`;
            };

            const renderMessages = (items = []) => {
                if (!thread) return;
                if (!items.length) {
                    setStatus('<?php echo esc_js(__('Envie a primeira mensagem.', 'juntaplay')); ?>');
                    return;
                }

                thread.innerHTML = '';
                items.forEach((msg) => {
                    const isMine = msg.sender_id === config.currentUserId;
                    const wrapper = document.createElement('div');
                    wrapper.className = `juntaplay-chat-message${isMine ? ' is-mine' : ''}`;
                    const bubble = document.createElement('div');
                    bubble.className = 'juntaplay-chat-message__bubble';
                    bubble.textContent = msg.message || '';
                    const time = document.createElement('span');
                    time.className = 'juntaplay-chat-message__time';
                    time.textContent = (msg.created_at || '').replace('T', ' ');
                    wrapper.appendChild(bubble);
                    wrapper.appendChild(time);
                    thread.appendChild(wrapper);
                });
                thread.scrollTop = thread.scrollHeight;
            };

            const renderSubscribers = (items = []) => {
                if (!subscriberList) return;
                subscriberList.innerHTML = '';

                items.forEach((subscriber) => {
                    const avatar = subscriber.avatar ? `<img src="${subscriber.avatar}" alt="" loading="lazy" />` : (subscriber.name || '').slice(0, 1);
                    const li = document.createElement('li');
                    li.className = 'juntaplay-chat-card';
                    li.dataset.chatSelect = 'subscriber';
                    li.dataset.subscriberId = subscriber.id;
                    li.innerHTML = `
                        <span class="juntaplay-chat-card__avatar" aria-hidden="true">${avatar}</span>
                        <span class="juntaplay-chat-card__meta">
                            <strong class="juntaplay-chat-card__name">${subscriber.name || '<?php echo esc_js(__('Assinante', 'juntaplay')); ?>'}</strong>
                            <span class="juntaplay-chat-card__group">${subscriber.group || ''}</span>
                        </span>
                    `;
                    li.addEventListener('click', () => selectSubscriber(subscriber.id, subscriber.name, subscriber.group, subscriber.avatar));
                    subscriberList.appendChild(li);
                });
            };

            const renderGroups = (items = []) => {
                if (!groupList) return;
                groupList.innerHTML = '';

                items.forEach((group) => {
                    const avatar = group.avatar ? `<img src="${group.avatar}" alt="" loading="lazy" />` : (group.title || '').slice(0, 1);
                    const li = document.createElement('li');
                    li.className = `juntaplay-chat-card${group.id === activeGroupId ? ' is-active' : ''}`;
                    li.dataset.chatSelect = 'group';
                    li.dataset.groupId = group.id;
                    li.innerHTML = `
                        <span class="juntaplay-chat-card__avatar" aria-hidden="true">${avatar}</span>
                        <span class="juntaplay-chat-card__meta">
                            <strong class="juntaplay-chat-card__name">${group.title || '<?php echo esc_js(__('Grupo', 'juntaplay')); ?>'}</strong>
                            <span class="juntaplay-chat-card__group">${group.subtitle || ''}</span>
                        </span>
                    `;
                    li.addEventListener('click', () => selectGroup(group.id));
                    groupList.appendChild(li);
                });
            };

            const selectGroup = (groupId) => {
                activeGroupId = groupId;
                activeAdminId = config.adminId || config.currentUserId || activeAdminId;
                activeSubscriberId = 0;
                renderGroups(groups);
                renderSubscribers([]);
                setStatus('<?php echo esc_js(__('Selecione um assinante para iniciar o chat.', 'juntaplay')); ?>');

                api(config.rest.context + '?group_id=' + groupId)
                    .then((ctx) => {
                        if (ctx && Array.isArray(ctx.subscribers)) {
                            renderSubscribers(ctx.subscribers);
                        }
                        if (headerTitle) headerTitle.textContent = '<?php echo esc_js(__('Selecione um assinante', 'juntaplay')); ?>';
                        if (headerSubtitle) headerSubtitle.textContent = '<?php echo esc_js(__('Escolha um participante para começar', 'juntaplay')); ?>';
                    })
                    .catch(() => setStatus('<?php echo esc_js(__('Não foi possível carregar assinantes.', 'juntaplay')); ?>'));
            };

            const selectSubscriber = (subscriberId, name, groupName, avatar) => {
                activeSubscriberId = subscriberId;
                if (headerTitle) headerTitle.textContent = name || '';
                if (headerSubtitle) headerSubtitle.textContent = groupName || '';

                renderMessages([]);

                const params = new URLSearchParams({
                    admin_id: activeAdminId || config.adminId || 0,
                    subscriber_id: subscriberId,
                    group_id: activeGroupId || 0,
                    mark_read: true,
                });

                api(config.rest.messages + '?' + params.toString())
                    .then((payload) => renderMessages((payload && payload.messages) || []))
                    .catch(() => setStatus('<?php echo esc_js(__('Não foi possível carregar mensagens.', 'juntaplay')); ?>'));
            };

            const sendMessage = () => {
                if (!input || !activeSubscriberId) return;
                const message = input.value.trim();
                if (!message) return;

                const body = JSON.stringify({
                    admin_id: activeAdminId || config.adminId || 0,
                    subscriber_id: activeSubscriberId,
                    group_id: activeGroupId || 0,
                    message,
                });

                api(config.rest.send, { method: 'POST', body, headers: { 'Content-Type': 'application/json' } })
                    .then(() => {
                        input.value = '';
                        const params = new URLSearchParams({
                            admin_id: activeAdminId || config.adminId || 0,
                            subscriber_id: activeSubscriberId,
                            group_id: activeGroupId || 0,
                            mark_read: true,
                        });

                        return api(config.rest.messages + '?' + params.toString());
                    })
                    .then((payload) => renderMessages((payload && payload.messages) || []))
                    .catch(() => setStatus('<?php echo esc_js(__('Falha ao enviar mensagem.', 'juntaplay')); ?>'));
            };

            if (sendButton) sendButton.addEventListener('click', sendMessage);
            if (input) input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    sendMessage();
                }
            });

            if (!config.isAdmin && memberGroups.length) {
                groups = memberGroups;
                if (sidePanel) sidePanel.hidden = true;
                if (memberGroups[0]) {
                    activeGroupId = memberGroups[0].id;
                    activeAdminId = memberGroups[0].owner_id || config.adminId || 0;
                    selectSubscriber(config.subscriberId || config.currentUserId, config.recipientName || '', memberGroups[0].title || '', config.recipientAvatar || '');
                }
            } else {
                renderGroups(groups);
                if (groups[0]) {
                    activeGroupId = groups[0].id;
                    activeAdminId = groups[0].owner_id || config.adminId || config.currentUserId;
                    selectGroup(activeGroupId);
                }
            }
        })();
    </script>
    <?php
    return (string) ob_get_clean();
}

/**
 * Full chat shell shortcode.
 */
function juntaplay_chatonline_shortcode(): string
{
    return juntaplay_chat_center_shortcode();
}

function juntaplay_chat_register_shortcodes(): void
{
    add_shortcode('juntaplay_chat_center', 'juntaplay_chat_center_shortcode');
    add_shortcode('juntaplay_chatonline', 'juntaplay_chatonline_shortcode');
}

add_action('init', 'juntaplay_chat_register_shortcodes');
