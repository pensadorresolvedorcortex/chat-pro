<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function apply_filters;
use function current_time;
use function do_action;
use function sanitize_key;
use function sprintf;
use function wp_cache_get;
use function wp_cache_set;

defined('ABSPATH') || exit;

class GroupMembers
{
    public static function add(int $group_id, int $user_id, string $role = 'member', string $status = 'active'): void
    {
        global $wpdb;

        $group_id = absint($group_id);
        $user_id  = absint($user_id);

        if ($group_id <= 0 || $user_id <= 0) {
            return;
        }

        $table = "{$wpdb->prefix}jp_group_members";

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT role, status FROM $table WHERE group_id = %d AND user_id = %d",
                $group_id,
                $user_id
            ),
            ARRAY_A
        );

        $wpdb->replace(
            $table,
            [
                'group_id'  => $group_id,
                'user_id'   => $user_id,
                'role'      => $role,
                'status'    => $status,
                'joined_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        if (!$existing) {
            do_action('juntaplay/group_members/added', $group_id, $user_id, $role, $status);

            return;
        }

        $previous_status = (string) ($existing['status'] ?? '');
        $previous_role   = (string) ($existing['role'] ?? '');

        if ($previous_status !== $status) {
            do_action('juntaplay/group_members/status_changed', $group_id, $user_id, $previous_status, $status, $role);
        }

        if ($previous_role !== $role) {
            do_action('juntaplay/group_members/role_changed', $group_id, $user_id, $previous_role, $role, $status);
        }
    }

    public static function count_active(int $group_id): int
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_group_members";

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE group_id = %d AND status = 'active'",
                $group_id
            )
        );
    }

    /**
     * @return int[]
     */
    public static function get_user_ids(int $group_id, string $status = 'active'): array
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return [];
        }

        $table  = "{$wpdb->prefix}jp_group_members";
        $status = sanitize_key($status);

        if ($status === 'all' || $status === '') {
            $query = $wpdb->prepare("SELECT user_id FROM $table WHERE group_id = %d", $group_id);
        } else {
            $query = $wpdb->prepare(
                "SELECT user_id FROM $table WHERE group_id = %d AND status = %s",
                $group_id,
                $status
            );
        }

        $results = $wpdb->get_col($query) ?: [];

        return array_map('intval', $results);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_details(int $group_id, int $limit = 5, string $status = 'active'): array
    {
        global $wpdb;

        $group_id = absint($group_id);
        $limit    = absint($limit);

        if ($group_id <= 0 || $limit <= 0) {
            return [];
        }

        $members_table = "{$wpdb->prefix}jp_group_members";
        $users_table   = $wpdb->users;
        $status        = sanitize_key($status);

        $where  = $status !== '' && $status !== 'all'
            ? $wpdb->prepare('AND gm.status = %s', $status)
            : '';

        $sql = $wpdb->prepare(
            "SELECT gm.user_id, gm.role, gm.status, gm.joined_at, u.display_name
             FROM $members_table gm
             LEFT JOIN $users_table u ON u.ID = gm.user_id
             WHERE gm.group_id = %d $where
             ORDER BY (gm.role = 'owner') DESC, gm.joined_at ASC
             LIMIT %d",
            $group_id,
            $limit
        );

        $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];

        $members = [];
        foreach ($rows as $row) {
            $members[] = [
                'user_id' => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                'role'    => isset($row['role']) ? (string) $row['role'] : 'member',
                'status'  => isset($row['status']) ? (string) $row['status'] : 'active',
                'name'    => (string) ($row['display_name'] ?? ''),
            ];
        }

        return $members;
    }

    public static function user_has_membership(int $group_id, int $user_id): bool
    {
        global $wpdb;

        $group_id = absint($group_id);
        $user_id  = absint($user_id);

        if ($group_id <= 0 || $user_id <= 0) {
            return false;
        }

        $cache_key = sprintf('jp_group_membership_%d_%d', $group_id, $user_id);
        $cached    = wp_cache_get($cache_key, 'juntaplay');

        if ($cached !== false) {
            return (bool) $cached;
        }

        $table = "{$wpdb->prefix}jp_group_members";

        $allowed_statuses = apply_filters(
            'juntaplay/group_members/active_statuses',
            ['active'],
            $group_id,
            $user_id
        );
        $allowed_statuses = array_values(array_filter(array_map('sanitize_key', (array) $allowed_statuses)));

        if (!$allowed_statuses) {
            $allowed_statuses = ['active'];
        }

        $placeholders = implode(',', array_fill(0, count($allowed_statuses), '%s'));

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE group_id = %d AND user_id = %d AND status IN ($placeholders)",
                array_merge([$group_id, $user_id], $allowed_statuses)
            )
        );

        $has_membership = $count > 0;

        wp_cache_set($cache_key, $has_membership ? 1 : 0, 'juntaplay', 10 * MINUTE_IN_SECONDS);

        return $has_membership;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get_membership(int $group_id, int $user_id): ?array
    {
        global $wpdb;

        $group_id = absint($group_id);
        $user_id  = absint($user_id);

        if ($group_id <= 0 || $user_id <= 0) {
            return null;
        }

        $table = "{$wpdb->prefix}jp_group_members";

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE group_id = %d AND user_id = %d",
                $group_id,
                $user_id
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return [
            'id'        => isset($row['id']) ? (int) $row['id'] : 0,
            'group_id'  => $group_id,
            'user_id'   => $user_id,
            'role'      => (string) ($row['role'] ?? 'member'),
            'status'    => (string) ($row['status'] ?? 'active'),
            'joined_at' => (string) ($row['joined_at'] ?? ''),
        ];
    }

    public static function update_status(int $group_id, int $user_id, string $status): bool
    {
        global $wpdb;

        $group_id = absint($group_id);
        $user_id  = absint($user_id);
        $status   = sanitize_key($status);

        if ($group_id <= 0 || $user_id <= 0 || $status === '') {
            return false;
        }

        $table = "{$wpdb->prefix}jp_group_members";

        if (in_array($status, ['canceled', 'exit_scheduled', 'exited'], true)) {
            $deleted = $wpdb->delete(
                $table,
                [
                    'group_id' => $group_id,
                    'user_id'  => $user_id,
                ],
                ['%d', '%d']
            );

            if ($deleted) {
                $cache_key = sprintf('jp_group_membership_%d_%d', $group_id, $user_id);
                wp_cache_delete($cache_key, 'juntaplay');
            }

            return (bool) $deleted;
        }

        $updated = $wpdb->update(
            $table,
            ['status' => $status],
            [
                'group_id' => $group_id,
                'user_id'  => $user_id,
            ],
            ['%s'],
            ['%d', '%d']
        );

        if ($updated) {
            $cache_key = sprintf('jp_group_membership_%d_%d', $group_id, $user_id);
            wp_cache_delete($cache_key, 'juntaplay');
        }

        return (bool) $updated;
    }
}
