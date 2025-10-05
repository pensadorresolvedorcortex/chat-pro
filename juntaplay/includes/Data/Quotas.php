<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

defined('ABSPATH') || exit;

use function array_map;
use function preg_replace;
use function str_contains;
use function wp_parse_args;

class Quotas
{
    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function list_numbers(int $pool_id, array $args = []): array
    {
        global $wpdb;

        $defaults = [
            'page'      => 1,
            'per_page'  => 120,
            'status'    => 'available',
            'search'    => '',
            'sort'      => 'ASC',
        ];

        $args = wp_parse_args($args, $defaults);

        $page     = max(1, (int) $args['page']);
        $per_page = max(20, min(240, (int) $args['per_page']));
        $offset   = ($page - 1) * $per_page;

        $table   = "{$wpdb->prefix}jp_quotas";
        $where   = ['pool_id = %d'];
        $params  = [$pool_id];

        if ($args['status'] && $args['status'] !== 'all') {
            if ($args['status'] === 'open') {
                $where[] = "status IN ('available','reserved')";
            } else {
                $where[]  = 'status = %s';
                $params[] = (string) $args['status'];
            }
        }

        if ($args['search']) {
            $search = preg_replace('/[^0-9\-]/', '', (string) $args['search']);
            if (str_contains((string) $search, '-')) {
                [$start, $end] = array_map('intval', explode('-', (string) $search, 2));
                if ($start > 0 && $end >= $start) {
                    $where[]  = 'number BETWEEN %d AND %d';
                    $params[] = $start;
                    $params[] = $end;
                }
            } elseif ($search !== '') {
                $where[]  = 'number = %d';
                $params[] = (int) $search;
            }
        }

        $where_sql = implode(' AND ', $where);
        $sort      = strtoupper((string) $args['sort']) === 'DESC' ? 'DESC' : 'ASC';

        $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS number, status, reserved_until
             FROM $table
             WHERE $where_sql
             ORDER BY number $sort
             LIMIT %d OFFSET %d",
            ...array_merge($params, [$per_page, $offset])
        );

        $rows  = $query ? $wpdb->get_results($query, ARRAY_A) : [];
        $total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
        $pages = (int) ceil($total / $per_page);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'number' => isset($row['number']) ? (int) $row['number'] : 0,
                'status' => (string) ($row['status'] ?? 'available'),
            ];
        }

        return [
            'items'    => $items,
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
            'per_page' => $per_page,
        ];
    }

    public static function reserve(int $pool_id, array $numbers, int $user_id, int $minutes = 15): array
    {
        global $wpdb;

        $table   = "{$wpdb->prefix}jp_quotas";
        $reserved = [];
        $expires = gmdate('Y-m-d H:i:s', time() + ($minutes * 60));

        foreach ($numbers as $number) {
            $number  = (int) $number;
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE $table
                 SET status='reserved', user_id=%d, reserved_until=%s
                 WHERE pool_id=%d AND number=%d AND status='available'
                 LIMIT 1",
                $user_id,
                $expires,
                $pool_id,
                $number
            ));

            if ($updated) {
                $reserved[] = $number;
            }
        }

        return $reserved;
    }

    public static function pay(int $pool_id, array $numbers, int $order_id, int $user_id): void
    {
        global $wpdb;

        if (empty($numbers)) {
            return;
        }

        $table = "{$wpdb->prefix}jp_quotas";
        $in    = implode(',', array_map('intval', $numbers));

        $wpdb->query(
            "UPDATE $table
             SET status='paid', order_id={$order_id}, user_id={$user_id}, reserved_until=NULL
             WHERE pool_id={$pool_id} AND number IN ($in) AND status IN ('reserved','available')"
        );
    }

    public static function release_by_order(int $order_id): void
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_quotas";
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table
                 SET status='available', user_id=NULL, order_id=NULL, reserved_until=NULL
                 WHERE order_id=%d",
                $order_id
            )
        );
    }

    public static function seed(int $pool_id): void
    {
        global $wpdb;

        $pool = Pools::get($pool_id);

        if (!$pool) {
            return;
        }

        $table      = "{$wpdb->prefix}jp_quotas";
        $start      = (int) $pool->quota_start;
        $end        = (int) $pool->quota_end;
        $numbers    = range($start, $end);
        $existing   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE pool_id = %d", $pool_id));

        if ($existing >= count($numbers)) {
            return;
        }

        $chunks = array_chunk($numbers, 500);

        foreach ($chunks as $chunk) {
            $values = [];

            foreach ($chunk as $number) {
                $number = (int) $number;
                $values[] = $wpdb->prepare('(%d,%d,%s)', $pool_id, $number, 'available');
            }

            $sql = "INSERT IGNORE INTO $table (pool_id, number, status) VALUES " . implode(',', $values);
            $wpdb->query($sql);
        }
    }
}
