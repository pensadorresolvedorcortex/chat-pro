<?php

declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function array_map;
use function current_time;
use function is_array;
use function json_decode;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function wp_json_encode;

defined('ABSPATH') || exit;

class GroupMembershipEvents
{
    public const TYPE_CANCEL = 'cancel';
    public const TYPE_EXIT_SCHEDULED = 'exit_scheduled';
    public const TYPE_EXITED = 'exited';
    public const TYPE_ORDER_JOIN = 'order_join';

    /**
     * @param array<string, mixed> $metadata
     */
    public static function log(int $group_id, int $user_id, string $type, string $message = '', array $metadata = []): int
    {
        global $wpdb;

        $group_id = absint($group_id);
        $user_id  = absint($user_id);
        $type     = sanitize_text_field($type);

        if ($group_id <= 0 || $user_id <= 0 || $type === '') {
            return 0;
        }

        $table  = "{$wpdb->prefix}jp_group_membership_events";
        $stored = [
            'group_id'  => $group_id,
            'user_id'   => $user_id,
            'type'      => $type,
            'message'   => $message !== '' ? sanitize_textarea_field($message) : null,
            'metadata'  => $metadata ? wp_json_encode($metadata) : null,
            'created_at'=> current_time('mysql'),
        ];

        $inserted = $wpdb->insert($table, $stored, ['%d', '%d', '%s', '%s', '%s', '%s']);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_recent_for_group(int $group_id, int $limit = 10): array
    {
        global $wpdb;

        $group_id = absint($group_id);
        $limit    = max(1, min(50, absint($limit)));

        if ($group_id <= 0) {
            return [];
        }

        $table = "{$wpdb->prefix}jp_group_membership_events";

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, group_id, user_id, type, message, metadata, created_at
                 FROM $table
                 WHERE group_id = %d
                 ORDER BY created_at DESC
                 LIMIT %d",
                $group_id,
                $limit
            ),
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        return array_map(static function (array $row): array {
            $metadata = [];

            if (!empty($row['metadata'])) {
                $decoded = json_decode((string) $row['metadata'], true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }

            return [
                'id'         => isset($row['id']) ? (int) $row['id'] : 0,
                'group_id'   => isset($row['group_id']) ? (int) $row['group_id'] : 0,
                'user_id'    => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                'type'       => (string) ($row['type'] ?? ''),
                'message'    => (string) ($row['message'] ?? ''),
                'metadata'   => $metadata,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $results);
    }

    /**
     * @param int[] $group_ids
     * @return array<int, array<string, mixed>>
     */
    public static function get_latest_for_user(int $user_id, array $group_ids, string $type = self::TYPE_CANCEL): array
    {
        global $wpdb;

        $user_id = absint($user_id);
        $group_ids = array_values(array_filter(array_map('absint', $group_ids)));
        $type = sanitize_text_field($type);

        if ($user_id <= 0 || !$group_ids || $type === '') {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
        $table        = "{$wpdb->prefix}jp_group_membership_events";

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, group_id, user_id, type, message, metadata, created_at
                 FROM $table
                 WHERE user_id = %d AND type = %s AND group_id IN ($placeholders)
                 ORDER BY created_at DESC",
                ...array_merge([$user_id, $type], $group_ids)
            ),
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        $latest = [];

        foreach ($results as $row) {
            $group_id = isset($row['group_id']) ? (int) $row['group_id'] : 0;

            if ($group_id <= 0 || isset($latest[$group_id])) {
                continue;
            }

            $metadata = [];
            if (!empty($row['metadata'])) {
                $decoded = json_decode((string) $row['metadata'], true);
                if (is_array($decoded)) {
                    $metadata = $decoded;
                }
            }

            $latest[$group_id] = [
                'id'         => isset($row['id']) ? (int) $row['id'] : 0,
                'group_id'   => $group_id,
                'user_id'    => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                'type'       => (string) ($row['type'] ?? $type),
                'message'    => (string) ($row['message'] ?? ''),
                'metadata'   => $metadata,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return $latest;
    }
}

