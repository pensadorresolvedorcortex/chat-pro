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
        static $has_rendered = false;

        // Prevent duplicate rendering when the shortcode is invoked multiple times
        // on the same request (e.g., theme + endpoint). This keeps the selector
        // and chat UI from appearing twice on the page.
        if ($has_rendered) {
            return '';
        }

        $has_rendered = true;

        if (!is_user_logged_in()) {
            return '<p>É necessário estar logado para acessar suas mensagens.</p>';
        }

        $current_user = wp_get_current_user();
        $chat_context = self::resolve_chat_state((int) $current_user->ID);

        $status = $chat_context['status'] ?? 'unavailable';
        $mode   = $chat_context['mode'] ?? 'member';

        if ($mode === 'admin') {
            $admin_conversations = $chat_context['admin_conversations'] ?? [];
            $admin_notice        = $chat_context['admin_notice'] ?? '';

            if ($status === 'admin_grid') {
                $members       = $admin_conversations;
                $group_id      = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $group_title   = '';
                $group_cover   = '';
                $selected_group = $chat_context['selected_group'] ?? [];

                if (isset($selected_group['title'])) {
                    $group_title = (string) $selected_group['title'];
                }

                if (isset($selected_group['cover_url'])) {
                    $group_cover = (string) $selected_group['cover_url'];
                }

                foreach ($members as $conversation) {
                    if ($group_title === '' && isset($conversation['group_title'])) {
                        $group_title = (string) $conversation['group_title'];
                    }

                    if ($group_cover === '' && isset($conversation['group_cover'])) {
                        $group_cover = (string) $conversation['group_cover'];
                    }

                    if ($group_id === 0 && isset($conversation['group_id'])) {
                        $group_id = (int) $conversation['group_id'];
                    }

                    if ($group_id > 0 && $group_title !== '' && $group_cover !== '') {
                        break;
                    }
                }

                $group_title = $group_title !== '' ? $group_title : __('Grupo', 'juntaplay');

                include JP_DIR . 'templates/chat-admin-grid.php';

                return '';
            }

            if ($status === 'admin_empty') {
                return self::render_admin_empty_state();
            }
        }

        if ($status === 'unavailable') {
            return self::render_unavailable_state();
        }

        if ($status === 'selection') {
            return self::render_group_selector($chat_context['eligible_groups']);
        }

        $selected_group = $chat_context['selected_group'] ?? [];
        $participant    = $chat_context['participant'] ?? [];

        $group_id = isset($selected_group['id']) ? (int) $selected_group['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $participant_id = isset($participant['id']) ? (int) $participant['id'] : 0;

        if (($mode === 'admin' && ($group_id <= 0 || $participant_id <= 0)) || ($mode !== 'admin' && $group_id <= 0)) {
            return $mode === 'admin' ? self::render_admin_empty_state() : self::render_unavailable_state();
        }

        $user_id    = (int) $current_user->ID;
        $user_name  = $current_user->display_name ?: $current_user->user_login;
        $user_avatar = self::resolve_avatar($user_id);
        $group_name   = isset($selected_group['title']) ? (string) $selected_group['title'] : '';
        $group_cover  = isset($selected_group['cover_url']) ? (string) $selected_group['cover_url'] : '';
        $admin_id     = isset($selected_group['owner_id']) ? (int) $selected_group['owner_id'] : 0;
        $admin        = $admin_id > 0 ? get_user_by('id', $admin_id) : null;
        $admin_name   = $admin && $admin->exists() ? ($admin->display_name ?: $admin->user_login) : '';
        $admin_avatar = $admin_id > 0 ? self::resolve_avatar($admin_id) : '';

        if ($mode === 'admin') {
            $user_id     = $participant_id;
            $user_record = $participant_id > 0 ? get_user_by('id', $participant_id) : null;
            $user_name   = $participant['name'] ?? ($user_record && $user_record->exists() ? ($user_record->display_name ?: $user_record->user_login) : __('Participante', 'juntaplay'));
            $user_avatar = $participant['avatar'] ?? self::resolve_avatar($participant_id);
            $admin_id    = (int) $current_user->ID;
            $admin_name  = $current_user->display_name ?: $current_user->user_login;
            $admin_avatar = self::resolve_avatar($admin_id);
        }

        $group_label = $group_name !== '' ? $group_name : __('Grupo', 'juntaplay');
        $admin_label = $admin_name !== '' ? $admin_name : __('Administrador', 'juntaplay');
        $admin_image = $admin_avatar !== ''
            ? $admin_avatar
            : ($admin_id > 0 ? get_avatar_url($admin_id, ['size' => 120]) : $user_avatar);

        $back_url = '';
        if ($mode === 'admin') {
            $back_url = add_query_arg(
                [
                    'section'  => 'juntaplay-chat',
                    'group_id' => $group_id,
                ],
                esc_url_raw(remove_query_arg('participant_id'))
            );
        }

        ob_start();
        ?>
        <div id="jp-chat-wrapper">
            <div id="jp-chat-header">
                <?php if ($mode === 'admin') : ?>
                    <a class="jp-chat-back-link" href="<?php echo esc_url($back_url); ?>" aria-label="<?php esc_attr_e('Voltar para a lista de assinantes', 'juntaplay'); ?>">←</a>
                <?php endif; ?>
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

        $chat_context   = self::resolve_chat_state(get_current_user_id());
        $selected_group = $chat_context['selected_group'] ?? [];
        $participant    = $chat_context['participant'] ?? [];
        $group_id       = isset($selected_group['id']) ? (int) $selected_group['id'] : 0;
        $participant_id = isset($participant['id']) ? (int) $participant['id'] : 0;
        $mode           = $chat_context['mode'] ?? 'member';
        $status         = $chat_context['status'] ?? '';

        wp_enqueue_style(
            'juntaplay-chat-css',
            JP_URL . 'assets/css/chat.css',
            [],
            '1.0.0'
        );

        if ($status !== 'ready' || $group_id <= 0) {
            return;
        }

        if ($mode === 'admin' && $participant_id <= 0) {
            return;
        }

        wp_enqueue_script(
            'juntaplay-chat-js',
            JP_URL . 'assets/js/chat.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script('juntaplay-chat-js', 'jpChatData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('juntaplay_chat_nonce'),
            'group_id' => $group_id,
            'participant_id' => $participant_id,
            'mode' => $mode,
        ]);
    }

    /**
     * @return array{status:string,eligible_groups:array<int,array<string,mixed>>,selected_group?:array<string,mixed>}
     */
    private static function resolve_chat_state(int $user_id): array
    {
        $group_id       = isset($_GET['group_id']) ? absint($_GET['group_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $participant_id = isset($_GET['participant_id']) ? absint($_GET['participant_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $groups         = Groups::get_groups_for_user($user_id);

        $owned_groups = $groups['owned'] ?? [];
        $admin_state  = self::resolve_admin_state($user_id, $owned_groups, $group_id, $participant_id);
        if ($admin_state !== null) {
            return $admin_state;
        }

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
                'mode'            => 'member',
            ];
        }

        if (count($member_groups) === 1) {
            return [
                'status'          => 'ready',
                'eligible_groups' => $member_groups,
                'selected_group'  => $member_groups[0],
                'mode'            => 'member',
            ];
        }

        foreach ($member_groups as $group) {
            if (isset($group['id']) && (int) $group['id'] === $group_id) {
                return [
                    'status'          => 'ready',
                    'eligible_groups' => $member_groups,
                    'selected_group'  => $group,
                    'mode'            => 'member',
                ];
            }
        }

        return [
            'status'          => 'selection',
            'eligible_groups' => $member_groups,
            'mode'            => 'member',
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $owned_groups
     * @return array<string,mixed>|null
     */
    private static function resolve_admin_state(int $admin_id, array $owned_groups, int $group_id, int $participant_id): ?array
    {
        if (empty($owned_groups)) {
            return null;
        }

        $owned_group_map = [];
        foreach ($owned_groups as $owned_group) {
            $owned_group_map[(int) ($owned_group['id'] ?? 0)] = $owned_group;
        }

        $conversations = self::enrich_conversations_with_members(
            self::get_admin_conversations($admin_id, $owned_groups),
            $group_id,
            $admin_id,
            $owned_groups
        );

        $is_group_entry = $group_id > 0 && isset($owned_group_map[$group_id]);

        if ($is_group_entry) {
            $members = self::filter_group_members(self::get_group_members($group_id), $admin_id);
            $scoped_conversations = array_values(array_filter(
                $conversations,
                static function (array $conversation) use ($group_id): bool {
                    return isset($conversation['group_id']) && (int) $conversation['group_id'] === $group_id;
                }
            ));

            $admin_grid = self::enrich_conversations_with_members(
                $scoped_conversations,
                $group_id,
                $admin_id,
                $owned_groups,
                $members
            );

            if (count($members) === 0) {
                return [
                    'status'              => 'admin_grid',
                    'mode'                => 'admin',
                    'selected_group'      => self::map_owned_group($owned_groups, $group_id, $admin_id, '', ''),
                    'admin_conversations' => $admin_grid,
                    // Keep the empty list placeholder visible without duplicating a notice.
                    'admin_notice'        => '',
                ];
            }

            $selected = null;
            if ($participant_id > 0) {
                foreach ($admin_grid as $conversation) {
                    if (isset($conversation['user_id']) && (int) $conversation['user_id'] === $participant_id) {
                        $selected = $conversation;
                        break;
                    }
                }
            }

            if ($selected !== null) {
                $selected_group = self::map_owned_group(
                    $owned_groups,
                    isset($selected['group_id']) ? (int) $selected['group_id'] : $group_id,
                    $admin_id,
                    (string) ($selected['group_title'] ?? ''),
                    (string) ($selected['group_cover'] ?? '')
                );

                return [
                    'status'              => 'ready',
                    'mode'                => 'admin',
                    'selected_group'      => $selected_group,
                    'participant'         => [
                        'id'     => isset($selected['user_id']) ? (int) $selected['user_id'] : 0,
                        'name'   => (string) ($selected['user_name'] ?? ''),
                        'avatar' => (string) ($selected['user_avatar'] ?? ''),
                    ],
                    'admin_conversations' => $admin_grid,
                ];
            }

            return [
                'status'              => 'admin_grid',
                'mode'                => 'admin',
                'selected_group'      => self::map_owned_group($owned_groups, $group_id, $admin_id, '', ''),
                'admin_conversations' => $admin_grid,
                'admin_notice'        => $participant_id > 0
                    ? __('O assinante selecionado não pertence a este grupo.', 'juntaplay')
                    : '',
            ];
        }

        $search_space = ($participant_id <= 0 && $group_id > 0)
            ? array_values(array_filter(
                $conversations,
                static function (array $conversation) use ($group_id): bool {
                    return isset($conversation['group_id']) && (int) $conversation['group_id'] === $group_id;
                }
            ))
            : $conversations;

        if (count($search_space) === 0) {
            return [
                'status'              => 'admin_grid',
                'mode'                => 'admin',
                'admin_conversations' => [],
                'admin_notice'        => $participant_id > 0
                    ? __('Não encontramos este assinante neste grupo.', 'juntaplay')
                    : __('Você ainda não possui conversas com assinantes.', 'juntaplay'),
            ];
        }

        $selected = null;
        foreach ($search_space as $conversation) {
            $conversation_group = isset($conversation['group_id']) ? (int) $conversation['group_id'] : 0;
            $conversation_user  = isset($conversation['user_id']) ? (int) $conversation['user_id'] : 0;

            if (
                $participant_id > 0
                && $conversation_user === $participant_id
                && ($group_id <= 0 || $conversation_group === $group_id)
            ) {
                $selected = $conversation;
                break;
            }
        }

        if ($selected === null && count($search_space) === 1) {
            $selected = $search_space[0];
        }

        if ($selected !== null) {
            $selected_group = self::map_owned_group(
                $owned_groups,
                isset($selected['group_id']) ? (int) $selected['group_id'] : 0,
                $admin_id,
                (string) ($selected['group_title'] ?? ''),
                (string) ($selected['group_cover'] ?? '')
            );

            return [
                'status'              => 'ready',
                'mode'                => 'admin',
                'selected_group'      => $selected_group,
                'participant'         => [
                    'id'     => isset($selected['user_id']) ? (int) $selected['user_id'] : 0,
                    'name'   => (string) ($selected['user_name'] ?? ''),
                    'avatar' => (string) ($selected['user_avatar'] ?? ''),
                ],
                'admin_conversations' => $search_space,
            ];
        }

        return [
            'status'              => 'admin_grid',
            'mode'                => 'admin',
            'admin_conversations' => $search_space,
            'admin_notice'        => $participant_id > 0
                ? __('O assinante selecionado não pertence a este grupo.', 'juntaplay')
                : '',
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $owned_groups
     * @return array<int,array<string,mixed>>
     */
    private static function get_admin_conversations(int $admin_id, array $owned_groups): array
    {
        global $wpdb;

        $table = $wpdb->prefix . 'juntaplay_chat_messages';

        $group_map = [];
        foreach ($owned_groups as $group) {
            $group_id = isset($group['id']) ? (int) $group['id'] : 0;
            if ($group_id > 0) {
                $group_map[$group_id] = $group;
            }
        }

        if (!$group_map) {
            return [];
        }

        $group_ids = array_keys($group_map);
        $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));

        $query = $wpdb->prepare(
            "SELECT group_id, user_id, MAX(id) AS last_id, MAX(created_at) AS last_created_at
             FROM {$table}
             WHERE admin_id = %d AND group_id IN ({$placeholders})
             GROUP BY group_id, user_id
             ORDER BY last_created_at DESC",
            ...array_merge([$admin_id], $group_ids)
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $conversations = [];

        foreach ($rows as $row) {
            $group_id = isset($row['group_id']) ? (int) $row['group_id'] : 0;
            $user_id  = isset($row['user_id']) ? (int) $row['user_id'] : 0;

            if ($group_id <= 0 || $user_id <= 0 || !isset($group_map[$group_id])) {
                continue;
            }

            $last_message = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT message, image_url
                     FROM {$table}
                     WHERE admin_id = %d AND group_id = %d AND user_id = %d
                     ORDER BY created_at DESC, id DESC
                     LIMIT 1",
                    $admin_id,
                    $group_id,
                    $user_id
                ),
                ARRAY_A
            );

            $user = get_user_by('id', $user_id);

            $conversations[] = [
                'group_id'        => $group_id,
                'group_title'     => (string) ($group_map[$group_id]['title'] ?? ''),
                'group_cover'     => (string) ($group_map[$group_id]['cover_url'] ?? ''),
                'user_id'         => $user_id,
                'user_name'       => $user && $user->exists() ? ($user->display_name ?: $user->user_login) : __('Participante', 'juntaplay'),
                'user_avatar'     => self::resolve_avatar($user_id),
                'last_message'    => isset($last_message['image_url']) && $last_message['image_url'] !== ''
                    ? __('📷 Imagem', 'juntaplay')
                    : (string) ($last_message['message'] ?? ''),
                'last_created_at' => (string) ($row['last_created_at'] ?? ''),
            ];
        }

        return $conversations;
    }

    /**
     * @param array<int,array<string,mixed>> $conversations
     * @param array<int,array<string,mixed>> $owned_groups
     * @return array<int,array<string,mixed>>
     */
    private static function enrich_conversations_with_members(
        array $conversations,
        int $group_id,
        int $admin_id,
        array $owned_groups,
        array $members = []
    ): array {
        if ($group_id <= 0) {
            return $conversations;
        }

        $members = self::filter_group_members($members ?: self::get_group_members($group_id), $admin_id);
        if (empty($members)) {
            return $conversations;
        }

        $existing_user_ids = [];
        foreach ($conversations as $conversation) {
            if (isset($conversation['user_id'])) {
                $existing_user_ids[(int) $conversation['user_id']] = true;
            }
        }

        $group_meta = self::map_owned_group($owned_groups, $group_id, $admin_id, '', '');

        foreach ($members as $member) {
            $user_id = isset($member['id']) ? (int) $member['id'] : 0;
            if ($user_id <= 0 || $user_id === $admin_id || isset($existing_user_ids[$user_id])) {
                continue;
            }

            $user = get_user_by('id', $user_id);

            $conversations[] = [
                'group_id'        => $group_id,
                'group_title'     => (string) ($group_meta['title'] ?? ''),
                'group_cover'     => (string) ($group_meta['cover_url'] ?? ''),
                'user_id'         => $user_id,
                'user_name'       => $user && $user->exists() ? ($user->display_name ?: $user->user_login) : __('Participante', 'juntaplay'),
                'user_avatar'     => self::resolve_avatar($user_id),
                'last_message'    => '',
                'last_created_at' => '',
            ];
        }

        return $conversations;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function get_group_members(int $group_id): array
    {
        if ($group_id <= 0 || !class_exists(Groups::class) || !method_exists(Groups::class, 'get_group_members')) {
            return [];
        }

        $members = Groups::get_group_members($group_id);

        if (!is_array($members)) {
            return [];
        }

        $normalized = [];

        foreach ($members as $member) {
            $normalized_member = self::normalize_member_entry($member);

            if ($normalized_member !== null) {
                $normalized[] = $normalized_member;
            }
        }

        return $normalized;
    }

    /**
     * Extracts valid member ids and removes duplicates/admin from a given list.
     *
     * @param array<int,array<string,mixed>> $members
     * @return array<int,array<string,mixed>>
     */
    private static function filter_group_members(array $members, int $admin_id): array
    {
        $filtered = [];
        $seen     = [];

        foreach ($members as $member) {
            $member      = self::normalize_member_entry($member) ?? [];
            $member_id   = isset($member['id']) ? (int) $member['id'] : 0;
            $is_duplicate = $member_id <= 0 || $member_id === $admin_id || isset($seen[$member_id]);

            if ($is_duplicate) {
                continue;
            }

            $seen[$member_id] = true;
            $filtered[]       = $member;
        }

        return $filtered;
    }

    /**
     * @param array<string,mixed>|object|int $member
     */
    private static function normalize_member_entry($member): ?array
    {
        if (is_array($member)) {
            $id = self::resolve_member_id($member);

            if ($id <= 0) {
                return null;
            }

            $member['id'] = $id;

            return $member;
        }

        if (is_object($member)) {
            $id = 0;

            if (isset($member->ID)) {
                $id = (int) $member->ID;
            } elseif (isset($member->user_id)) {
                $id = (int) $member->user_id;
            }

            return $id > 0 ? ['id' => $id] : null;
        }

        if (is_numeric($member)) {
            return ['id' => (int) $member];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $member
     */
    private static function resolve_member_id(array $member): int
    {
        $id = $member['id'] ?? $member['user_id'] ?? $member['ID'] ?? $member['member_id'] ?? 0;

        return (int) $id;
    }

    /**
     * @param array<int,array<string,mixed>> $owned_groups
     * @return array<string,mixed>
     */
    private static function map_owned_group(array $owned_groups, int $group_id, int $admin_id, string $fallback_title, string $fallback_cover): array
    {
        foreach ($owned_groups as $group) {
            if ((int) ($group['id'] ?? 0) === $group_id) {
                return $group;
            }
        }

        return [
            'id'        => $group_id,
            'owner_id'  => $admin_id,
            'title'     => $fallback_title,
            'cover_url' => $fallback_cover,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $conversations
     */
    private static function render_admin_grid(array $conversations): string
    {
        ob_start();
        ?>
        <div id="jp-chat-wrapper" class="jp-chat-admin-state">
            <div class="jp-chat-heading">
                <h3><?php esc_html_e('Converse com seus participantes', 'juntaplay'); ?></h3>
                <p class="jp-chat-subtitle"><?php esc_html_e('Selecione uma conversa para responder os participantes do seu grupo.', 'juntaplay'); ?></p>
            </div>
            <div class="jp-chat-admin-grid">
                <?php foreach ($conversations as $conversation) :
                    $participant_id = isset($conversation['user_id']) ? (int) $conversation['user_id'] : 0;
                    $group_id       = isset($conversation['group_id']) ? (int) $conversation['group_id'] : 0;
                    $participant    = isset($conversation['user_name']) ? (string) $conversation['user_name'] : __('Participante', 'juntaplay');
                    $participant_avatar = isset($conversation['user_avatar']) ? (string) $conversation['user_avatar'] : '';
                    $group_title    = isset($conversation['group_title']) ? (string) $conversation['group_title'] : __('Grupo', 'juntaplay');
                    $group_cover    = isset($conversation['group_cover']) ? (string) $conversation['group_cover'] : '';
                    $last_message   = isset($conversation['last_message']) ? (string) $conversation['last_message'] : '';
                    $chat_url       = add_query_arg(
                        [
                            'section'        => 'juntaplay-chat',
                            'participant_id' => $participant_id,
                            'group_id'       => $group_id,
                        ],
                        esc_url_raw(remove_query_arg(['participant_id', 'group_id']))
                    );
                    ?>
                    <a class="jp-chat-admin-card" href="<?php echo esc_url($chat_url); ?>">
                        <span class="jp-chat-admin-glow" aria-hidden="true"></span>
                        <div class="jp-chat-admin-card-body">
                            <div class="jp-chat-admin-avatar-frame">
                                <?php if ($participant_avatar !== '') : ?>
                                    <img src="<?php echo esc_url($participant_avatar); ?>" alt="" class="jp-chat-admin-avatar" loading="lazy">
                                <?php else : ?>
                                    <div class="jp-chat-admin-avatar jp-chat-admin-avatar--placeholder" aria-hidden="true">👤</div>
                                <?php endif; ?>
                            </div>
                            <div class="jp-chat-admin-card-content">
                                <div class="jp-chat-admin-name"><?php echo esc_html($participant); ?></div>
                                <div class="jp-chat-admin-group" title="<?php echo esc_attr($group_title); ?>"><?php echo esc_html($group_title); ?></div>
                                <?php if ($last_message !== '') : ?>
                                    <div class="jp-chat-admin-last"><?php echo esc_html($last_message); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="jp-chat-admin-group-thumb">
                                <?php if ($group_cover !== '') : ?>
                                    <img src="<?php echo esc_url($group_cover); ?>" alt="" loading="lazy">
                                <?php else : ?>
                                    <span aria-hidden="true">💬</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function render_admin_empty_state(): string
    {
        ob_start();
        ?>
        <div id="jp-chat-wrapper" class="jp-chat-admin-empty">
            <div class="jp-chat-heading">
                <h3><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></h3>
                <p class="jp-chat-subtitle"><?php esc_html_e('Não encontramos assinantes para este grupo no momento.', 'juntaplay'); ?></p>
            </div>
            <div class="jp-chat-admin-empty-card">
                <span class="jp-chat-admin-empty-icon" aria-hidden="true">💬</span>
                <p class="jp-chat-admin-empty-text"><?php esc_html_e('Adicione assinantes ao grupo para iniciar conversas privadas.', 'juntaplay'); ?></p>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
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
