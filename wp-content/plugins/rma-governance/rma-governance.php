<?php
/**
 * Plugin Name: RMA Governance
 * Description: Workflow de 3 aceites para entidades RMA com logs de auditoria.
 * Version: 0.4.3
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Governance {
    private const CPT = 'rma_entidade';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/approve', [
            'methods' => 'POST',
            'callback' => [$this, 'approve_entity'],
            'permission_callback' => [$this, 'can_approve'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/reject', [
            'methods' => 'POST',
            'callback' => [$this, 'reject_entity'],
            'permission_callback' => [$this, 'can_approve'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/resubmit', [
            'methods' => 'POST',
            'callback' => [$this, 'resubmit_entity'],
            'permission_callback' => [$this, 'can_resubmit'],
        ]);
    }

    public function can_approve(): bool {
        return current_user_can('edit_others_posts');
    }


    public function can_resubmit(WP_REST_Request $request): bool {
        if (! is_user_logged_in()) {
            return false;
        }

        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return false;
        }

        if (current_user_can('edit_others_posts')) {
            return true;
        }

        return (int) get_post_field('post_author', $entity_id) === get_current_user_id();
    }

    public function approve_entity(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $status = $this->normalized_governance_status($entity_id);
        if ($status === 'aprovado') {
            return new WP_REST_Response(['message' => 'Entidade já aprovada.'], 409);
        }

        if ($status === 'recusado') {
            return new WP_REST_Response([
                'message' => 'Entidade recusada deve ser reenviada antes de novos aceites.',
                'current_status' => $status,
            ], 409);
        }

        if (! in_array($status, ['pendente', 'em_analise'], true)) {
            return new WP_REST_Response([
                'message' => 'Status de governança inválido para aceite.',
                'current_status' => $status,
            ], 409);
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return new WP_REST_Response(['message' => 'Usuário não autenticado.'], 401);
        }

        $author_id = (int) get_post_field('post_author', $entity_id);
        if ($author_id > 0 && $author_id === $user_id) {
            return new WP_REST_Response([
                'message' => 'Auto-aceite não é permitido para a própria entidade.',
                'current_status' => $status,
            ], 409);
        }

        $approvals = get_post_meta($entity_id, 'governance_approvals', true);
        $approvals = is_array($approvals) ? $approvals : [];

        foreach ($approvals as $entry) {
            if ((int) ($entry['user_id'] ?? 0) === $user_id) {
                return new WP_REST_Response(['message' => 'Usuário já registrou aceite nesta entidade.'], 409);
            }
        }

        if (count($approvals) >= 3) {
            return new WP_REST_Response(['message' => 'Limite de 3 aceites já atingido.'], 409);
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $comment = $this->limit_text(sanitize_textarea_field((string) $request->get_param('comment')), 1000);

        $approvals[] = [
            'user_id' => $user_id,
            'datetime' => current_time('mysql', true),
            'ip' => $ip,
            'comment' => $comment,
        ];

        $new_status = count($approvals) >= 3 ? 'aprovado' : 'em_analise';
        update_post_meta($entity_id, 'governance_approvals', $approvals);
        update_post_meta($entity_id, 'governance_status', $new_status);
        delete_post_meta($entity_id, 'governance_rejection_reason');

        $this->append_audit_log($entity_id, 'approve', [
            'user_id' => $user_id,
            'ip' => $ip,
            'comment' => $comment,
            'approvals_count' => count($approvals),
        ]);

        if ($new_status === 'aprovado') {
            wp_update_post([
                'ID' => $entity_id,
                'post_status' => 'publish',
            ]);
            do_action('rma/entity_approved', $entity_id, $approvals);
        } else {
            wp_update_post([
                'ID' => $entity_id,
                'post_status' => 'draft',
            ]);
        }

        return new WP_REST_Response([
            'entity_id' => $entity_id,
            'approvals_count' => count($approvals),
            'governance_status' => $new_status,
        ]);
    }

    public function reject_entity(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $status = $this->normalized_governance_status($entity_id);
        if ($status === 'aprovado') {
            return new WP_REST_Response(['message' => 'Entidade aprovada não pode ser recusada diretamente.'], 409);
        }

        if ($status === 'recusado') {
            return new WP_REST_Response([
                'message' => 'Entidade já está recusada. Use o reenvio para reiniciar o ciclo.',
                'current_status' => $status,
            ], 409);
        }

        if (! in_array($status, ['pendente', 'em_analise'], true)) {
            return new WP_REST_Response([
                'message' => 'Status de governança inválido para recusa.',
                'current_status' => $status,
            ], 409);
        }

        $reason = $this->limit_text(sanitize_textarea_field((string) $request->get_param('reason')), 1000);
        if ($reason === '') {
            return new WP_REST_Response(['message' => 'Motivo da recusa é obrigatório.'], 422);
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return new WP_REST_Response(['message' => 'Usuário não autenticado.'], 401);
        }

        update_post_meta($entity_id, 'governance_status', 'recusado');
        update_post_meta($entity_id, 'governance_rejection_reason', $reason);
        update_post_meta($entity_id, 'governance_approvals', []);

        wp_update_post([
            'ID' => $entity_id,
            'post_status' => 'draft',
        ]);

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        $this->append_audit_log($entity_id, 'reject', [
            'user_id' => $user_id,
            'ip' => $ip,
            'reason' => $reason,
        ]);

        do_action('rma/entity_rejected', $entity_id, $reason);

        return new WP_REST_Response([
            'entity_id' => $entity_id,
            'governance_status' => 'recusado',
            'reason' => $reason,
        ]);
    }


    public function resubmit_entity(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $current_status = $this->normalized_governance_status($entity_id);
        if ($current_status !== 'recusado') {
            return new WP_REST_Response([
                'message' => 'Somente entidades recusadas podem ser reenviadas.',
                'current_status' => $current_status,
            ], 409);
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return new WP_REST_Response(['message' => 'Usuário não autenticado.'], 401);
        }

        update_post_meta($entity_id, 'governance_status', 'pendente');
        update_post_meta($entity_id, 'governance_approvals', []);
        delete_post_meta($entity_id, 'governance_rejection_reason');

        wp_update_post([
            'ID' => $entity_id,
            'post_status' => 'draft',
        ]);

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        $this->append_audit_log($entity_id, 'resubmit', [
            'user_id' => $user_id,
            'ip' => $ip,
        ]);

        do_action('rma/entity_resubmitted', $entity_id);

        return new WP_REST_Response([
            'entity_id' => $entity_id,
            'governance_status' => 'pendente',
            'message' => 'Entidade reenviada para análise.',
        ]);
    }


    private function normalized_governance_status(int $entity_id): string {
        $status = (string) get_post_meta($entity_id, 'governance_status', true);
        $status = trim($status);

        if ($status === '') {
            return 'pendente';
        }

        return $status;
    }


    private function limit_text(string $value, int $max): string {
        if ($max <= 0) {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }

    private function append_audit_log(int $entity_id, string $action, array $data): void {
        $logs = get_post_meta($entity_id, 'governance_audit_logs', true);
        $logs = is_array($logs) ? $logs : [];

        $logs[] = [
            'action' => $action,
            'datetime' => current_time('mysql', true),
            'data' => $data,
        ];

        $max_logs = 200;
        if (count($logs) > $max_logs) {
            $logs = array_slice($logs, -1 * $max_logs);
        }

        update_post_meta($entity_id, 'governance_audit_logs', $logs);
    }
}

new RMA_Governance();
