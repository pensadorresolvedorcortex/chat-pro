<?php

declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function __;
use function absint;
use function array_filter;
use function array_map;
use function count;
use function current_time;
use function implode;
use function in_array;
use function is_array;
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
}
