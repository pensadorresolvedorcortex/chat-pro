<?php

declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function __;
use function absint;
use function array_filter;
use function array_fill;
use function array_map;
use function count;
use function current_time;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function sanitize_key;
use function sanitize_textarea_field;
use function wp_json_encode;

defined('ABSPATH') || exit;

class GroupComplaints
{
    public const STATUS_OPEN         = 'open';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_RESOLVED     = 'resolved';
    public const STATUS_REJECTED     = 'rejected';

    private const TICKET_OFFSET = 388576;

    /**
     * @return array<string, string>
     */
    public static function get_reasons(): array
    {
        return [
            'access'  => __('Não recebi o acesso prometido', 'juntaplay'),
            'quality' => __('O conteúdo entregue está diferente do combinado', 'juntaplay'),
            'payment' => __('Cobrança incorreta ou duplicada', 'juntaplay'),
            'support' => __('Administrador não responde ou sumiu', 'juntaplay'),
            'other'   => __('Outro motivo', 'juntaplay'),
        ];
    }

    public static function get_reason_label(string $reason): string
    {
        $reasons = self::get_reasons();
        $reason  = sanitize_key($reason);

        return $reasons[$reason] ?? $reasons['other'];
    }

    /**
     * @return array<string, string>
     */
    public static function describe_status(string $status): array
    {
        $status = sanitize_key($status);

        switch ($status) {
            case self::STATUS_RESOLVED:
                return [
                    'label'   => __('Resolvida', 'juntaplay'),
                    'tone'    => 'positive',
                    'message' => __('A reclamação foi resolvida e os créditos envolvidos foram liberados.', 'juntaplay'),
                ];
            case self::STATUS_REJECTED:
                return [
                    'label'   => __('Encerrada', 'juntaplay'),
                    'tone'    => 'info',
                    'message' => __('A reclamação foi encerrada após análise da equipe JuntaPlay.', 'juntaplay'),
                ];
            case self::STATUS_UNDER_REVIEW:
                return [
                    'label'   => __('Em análise', 'juntaplay'),
                    'tone'    => 'warning',
                    'message' => __('Nossa equipe está verificando as evidências enviadas. Você receberá atualizações por e-mail.', 'juntaplay'),
                ];
            case self::STATUS_OPEN:
            default:
                return [
                    'label'   => __('Aberta', 'juntaplay'),
                    'tone'    => 'warning',
                    'message' => __('Recebemos sua reclamação e o administrador do grupo já foi notificado.', 'juntaplay'),
                ];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $group_id = isset($data['group_id']) ? absint($data['group_id']) : 0;
        $user_id  = isset($data['user_id']) ? absint($data['user_id']) : 0;

        if ($group_id <= 0 || $user_id <= 0) {
            return 0;
        }

        $table = "{$wpdb->prefix}jp_group_complaints";

        $reason  = isset($data['reason']) ? sanitize_key((string) $data['reason']) : 'other';
        $message = isset($data['message']) ? sanitize_textarea_field((string) $data['message']) : '';
        $order_id = isset($data['order_id']) ? absint($data['order_id']) : 0;
        $status   = isset($data['status']) ? sanitize_key((string) $data['status']) : self::STATUS_OPEN;

        if ($message === '') {
            return 0;
        }

        $attachments = [];
        if (!empty($data['attachments']) && is_array($data['attachments'])) {
            $attachments = array_filter(array_map('absint', $data['attachments']));
        }

        $payload = [
            'group_id'    => $group_id,
            'user_id'     => $user_id,
            'order_id'    => $order_id > 0 ? $order_id : null,
            'reason'      => $reason !== '' ? $reason : 'other',
            'message'     => $message,
            'attachments' => $attachments ? wp_json_encode($attachments) : null,
            'status'      => $status !== '' ? $status : self::STATUS_OPEN,
            'created_at'  => current_time('mysql'),
            'updated_at'  => current_time('mysql'),
        ];

        $formats = ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table, $payload, $formats);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @param int   $user_id
     * @param int[] $group_ids
     * @return array<int, array<string, mixed>>
     */
    public static function get_summary_for_user(int $user_id, array $group_ids): array
    {
        global $wpdb;

        $user_id = absint($user_id);
        $group_ids = array_values(array_filter(array_map('absint', $group_ids)));

        if ($user_id <= 0 || !$group_ids) {
            return [];
        }

        $table = "{$wpdb->prefix}jp_group_complaints";

        $placeholders = implode(', ', array_fill(0, count($group_ids), '%d'));
        $sql = $wpdb->prepare(
            "SELECT id, group_id, reason, status, order_id, created_at, updated_at
             FROM $table
             WHERE user_id = %d AND group_id IN ($placeholders)
             ORDER BY created_at DESC",
            ...array_merge([$user_id], $group_ids)
        );

        $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];

        $summary = [];

        foreach ($rows as $row) {
            $group_id = isset($row['group_id']) ? (int) $row['group_id'] : 0;
            if ($group_id <= 0) {
                continue;
            }

            if (!isset($summary[$group_id])) {
                $summary[$group_id] = [
                    'open'   => 0,
                    'total'  => 0,
                    'latest' => null,
                ];
            }

            $status = isset($row['status']) ? sanitize_key((string) $row['status']) : self::STATUS_OPEN;

            $summary[$group_id]['total']++;

            if (in_array($status, [self::STATUS_OPEN, self::STATUS_UNDER_REVIEW], true)) {
                $summary[$group_id]['open']++;
            }

            if ($summary[$group_id]['latest'] === null) {
                $summary[$group_id]['latest'] = [
                    'id'         => isset($row['id']) ? (int) $row['id'] : 0,
                    'status'     => $status,
                    'reason'     => isset($row['reason']) ? sanitize_key((string) $row['reason']) : 'other',
                    'order_id'   => isset($row['order_id']) ? absint($row['order_id']) : 0,
                    'created_at' => isset($row['created_at']) ? (string) $row['created_at'] : '',
                    'updated_at' => isset($row['updated_at']) ? (string) $row['updated_at'] : '',
                ];
            }
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(int $complaint_id): ?array
    {
        global $wpdb;

        $complaint_id = absint($complaint_id);

        if ($complaint_id <= 0) {
            return null;
        }

        $table = "{$wpdb->prefix}jp_group_complaints";

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $complaint_id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return self::normalize_row($row);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_for_user(int $user_id, string $filter = 'open'): array
    {
        global $wpdb;

        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return [];
        }

        [$status_sql, $statuses] = self::build_status_filter($filter);

        $table   = "{$wpdb->prefix}jp_group_complaints";
        $groups  = "{$wpdb->prefix}jp_groups";
        $query   = "SELECT c.*, g.title AS group_title, g.owner_id
                    FROM $table c
                    LEFT JOIN $groups g ON g.id = c.group_id
                    WHERE c.user_id = %d";

        if ($status_sql !== '') {
            $query .= " AND c.status IN ($status_sql)";
        }

        $query .= ' ORDER BY c.created_at DESC';

        $prepared = $wpdb->prepare($query, ...array_merge([$user_id], $statuses));

        $rows = $wpdb->get_results($prepared, ARRAY_A);

        if (!$rows) {
            return [];
        }

        return array_map([self::class, 'normalize_row'], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function get_for_owner(int $user_id, string $filter = 'open'): array
    {
        global $wpdb;

        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return [];
        }

        [$status_sql, $statuses] = self::build_status_filter($filter);

        $table  = "{$wpdb->prefix}jp_group_complaints";
        $groups = "{$wpdb->prefix}jp_groups";

        $query = "SELECT c.*, g.title AS group_title, g.owner_id
                  FROM $table c
                  INNER JOIN $groups g ON g.id = c.group_id
                  WHERE g.owner_id = %d";

        if ($status_sql !== '') {
            $query .= " AND c.status IN ($status_sql)";
        }

        $query .= ' ORDER BY c.created_at DESC';

        $prepared = $wpdb->prepare($query, ...array_merge([$user_id], $statuses));

        $rows = $wpdb->get_results($prepared, ARRAY_A);

        if (!$rows) {
            return [];
        }

        return array_map([self::class, 'normalize_row'], $rows);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public static function get_counts(int $user_id): array
    {
        global $wpdb;

        $user_id = absint($user_id);

        if ($user_id <= 0) {
            return [
                'participant' => [],
                'owner'       => [],
            ];
        }

        $table  = "{$wpdb->prefix}jp_group_complaints";
        $groups = "{$wpdb->prefix}jp_groups";

        $participant_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) AS total FROM $table WHERE user_id = %d GROUP BY status",
                $user_id
            ),
            ARRAY_A
        );

        $owner_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.status, COUNT(*) AS total
                 FROM $table c
                 INNER JOIN $groups g ON g.id = c.group_id
                 WHERE g.owner_id = %d
                 GROUP BY c.status",
                $user_id
            ),
            ARRAY_A
        );

        return [
            'participant' => self::format_counts($participant_rows),
            'owner'       => self::format_counts($owner_rows),
        ];
    }

    public static function update_status(int $complaint_id, string $status, array $extra = []): bool
    {
        global $wpdb;

        $complaint_id = absint($complaint_id);
        $status       = sanitize_key($status);

        if ($complaint_id <= 0 || $status === '') {
            return false;
        }

        $table = "{$wpdb->prefix}jp_group_complaints";

        $data   = [
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%s', '%s'];

        if (isset($extra['admin_note'])) {
            $data['admin_note'] = sanitize_textarea_field((string) $extra['admin_note']);
            $format[]           = '%s';
        }

        if (isset($extra['resolved_by'])) {
            $data['resolved_by'] = absint($extra['resolved_by']);
            $format[]            = '%d';
        }

        if (isset($extra['resolved_at'])) {
            $data['resolved_at'] = (string) $extra['resolved_at'];
            $format[]            = '%s';
        }

        $updated = $wpdb->update(
            $table,
            $data,
            ['id' => $complaint_id],
            $format,
            ['%d']
        );

        return (bool) $updated;
    }

    public static function get_ticket_number(int $complaint_id): string
    {
        $complaint_id = absint($complaint_id);

        if ($complaint_id <= 0) {
            return '#000000';
        }

        return '#' . (string) (self::TICKET_OFFSET + $complaint_id);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function normalize_row(array $row): array
    {
        $attachments = [];
        if (!empty($row['attachments'])) {
            $decoded = json_decode((string) $row['attachments'], true);
            if (is_array($decoded)) {
                $attachments = array_map('absint', $decoded);
            }
        }

        $status    = sanitize_key((string) ($row['status'] ?? self::STATUS_OPEN));
        $status_ui = self::describe_status($status);

        $complaint = [
            'id'             => isset($row['id']) ? (int) $row['id'] : 0,
            'group_id'       => isset($row['group_id']) ? (int) $row['group_id'] : 0,
            'user_id'        => isset($row['user_id']) ? (int) $row['user_id'] : 0,
            'order_id'       => isset($row['order_id']) ? (int) $row['order_id'] : 0,
            'reason'         => (string) ($row['reason'] ?? 'other'),
            'message'        => (string) ($row['message'] ?? ''),
            'attachments'    => $attachments,
            'status'         => $status,
            'status_label'   => $status_ui['label'],
            'status_tone'    => $status_ui['tone'],
            'status_message' => $status_ui['message'],
            'admin_note'     => (string) ($row['admin_note'] ?? ''),
            'resolved_by'    => isset($row['resolved_by']) ? (int) $row['resolved_by'] : 0,
            'resolved_at'    => (string) ($row['resolved_at'] ?? ''),
            'created_at'     => (string) ($row['created_at'] ?? ''),
            'updated_at'     => (string) ($row['updated_at'] ?? ''),
            'group_title'    => (string) ($row['group_title'] ?? ''),
            'owner_id'       => isset($row['owner_id']) ? (int) $row['owner_id'] : 0,
        ];

        $complaint['ticket'] = self::get_ticket_number($complaint['id']);
        $complaint['reason_label'] = self::get_reason_label($complaint['reason']);

        return $complaint;
    }

    /**
     * @param array<int, array<string, mixed>>|null $rows
     * @return array<string, int>
     */
    private static function format_counts(?array $rows): array
    {
        $counts = [];

        if (!$rows) {
            return $counts;
        }

        foreach ($rows as $row) {
            $status = sanitize_key((string) ($row['status'] ?? ''));
            if ($status === '') {
                continue;
            }

            $counts[$status] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    /**
     * @return array{string, array<int, string>}
     */
    private static function build_status_filter(string $filter): array
    {
        $filter = sanitize_key($filter);

        switch ($filter) {
            case 'closed':
                $statuses = [self::STATUS_RESOLVED, self::STATUS_REJECTED];
                break;
            case 'all':
                $statuses = [];
                break;
            case 'open':
            default:
                $statuses = [self::STATUS_OPEN, self::STATUS_UNDER_REVIEW];
                break;
        }

        if (!$statuses) {
            return ['', []];
        }

        return [implode(',', array_fill(0, count($statuses), '%s')), $statuses];
    }

    public static function has_open_for_period(int $user_id, int $group_id, string $start, string $end): bool
    {
        global $wpdb;

        $user_id = absint($user_id);
        $group_id = absint($group_id);

        if ($user_id <= 0 || $group_id <= 0) {
            return false;
        }

        $table = "{$wpdb->prefix}jp_group_complaints";

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE user_id = %d AND group_id = %d
               AND status IN (%s, %s)
               AND created_at BETWEEN %s AND %s",
            $user_id,
            $group_id,
            self::STATUS_OPEN,
            self::STATUS_UNDER_REVIEW,
            $start !== '' ? $start : '1970-01-01 00:00:00',
            $end !== '' ? $end : current_time('mysql')
        );

        return (int) $wpdb->get_var($query) > 0;
    }
}
