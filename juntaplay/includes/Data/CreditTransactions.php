<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function current_time;
use function is_array;
use function wp_json_encode;

defined('ABSPATH') || exit;

class CreditTransactions
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_FAILED    = 'failed';

    public const TYPE_DEPOSIT     = 'deposit';
    public const TYPE_WITHDRAWAL  = 'withdrawal';
    public const TYPE_ADJUSTMENT  = 'adjustment';
    public const TYPE_PURCHASE    = 'purchase';
    public const TYPE_REFUND      = 'refund';
    public const TYPE_BONUS       = 'bonus';

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

        $table    = "{$wpdb->prefix}jp_credit_transactions";
        $type     = isset($data['type']) ? (string) $data['type'] : self::TYPE_ADJUSTMENT;
        $status   = isset($data['status']) ? (string) $data['status'] : self::STATUS_COMPLETED;
        $amount   = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $balance  = isset($data['balance_after']) ? (float) $data['balance_after'] : null;
        $reference = isset($data['reference']) ? (string) $data['reference'] : '';
        $context  = isset($data['context']) && is_array($data['context']) ? $data['context'] : [];

        $payload = [
            'user_id'       => $user_id,
            'type'          => $type,
            'status'        => $status,
            'amount'        => $amount,
            'balance_after' => $balance,
            'reference'     => $reference !== '' ? $reference : null,
            'context'       => $context ? wp_json_encode($context) : null,
            'created_at'    => current_time('mysql'),
        ];

        $formats = ['%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table, $payload, $formats);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @return array<string, mixed>
     */
    public static function get_for_user(int $user_id, int $page = 1, int $per_page = 10, array $filters = []): array
    {
        global $wpdb;

        $user_id = absint($user_id);
        if ($user_id <= 0) {
            return ['items' => [], 'total' => 0, 'pages' => 0];
        }

        $page     = max(1, $page);
        $per_page = max(1, min(100, $per_page));
        $offset   = ($page - 1) * $per_page;

        $table = "{$wpdb->prefix}jp_credit_transactions";

        $where  = ['user_id = %d'];
        $params = [$user_id];

        if (!empty($filters['type'])) {
            $where[]  = 'type = %s';
            $params[] = (string) $filters['type'];
        }

        if (!empty($filters['status'])) {
            $where[]  = 'status = %s';
            $params[] = (string) $filters['status'];
        }

        $where_sql = implode(' AND ', $where);

        $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS *
             FROM $table
             WHERE $where_sql
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            array_merge($params, [$per_page, $offset])
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
        $pages = (int) ceil($total / $per_page);

        $items = [];
        foreach ($rows as $row) {
            $items[] = self::format_row($row);
        }

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(int $transaction_id, int $user_id): ?array
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_credit_transactions";

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d AND user_id = %d",
            $transaction_id,
            $user_id
        );

        $row = $wpdb->get_row($query, ARRAY_A);

        if (!$row) {
            return null;
        }

        return self::format_row($row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_recent(int $user_id, int $limit = 5): array
    {
        global $wpdb;

        $limit   = max(1, min(50, $limit));
        $table   = "{$wpdb->prefix}jp_credit_transactions";
        $query   = $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        );
        $results = $wpdb->get_results($query, ARRAY_A) ?: [];

        $items = [];
        foreach ($results as $row) {
            $items[] = self::format_row($row);
        }

        return $items;
    }

    public static function sum_by_status(int $user_id, string $status): float
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_credit_transactions";

        $query = $wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table WHERE user_id = %d AND status = %s",
            $user_id,
            $status
        );

        return (float) $wpdb->get_var($query);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function format_row(array $row): array
    {
        $context = [];
        if (!empty($row['context'])) {
            $decoded = json_decode((string) $row['context'], true);
            if (is_array($decoded)) {
                $context = $decoded;
            }
        }

        return [
            'id'            => isset($row['id']) ? (int) $row['id'] : 0,
            'user_id'       => isset($row['user_id']) ? (int) $row['user_id'] : 0,
            'type'          => (string) ($row['type'] ?? self::TYPE_ADJUSTMENT),
            'status'        => (string) ($row['status'] ?? self::STATUS_COMPLETED),
            'amount'        => isset($row['amount']) ? (float) $row['amount'] : 0.0,
            'balance_after' => isset($row['balance_after']) ? (float) $row['balance_after'] : null,
            'reference'     => (string) ($row['reference'] ?? ''),
            'context'       => $context,
            'created_at'    => (string) ($row['created_at'] ?? ''),
        ];
    }
}
