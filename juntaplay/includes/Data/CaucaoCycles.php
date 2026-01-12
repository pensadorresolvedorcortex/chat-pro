<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function array_filter;
use function array_map;
use function array_values;
use function current_time;

defined('ABSPATH') || exit;

class CaucaoCycles
{
    public const STATUS_RETIDO             = 'retido';
    public const STATUS_LIBERADO           = 'liberado';
    public const STATUS_RETIDO_DEFINITIVO  = 'retido_definitivo';
    public const STATUS_USADO_PARA_PREJUIZO = 'usado_para_prejuizo';

    /**
     * @param array<string, mixed> $data
     */
    public static function create_retained(array $data): int
    {
        global $wpdb;

        $user_id  = isset($data['user_id']) ? absint($data['user_id']) : 0;
        $group_id = isset($data['group_id']) ? absint($data['group_id']) : 0;
        $order_id = isset($data['order_id']) ? absint($data['order_id']) : 0;
        $amount   = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $start    = isset($data['cycle_start']) ? (string) $data['cycle_start'] : current_time('mysql');
        $end      = isset($data['cycle_end']) ? (string) $data['cycle_end'] : '';

        if ($user_id <= 0 || $group_id <= 0 || $order_id <= 0 || $amount <= 0.0) {
            return 0;
        }

        if ($end === '') {
            $end = gmdate('Y-m-d H:i:s', strtotime('+31 days', strtotime($start)) ?: time());
        }

        $table = "{$wpdb->prefix}jp_caucao_cycles";

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'     => $user_id,
                'group_id'    => $group_id,
                'order_id'    => $order_id,
                'amount'      => $amount,
                'status'      => self::STATUS_RETIDO,
                'cycle_start' => $start,
                'cycle_end'   => $end,
                'created_at'  => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public static function mark_status(int $id, string $status, string $note = ''): bool
    {
        global $wpdb;

        $id = absint($id);
        if ($id <= 0) {
            return false;
        }

        $table = "{$wpdb->prefix}jp_caucao_cycles";

        $updated = $wpdb->update(
            $table,
            [
                'status'       => $status,
                'validated_at' => current_time('mysql'),
                'note'         => $note !== '' ? $note : null,
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        return (bool) $updated;
    }

    public static function delete(int $id): bool
    {
        global $wpdb;

        $id = absint($id);
        if ($id <= 0) {
            return false;
        }

        $table = "{$wpdb->prefix}jp_caucao_cycles";

        return (bool) $wpdb->delete($table, ['id' => $id], ['%d']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function due_for_validation(int $limit = 50): array
    {
        global $wpdb;

        $limit = max(1, min(200, $limit));
        $table = "{$wpdb->prefix}jp_caucao_cycles";

        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE status = %s AND cycle_end <= %s ORDER BY cycle_end ASC LIMIT %d",
            self::STATUS_RETIDO,
            current_time('mysql'),
            $limit
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];

        return array_map([self::class, 'format_row'], $rows);
    }

    /**
     * @param int[] $group_ids
     * @return array<int, array<string, mixed>>
     */
    public static function get_latest_for_user_groups(int $user_id, array $group_ids): array
    {
        global $wpdb;

        $user_id = absint($user_id);
        $group_ids = array_values(array_filter(array_map('absint', $group_ids)));

        if ($user_id <= 0 || !$group_ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
        $table = "{$wpdb->prefix}jp_caucao_cycles";

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND group_id IN ($placeholders) ORDER BY created_at DESC",
                ...array_merge([$user_id], $group_ids)
            ),
            ARRAY_A
        ) ?: [];

        if (!$rows) {
            return [];
        }

        $latest = [];

        foreach ($rows as $row) {
            $group_id = isset($row['group_id']) ? (int) $row['group_id'] : 0;

            if ($group_id <= 0 || isset($latest[$group_id])) {
                continue;
            }

            $latest[$group_id] = self::format_row($row);
        }

        return $latest;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function format_row(array $row): array
    {
        return [
            'id'          => isset($row['id']) ? (int) $row['id'] : 0,
            'user_id'     => isset($row['user_id']) ? (int) $row['user_id'] : 0,
            'group_id'    => isset($row['group_id']) ? (int) $row['group_id'] : 0,
            'order_id'    => isset($row['order_id']) ? (int) $row['order_id'] : 0,
            'amount'      => isset($row['amount']) ? (float) $row['amount'] : 0.0,
            'status'      => (string) ($row['status'] ?? self::STATUS_RETIDO),
            'cycle_start' => (string) ($row['cycle_start'] ?? ''),
            'cycle_end'   => (string) ($row['cycle_end'] ?? ''),
            'validated_at'=> (string) ($row['validated_at'] ?? ''),
            'note'        => (string) ($row['note'] ?? ''),
            'created_at'  => (string) ($row['created_at'] ?? ''),
        ];
    }
}
