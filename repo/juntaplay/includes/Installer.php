<?php
declare(strict_types=1);

namespace JuntaPlay;

use wpdb;
use function get_option;
use function update_option;

defined('ABSPATH') || exit;

class Installer
{
    public function activate(): void
    {
        $this->create_tables();
        self::bootstrap_cron();
        self::schedule_cron();
        $this->maybe_create_pages();

        add_option('juntaplay_db_version', JP_DB_VERSION);
    }

    private function create_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $pools_table     = "CREATE TABLE {$wpdb->prefix}jp_pools (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            product_id BIGINT UNSIGNED NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            quota_start INT NOT NULL DEFAULT 1,
            quota_end INT NOT NULL DEFAULT 1,
            quotas_total INT NOT NULL DEFAULT 0,
            quotas_paid INT NOT NULL DEFAULT 0,
            category VARCHAR(100) NOT NULL DEFAULT '',
            excerpt TEXT NULL,
            service_url VARCHAR(255) NULL,
            thumbnail_id BIGINT UNSIGNED NULL,
            icon_id BIGINT UNSIGNED NULL,
            cover_id BIGINT UNSIGNED NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category_status (category, status),
            KEY featured_status (is_featured, status)
        ) $charset_collate;";

        $quotas_table = "CREATE TABLE {$wpdb->prefix}jp_quotas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            pool_id BIGINT UNSIGNED NOT NULL,
            number INT NOT NULL,
            status ENUM('available','reserved','paid','canceled','expired') NOT NULL DEFAULT 'available',
            user_id BIGINT UNSIGNED NULL,
            order_id BIGINT UNSIGNED NULL,
            reserved_until DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY pool_number (pool_id, number),
            KEY pool_status (pool_id, status),
            KEY reserved_until (reserved_until)
        ) $charset_collate;";

        $groups_table = "CREATE TABLE {$wpdb->prefix}jp_groups (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            owner_id BIGINT UNSIGNED NOT NULL,
            pool_id BIGINT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            service_name VARCHAR(255) NOT NULL DEFAULT '',
            service_url VARCHAR(255) NULL,
            rules TEXT NULL,
            description TEXT NULL,
            price_regular DECIMAL(10,2) NOT NULL DEFAULT 0,
            price_promotional DECIMAL(10,2) NULL,
            member_price DECIMAL(10,2) NULL,
            slots_total INT NOT NULL DEFAULT 0,
            slots_reserved INT NOT NULL DEFAULT 0,
            support_channel VARCHAR(100) NOT NULL DEFAULT '',
            delivery_time VARCHAR(100) NOT NULL DEFAULT '',
            access_method VARCHAR(100) NOT NULL DEFAULT '',
            access_url VARCHAR(255) NULL,
            access_login VARCHAR(150) NULL,
            access_password VARCHAR(150) NULL,
            access_notes TEXT NULL,
            access_observations TEXT NULL,
            cover_id BIGINT UNSIGNED NULL,
            category VARCHAR(100) NOT NULL DEFAULT '',
            relationship_type VARCHAR(50) NOT NULL DEFAULT '',
            instant_access TINYINT(1) NOT NULL DEFAULT 0,
            email_validation_hash VARCHAR(255) NULL,
            email_validation_sent_at DATETIME NULL,
            email_validated_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            visibility VARCHAR(20) NOT NULL DEFAULT 'public',
            review_note TEXT NULL,
            reviewed_by BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            slug VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY owner_status (owner_id, status),
            KEY pool_status_visibility (pool_id, status, visibility),
            KEY status_created (status, created_at)
        ) $charset_collate;";

        $group_members_table = "CREATE TABLE {$wpdb->prefix}jp_group_members (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'member',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user (group_id, user_id),
            KEY user_status (user_id, status)
        ) $charset_collate;";

        $group_complaints_table = "CREATE TABLE {$wpdb->prefix}jp_group_complaints (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            reason VARCHAR(50) NOT NULL DEFAULT 'other',
            message TEXT NOT NULL,
            attachments LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            admin_note TEXT NULL,
            resolved_by BIGINT UNSIGNED NULL,
            resolved_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_status (group_id, status),
            KEY user_group (user_id, group_id)
        ) $charset_collate;";

        $complaint_messages_table = "CREATE TABLE {$wpdb->prefix}jp_group_complaint_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            complaint_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'message',
            message LONGTEXT NULL,
            attachments LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY complaint_created (complaint_id, created_at)
        ) $charset_collate;";

        $membership_events_table = "CREATE TABLE {$wpdb->prefix}jp_group_membership_events (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            message TEXT NULL,
            metadata LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY group_type (group_id, type),
            KEY user_type (user_id, type)
        ) $charset_collate;";

        $credit_transactions_table = "CREATE TABLE {$wpdb->prefix}jp_credit_transactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'completed',
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            balance_after DECIMAL(10,2) NULL,
            reference VARCHAR(191) NULL,
            context LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_status (user_id, status),
            KEY user_created (user_id, created_at),
            KEY reference (reference)
        ) $charset_collate;";

        $credit_withdrawals_table = "CREATE TABLE {$wpdb->prefix}jp_credit_withdrawals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            method VARCHAR(20) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            destination LONGTEXT NULL,
            reference VARCHAR(100) NULL,
            admin_note TEXT NULL,
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_status (user_id, status),
            KEY reference (reference)
        ) $charset_collate;";

        $notifications_table = "CREATE TABLE {$wpdb->prefix}jp_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            action_url VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'unread',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_status (user_id, status),
            KEY user_created (user_id, created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($pools_table);
        dbDelta($quotas_table);
        dbDelta($groups_table);
        dbDelta($group_members_table);
        dbDelta($group_complaints_table);
        dbDelta($complaint_messages_table);
        dbDelta($membership_events_table);
        dbDelta($credit_transactions_table);
        dbDelta($credit_withdrawals_table);
        dbDelta($notifications_table);
    }

    public static function bootstrap_cron(): void
    {
        add_filter('cron_schedules', [self::class, 'register_custom_schedule']);
        add_action('juntaplay_cron_release_expired', [self::class, 'release_expired']);
    }

    public static function register_custom_schedule(array $schedules): array
    {
        if (!isset($schedules['minute'])) {
            $schedules['minute'] = [
                'interval' => 60,
                'display'  => __('A cada minuto', 'juntaplay'),
            ];
        }

        return $schedules;
    }

    public static function schedule_cron(): void
    {
        if (!wp_next_scheduled('juntaplay_cron_release_expired')) {
            wp_schedule_event(time() + 60, 'minute', 'juntaplay_cron_release_expired');
        }
    }

    public static function release_expired(): void
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_quotas";
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table
                SET status='available', user_id=NULL, order_id=NULL, reserved_until=NULL
                WHERE status='reserved' AND reserved_until IS NOT NULL AND reserved_until < %s",
                current_time('mysql')
            )
        );
    }

    private function maybe_create_pages(): void
    {
        $pages = [
            'campanhas'    => ['title' => 'Campanhas', 'shortcode' => '[juntaplay_pools]'],
            'minhas-cotas' => ['title' => 'Minhas Cotas', 'shortcode' => '[juntaplay_my_quotas]'],
            'extrato'      => ['title' => 'Extrato', 'shortcode' => '[juntaplay_statement]'],
            'perfil'       => ['title' => 'Perfil', 'shortcode' => '[juntaplay_profile]'],
            'entrar'       => ['title' => 'Entrar', 'shortcode' => '[juntaplay_login_form]'],
            'regras'       => ['title' => 'Regras', 'shortcode' => '[juntaplay_terms]'],
            'painel'       => ['title' => 'Painel', 'shortcode' => '[juntaplay_dashboard]'],
            'grupos'       => ['title' => 'Grupos', 'shortcode' => '[juntaplay_groups]'],
            'verificar-acesso' => ['title' => 'Verificar acesso', 'shortcode' => '[juntaplay_two_factor]'],
            'relacao-administrador' => ['title' => 'Relação com Administrador', 'shortcode' => '[juntaplay_group_relationship]'],
        ];

        foreach ($pages as $slug => $data) {
            if (get_page_by_path($slug)) {
                continue;
            }

            $page_id = wp_insert_post([
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => $data['shortcode'],
            ]);

            if ($page_id && !is_wp_error($page_id)) {
                add_option('juntaplay_page_' . $slug, (int) $page_id);
            }
        }
    }

    public static function maybe_upgrade(): void
    {
        $current_version = (string) get_option('juntaplay_db_version', '0');

        if (version_compare($current_version, JP_DB_VERSION, '>=')) {
            return;
        }

        $installer = new self();
        $installer->upgrade_pools_columns();
        $installer->create_tables();

        update_option('juntaplay_db_version', JP_DB_VERSION);
    }

    private function upgrade_pools_columns(): void
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_pools";

        $columns = [
            'service_url' => "ALTER TABLE $table ADD COLUMN service_url VARCHAR(255) NULL AFTER excerpt",
            'icon_id'     => "ALTER TABLE $table ADD COLUMN icon_id BIGINT UNSIGNED NULL AFTER thumbnail_id",
            'cover_id'    => "ALTER TABLE $table ADD COLUMN cover_id BIGINT UNSIGNED NULL AFTER icon_id",
        ];

        foreach ($columns as $column => $statement) {
            $exists = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE %s', $column));
            if ($exists) {
                continue;
            }

            $wpdb->query($statement); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }
    }
}
