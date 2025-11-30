<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function is_array;
use function wp_json_encode;

defined('ABSPATH') || exit;

class CreditWithdrawals
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_APPROVED   = 'approved';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_CANCELED   = 'canceled';

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $user_id = isset($data['user_id']) ? absint($data['user_id']) : 0;

        if ($user_id <= 0) {
            return 0;
        }

        $table       = "{$wpdb->prefix}jp_credit_withdrawals";
        $amount      = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $method      = isset($data['method']) ? (string) $data['method'] : 'pix';
        $status      = isset($data['status']) ? (string) $data['status'] : self::STATUS_PENDING;
        $destination = isset($data['destination']) && is_array($data['destination']) ? $data['destination'] : [];
        $reference   = isset($data['reference']) ? (string) $data['reference'] : '';

        $payload = [
            'user_id'    => $user_id,
            'amount'     => $amount,
            'method'     => $method,
            'status'     => $status,
            'destination'=> $destination ? wp_json_encode($destination) : null,
            'reference'  => $reference !== '' ? $reference : null,
            'requested_at' => current_time('mysql'),
            'updated_at'   => current_time('mysql'),
        ];

        $formats = ['%d', '%f', '%s', '%s', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table, $payload, $formats);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_for_user(int $user_id, int $limit = 10): array
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_credit_withdrawals";
        $limit = max(1, min(50, $limit));

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY requested_at DESC LIMIT %d",
            $user_id,
            $limit
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $items = [];

        foreach ($rows as $row) {
            $items[] = self::format_row($row);
        }

        return $items;
    }

    public static function get_pending_total(int $user_id): float
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_credit_withdrawals";

        $query = $wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM $table
             WHERE user_id = %d AND status IN ('pending','processing')",
            $user_id
        );

        return (float) $wpdb->get_var($query);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(int $withdrawal_id, int $user_id): ?array
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_credit_withdrawals";

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $withdrawal_id,
            $user_id
        );

        $row = $wpdb->get_row($query, ARRAY_A);

        if (!$row) {
            return null;
        }

        return self::format_row($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function format_row(array $row): array
    {
        $destination = [];
        if (!empty($row['destination'])) {
            $decoded = json_decode((string) $row['destination'], true);
            if (is_array($decoded)) {
                $destination = $decoded;
            }
        }

        return [
            'id'          => isset($row['id']) ? (int) $row['id'] : 0,
            'user_id'     => isset($row['user_id']) ? (int) $row['user_id'] : 0,
            'amount'      => isset($row['amount']) ? (float) $row['amount'] : 0.0,
            'method'      => (string) ($row['method'] ?? 'pix'),
            'status'      => (string) ($row['status'] ?? self::STATUS_PENDING),
            'destination' => $destination,
            'reference'   => (string) ($row['reference'] ?? ''),
            'requested_at'=> (string) ($row['requested_at'] ?? ''),
            'processed_at'=> (string) ($row['processed_at'] ?? ''),
            'updated_at'  => (string) ($row['updated_at'] ?? ''),
            'admin_note'  => (string) ($row['admin_note'] ?? ''),
        ];
    }
}
