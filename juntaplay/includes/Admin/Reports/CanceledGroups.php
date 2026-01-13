<?php
declare(strict_types=1);

namespace JuntaPlay\Admin\Reports;

use JuntaPlay\Data\CaucaoCycles;
use JuntaPlay\Data\GroupComplaints;
use JuntaPlay\Data\Groups;
use function __;
use function absint;
use function add_action;
use function add_query_arg;
use function add_submenu_page;
use function admin_url;
use function current_user_can;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function esc_url;
use function max;
use function number_format_i18n;
use function sanitize_key;
use function sanitize_text_field;
use function sprintf;
use function strtotime;
use function wp_die;
use function wp_nonce_field;
use function wp_unslash;
use function wp_verify_nonce;
use function wp_date;

defined('ABSPATH') || exit;

class CanceledGroups
{
    private const PER_PAGE = 20;

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'juntaplay',
            __('Relatórios — Grupos Cancelados', 'juntaplay'),
            __('Relatórios', 'juntaplay'),
            'manage_options',
            'juntaplay-reports-canceled-groups',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        $filters = $this->get_filters();

        if ($filters['export']) {
            $this->export_csv($filters);
        }

        $page     = $filters['page'];
        $per_page = self::PER_PAGE;

        $groups_data = $this->query_groups($filters, $page, $per_page);
        $groups       = $groups_data['items'];
        $group_ids    = $groups_data['ids'];
        $total        = $groups_data['total'];
        $pages        = $groups_data['pages'];

        $participants_by_group = $this->query_participants($group_ids, $filters);

        $group_options  = $this->get_group_options();
        $admin_options  = $this->get_admin_options();
        $status_options = $this->get_caucao_status_options();

        $base_url = admin_url('admin.php');
        $page_slug = 'juntaplay-reports-canceled-groups';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Relatórios — Grupos Cancelados', 'juntaplay') . '</h1>';

        echo '<p>' . esc_html__('Relatório somente leitura para auditoria de cancelamentos de grupos e situação dos calções.', 'juntaplay') . '</p>';
        echo '<style>
            .juntaplay-report-group { margin: 16px 0; }
            .juntaplay-report-group summary { cursor: pointer; font-weight: 600; }
            .juntaplay-status { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; }
            .juntaplay-status--success { background: #e7f7ee; color: #126b3e; }
            .juntaplay-status--warning { background: #fff7e0; color: #8a5d00; }
            .juntaplay-status--danger { background: #fde7e7; color: #8f1d1d; }
            .juntaplay-status--muted { background: #f1f1f1; color: #555; }
        </style>';

        echo '<form method="get" class="juntaplay-report-filters">';
        echo '<input type="hidden" name="page" value="' . esc_attr($page_slug) . '" />';

        echo '<div class="juntaplay-report-filters__row">';
        echo '<label>' . esc_html__('Período (cancelamento)', 'juntaplay') . '</label>';
        echo '<input type="date" name="start_date" value="' . esc_attr($filters['start_date']) . '" />';
        echo '<input type="date" name="end_date" value="' . esc_attr($filters['end_date']) . '" />';
        echo '</div>';

        echo '<div class="juntaplay-report-filters__row">';
        echo '<label>' . esc_html__('Status do calção', 'juntaplay') . '</label>';
        echo '<select name="caucao_status">';
        echo '<option value="">' . esc_html__('Todos', 'juntaplay') . '</option>';
        foreach ($status_options as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . ($filters['caucao_status'] === $value ? ' selected' : '') . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="juntaplay-report-filters__row">';
        echo '<label>' . esc_html__('Grupo', 'juntaplay') . '</label>';
        echo '<select name="group_id">';
        echo '<option value="">' . esc_html__('Todos', 'juntaplay') . '</option>';
        foreach ($group_options as $group_id => $group_label) {
            echo '<option value="' . esc_attr((string) $group_id) . '"' . ((string) $filters['group_id'] === (string) $group_id ? ' selected' : '') . '>' . esc_html($group_label) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="juntaplay-report-filters__row">';
        echo '<label>' . esc_html__('Administrador', 'juntaplay') . '</label>';
        echo '<select name="admin_id">';
        echo '<option value="">' . esc_html__('Todos', 'juntaplay') . '</option>';
        foreach ($admin_options as $admin_id => $admin_label) {
            echo '<option value="' . esc_attr((string) $admin_id) . '"' . ((string) $filters['admin_id'] === (string) $admin_id ? ' selected' : '') . '>' . esc_html($admin_label) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '<div class="juntaplay-report-filters__row">';
        echo '<button type="submit" class="button button-primary">' . esc_html__('Filtrar', 'juntaplay') . '</button>';
        wp_nonce_field('juntaplay_report_export', 'juntaplay_report_export_nonce');
        echo '<button type="submit" class="button" name="export" value="1">' . esc_html__('Exportar CSV', 'juntaplay') . '</button>';
        echo '</div>';

        echo '</form>';

        if (!$groups) {
            echo '<p>' . esc_html__('Nenhum grupo cancelado encontrado com os filtros aplicados.', 'juntaplay') . '</p>';
            echo '</div>';
            return;
        }

        echo '<div class="juntaplay-report-table">';
        foreach ($groups as $group) {
            $group_id = (int) $group['id'];
            $participants = $participants_by_group[$group_id] ?? [];
            $summary = sprintf(
                '#%d — %s',
                $group_id,
                $group['title'] !== '' ? $group['title'] : __('Grupo sem nome', 'juntaplay')
            );

            echo '<details class="juntaplay-report-group">';
            echo '<summary>' . esc_html($summary) . '</summary>';

            echo '<table class="widefat striped">';
            echo '<tbody>';
            echo '<tr><th>' . esc_html__('Administrador que cancelou', 'juntaplay') . '</th><td>' . esc_html($group['admin_name']) . '</td></tr>';
            echo '<tr><th>' . esc_html__('Data/hora do cancelamento', 'juntaplay') . '</th><td>' . esc_html($group['canceled_at']) . '</td></tr>';
            echo '<tr><th>' . esc_html__('Status atual do grupo', 'juntaplay') . '</th><td>' . esc_html($group['status']) . '</td></tr>';
            echo '<tr><th>' . esc_html__('Total de participantes no cancelamento', 'juntaplay') . '</th><td>' . esc_html((string) $group['participants_total']) . '</td></tr>';
            echo '</tbody>';
            echo '</table>';

            echo '<h3>' . esc_html__('Participantes e calções', 'juntaplay') . '</h3>';
            echo '<table class="widefat striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Participante', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('ID', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Status da membership', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Saída efetiva', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Reclamação aberta', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Valor do calção', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Status do calção', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Liberação prevista', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Liberação efetiva', 'juntaplay') . '</th>';
            echo '<th>' . esc_html__('Transação de crédito', 'juntaplay') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            if (!$participants) {
                echo '<tr><td colspan="10">' . esc_html__('Nenhum participante encontrado para este grupo.', 'juntaplay') . '</td></tr>';
            } else {
                foreach ($participants as $participant) {
                    $caucao_status = $participant['caucao_status'] !== '' ? $participant['caucao_status'] : '—';
                    $status_class = $this->map_caucao_status_class($participant['caucao_status']);
                    $complaint_label = $participant['has_complaint'] ? __('Sim', 'juntaplay') : __('Não', 'juntaplay');
                    echo '<tr>';
                    echo '<td>' . esc_html($participant['user_name']) . '</td>';
                    echo '<td>' . esc_html((string) $participant['user_id']) . '</td>';
                    echo '<td>' . esc_html($participant['membership_status']) . '</td>';
                    echo '<td>' . esc_html($participant['exit_effective_at']) . '</td>';
                    echo '<td>' . esc_html($complaint_label) . '</td>';
                    echo '<td>' . esc_html($participant['caucao_amount']) . '</td>';
                    echo '<td><span class="' . esc_attr($status_class) . '">' . esc_html($caucao_status) . '</span></td>';
                    echo '<td>' . esc_html($participant['caucao_release_expected']) . '</td>';
                    echo '<td>' . esc_html($participant['caucao_release_actual']) . '</td>';
                    echo '<td>' . esc_html($participant['credit_transaction_id']) . '</td>';
                    echo '</tr>';
                }
            }

            echo '</tbody>';
            echo '</table>';
            echo '</details>';
        }
        echo '</div>';

        $pagination = $this->render_pagination($page, $pages, $filters, $base_url, $page_slug);
        if ($pagination !== '') {
            echo $pagination;
        }

        echo '</div>';
    }

    /**
     * Build the core report filters from the request.
     *
     * @return array<string, mixed>
     */
    private function get_filters(): array
    {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : '';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : '';
        $caucao_status = isset($_GET['caucao_status']) ? sanitize_key(wp_unslash($_GET['caucao_status'])) : '';
        $group_id = isset($_GET['group_id']) ? absint(wp_unslash($_GET['group_id'])) : 0;
        $admin_id = isset($_GET['admin_id']) ? absint(wp_unslash($_GET['admin_id'])) : 0;
        $page = isset($_GET['paged']) ? absint(wp_unslash($_GET['paged'])) : 1;
        $export = isset($_GET['export']) ? absint(wp_unslash($_GET['export'])) === 1 : false;

        return [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'caucao_status' => $caucao_status,
            'group_id' => $group_id,
            'admin_id' => $admin_id,
            'page' => max(1, $page),
            'export' => $export,
        ];
    }

    /**
     * Query canceled groups by admin, with optional filters.
     *
     * Data origins:
     * - jp_groups.reviewed_by + reviewed_at store who canceled and when (status set to canceled_by_admin).
     * - jp_group_members provides the participant count at cancellation time.
     * - Optional caucao filter uses jp_caucao_cycles current status.
     *
     * @return array{items: array<int, array<string, mixed>>, ids: array<int, int>, total: int, pages: int}
     */
    private function query_groups(array $filters, int $page, int $per_page): array
    {
        global $wpdb;

        $groups_table = "{$wpdb->prefix}jp_groups";
        $members_table = "{$wpdb->prefix}jp_group_members";
        $cycles_table = "{$wpdb->prefix}jp_caucao_cycles";
        $users_table = "{$wpdb->users}";

        $where = ['g.status = %s'];
        $params = [Groups::STATUS_CANCELED_BY_ADMIN];

        if ($filters['start_date'] !== '') {
            $where[] = 'g.reviewed_at >= %s';
            $params[] = $filters['start_date'] . ' 00:00:00';
        }

        if ($filters['end_date'] !== '') {
            $where[] = 'g.reviewed_at <= %s';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }

        if ($filters['group_id'] > 0) {
            $where[] = 'g.id = %d';
            $params[] = $filters['group_id'];
        }

        if ($filters['admin_id'] > 0) {
            $where[] = 'g.reviewed_by = %d';
            $params[] = $filters['admin_id'];
        }

        if ($filters['caucao_status'] !== '') {
            $where[] = "EXISTS (
                SELECT 1 FROM $cycles_table cc
                WHERE cc.group_id = g.id AND cc.status = %s
            )";
            $params[] = $filters['caucao_status'];
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        // Group → participant relationship (count at cancel time): jp_group_members joined_at <= jp_groups.reviewed_at.
        $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS
                g.id,
                g.title,
                g.status,
                g.reviewed_at,
                g.reviewed_by,
                COALESCE(u.display_name, u.user_login) AS admin_name,
                (
                    SELECT COUNT(*)
                    FROM $members_table gm
                    WHERE gm.group_id = g.id
                      AND (g.reviewed_at IS NULL OR gm.joined_at <= g.reviewed_at)
                ) AS participants_total
             FROM $groups_table g
             LEFT JOIN $users_table u ON u.ID = g.reviewed_by
             WHERE $where_sql
             ORDER BY g.reviewed_at DESC
             LIMIT %d OFFSET %d",
            array_merge($params, [$per_page, $offset])
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
        $pages = (int) max(1, ceil($total / $per_page));

        $items = [];
        $ids = [];

        foreach ($rows as $row) {
            $group_id = (int) $row['id'];
            $ids[] = $group_id;

            $items[] = [
                'id' => $group_id,
                'title' => (string) ($row['title'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'canceled_at' => $this->format_datetime((string) ($row['reviewed_at'] ?? '')),
                'admin_name' => (string) ($row['admin_name'] ?? ''),
                'participants_total' => (int) ($row['participants_total'] ?? 0),
            ];
        }

        return [
            'items' => $items,
            'ids' => $ids,
            'total' => $total,
            'pages' => $pages,
        ];
    }

    /**
     * Query participants and caucao data for each canceled group.
     *
     * Data origins and relationships:
     * - jp_group_members provides membership status and exit_effective_at.
     * - jp_caucao_cycles provides caucao amount, status, cycle_end, validated_at (latest cycle per user/group).
     * - jp_group_complaints informs complaint existence for user/group.
     * - jp_credit_transactions identifies credited caucao release (context contains caucao_id).
     *
     * @return array<int, array<int, array<string, string|int|bool>>>
     */
    private function query_participants(array $group_ids, array $filters): array
    {
        global $wpdb;

        if (!$group_ids) {
            return [];
        }

        $members_table = "{$wpdb->prefix}jp_group_members";
        $cycles_table = "{$wpdb->prefix}jp_caucao_cycles";
        $complaints_table = "{$wpdb->prefix}jp_group_complaints";
        $credit_table = "{$wpdb->prefix}jp_credit_transactions";
        $users_table = "{$wpdb->users}";

        $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
        $params = $group_ids;

        $status_filter = '';
        if ($filters['caucao_status'] !== '') {
            $status_filter = 'AND cc.status = %s';
            $params[] = $filters['caucao_status'];
        }

        // Latest caucao cycle per participant/group to reflect current status.
        $latest_cycles = "
            SELECT cc1.*
            FROM $cycles_table cc1
            INNER JOIN (
                SELECT user_id, group_id, MAX(cycle_end) AS max_cycle_end
                FROM $cycles_table
                GROUP BY user_id, group_id
            ) latest
            ON latest.user_id = cc1.user_id
            AND latest.group_id = cc1.group_id
            AND latest.max_cycle_end = cc1.cycle_end
        ";

        $query = $wpdb->prepare(
            "SELECT
                gm.group_id,
                gm.user_id,
                gm.status AS membership_status,
                gm.exit_effective_at,
                COALESCE(u.display_name, u.user_login) AS user_name,
                cc.id AS caucao_id,
                cc.amount AS caucao_amount,
                cc.status AS caucao_status,
                cc.cycle_end,
                cc.validated_at,
                EXISTS (
                    SELECT 1
                    FROM $complaints_table gc
                    WHERE gc.group_id = gm.group_id
                      AND gc.user_id = gm.user_id
                      AND gc.status IN (%s, %s)
                    LIMIT 1
                ) AS has_complaint,
                (
                    SELECT MIN(ct.id)
                    FROM $credit_table ct
                    WHERE ct.user_id = gm.user_id
                      AND ct.type = %s
                      AND cc.id IS NOT NULL
                      AND ct.context LIKE CONCAT('%\"caucao_id\":', cc.id, '%')
                ) AS credit_transaction_id
             FROM $members_table gm
             INNER JOIN $users_table u ON u.ID = gm.user_id
             LEFT JOIN ($latest_cycles) cc ON cc.user_id = gm.user_id AND cc.group_id = gm.group_id
             WHERE gm.group_id IN ($placeholders)
             $status_filter
             ORDER BY u.display_name ASC",
            array_merge(
                [
                    GroupComplaints::STATUS_OPEN,
                    GroupComplaints::STATUS_UNDER_REVIEW,
                    'caucao',
                ],
                $params
            )
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $grouped = [];

        foreach ($rows as $row) {
            $group_id = (int) $row['group_id'];
            $grouped[$group_id][] = [
                'user_id' => (int) $row['user_id'],
                'user_name' => (string) $row['user_name'],
                'membership_status' => (string) $row['membership_status'],
                'exit_effective_at' => $this->format_datetime((string) ($row['exit_effective_at'] ?? '')),
                'has_complaint' => (bool) $row['has_complaint'],
                'caucao_amount' => $this->format_currency((float) ($row['caucao_amount'] ?? 0)),
                'caucao_status' => (string) ($row['caucao_status'] ?? ''),
                'caucao_release_expected' => $this->format_datetime((string) ($row['cycle_end'] ?? '')),
                'caucao_release_actual' => $this->format_datetime((string) ($row['validated_at'] ?? '')),
                'credit_transaction_id' => $row['credit_transaction_id'] ? (string) $row['credit_transaction_id'] : '',
            ];
        }

        return $grouped;
    }

    /**
     * Export report as CSV for the filtered dataset.
     */
    private function export_csv(array $filters): void
    {
        $nonce = isset($_GET['juntaplay_report_export_nonce'])
            ? sanitize_text_field(wp_unslash($_GET['juntaplay_report_export_nonce']))
            : '';

        if (!wp_verify_nonce($nonce, 'juntaplay_report_export')) {
            wp_die(__('Não foi possível validar a exportação.', 'juntaplay'));
        }

        $groups_data = $this->query_groups($filters, 1, 5000);
        $participants_by_group = $this->query_participants($groups_data['ids'], $filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=juntaplay-relatorio-grupos-cancelados.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'group_id',
            'group_title',
            'admin_name',
            'canceled_at',
            'group_status',
            'participants_total',
            'user_id',
            'user_name',
            'membership_status',
            'exit_effective_at',
            'has_complaint',
            'caucao_amount',
            'caucao_status',
            'caucao_release_expected',
            'caucao_release_actual',
            'credit_transaction_id',
        ]);

        foreach ($groups_data['items'] as $group) {
            $group_id = (int) $group['id'];
            $participants = $participants_by_group[$group_id] ?? [];

            if (!$participants) {
                fputcsv($output, [
                    $group_id,
                    $group['title'],
                    $group['admin_name'],
                    $group['canceled_at'],
                    $group['status'],
                    $group['participants_total'],
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]);
                continue;
            }

            foreach ($participants as $participant) {
                fputcsv($output, [
                    $group_id,
                    $group['title'],
                    $group['admin_name'],
                    $group['canceled_at'],
                    $group['status'],
                    $group['participants_total'],
                    $participant['user_id'],
                    $participant['user_name'],
                    $participant['membership_status'],
                    $participant['exit_effective_at'],
                    $participant['has_complaint'] ? 'sim' : 'nao',
                    $participant['caucao_amount'],
                    $participant['caucao_status'],
                    $participant['caucao_release_expected'],
                    $participant['caucao_release_actual'],
                    $participant['credit_transaction_id'],
                ]);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Provide group options for filter dropdown (canceled by admin).
     *
     * @return array<int, string>
     */
    private function get_group_options(): array
    {
        global $wpdb;

        $groups_table = "{$wpdb->prefix}jp_groups";
        $query = $wpdb->prepare(
            "SELECT id, title FROM $groups_table WHERE status = %s ORDER BY reviewed_at DESC",
            Groups::STATUS_CANCELED_BY_ADMIN
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $options = [];

        foreach ($rows as $row) {
            $group_id = (int) $row['id'];
            $title = (string) ($row['title'] ?? '');
            $options[$group_id] = $title !== '' ? sprintf('#%d — %s', $group_id, $title) : sprintf('#%d', $group_id);
        }

        return $options;
    }

    /**
     * Provide admin options for filter dropdown.
     *
     * @return array<int, string>
     */
    private function get_admin_options(): array
    {
        global $wpdb;

        $groups_table = "{$wpdb->prefix}jp_groups";
        $users_table = "{$wpdb->users}";

        $query = $wpdb->prepare(
            "SELECT DISTINCT g.reviewed_by AS admin_id, COALESCE(u.display_name, u.user_login) AS admin_name
             FROM $groups_table g
             LEFT JOIN $users_table u ON u.ID = g.reviewed_by
             WHERE g.status = %s AND g.reviewed_by IS NOT NULL
             ORDER BY admin_name ASC",
            Groups::STATUS_CANCELED_BY_ADMIN
        );

        $rows = $wpdb->get_results($query, ARRAY_A) ?: [];
        $options = [];

        foreach ($rows as $row) {
            $admin_id = (int) ($row['admin_id'] ?? 0);
            if ($admin_id <= 0) {
                continue;
            }
            $options[$admin_id] = (string) ($row['admin_name'] ?? '');
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function get_caucao_status_options(): array
    {
        return [
            CaucaoCycles::STATUS_RETIDO => __('retido', 'juntaplay'),
            CaucaoCycles::STATUS_LIBERACAO_PROGRAMADA_CANCELAMENTO_GRUPO => __('liberacao_programada_por_cancelamento_de_grupo', 'juntaplay'),
            CaucaoCycles::STATUS_LIBERADO => __('liberado', 'juntaplay'),
            CaucaoCycles::STATUS_RETIDO_DEFINITIVO => __('retido_definitivamente', 'juntaplay'),
        ];
    }

    private function format_datetime(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return wp_date('d/m/Y H:i', strtotime($value));
    }

    private function format_currency(float $value): string
    {
        if ($value <= 0) {
            return '';
        }

        return number_format_i18n($value, 2);
    }

    private function map_caucao_status_class(string $status): string
    {
        return match ($status) {
            CaucaoCycles::STATUS_LIBERADO => 'juntaplay-status juntaplay-status--success',
            CaucaoCycles::STATUS_LIBERACAO_PROGRAMADA_CANCELAMENTO_GRUPO => 'juntaplay-status juntaplay-status--warning',
            CaucaoCycles::STATUS_RETIDO_DEFINITIVO => 'juntaplay-status juntaplay-status--danger',
            default => 'juntaplay-status juntaplay-status--muted',
        };
    }

    /**
     * Render pagination links.
     */
    private function render_pagination(int $page, int $pages, array $filters, string $base_url, string $page_slug): string
    {
        if ($pages <= 1) {
            return '';
        }

        $links = '<div class="tablenav"><div class="tablenav-pages">';

        for ($i = 1; $i <= $pages; $i++) {
            $args = [
                'page' => $page_slug,
                'paged' => $i,
            ];

            foreach (['start_date', 'end_date', 'caucao_status', 'group_id', 'admin_id'] as $key) {
                if (!empty($filters[$key])) {
                    $args[$key] = $filters[$key];
                }
            }

            $url = add_query_arg($args, $base_url);
            $class = $i === $page ? 'class="page-numbers current"' : 'class="page-numbers"';
            $links .= '<a ' . $class . ' href="' . esc_url($url) . '">' . esc_html((string) $i) . '</a> ';
        }

        $links .= '</div></div>';

        return $links;
    }
}
