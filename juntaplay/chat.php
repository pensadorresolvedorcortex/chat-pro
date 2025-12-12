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
