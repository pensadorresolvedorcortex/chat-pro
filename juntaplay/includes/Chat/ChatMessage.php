<?php

declare(strict_types=1);

namespace JuntaPlay\Chat;

use wpdb;

defined('ABSPATH') || exit;

class ChatMessage
{
    private wpdb $wpdb;

    private string $table;

    private bool $table_checked = false;

    private bool $table_exists  = false;

    public function __construct(?wpdb $wpdb = null)
    {
        $this->wpdb  = $wpdb ?: $GLOBALS['wpdb'];
        $this->table = $this->wpdb->prefix . 'juntaplay_chat_messages';
    }

    public function create_message(array $data)
    {
        if (!$this->ensure_table()) {
            return false;
        }

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

    /**
     * @param int|string $group_id
     * @param int|string $user_id
     * @param int|string $admin_id
     * @param int|string $limit
     */
    public function get_messages($group_id, $user_id, $admin_id, $limit = 50)
    {
        if (!$this->ensure_table()) {
            return [];
        }

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

    /**
     * @param int|string $group_id
     * @param int|string $user_id
     */
    public function mark_as_read_by_admin($group_id, $user_id)
    {
        if (!$this->ensure_table()) {
            return 0;
        }

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

    /**
     * @param int|string $group_id
     * @param int|string $user_id
     */
    public function mark_as_read_by_user($group_id, $user_id)
    {
        if (!$this->ensure_table()) {
            return 0;
        }

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

    /**
     * @param int|string $admin_id
     */
    public function count_unread_for_admin($admin_id): int
    {
        if (!$this->ensure_table()) {
            return 0;
        }

        $admin_id = absint($admin_id);

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}\n                 WHERE admin_id = %d AND is_read_admin = 0",
                $admin_id
            )
        );
    }

    /**
     * @param int|string $user_id
     */
    public function count_unread_for_user($user_id): int
    {
        if (!$this->ensure_table()) {
            return 0;
        }

        $user_id = absint($user_id);

        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}\n                 WHERE user_id = %d AND is_read_user = 0",
                $user_id
            )
        );
    }

    private function ensure_table(): bool
    {
        if ($this->table_checked) {
            return $this->table_exists;
        }

        $this->table_checked = true;

        if ($this->table_exists()) {
            $this->table_exists = true;
            return true;
        }

        $this->create_table();

        $this->table_exists = $this->table_exists();

        if (!$this->table_exists) {
            error_log('JuntaPlay chat table could not be created.');
        }

        return $this->table_exists;
    }

    private function create_table(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $this->wpdb->get_charset_collate();
        $table_name      = $this->table;
        $sql             = "CREATE TABLE {$table_name} (" .
            'id BIGINT NOT NULL AUTO_INCREMENT,' .
            'group_id BIGINT NULL,' .
            'user_id BIGINT NULL,' .
            'admin_id BIGINT NULL,' .
            "sender VARCHAR(10) NOT NULL," .
            'message TEXT NOT NULL,' .
            'image_url VARCHAR(255) NULL,' .
            'is_read_admin TINYINT NOT NULL DEFAULT 0,' .
            'is_read_user TINYINT NOT NULL DEFAULT 0,' .
            'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,' .
            'PRIMARY KEY (id)' .
        ") {$charset_collate};";

        dbDelta($sql);
    }

    private function table_exists(): bool
    {
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
                $this->wpdb->dbname,
                $this->table
            )
        ) > 0;
    }
}
