<?php
declare(strict_types=1);

namespace JuntaPlay\Admin\Reports;

use JuntaPlay\Data\Groups;
use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\CaucaoCycles;
use JuntaPlay\Data\CreditTransactions;

use function __;
use function esc_html__;
use function esc_html;
use function get_user_by;
use function is_super_admin;
use function wp_die;
use function date_i18n;
use function get_option;
use function number_format_i18n;

defined('ABSPATH') || exit;

class CancelledGroups
{
    public function render_page(): void
    {
        if (!is_super_admin()) {
            wp_die(esc_html__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        $status_badges = [
            Groups::STATUS_CANCELED_BY_ADMIN => 'jp-badge jp-badge--dark',
        ];
        $membership_badges = [
            GroupMembers::STATUS_ACTIVE_UNTIL_END_OF_CYCLE => 'jp-badge jp-badge--warning',
            GroupMembers::STATUS_EXITED_BY_GROUP_CANCELLATION => 'jp-badge jp-badge--muted',
        ];
        $caucao_badges = [
            'retido' => 'jp-badge jp-badge--warning',
            'liberacao_programada_por_cancelamento_de_grupo' => 'jp-badge jp-badge--info',
            'liberado' => 'jp-badge jp-badge--positive',
            'retido_definitivo' => 'jp-badge jp-badge--danger',
        ];
        $credit_badges = [
            'creditado' => 'jp-badge jp-badge--positive',
            'nao_creditado' => 'jp-badge jp-badge--muted',
        ];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Relatório — Grupos Cancelados', 'juntaplay') . '</h1>';
        echo '<p>' . esc_html__('Este relatório exibirá os grupos cancelados por administradores e a situação dos calções associados.', 'juntaplay') . '</p>';
        echo '<p>' . esc_html__('Os dados serão carregados em etapas futuras.', 'juntaplay') . '</p>';

        $groups = Groups::all([
            'status' => Groups::STATUS_CANCELED_BY_ADMIN,
            'limit'  => 200,
        ]);

        $rows = array_values(array_filter($groups, static fn($group) => is_array($group)));

        usort(
            $rows,
            static function (array $left, array $right): int {
                $left_date = (string) ($left['reviewed_at'] ?? $left['updated_at'] ?? '');
                $right_date = (string) ($right['reviewed_at'] ?? $right['updated_at'] ?? '');
                $left_ts = $left_date !== '' ? strtotime($left_date) : 0;
                $right_ts = $right_date !== '' ? strtotime($right_date) : 0;

                return $right_ts <=> $left_ts;
            }
        );

        if (!$rows) {
            echo '<p>' . esc_html__('Nenhum grupo cancelado encontrado.', 'juntaplay') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('ID do grupo', 'juntaplay') . '</th>';
        echo '<th>' . esc_html__('Nome do grupo', 'juntaplay') . '</th>';
        echo '<th>' . esc_html__('Status', 'juntaplay') . '</th>';
        echo '<th>' . esc_html__('Data de cancelamento', 'juntaplay') . '</th>';
        echo '<th>' . esc_html__('Administrador', 'juntaplay') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($rows as $group) {
            $group_id = isset($group['id']) ? (int) $group['id'] : 0;
            $group_name = isset($group['title']) ? (string) $group['title'] : '';
            $status = isset($group['status']) ? (string) $group['status'] : '';
            $cancelled_at_raw = (string) ($group['reviewed_at'] ?? $group['updated_at'] ?? '');
            $cancelled_at = $cancelled_at_raw !== ''
                ? date_i18n('d/m/Y', strtotime($cancelled_at_raw))
                : '—';
            $admin_id = isset($group['reviewed_by']) ? (int) $group['reviewed_by'] : 0;
            $admin_user = $admin_id > 0 ? get_user_by('id', $admin_id) : null;
            $admin_label = $admin_user
                ? sprintf('%s (ID: %d)', (string) $admin_user->display_name, $admin_id)
                : __('Administrador não encontrado', 'juntaplay');
            $display_name = $group_name !== '' ? $group_name : __('(Sem título)', 'juntaplay');
            $group_status_class = $status_badges[$status] ?? 'jp-badge jp-badge--muted';
            $group_status_label = $status !== '' ? $status : Groups::STATUS_CANCELED_BY_ADMIN;
            $cycles_by_user = CaucaoCycles::get_latest_for_group($group_id);

            echo '<tr>';
            echo '<td>' . esc_html((string) $group_id) . '</td>';
            echo '<td>' . esc_html($display_name) . '</td>';
            echo '<td><span class="' . esc_html($group_status_class) . '">' . esc_html($group_status_label) . '</span></td>';
            echo '<td>' . esc_html($cancelled_at) . '</td>';
            echo '<td>' . esc_html($admin_label) . '</td>';
            echo '</tr>';

            $impacted_members = [];
            foreach ([GroupMembers::STATUS_ACTIVE_UNTIL_END_OF_CYCLE, GroupMembers::STATUS_EXITED_BY_GROUP_CANCELLATION] as $member_status) {
                $members = GroupMembers::get_details($group_id, 200, $member_status);
                foreach ($members as $member) {
                    if (!is_array($member)) {
                        continue;
                    }

                    $member_id = isset($member['user_id']) ? (int) $member['user_id'] : 0;
                    if ($member_id <= 0) {
                        continue;
                    }

                    $member_user = get_user_by('id', $member_id);
                    $member_name = $member_user ? (string) $member_user->display_name : __('Usuário não encontrado', 'juntaplay');
                    $membership = GroupMembers::get_membership($group_id, $member_id);
                    $exit_effective_raw = is_array($membership) ? (string) ($membership['exit_effective_at'] ?? '') : '';
                    $exit_effective_at = $exit_effective_raw !== ''
                        ? date_i18n('d/m/Y', strtotime($exit_effective_raw))
                        : '—';
                    $impacted_members[] = [
                        'id' => $member_id,
                        'name' => $member_name,
                        'status' => isset($member['status']) ? (string) $member['status'] : $member_status,
                        'exit_effective_at' => $exit_effective_at,
                    ];
                }
            }

            $summary_total = count($impacted_members);
            $summary_released = 0;
            $summary_pending = 0;
            $summary_blocked = 0;
            foreach ($impacted_members as $member) {
                $cycle = isset($cycles_by_user[$member['id']]) && is_array($cycles_by_user[$member['id']])
                    ? $cycles_by_user[$member['id']]
                    : null;
                $cycle_status = is_array($cycle) ? (string) ($cycle['status'] ?? '') : '';

                if ($cycle_status === 'liberado') {
                    $summary_released++;
                } elseif ($cycle_status === 'retido_definitivo') {
                    $summary_blocked++;
                } elseif ($cycle_status !== '') {
                    $summary_pending++;
                }
            }

            echo '<tr>';
            echo '<td colspan="5">';
            echo '<details>';
            echo '<summary>' . esc_html__('Participantes impactados', 'juntaplay') . '</summary>';
            echo '<div class="jp-report-summary" style="margin:10px 0;">';
            echo '<span class="jp-badge jp-badge--muted">' . esc_html(sprintf(__('Total impactados: %d', 'juntaplay'), $summary_total)) . '</span> ';
            echo '<span class="jp-badge jp-badge--positive">' . esc_html(sprintf(__('Calções liberados: %d', 'juntaplay'), $summary_released)) . '</span> ';
            echo '<span class="jp-badge jp-badge--warning">' . esc_html(sprintf(__('Calções pendentes: %d', 'juntaplay'), $summary_pending)) . '</span> ';
            echo '<span class="jp-badge jp-badge--danger">' . esc_html(sprintf(__('Calções bloqueados: %d', 'juntaplay'), $summary_blocked)) . '</span>';
            echo '</div>';

            if (!$impacted_members) {
                echo '<p>' . esc_html__('Nenhum participante impactado.', 'juntaplay') . '</p>';
            } else {
                echo '<table class="widefat striped" style="margin-top:10px;">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>' . esc_html__('Participante', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Status', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Data efetiva de saída', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Valor do calção', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Status do calção', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Liberação prevista', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Liberação efetiva', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Crédito em saldo', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Data do crédito', 'juntaplay') . '</th>';
                echo '<th>' . esc_html__('Valor creditado', 'juntaplay') . '</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                foreach ($impacted_members as $member) {
                    $member_label = sprintf(
                        '%s (ID: %d)',
                        (string) $member['name'],
                        (int) $member['id']
                    );
                    $member_status = (string) $member['status'];
                    $member_status_class = $membership_badges[$member_status] ?? 'jp-badge jp-badge--muted';
                    $cycle = isset($cycles_by_user[$member['id']]) && is_array($cycles_by_user[$member['id']])
                        ? $cycles_by_user[$member['id']]
                        : null;
                    $cycle_amount = is_array($cycle) ? (float) ($cycle['amount'] ?? 0.0) : 0.0;
                    $cycle_status = is_array($cycle) ? (string) ($cycle['status'] ?? '') : '';
                    $cycle_end_raw = is_array($cycle) ? (string) ($cycle['cycle_end'] ?? '') : '';
                    $cycle_validated_raw = is_array($cycle) ? (string) ($cycle['validated_at'] ?? '') : '';
                    $cycle_end_display = $cycle_end_raw !== ''
                        ? date_i18n('d/m/Y', strtotime($cycle_end_raw))
                        : '—';
                    $cycle_validated_display = $cycle_validated_raw !== ''
                        ? date_i18n('d/m/Y', strtotime($cycle_validated_raw))
                        : '—';
                    $cycle_amount_display = $cycle_amount > 0 ? number_format_i18n($cycle_amount, 2) : '—';
                    $cycle_status_display = $cycle_status !== '' ? $cycle_status : __('Ciclo de calção não encontrado', 'juntaplay');
                    $cycle_status_class = $cycle_status !== '' && isset($caucao_badges[$cycle_status])
                        ? $caucao_badges[$cycle_status]
                        : 'jp-badge jp-badge--muted';
                    $credit_label = __('Não creditado', 'juntaplay');
                    $credit_value_display = '—';
                    $credit_date_display = '—';
                    $credit_badge_class = $credit_badges['nao_creditado'];
                    $credit_transactions = CreditTransactions::get_recent((int) $member['id'], 50);
                    $cycle_id = is_array($cycle) ? (int) ($cycle['id'] ?? 0) : 0;
                    foreach ($credit_transactions as $transaction) {
                        if (!is_array($transaction)) {
                            continue;
                        }

                        if (($transaction['type'] ?? '') !== CreditTransactions::TYPE_CAUCAO) {
                            continue;
                        }

                        $context = isset($transaction['context']) && is_array($transaction['context'])
                            ? $transaction['context']
                            : [];
                        $transaction_group_id = isset($context['group_id']) ? (int) $context['group_id'] : 0;
                        $transaction_cycle_id = isset($context['cycle_id']) ? (int) $context['cycle_id'] : 0;

                        if ($transaction_group_id > 0 && $transaction_group_id !== $group_id) {
                            continue;
                        }

                        if ($transaction_cycle_id > 0 && $cycle_id > 0 && $transaction_cycle_id !== $cycle_id) {
                            continue;
                        }

                        $credit_label = __('Creditado', 'juntaplay');
                        $credit_badge_class = $credit_badges['creditado'];
                        $credit_amount = isset($transaction['amount']) ? (float) $transaction['amount'] : 0.0;
                        $credit_value_display = $credit_amount > 0 ? number_format_i18n($credit_amount, 2) : '—';
                        $credit_created = isset($transaction['created_at']) ? (string) $transaction['created_at'] : '';
                        $credit_date_display = $credit_created !== ''
                            ? date_i18n('d/m/Y', strtotime($credit_created))
                            : '—';
                        break;
                    }
                    echo '<tr>';
                    echo '<td>' . esc_html($member_label) . '</td>';
                    echo '<td><span class="' . esc_html($member_status_class) . '">' . esc_html($member_status) . '</span></td>';
                    echo '<td>' . esc_html((string) $member['exit_effective_at']) . '</td>';
                    echo '<td>' . esc_html($cycle_amount_display) . '</td>';
                    echo '<td><span class="' . esc_html($cycle_status_class) . '">' . esc_html($cycle_status_display) . '</span></td>';
                    echo '<td>' . esc_html($cycle_end_display) . '</td>';
                    echo '<td>' . esc_html($cycle_validated_display) . '</td>';
                    echo '<td><span class="' . esc_html($credit_badge_class) . '">' . esc_html($credit_label) . '</span></td>';
                    echo '<td>' . esc_html($credit_date_display) . '</td>';
                    echo '<td>' . esc_html($credit_value_display) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
            }

            echo '</details>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}
