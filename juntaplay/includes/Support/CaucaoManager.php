<?php
declare(strict_types=1);

namespace JuntaPlay\Support;

use JuntaPlay\Data\CaucaoCycles;
use JuntaPlay\Data\CreditTransactions;
use JuntaPlay\Data\GroupComplaints;
use JuntaPlay\Data\GroupMembers;

use function apply_filters;
use function current_time;
use function strtotime;
use function sprintf;

defined('ABSPATH') || exit;

class CaucaoManager
{
    public static function retain(int $user_id, int $group_id, int $order_id, float $amount, string $cycle_start = ''): int
    {
        return CaucaoCycles::create_retained([
            'user_id'  => $user_id,
            'group_id' => $group_id,
            'order_id' => $order_id,
            'amount'   => $amount,
            'cycle_start' => $cycle_start,
        ]);
    }

    public static function process_due_cycles(): void
    {
        $cycles = CaucaoCycles::due_for_validation(50);

        foreach ($cycles as $cycle) {
            $decision = apply_filters('juntaplay/caucao/validate_cycle', self::evaluate_cycle($cycle), $cycle);

            $approve = is_array($decision) ? (bool) ($decision['approve'] ?? false) : (bool) $decision;
            $status  = is_array($decision) && !empty($decision['status']) ? (string) $decision['status'] : CaucaoCycles::STATUS_LIBERADO;
            $note    = is_array($decision) && !empty($decision['note']) ? (string) $decision['note'] : '';

            if ($approve) {
                $operation = Wallet::credit(
                    (int) $cycle['user_id'],
                    (float) $cycle['amount'],
                    CreditTransactions::TYPE_CAUCAO,
                    [
                        'role'       => 'participant',
                        'group_id'   => $cycle['group_id'] ?? 0,
                        'order_id'   => $cycle['order_id'] ?? 0,
                        'reference'  => sprintf('JPS-%d', $cycle['order_id'] ?? 0),
                        'caucao_id'  => $cycle['id'] ?? 0,
                        'cycle_end'  => $cycle['cycle_end'] ?? '',
                        'cycle_start'=> $cycle['cycle_start'] ?? '',
                        'source'     => 'caucao_release',
                    ]
                );

                if ($operation) {
                    CaucaoCycles::mark_status((int) $cycle['id'], CaucaoCycles::STATUS_LIBERADO, $note);
                } else {
                    CaucaoCycles::mark_status((int) $cycle['id'], CaucaoCycles::STATUS_RETIDO_DEFINITIVO, 'Falha ao creditar caução');
                }
            } else {
                if ($status === CaucaoCycles::STATUS_RETIDO) {
                    continue;
                }

                $final_status = in_array($status, [CaucaoCycles::STATUS_RETIDO_DEFINITIVO, CaucaoCycles::STATUS_USADO_PARA_PREJUIZO], true)
                    ? $status
                    : CaucaoCycles::STATUS_RETIDO_DEFINITIVO;

                CaucaoCycles::mark_status((int) $cycle['id'], $final_status, $note !== '' ? $note : current_time('mysql'));
            }
        }
    }

    /**
     * @param array<string, mixed> $cycle
     * @return array<string, mixed>
     */
    private static function evaluate_cycle(array $cycle): array
    {
        $user_id   = (int) ($cycle['user_id'] ?? 0);
        $group_id  = (int) ($cycle['group_id'] ?? 0);
        $cycle_end = isset($cycle['cycle_end']) ? (string) $cycle['cycle_end'] : '';
        $cycle_end_ts = $cycle_end !== '' ? strtotime($cycle_end) : false;

        $membership = GroupMembers::get_membership($group_id, $user_id);

        if (!$membership) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO_DEFINITIVO,
                'note'    => 'Participante não possui vínculo com o grupo.',
            ];
        }

        $exit_effective_at = isset($membership['exit_effective_at']) ? (string) $membership['exit_effective_at'] : '';
        $exit_effective_ts = $exit_effective_at !== '' ? strtotime($exit_effective_at) : false;

        $has_open_complaint = GroupComplaints::has_open_for_period($user_id, $group_id, (string) ($cycle['cycle_start'] ?? ''), $cycle_end);

        if ($has_open_complaint) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO_DEFINITIVO,
                'note'    => 'Reclamação ou disputa financeira aberta no ciclo.',
            ];
        }

        if (
            $membership['status'] === GroupMembers::STATUS_EXIT_SCHEDULED
            && $exit_effective_ts
            && $exit_effective_ts > current_time('timestamp')
        ) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO,
                'note'    => 'Saída agendada ainda dentro do ciclo mínimo.',
            ];
        }

        if (
            $exit_effective_ts
            && $cycle_end_ts
            && $exit_effective_ts > $cycle_end_ts
        ) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO,
                'note'    => 'Caução aguardando a saída efetiva do grupo.',
            ];
        }

        if ($membership['status'] === GroupMembers::STATUS_ACTIVE && !$exit_effective_ts) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO,
                'note'    => 'Participação ativa, caução permanece retida até a saída.',
            ];
        }

        if ($membership['status'] === GroupMembers::STATUS_EXITED && $exit_effective_ts && $exit_effective_ts > current_time('timestamp')) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO,
                'note'    => 'Saída ainda não efetivada para liberação do caução.',
            ];
        }

        if (!in_array($membership['status'], [GroupMembers::STATUS_ACTIVE, GroupMembers::STATUS_EXIT_SCHEDULED, GroupMembers::STATUS_EXITED], true)) {
            return [
                'approve' => false,
                'status'  => CaucaoCycles::STATUS_RETIDO_DEFINITIVO,
                'note'    => 'Status do membro não permite liberação automática.',
            ];
        }

        return [
            'approve' => true,
            'status'  => CaucaoCycles::STATUS_LIBERADO,
            'note'    => 'Ciclo validado sem pendências.',
        ];
    }
}
