<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function wp_json_encode;
use function json_decode;
use function is_array;

defined('ABSPATH') || exit;

class PaymentSplits
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $order_id       = isset($data['order_id']) ? absint($data['order_id']) : 0;
        $group_id       = isset($data['group_id']) ? absint($data['group_id']) : 0;
        $group_title    = isset($data['group_title']) ? (string) $data['group_title'] : '';
        $base_amount    = isset($data['base_amount']) ? (float) $data['base_amount'] : 0.0;
        $superadmin_id  = isset($data['superadmin_id']) ? absint($data['superadmin_id']) : 0;
        $admin_id       = isset($data['admin_id']) ? absint($data['admin_id']) : 0;
        $participant_id = isset($data['participant_id']) ? absint($data['participant_id']) : 0;
        $percentage     = isset($data['percentage']) ? (float) $data['percentage'] : 0.0;
        $superadmin     = isset($data['superadmin_amount']) ? (float) $data['superadmin_amount'] : 0.0;
        $admin_amount   = isset($data['admin_amount']) ? (float) $data['admin_amount'] : 0.0;
        $deposit        = isset($data['deposit_amount']) ? (float) $data['deposit_amount'] : 0.0;
        $payload        = isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : [];
        $status         = isset($data['status']) ? (string) $data['status'] : self::STATUS_PENDING;
        $error_message  = isset($data['error_message']) ? (string) $data['error_message'] : '';
        $processed_at   = isset($data['processed_at']) ? (string) $data['processed_at'] : null;

        if ($order_id <= 0 || $group_id <= 0) {
            return 0;
        }

        $table = "{$wpdb->prefix}jp_payment_splits";

        $inserted = $wpdb->insert(
            $table,
            [
                'order_id'          => $order_id,
                'group_id'          => $group_id,
                'group_title'       => $group_title !== '' ? $group_title : null,
                'base_amount'       => $base_amount,
                'superadmin_id'     => $superadmin_id ?: null,
                'admin_id'          => $admin_id ?: null,
                'participant_id'    => $participant_id ?: null,
                'percentage'        => $percentage,
                'superadmin_amount' => $superadmin,
                'admin_amount'      => $admin_amount,
                'deposit_amount'    => $deposit,
                'status'            => $status,
                'error_message'     => $error_message !== '' ? $error_message : null,
                'processed_at'      => $processed_at,
                'payload'           => $payload ? wp_json_encode($payload) : null,
                'created_at'        => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%d', '%d', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function mark_completed(int $id): bool
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_payment_splits";

        return (bool) $wpdb->update(
            $table,
            [
                'status'       => self::STATUS_COMPLETED,
                'processed_at' => current_time('mysql'),
                'error_message'=> null,
            ],
            ['id' => absint($id)],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    public static function mark_failed(int $id, string $message): bool
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_payment_splits";

        return (bool) $wpdb->update(
            $table,
            [
                'status'        => self::STATUS_FAILED,
                'error_message' => $message,
                'processed_at'  => current_time('mysql'),
            ],
            ['id' => absint($id)],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    public static function exists_for_order(int $order_id): bool
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_payment_splits";
        $query = $wpdb->prepare("SELECT COUNT(1) FROM $table WHERE order_id = %d", $order_id);

        return (int) $wpdb->get_var($query) > 0;
    }

    public static function has_completed_for_order(int $order_id): bool
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_payment_splits";
        $query = $wpdb->prepare(
            "SELECT COUNT(1) FROM $table WHERE order_id = %d AND status = %s",
            $order_id,
            self::STATUS_COMPLETED
        );

        return (int) $wpdb->get_var($query) > 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function latest(int $limit = 50): array
    {
        global $wpdb;

        $limit = max(1, min(200, $limit));
        $table = "{$wpdb->prefix}jp_payment_splits";

        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d", $limit),
            ARRAY_A
        ) ?: [];

        $items = [];
        foreach ($results as $row) {
            $items[] = self::format_row($row);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function format_row(array $row): array
    {
        $payload = [];
        if (!empty($row['payload'])) {
            $decoded = json_decode((string) $row['payload'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return [
            'id'               => isset($row['id']) ? (int) $row['id'] : 0,
            'order_id'         => isset($row['order_id']) ? (int) $row['order_id'] : 0,
            'group_id'         => isset($row['group_id']) ? (int) $row['group_id'] : 0,
            'group_title'      => (string) ($row['group_title'] ?? ''),
            'base_amount'      => isset($row['base_amount']) ? (float) $row['base_amount'] : 0.0,
            'superadmin_id'    => isset($row['superadmin_id']) ? (int) $row['superadmin_id'] : 0,
            'admin_id'         => isset($row['admin_id']) ? (int) $row['admin_id'] : 0,
            'participant_id'   => isset($row['participant_id']) ? (int) $row['participant_id'] : 0,
            'percentage'       => isset($row['percentage']) ? (float) $row['percentage'] : 0.0,
            'superadmin_amount'=> isset($row['superadmin_amount']) ? (float) $row['superadmin_amount'] : 0.0,
            'admin_amount'     => isset($row['admin_amount']) ? (float) $row['admin_amount'] : 0.0,
            'deposit_amount'   => isset($row['deposit_amount']) ? (float) $row['deposit_amount'] : 0.0,
            'status'           => (string) ($row['status'] ?? self::STATUS_PENDING),
            'error_message'    => (string) ($row['error_message'] ?? ''),
            'processed_at'     => (string) ($row['processed_at'] ?? ''),
            'payload'          => $payload,
            'created_at'       => (string) ($row['created_at'] ?? ''),
        ];
    }
}
