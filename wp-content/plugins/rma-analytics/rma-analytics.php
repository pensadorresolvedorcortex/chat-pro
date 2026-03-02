<?php
/**
 * Plugin Name: RMA Analytics
 * Description: Relatórios administrativos e exportação CSV para entidades RMA.
 * Version: 0.4.3
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Analytics {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('rma/v1', '/analytics/summary', [
            'methods' => 'GET',
            'callback' => [$this, 'summary'],
            'permission_callback' => [$this, 'can_view'],
        ]);

        register_rest_route('rma/v1', '/analytics/export', [
            'methods' => 'GET',
            'callback' => [$this, 'export_csv'],
            'permission_callback' => [$this, 'can_view'],
        ]);
    }

    public function can_view(): bool {
        return current_user_can('manage_options');
    }

    public function summary(): WP_REST_Response {
        $all = new WP_Query([
            'post_type' => 'rma_entidade',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $data = [
            'total' => count($all->posts),
            'ativos' => 0,
            'inativos' => 0,
            'adimplentes' => 0,
            'inadimplentes' => 0,
            'por_uf' => [],
            'faturamento_total' => 0.0,
            'faturamento_pago' => 0.0,
            'tickets_pagos' => 0,
            'ticket_medio_pago' => 0.0,
            'inadimplencia_percentual' => 0.0,
            'adimplencia_percentual' => 0.0,
        ];

        foreach ($all->posts as $id) {
            $governance = get_post_meta($id, 'governance_status', true);
            $finance = get_post_meta($id, 'finance_status', true);
            $uf = $this->normalized_uf((string) get_post_meta($id, 'uf', true));

            $data['por_uf'][$uf] = ($data['por_uf'][$uf] ?? 0) + 1;
            if ($governance === 'aprovado') {
                $data['ativos']++;
            } else {
                $data['inativos']++;
            }

            if ($finance === 'adimplente') {
                $data['adimplentes']++;
            } else {
                $data['inadimplentes']++;
            }

            $history = get_post_meta($id, 'finance_history', true);
            $history = is_array($history) ? $history : [];
            foreach ($history as $event) {
                $value = (float) ($event['total'] ?? 0);
                $data['faturamento_total'] += $value;
                if ((string) ($event['finance_status'] ?? '') === 'adimplente') {
                    $data['faturamento_pago'] += $value;
                    $data['tickets_pagos']++;
                }
            }
        }

        if ($data['tickets_pagos'] > 0) {
            $data['ticket_medio_pago'] = round($data['faturamento_pago'] / $data['tickets_pagos'], 2);
        }

        if ($data['total'] > 0) {
            $data['inadimplencia_percentual'] = round(($data['inadimplentes'] / $data['total']) * 100, 2);
            $data['adimplencia_percentual'] = round(($data['adimplentes'] / $data['total']) * 100, 2);
        }

        ksort($data['por_uf']);

        wp_reset_postdata();

        return new WP_REST_Response($data);
    }

    public function export_csv(): WP_REST_Response {
        $query = new WP_Query([
            'post_type' => 'rma_entidade',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);

        $lines = ['id,nome,uf,governance_status,finance_status'];
        foreach ($query->posts as $id) {
            $id = (int) $id;
            $line = [
                $id,
                $this->csv_quoted($this->csv_safe(get_the_title($id))),
                $this->csv_quoted($this->csv_safe($this->normalized_uf((string) get_post_meta($id, 'uf', true)))),
                $this->csv_quoted($this->csv_safe((string) get_post_meta($id, 'governance_status', true))),
                $this->csv_quoted($this->csv_safe((string) get_post_meta($id, 'finance_status', true))),
            ];
            $lines[] = implode(',', $line);
        }

        wp_reset_postdata();

        return new WP_REST_Response([
            'filename' => 'rma-entities-' . gmdate('Ymd-His') . '.csv',
            'csv' => implode("\n", $lines),
        ]);
    }


    private function normalized_uf(string $value): string {
        $uf = strtoupper(trim($value));

        if (! preg_match('/^[A-Z]{2}$/', $uf)) {
            return 'N/D';
        }

        return $uf;
    }


    private function csv_quoted(string $value): string {
        return '"' . str_replace('"', '""', $value) . '"';
    }

    private function csv_safe(string $value): string {
        $trimmed = trim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'" . $trimmed;
        }

        return $trimmed;
    }
}

new RMA_Analytics();
