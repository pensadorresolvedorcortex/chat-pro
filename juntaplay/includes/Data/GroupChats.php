<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function get_user_meta;
use function sanitize_text_field;

defined('ABSPATH') || exit;

class GroupChats
{
    /**
     * Ensure a chat exists between the group admin and the provided member.
     */
    public static function ensure_chat(int $group_id, int $member_id, int $admin_id): int
    {
        global $wpdb;

        $group_id  = absint($group_id);
        $member_id = absint($member_id);
        $admin_id  = absint($admin_id);

        if ($group_id <= 0 || $member_id <= 0 || $admin_id <= 0) {
            return 0;
        }

        $table = "{$wpdb->prefix}jp_group_chats";

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE group_id = %d AND member_id = %d",
                $group_id,
                $member_id
            )
        );

        if ($existing) {
            return (int) $existing;
        }

        $inserted = $wpdb->insert(
            $table,
            [
                'group_id'  => $group_id,
                'member_id' => $member_id,
                'admin_id'  => $admin_id,
                'created_at'=> current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function list_for_user(int $user_id): array
    {
        global $wpdb;

        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return [];
        }

        $chats_table   = "{$wpdb->prefix}jp_group_chats";
        $groups_table  = "{$wpdb->prefix}jp_groups";
        $members_table = "{$wpdb->prefix}jp_group_members";
        $users_table   = $wpdb->users;

        $query = $wpdb->prepare(
            "SELECT c.*, g.title AS group_title, g.owner_id, g.slug, g.cover_id,
                    admin_user.display_name AS admin_name,
                    member_user.display_name AS member_name,
                    gm_member.role AS member_role, gm_member.status AS member_status,
                    gm_admin.role AS admin_role, gm_admin.status AS admin_status,
                    gm_viewer.role AS viewer_role, gm_viewer.status AS viewer_status,
                    (SELECT message FROM {$wpdb->prefix}jp_group_chat_messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_message,
                    (SELECT type FROM {$wpdb->prefix}jp_group_chat_messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_type,
                    (SELECT created_at FROM {$wpdb->prefix}jp_group_chat_messages WHERE chat_id = c.id ORDER BY created_at DESC, id DESC LIMIT 1) AS last_created_at
             FROM $chats_table c
             INNER JOIN $groups_table g ON g.id = c.group_id
             LEFT JOIN $members_table gm_member ON gm_member.group_id = c.group_id AND gm_member.user_id = c.member_id
             LEFT JOIN $members_table gm_admin ON gm_admin.group_id = c.group_id AND gm_admin.user_id = c.admin_id
             LEFT JOIN $members_table gm_viewer ON gm_viewer.group_id = c.group_id AND gm_viewer.user_id = %d
             LEFT JOIN $users_table admin_user ON admin_user.ID = c.admin_id
             LEFT JOIN $users_table member_user ON member_user.ID = c.member_id
             WHERE c.member_id = %d OR c.admin_id = %d
             ORDER BY c.updated_at DESC, c.id DESC",
            $user_id,
            $user_id,
            $user_id
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];

        return array_map(static function (array $row) use ($user_id): array {
            $member_role_raw    = trim((string) ($row['member_role'] ?? ''));
            $admin_role_raw     = trim((string) ($row['admin_role'] ?? ''));
            $viewer_role_raw    = trim((string) ($row['viewer_role'] ?? ''));
            $member_status_raw  = trim((string) ($row['member_status'] ?? ''));
            $admin_status_raw   = trim((string) ($row['admin_status'] ?? ''));
            $viewer_status_raw  = trim((string) ($row['viewer_status'] ?? ''));

            $member_role    = $member_role_raw !== '' ? $member_role_raw : 'member';
            $admin_role     = $admin_role_raw !== '' ? $admin_role_raw : 'admin';
            $viewer_role    = $viewer_role_raw !== '' ? $viewer_role_raw : '';
            $member_status  = $member_status_raw !== '' ? $member_status_raw : '';
            $admin_status   = $admin_status_raw !== '' ? $admin_status_raw : '';
            $viewer_status  = $viewer_status_raw !== '' ? $viewer_status_raw : '';

            $is_member         = $user_id === (int) ($row['member_id'] ?? 0);
            $membership_role   = $viewer_role !== '' ? $viewer_role : ($is_member ? $member_role : $admin_role);
            $membership_status = $viewer_status !== '' ? $viewer_status : ($is_member ? $member_status : $admin_status);

            return [
                'id'            => isset($row['id']) ? (int) $row['id'] : 0,
                'group_id'      => isset($row['group_id']) ? (int) $row['group_id'] : 0,
                'member_id'     => isset($row['member_id']) ? (int) $row['member_id'] : 0,
                'admin_id'      => isset($row['admin_id']) ? (int) $row['admin_id'] : 0,
                'group_title'   => (string) ($row['group_title'] ?? ''),
                'group_slug'    => (string) ($row['slug'] ?? ''),
                'admin_name'    => (string) ($row['admin_name'] ?? ''),
                'member_name'   => (string) ($row['member_name'] ?? ''),
                'admin_role'    => $admin_role,
                'admin_status'  => $admin_status,
                'member_role'   => $member_role,
                'member_status' => $member_status,
                'viewer_role'   => $viewer_role,
                'viewer_status' => $viewer_status,
                'membership_role' => $membership_role,
                'membership_status' => $membership_status,
                'last_message'  => (string) ($row['last_message'] ?? ''),
                'last_type'     => (string) ($row['last_type'] ?? ''),
                'last_created_at' => (string) ($row['last_created_at'] ?? ''),
                'updated_at'    => (string) ($row['updated_at'] ?? ''),
                'cover_id'      => isset($row['cover_id']) ? (int) $row['cover_id'] : 0,
            ];
        }, $rows);
    }

    public static function touch(int $chat_id): void
    {
        global $wpdb;

        $chat_id = absint($chat_id);
        if ($chat_id <= 0) {
            return;
        }

        $wpdb->update(
            "{$wpdb->prefix}jp_group_chats",
            ['updated_at' => current_time('mysql')],
            ['id' => $chat_id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(int $chat_id): ?array
    {
        global $wpdb;

        $chat_id = absint($chat_id);
        if ($chat_id <= 0) {
            return null;
        }

        $table = "{$wpdb->prefix}jp_group_chats";

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $chat_id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return [
            'id'        => isset($row['id']) ? (int) $row['id'] : 0,
            'group_id'  => isset($row['group_id']) ? (int) $row['group_id'] : 0,
            'member_id' => isset($row['member_id']) ? (int) $row['member_id'] : 0,
            'admin_id'  => isset($row['admin_id']) ? (int) $row['admin_id'] : 0,
            'updated_at'=> (string) ($row['updated_at'] ?? ''),
        ];
    }

    public static function user_can_access(int $chat_id, int $user_id): bool
    {
        global $wpdb;

        $chat_id = absint($chat_id);
        $user_id = absint($user_id);

        if ($chat_id <= 0 || $user_id <= 0) {
            return false;
        }

        $table = "{$wpdb->prefix}jp_group_chats";

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE id = %d AND (member_id = %d OR admin_id = %d)",
                $chat_id,
                $user_id,
                $user_id
            )
        );

        return $count > 0;
    }

    /**
     * @return array<string, int>|null
     */
    public static function get_latest_unread_chat(int $user_id): ?array
    {
        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return null;
        }

        $unread = get_user_meta($user_id, 'juntaplay_mensagens_nao_lidas', true);

        if (!is_array($unread) || $unread === []) {
            return null;
        }

        $latest = end($unread);
        if (!is_array($latest)) {
            return null;
        }

        $sender_id = isset($latest['sender_id']) ? (int) $latest['sender_id'] : 0;

        if ($sender_id <= 0) {
            return null;
        }

        return [
            'sender_id' => $sender_id,
        ];
    }
}
