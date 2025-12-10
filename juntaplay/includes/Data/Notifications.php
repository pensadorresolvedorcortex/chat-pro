<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function sanitize_text_field;

defined('ABSPATH') || exit;

class Notifications
{
    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ   = 'read';

    /**
     * @param array<string, mixed> $data
     */
    public static function add(int $user_id, array $data): int
    {
        global $wpdb;

        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return 0;
        }

        $table    = "{$wpdb->prefix}jp_notifications";
        $type     = isset($data['type']) ? sanitize_text_field((string) $data['type']) : 'general';
        $title    = isset($data['title']) ? sanitize_text_field((string) $data['title']) : '';
        $message  = isset($data['message']) ? (string) $data['message'] : '';
        $url      = isset($data['action_url']) ? (string) $data['action_url'] : '';
        $status   = isset($data['status']) ? sanitize_text_field((string) $data['status']) : self::STATUS_UNREAD;

        if ($title === '' || $message === '') {
            return 0;
        }

        $payload = [
            'user_id'    => $user_id,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'action_url' => $url !== '' ? $url : null,
            'status'     => $status,
            'created_at' => current_time('mysql'),
        ];

        $formats = ['%d', '%s', '%s', '%s', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table, $payload, $formats);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_recent(int $user_id, int $limit = 10): array
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_notifications";
        $limit = max(1, min(50, $limit));

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id'         => isset($row['id']) ? (int) $row['id'] : 0,
                'user_id'    => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                'type'       => (string) ($row['type'] ?? 'general'),
                'title'      => (string) ($row['title'] ?? ''),
                'message'    => (string) ($row['message'] ?? ''),
                'action_url' => (string) ($row['action_url'] ?? ''),
                'status'     => (string) ($row['status'] ?? self::STATUS_UNREAD),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'read_at'    => (string) ($row['read_at'] ?? ''),
            ];
        }

        return $items;
    }

    public static function count_unread(int $user_id): int
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_notifications";

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND status = %s",
            $user_id,
            self::STATUS_UNREAD
        );

        return (int) $wpdb->get_var($query);
    }

    public static function count_unread_by_type(int $user_id, string $type): int
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_notifications";

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND status = %s AND type = %s",
            $user_id,
            self::STATUS_UNREAD,
            $type
        );

        return (int) $wpdb->get_var($query);
    }

    public static function delete_all(int $user_id): int
    {
        global $wpdb;

        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return 0;
        }

        $table = "{$wpdb->prefix}jp_notifications";

        return (int) $wpdb->delete($table, ['user_id' => $user_id], ['%d']);
    }

    /**
     * @param int[] $ids
     */
    public static function mark_read(int $user_id, array $ids): int
    {
        global $wpdb;

        $ids = array_filter(array_map('absint', $ids));

        if (!$ids) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $table        = "{$wpdb->prefix}jp_notifications";

        $query = $wpdb->prepare(
            "UPDATE $table
             SET status = %s, read_at = %s
             WHERE user_id = %d AND id IN ($placeholders)",
            ...array_merge([self::STATUS_READ, current_time('mysql'), $user_id], $ids)
        );

        return $query ? (int) $wpdb->query($query) : 0;
    }
}
