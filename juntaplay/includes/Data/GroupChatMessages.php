<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function get_permalink;
use function sanitize_textarea_field;
use function wp_json_encode;

defined('ABSPATH') || exit;

class GroupChatMessages
{
    public const TYPE_TEXT  = 'text';
    public const TYPE_IMAGE = 'image';

    public static function add_text(int $chat_id, int $sender_id, string $message): int
    {
        return self::add($chat_id, $sender_id, self::TYPE_TEXT, $message, null);
    }

    public static function add_image(int $chat_id, int $sender_id, int $attachment_id): int
    {
        return self::add($chat_id, $sender_id, self::TYPE_IMAGE, '', $attachment_id);
    }

    public static function add(int $chat_id, int $sender_id, string $type, string $message, ?int $attachment_id): int
    {
        global $wpdb;

        $chat_id   = absint($chat_id);
        $sender_id = absint($sender_id);
        $attachment_id = $attachment_id !== null ? absint($attachment_id) : null;
        $message   = sanitize_textarea_field($message);

        if ($chat_id <= 0 || $sender_id <= 0) {
            return 0;
        }

        $table = "{$wpdb->prefix}jp_group_chat_messages";

        $inserted = $wpdb->insert(
            $table,
            [
                'chat_id'       => $chat_id,
                'sender_id'     => $sender_id,
                'type'          => $type === self::TYPE_IMAGE ? self::TYPE_IMAGE : self::TYPE_TEXT,
                'message'       => $message !== '' ? $message : null,
                'attachment_id' => $attachment_id ?: null,
                'created_at'    => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s']
        );

        if ($inserted) {
            GroupChats::touch($chat_id);
        }

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_for_chat(int $chat_id, int $limit = 50, ?string $after = null): array
    {
        global $wpdb;

        $chat_id = absint($chat_id);
        $limit   = max(1, min(200, $limit));

        if ($chat_id <= 0) {
            return [];
        }

        $messages_table = "{$wpdb->prefix}jp_group_chat_messages";
        $users_table    = $wpdb->users;

        $where = $wpdb->prepare('WHERE chat_id = %d', $chat_id);
        $params = [$limit];

        if ($after !== null && $after !== '') {
            $where  .= $wpdb->prepare(' AND created_at > %s', $after);
        }

        $sql = "SELECT m.*, u.display_name, u.user_login
                FROM $messages_table m
                LEFT JOIN $users_table u ON u.ID = m.sender_id
                $where
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT %d";

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A) ?: [];

        $messages = [];
        foreach ($rows as $row) {
            $messages[] = [
                'id'            => isset($row['id']) ? (int) $row['id'] : 0,
                'chat_id'       => $chat_id,
                'sender_id'     => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'type'          => (string) ($row['type'] ?? self::TYPE_TEXT),
                'message'       => (string) ($row['message'] ?? ''),
                'attachment_id' => isset($row['attachment_id']) ? (int) $row['attachment_id'] : 0,
                'created_at'    => (string) ($row['created_at'] ?? ''),
                'sender_name'   => (string) ($row['display_name'] ?? $row['user_login'] ?? ''),
            ];
        }

        return array_reverse($messages);
    }
}
