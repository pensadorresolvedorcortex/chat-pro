<?php
/**
 * Plugin Name: JuntaPlay Chat
 * Description: Backend e endpoints completos do chat JuntaPlay (fases 1–8).
 * Version: 1.1.0
 */

declare(strict_types=1);

use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\Groups;

if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, 'juntaplay_chat_install');
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

    juntaplay_chat_increment_unread($recipient_id, $sender_id, $message_id, $admin_id, $subscriber_id, $group_id);

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
function juntaplay_chat_increment_unread(int $recipient_id, int $sender_id, int $message_id, ?int $admin_id = null, ?int $subscriber_id = null, ?int $group_id = null): void
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
        'sender_id'     => $sender_id,
        'sender'        => $sender_name,
        'admin_id'      => $admin_id,
        'subscriber_id' => $subscriber_id,
        'group_id'      => $group_id,
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

    $unread = array_values(array_filter($unread, static function ($entry) use ($admin_id, $subscriber_id, $group_id) {
        $matches = isset($entry['admin_id'], $entry['subscriber_id'])
            && (int) $entry['admin_id'] === $admin_id
            && (int) $entry['subscriber_id'] === $subscriber_id;

        if ($matches && $group_id !== null && isset($entry['group_id'])) {
            $matches = (int) $entry['group_id'] === $group_id;
        }

        return !$matches;
    }));

    update_user_meta($user_id, 'juntaplay_mensagens_nao_lidas', $unread);
}

/**
 * Return a sanitized user snapshot.
 */
function juntaplay_chat_user_avatar_from_meta(int $user_id, int $size = 96): string
{
    if ($user_id <= 0) {
        return '';
    }

    $meta_keys = apply_filters(
        'juntaplay_chat_avatar_meta_keys',
        [
            'juntaplay_avatar',
            'juntaplay_avatar_url',
            'avatar_url',
            'avatar',
            'profile_avatar',
            'profile_picture',
            'user_avatar',
            'picture',
            'photo',
            'google_avatar',
        ]
    );

    foreach ($meta_keys as $key) {
        $value = get_user_meta($user_id, $key, true);

        if (is_array($value)) {
            if (isset($value['full']) && is_string($value['full'])) {
                $value = $value['full'];
            } elseif (isset($value['url']) && is_string($value['url'])) {
                $value = $value['url'];
            } else {
                $first = array_values(array_filter($value, 'is_string'));
                $value = isset($first[0]) ? $first[0] : '';
            }
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value !== '') {
                return esc_url_raw($value);
            }
        }
    }

    $fallback = apply_filters('juntaplay_chat_default_avatar', '', $user_id, $size);

    return is_string($fallback) ? (string) $fallback : '';
}

function juntaplay_chat_user_snapshot(int $user_id): array
{
    $user = get_userdata($user_id);

    if (!$user) {
        return [];
    }

    return [
        'id'     => $user_id,
        'name'   => trim($user->display_name),
        'avatar' => juntaplay_chat_user_avatar_from_meta($user_id, 96),
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
    $ids      = GroupMembers::get_user_ids($group_id, 'active');

    $normalized = [];

    foreach ($ids as $id) {
        $id = (int) $id;

        if ($id <= 0 || $id === $owner_id || $id === $current_user_id) {
            continue;
        }

        $user = get_userdata($id);

        $normalized[] = [
            'id'     => $id,
            'name'   => $user ? (string) $user->display_name : '',
            'group'  => '',
            'avatar' => juntaplay_chat_user_avatar_from_meta($id, 96),
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
        'threads'     => juntaplay_chat_threads_for_user($current_user),
        'user'        => juntaplay_chat_user_snapshot($current_user),
    ]);
}

function juntaplay_chat_threads_for_user(int $user_id): array
{
    global $wpdb;

    $user_id = max(0, $user_id);

    if ($user_id === 0) {
        return [];
    }

    $table = juntaplay_chat_table();

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE admin_id = %d OR subscriber_id = %d ORDER BY created_at DESC",
            $user_id,
            $user_id
        ),
        ARRAY_A
    );

    if (!is_array($rows) || !$rows) {
        return [];
    }

    $unread_counts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT admin_id, subscriber_id, group_id, COUNT(*) AS unread_total FROM {$table} WHERE recipient_id = %d AND (read_at IS NULL OR read_at = '') GROUP BY admin_id, subscriber_id, group_id",
            $user_id
        ),
        ARRAY_A
    );

    $unread_map = [];
    foreach ($unread_counts as $entry) {
        $admin_id      = isset($entry['admin_id']) ? (int) $entry['admin_id'] : 0;
        $subscriber_id = isset($entry['subscriber_id']) ? (int) $entry['subscriber_id'] : 0;
        $group_id      = isset($entry['group_id']) ? (int) $entry['group_id'] : 0;
        if ($admin_id === 0 || $subscriber_id === 0) {
            continue;
        }
        $unread_map[$admin_id . ':' . $subscriber_id . ':' . $group_id] = (int) ($entry['unread_total'] ?? 0);
    }

    $threads     = [];
    $user_cache  = [];
    $group_cache = [];

    foreach ($rows as $row) {
        $admin_id      = isset($row['admin_id']) ? (int) $row['admin_id'] : 0;
        $subscriber_id = isset($row['subscriber_id']) ? (int) $row['subscriber_id'] : 0;
        $group_id      = isset($row['group_id']) ? (int) $row['group_id'] : 0;
        $thread_id     = isset($row['thread_id']) ? (int) $row['thread_id'] : 0;

        if ($admin_id === 0 || $subscriber_id === 0) {
            continue;
        }

        $key = $admin_id . ':' . $subscriber_id . ':' . $group_id;

        $has_unread_map = array_key_exists($key, $unread_map);

        $counterpart_id = 0;
        if ($user_id === $admin_id) {
            $counterpart_id = $subscriber_id;
        } elseif ($user_id === $subscriber_id) {
            $counterpart_id = $admin_id;
        } elseif (isset($row['sender_id'], $row['recipient_id'])) {
            $counterpart_id = (int) ($row['sender_id'] === $user_id ? $row['recipient_id'] : $row['sender_id']);
        }

        $counterpart_id = max(0, $counterpart_id);

        if (!isset($threads[$key])) {
            $thread_key = $thread_id > 0 ? $thread_id : (int) abs(crc32($key));

            if ($admin_id > 0 && !isset($user_cache[$admin_id])) {
                $user_cache[$admin_id] = get_userdata($admin_id) ?: null;
            }

            if ($subscriber_id > 0 && !isset($user_cache[$subscriber_id])) {
                $user_cache[$subscriber_id] = get_userdata($subscriber_id) ?: null;
            }

            if ($group_id > 0 && class_exists(Groups::class) && !isset($group_cache[$group_id])) {
                $group_cache[$group_id] = Groups::get($group_id);
            }

            $admin_name        = $user_cache[$admin_id] ? (string) $user_cache[$admin_id]->display_name : '';
            $admin_avatar_url  = $admin_id > 0 ? juntaplay_chat_user_avatar_from_meta($admin_id, 96) : '';
            $subscriber_name   = $user_cache[$subscriber_id] ? (string) $user_cache[$subscriber_id]->display_name : '';
            $subscriber_avatar = $subscriber_id > 0 ? juntaplay_chat_user_avatar_from_meta($subscriber_id, 96) : '';

            $group_entry = $group_cache[$group_id] ?? null;
            $group_name  = is_array($group_entry) ? (string) ($group_entry['title'] ?? '') : '';

            $counterpart         = $counterpart_id > 0 ? ($user_cache[$counterpart_id] ?? get_userdata($counterpart_id)) : null;
            $counterpart_name    = $counterpart ? (string) $counterpart->display_name : '';
            $counterpart_avatar  = $counterpart_id > 0 ? juntaplay_chat_user_avatar_from_meta($counterpart_id, 64) : '';

            $threads[$key] = [
                'thread_id'          => $thread_key,
                'group_id'           => $group_id,
                'group_name'         => $group_name,
                'admin_id'           => $admin_id,
                'subscriber_id'      => $subscriber_id,
                'admin_name'         => $admin_name,
                'admin_avatar'       => $admin_avatar_url,
                'subscriber_name'    => $subscriber_name,
                'subscriber_avatar'  => $subscriber_avatar,
                'counterpart_id'     => $counterpart_id,
                'counterpart_name'   => $counterpart_name,
                'counterpart_avatar' => $counterpart_avatar,
                'counterpart_avatar_url' => $counterpart_avatar,
                'last_message'       => '',
                'last_message_at'    => '',
                'unread_count'       => $has_unread_map ? (int) $unread_map[$key] : 0,
            ];
        }

        if ($threads[$key]['last_message'] === '') {
            $threads[$key]['last_message']    = isset($row['message']) ? (string) $row['message'] : '';
            $threads[$key]['last_message_at'] = isset($row['created_at']) ? (string) $row['created_at'] : '';
        }

        if (!$has_unread_map && isset($row['recipient_id']) && (int) $row['recipient_id'] === $user_id && empty($row['read_at'])) {
            $threads[$key]['unread_count'] = (int) ($threads[$key]['unread_count'] ?? 0) + 1;
        }
    }

    usort($threads, static function ($a, $b) {
        $a_time = isset($a['last_message_at']) ? strtotime((string) $a['last_message_at']) : 0;
        $b_time = isset($b['last_message_at']) ? strtotime((string) $b['last_message_at']) : 0;

        return $b_time <=> $a_time;
    });

    return array_values($threads);
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
    $profile_page_id   = (int) get_option('juntaplay_page_perfil');
    $messages_base_url = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil/');
    $messages_endpoint = user_trailingslashit(trailingslashit($messages_base_url) . 'juntaplay-chat');
    $messages_base_url = add_query_arg('section', 'juntaplay-chat', $messages_base_url);

    ob_start();
    ?>
    <div class="juntaplay-chat-placeholder" data-juntaplay-chat-placeholder>
        <h2><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></h2>
        <p><?php esc_html_e('Selecione um grupo ou assinante para iniciar a conversa.', 'juntaplay'); ?></p>
        <a class="button" href="<?php echo esc_url($messages_endpoint ?: $messages_base_url); ?>"><?php esc_html_e('Ir para o chat', 'juntaplay'); ?></a>
    </div>
    <?php
    return (string) ob_get_clean();
}
add_shortcode('juntaplay_chat_center', 'juntaplay_chat_center_shortcode');

if (!shortcode_exists('juntaplay_online')) {
    /**
     * Universal inbox shortcode: lists all conversations (admin or subscriber) with unread badges.
     */
    function juntaplay_chat_online_shortcode(): string
    {
        $profile_page_id   = (int) get_option('juntaplay_page_perfil');
        $messages_base_url = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil/');
        $messages_base_url = add_query_arg('section', 'juntaplay-chat', $messages_base_url);

        $context_url = rest_url('juntaplay/v1/chat/context');
        $messages_url = rest_url('juntaplay/v1/chat/messages');
        $send_url     = rest_url('juntaplay/v1/chat/send');
        $nonce       = wp_create_nonce('wp_rest');
        $uid         = wp_unique_id('jp-online-');

        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="juntaplay-online" data-rest-context="<?php echo esc_attr($context_url); ?>" data-rest-messages="<?php echo esc_attr($messages_url); ?>" data-rest-send="<?php echo esc_attr($send_url); ?>" data-rest-nonce="<?php echo esc_attr($nonce); ?>" data-messages-base="<?php echo esc_url($messages_base_url); ?>">
            <div class="juntaplay-online__header">
                <h3><?php esc_html_e('Conversas', 'juntaplay'); ?></h3>
                <p class="juntaplay-online__subtitle"><?php esc_html_e('Todas as suas conversas, como admin ou assinante.', 'juntaplay'); ?></p>
            </div>
            <ul class="juntaplay-online__list" data-online-thread-list></ul>
            <div class="juntaplay-online__empty" data-online-empty hidden>
                <p><?php esc_html_e('Nenhuma conversa encontrada.', 'juntaplay'); ?></p>
            </div>
            <div class="juntaplay-online__chat" data-online-chat hidden>
                <header class="juntaplay-online__chat-header">
                    <div class="juntaplay-online__chat-avatars" data-online-chat-avatars></div>
                    <div class="juntaplay-online__chat-meta">
                        <h4 data-online-chat-name></h4>
                        <p data-online-chat-group></p>
                    </div>
                </header>
                <div class="juntaplay-online__chat-thread" data-online-chat-thread aria-live="polite"></div>
                <div class="juntaplay-online__chat-compose">
                    <input type="text" data-online-chat-input placeholder="<?php echo esc_attr__('Digite sua mensagem...', 'juntaplay'); ?>" aria-label="<?php echo esc_attr__('Digite sua mensagem', 'juntaplay'); ?>" />
                    <button type="button" data-online-chat-send><?php esc_html_e('Enviar', 'juntaplay'); ?></button>
                </div>
            </div>
        </div>
        <style>
            .juntaplay-online {
                display: flex;
                flex-direction: column;
                gap: 12px;
                background: rgba(255, 255, 255, 0.82);
                border-radius: 18px;
                padding: 14px;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
                backdrop-filter: blur(16px);
            }

            .juntaplay-online__header h3 {
                margin: 0;
                font-size: 1rem;
                font-weight: 800;
                color: #0f172a;
            }

            .juntaplay-online__subtitle {
                margin: 2px 0 0;
                font-size: 13px;
                color: #6b7280;
            }

            .juntaplay-online__list {
                list-style: none;
                padding: 0;
                margin: 0;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .juntaplay-online__item {
                display: grid;
                grid-template-columns: 56px 1fr;
                gap: 12px;
                align-items: center;
                padding: 12px 12px;
                border-radius: 14px;
                background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.92));
                border: 1px solid rgba(15, 23, 42, 0.05);
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
                cursor: pointer;
                transition: transform 120ms ease, box-shadow 160ms ease;
            }

            .juntaplay-online__item:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 28px rgba(79, 70, 229, 0.12);
            }

            .juntaplay-online__avatar {
                position: relative;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                overflow: hidden;
                background: linear-gradient(135deg, #0ea5e9, #6366f1);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-weight: 700;
                font-size: 18px;
            }

            .juntaplay-online__avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .juntaplay-online__badge {
                position: absolute;
                top: -4px;
                right: -4px;
                min-width: 18px;
                height: 18px;
                padding: 0 5px;
                border-radius: 12px;
                background: linear-gradient(135deg, #22c55e, #0ea5e9);
                color: #fff;
                font-size: 11px;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 6px 14px rgba(14, 165, 233, 0.32);
            }

            .juntaplay-online__meta {
                display: grid;
                grid-template-columns: 1fr auto;
                align-items: center;
                gap: 6px;
                color: #0f172a;
            }

            .juntaplay-online__name {
                font-size: 15px;
                font-weight: 700;
                margin: 0;
            }

            .juntaplay-online__time {
                font-size: 12px;
                color: #6b7280;
            }

            .juntaplay-online__group {
                font-size: 12px;
                color: #0f172a;
                font-weight: 600;
            }

            .juntaplay-online__preview {
                font-size: 13px;
                color: #4b5563;
                margin: 2px 0 0;
                line-height: 1.35;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
            }

            .juntaplay-online__empty {
                font-size: 13px;
                color: #6b7280;
                padding: 10px 2px;
            }

            .juntaplay-online__chat {
                display: grid;
                grid-template-rows: auto 1fr auto;
                gap: 12px;
                border: 1px solid rgba(15, 23, 42, 0.06);
                border-radius: 14px;
                padding: 12px;
                background: #fff;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
                min-height: 240px;
            }

            .juntaplay-online__chat-header {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .juntaplay-online__chat-avatars {
                display: flex;
                gap: 6px;
                align-items: center;
            }

            .juntaplay-online__chat-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                overflow: hidden;
                background: linear-gradient(135deg, #0ea5e9, #6366f1);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-weight: 700;
                font-size: 15px;
            }

            .juntaplay-online__chat-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .juntaplay-online__chat-meta h4 {
                margin: 0;
                font-size: 15px;
                font-weight: 800;
                color: #0f172a;
            }

            .juntaplay-online__chat-meta p {
                margin: 2px 0 0;
                font-size: 12px;
                color: #6b7280;
            }

            .juntaplay-online__chat-thread {
                background: #f8fafc;
                border-radius: 12px;
                padding: 10px;
                overflow-y: auto;
                max-height: 360px;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .juntaplay-online__chat-message {
                display: flex;
                flex-direction: column;
                max-width: 80%;
                padding: 10px 12px;
                border-radius: 12px;
                background: #fff;
                color: #0f172a;
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.04);
            }

            .juntaplay-online__chat-message.is-own {
                margin-left: auto;
                background: linear-gradient(135deg, #6366f1, #22c55e);
                color: #fff;
            }

            .juntaplay-online__chat-time {
                margin-top: 4px;
                font-size: 11px;
                color: #6b7280;
            }

            .juntaplay-online__chat-message.is-own .juntaplay-online__chat-time {
                color: rgba(255, 255, 255, 0.82);
            }

            .juntaplay-online__chat-compose {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 8px;
            }

            .juntaplay-online__chat-compose input {
                border: 1px solid rgba(15, 23, 42, 0.12);
                border-radius: 12px;
                padding: 10px 12px;
                font-size: 14px;
                outline: none;
            }

            .juntaplay-online__chat-compose button {
                background-color: #FF4858;font-size: 15px;font-weight: 600;fill: #FFFFFF; color: #FFFFFF; border-style: solid;border-width: 2px 2px 2px 2px;border-color: #FF4858; border-radius: 60px 60px 60px 60px; padding: 12px 18px 12px 18px;
            }
            .juntaplay-online__chat-compose button:hover {
                transform: translateY(-1px);
                box-shadow: 0 10px 20px rgba(34, 197, 94, 0.25);
            }
        </style>
        <script>
            (() => {
                const root = document.getElementById('<?php echo esc_js($uid); ?>');
                if (!root) return;

                const list = root.querySelector('[data-online-thread-list]');
                const empty = root.querySelector('[data-online-empty]');
                const contextUrl = root.dataset.restContext;
                const messagesUrl = root.dataset.restMessages;
                const sendUrl = root.dataset.restSend;
                const nonce = root.dataset.restNonce;
                const messagesBase = root.dataset.messagesBase;

                const chatBox = root.querySelector('[data-online-chat]');
                const chatThread = root.querySelector('[data-online-chat-thread]');
                const chatInput = root.querySelector('[data-online-chat-input]');
                const chatSend = root.querySelector('[data-online-chat-send]');
                const chatName = root.querySelector('[data-online-chat-name]');
                const chatGroup = root.querySelector('[data-online-chat-group]');
                const chatAvatars = root.querySelector('[data-online-chat-avatars]');

                const state = {
                    threads: [],
                    userId: 0,
                    userAvatar: '',
                    userName: '',
                    activeKey: '',
                };

                const escape = (value = '') => {
                    const div = document.createElement('div');
                    div.textContent = value == null ? '' : String(value);
                    return div.innerHTML;
                };

                const firstInitial = (value = '') => {
                    const trimmed = (value || '').trim();
                    return trimmed ? trimmed[0].toUpperCase() : '';
                };

                const buildKey = (thread) => `${thread.admin_id || thread.owner_id || 0}:${thread.subscriber_id || thread.member_id || 0}:${thread.group_id || 0}`;

                const renderMessages = (messages = []) => {
                    if (!chatThread) return;

                    chatThread.innerHTML = '';

                    messages.forEach((message) => {
                        const isOwn = Number(message.sender_id) === Number(state.userId);
                        const time = message.created_at
                            ? new Date(message.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
                            : '';

                        const bubble = document.createElement('div');
                        bubble.className = `juntaplay-online__chat-message${isOwn ? ' is-own' : ''}`;
                        bubble.innerHTML = `${escape(message.message || '')}<span class="juntaplay-online__chat-time">${escape(time)}</span>`;
                        chatThread.appendChild(bubble);
                    });

                    chatThread.scrollTop = chatThread.scrollHeight;
                };

                const render = (threads = []) => {
                    list.innerHTML = '';
                    const normalized = Array.from(threads || []);

                    normalized.sort((a, b) => {
                        const parseTime = (item) => {
                            const raw = item.last_message_at || item.updated_at || item.created_at || '';
                            const ts = raw ? Date.parse(raw) : 0;
                            return Number.isFinite(ts) ? ts : 0;
                        };

                        return parseTime(b) - parseTime(a);
                    });

                    if (!normalized.length) {
                        empty.hidden = false;
                        return;
                    }

                    empty.hidden = true;

                    normalized.forEach((thread) => {
                        const counterpartName = thread.counterpart_name || thread.sender_name || thread.admin_name || thread.owner_name || thread.subscriber_name || '<?php echo esc_js(__('Contato', 'juntaplay')); ?>';
                        const groupName = thread.group_name || thread.title || '';
                        const preview = thread.last_message || thread.message || '';
                        const adminId = Number(thread.admin_id || thread.owner_id || 0);
                        const subscriberId = Number(thread.subscriber_id || thread.member_id || 0);
                        const groupId = Number(thread.group_id || 0);
                        const avatar =
                            thread.counterpart_avatar_url ||
                            thread.counterpart_avatar ||
                            thread.sender_avatar ||
                            thread.admin_avatar ||
                            thread.owner_avatar ||
                            thread.subscriber_avatar ||
                            '';
                        const unread = Number(thread.unread_count || thread.unread || 0);

                        const rawTime = thread.last_message_at || thread.updated_at || thread.created_at || '';
                        const time = rawTime ? new Date(rawTime) : null;
                        const timeLabel = time ? time.toLocaleString('pt-BR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' }) : '';

                        const li = document.createElement('li');
                        const key = buildKey(thread);
                        li.className = `juntaplay-online__item${state.activeKey === key ? ' is-active' : ''}`;

                        const deepLink = (() => {
                            if (!messagesBase) return '';
                            try {
                                const url = new URL(messagesBase, window.location.origin);
                                if (thread.thread_id) {
                                    url.searchParams.set('thread_id', thread.thread_id);
                                } else {
                                    if (adminId) url.searchParams.set('admin_id', adminId);
                                    if (subscriberId) url.searchParams.set('subscriber_id', subscriberId);
                                    if (groupId) url.searchParams.set('group_id', groupId);
                                }
                                return url.toString();
                            } catch (e) {
                                return '';
                            }
                        })();

                        if (deepLink) {
                            li.dataset.chatHref = deepLink;
                        }

                        const badge = unread > 0 ? `<span class="juntaplay-online__badge">${unread > 9 ? '9+' : unread}</span>` : '';

                        const avatarMarkup = avatar
                            ? `<span class="juntaplay-online__avatar" aria-hidden="true"><img src="${escape(avatar)}" alt="" loading="lazy" />${badge}</span>`
                            : `<span class="juntaplay-online__avatar" aria-hidden="true">${escape(firstInitial(counterpartName))}${badge}</span>`;

                        li.innerHTML = `
                            ${avatarMarkup}
                            <div>
                                <div class="juntaplay-online__meta">
                                    <p class="juntaplay-online__name">${escape(counterpartName)}</p>
                                    <span class="juntaplay-online__time">${escape(timeLabel)}</span>
                                </div>
                                <div class="juntaplay-online__group">${escape(groupName)}</div>
                                <p class="juntaplay-online__preview">${escape(preview)}</p>
                            </div>
                        `;

                        li.addEventListener('click', () => {
                            state.activeKey = key;

                            const selectionEvent = new CustomEvent('juntaplay-online-thread-selected', {
                                detail: { href: deepLink, thread },
                                cancelable: true,
                                bubbles: true,
                            });

                            if (!root.dispatchEvent(selectionEvent)) {
                                return;
                            }

                            if (!chatBox || !chatName || !chatGroup || !chatAvatars) return;

                            chatBox.hidden = false;
                            chatName.textContent = counterpartName;
                            chatGroup.textContent = groupName || '';

                            chatAvatars.innerHTML = '';

                            const avatarEl = document.createElement('span');
                            avatarEl.className = 'juntaplay-online__chat-avatar';
                            if (avatar) {
                                avatarEl.innerHTML = `<img src="${escape(avatar)}" alt="" loading="lazy" />`;
                            } else {
                                avatarEl.textContent = escape(firstInitial(counterpartName));
                            }

                            chatAvatars.appendChild(avatarEl);

                            const userAvatarEl = document.createElement('span');
                            userAvatarEl.className = 'juntaplay-online__chat-avatar';
                            if (state.userAvatar) {
                                userAvatarEl.innerHTML = `<img src="${escape(state.userAvatar)}" alt="" loading="lazy" />`;
                            } else {
                                userAvatarEl.textContent = escape(firstInitial(state.userName || '')); 
                            }
                            chatAvatars.appendChild(userAvatarEl);

                            const params = new URLSearchParams();
                            params.set('admin_id', adminId);
                            params.set('subscriber_id', subscriberId);
                            if (groupId) params.set('group_id', groupId);
                            params.set('mark_read', 'true');

                            fetch(`${messagesUrl}?${params.toString()}`, {
                                credentials: 'same-origin',
                                headers: { 'X-WP-Nonce': nonce },
                            })
                                .then((response) => (response.ok ? response.json() : null))
                                .then((data) => {
                                    let currentMessages = (data?.messages || data?.data?.messages || []).slice();
                                    renderMessages(currentMessages);

                                    const target = state.threads.find((entry) => buildKey(entry) === key);
                                    if (target) {
                                        target.unread_count = 0;
                                        render(state.threads);
                                    }

                                    if (chatInput) {
                                        chatInput.focus();
                                    }

                                    const sendPayload = () => {
                                        if (!chatInput) return;
                                        const text = chatInput.value.trim();
                                        if (!text) return;

                                        const form = new FormData();
                                        form.append('admin_id', String(adminId));
                                        form.append('subscriber_id', String(subscriberId));
                                        form.append('group_id', String(groupId));
                                        form.append('message', text);

                                        chatInput.value = '';

                                        fetch(sendUrl, {
                                            method: 'POST',
                                            credentials: 'same-origin',
                                            headers: { 'X-WP-Nonce': nonce },
                                            body: form,
                                        })
                                            .then((response) => (response.ok ? response.json() : null))
                                            .then((payload) => {
                                                const created = payload?.message || payload?.data?.message;
                                                if (created) {
                                                    currentMessages = currentMessages.concat(created);
                                                    renderMessages(currentMessages);
                                                }
                                            });
                                    };

                                    if (chatSend) {
                                        chatSend.onclick = sendPayload;
                                    }

                                    if (chatInput) {
                                        chatInput.onkeydown = (event) => {
                                            if (event.key === 'Enter') {
                                                event.preventDefault();
                                                sendPayload();
                                            }
                                        };
                                    }
                                });
                        });

                        list.appendChild(li);
                    });
                };

                fetch(contextUrl, {
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': nonce },
                })
                    .then((response) => (response.ok ? response.json() : null))
                    .then((data) => {
                        if (!data) return;

                        const threads = Array.isArray(data.threads)
                            ? data.threads
                            : Array.isArray(data.data?.threads)
                            ? data.data.threads
                            : [];

                        const user = data.user || data.data?.user || {};
                        state.userId = Number(user.id || 0);
                        state.userAvatar = user.avatar || '';
                        state.userName = user.name || '';
                        state.threads = threads;
                        render(threads);
                    })
                    .catch(() => {
                        empty.hidden = false;
                    });
            })();
        </script>
        <?php

        return (string) ob_get_clean();
    }

    add_shortcode('juntaplay_online', 'juntaplay_chat_online_shortcode');
}
