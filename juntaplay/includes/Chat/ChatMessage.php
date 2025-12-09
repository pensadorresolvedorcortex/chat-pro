<?php

declare(strict_types=1);

namespace JuntaPlay\Chat;

use wpdb;

defined('ABSPATH') || exit;

class ChatMessage
{
    private wpdb $wpdb;

    private string $table;

    public function __construct(?wpdb $wpdb = null)
    {
        $this->wpdb  = $wpdb ?: $GLOBALS['wpdb'];
        $this->table = $this->wpdb->prefix . 'juntaplay_chat_messages';
    }

    public function create_message(array $data)
    {
        $group_id = isset($data['group_id']) ? absint($data['group_id']) : 0;
        $user_id  = isset($data['user_id']) ? absint($data['user_id']) : 0;
        $admin_id = isset($data['admin_id']) ? absint($data['admin_id']) : 0;
        $sender   = isset($data['sender']) ? sanitize_text_field($data['sender']) : '';
        $message  = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';
        $image    = empty($data['image_url']) ? null : esc_url_raw($data['image_url']);

        return $this->wpdb->insert(
            $this->table,
            [
                'group_id'      => $group_id,
                'user_id'       => $user_id,
                'admin_id'      => $admin_id,
                'sender'        => $sender,
                'message'       => $message,
                'image_url'     => $image,
                'is_read_admin' => 0,
                'is_read_user'  => 0,
                'created_at'    => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s']
        );
    }

    public function get_messages(int $group_id, int $user_id, int $admin_id, int $limit = 50)
    {
        $group_id = absint($group_id);
        $user_id  = absint($user_id);
        $admin_id = absint($admin_id);
        $limit    = max(1, absint($limit));

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table}\n                 WHERE group_id = %d AND user_id = %d AND admin_id = %d\n                 ORDER BY created_at ASC\n                 LIMIT %d",
                $group_id,
                $user_id,
                $admin_id,
                $limit
            )
        );
    }

    public function mark_as_read_by_admin(int $group_id, int $user_id)
    {
        $group_id = absint($group_id);
        $user_id  = absint($user_id);

        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table}\n                 SET is_read_admin = 1\n                 WHERE group_id = %d AND user_id = %d",
                $group_id,
                $user_id
            )
        );
    }

    public function mark_as_read_by_user(int $group_id, int $user_id)
    {
        $group_id = absint($group_id);
        $user_id  = absint($user_id);

        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table}\n                 SET is_read_user = 1\n                 WHERE group_id = %d AND user_id = %d",
                $group_id,
                $user_id
            )
        );
    }

    public function count_unread_for_admin(int $admin_id): int
    {
        $admin_id = absint($admin_id);

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}\n                 WHERE admin_id = %d AND is_read_admin = 0",
                $admin_id
            )
        );
    }

    public function count_unread_for_user(int $user_id): int
    {
        $user_id = absint($user_id);

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}\n                 WHERE user_id = %d AND is_read_user = 0",
                $user_id
            )
        );
    }
}
