<?php

declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function is_array;
use function json_decode;
use function sanitize_textarea_field;
use function sanitize_text_field;
use function wp_json_encode;

defined('ABSPATH') || exit;

class GroupComplaintMessages
{
    public const TYPE_MESSAGE  = 'message';
    public const TYPE_PROPOSAL = 'proposal';
    public const TYPE_SYSTEM   = 'system';

    /**
     * @param array<int> $attachments
     */
    public static function add(int $complaint_id, ?int $user_id, string $type, string $message, array $attachments = []): int
    {
        global $wpdb;

        $complaint_id = absint($complaint_id);
        $user_id      = $user_id !== null ? absint($user_id) : 0;
        $type         = sanitize_text_field($type !== '' ? $type : self::TYPE_MESSAGE);
        $message      = sanitize_textarea_field($message);

        if ($complaint_id <= 0) {
            return 0;
        }

        $table  = "{$wpdb->prefix}jp_group_complaint_messages";
        $stored = [
            'complaint_id' => $complaint_id,
            'user_id'      => $user_id,
            'type'         => $type,
            'message'      => $message !== '' ? $message : null,
            'attachments'  => $attachments ? wp_json_encode(array_map('absint', $attachments)) : null,
            'created_at'   => current_time('mysql'),
        ];

        $formats = ['%d', '%d', '%s', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table, $stored, $formats);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_for_complaint(int $complaint_id): array
    {
        global $wpdb;

        $complaint_id = absint($complaint_id);

        if ($complaint_id <= 0) {
            return [];
        }

        $table = "{$wpdb->prefix}jp_group_complaint_messages";
        $users = $wpdb->users;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.id, m.complaint_id, m.user_id, m.type, m.message, m.attachments, m.created_at,
                        u.display_name, u.user_login
                 FROM $table m
                 LEFT JOIN $users u ON u.ID = m.user_id
                 WHERE m.complaint_id = %d
                 ORDER BY m.created_at ASC, m.id ASC",
                $complaint_id
            ),
            ARRAY_A
        );

        if (!$results) {
            return [];
        }

        $messages = [];

        foreach ($results as $row) {
            $attachments = [];
            if (!empty($row['attachments'])) {
                $decoded = json_decode((string) $row['attachments'], true);
                if (is_array($decoded)) {
                    $attachments = array_map('absint', $decoded);
                }
            }

            $messages[] = [
                'id'           => isset($row['id']) ? (int) $row['id'] : 0,
                'complaint_id' => $complaint_id,
                'user_id'      => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                'type'         => (string) ($row['type'] ?? self::TYPE_MESSAGE),
                'message'      => (string) ($row['message'] ?? ''),
                'attachments'  => $attachments,
                'created_at'   => (string) ($row['created_at'] ?? ''),
                'author_name'  => (string) ($row['display_name'] ?? $row['user_login'] ?? ''),
            ];
        }

        return $messages;
    }
}

