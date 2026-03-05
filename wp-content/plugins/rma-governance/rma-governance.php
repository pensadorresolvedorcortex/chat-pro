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
        add_action('admin_menu', [$this, 'register_admin_page']);
    }

    public function register_admin_page(): void {
        add_submenu_page(
            'edit.php?post_type=' . self::CPT,
            'Governança RMA',
            'Governança',
            'edit_others_posts',
            'rma-governance-audit',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page(): void {
        if (! current_user_can('edit_others_posts')) {
            wp_die('Você não tem permissão para acessar esta página.');
        }

        $this->handle_admin_actions();

        $rows = $this->load_governance_rows();
        $summary = $this->build_summary($rows);

        $selected_entity_id = isset($_GET['entity_id']) ? (int) $_GET['entity_id'] : 0;
        $selected = $selected_entity_id > 0 ? $this->load_entity_details($selected_entity_id) : null;

        $notice = isset($_GET['rma_notice']) ? sanitize_text_field(rawurldecode((string) wp_unslash($_GET['rma_notice']))) : '';
        $notice_type = isset($_GET['rma_notice_type']) ? sanitize_key((string) wp_unslash($_GET['rma_notice_type'])) : '';
        ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Maven+Pro:wght@600&display=swap" rel="stylesheet">
        <style>
            .rma-gov-wrap,
            .rma-gov-wrap * {
                font-family: 'Maven Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
                font-weight: 600 !important;
                box-sizing: border-box;
            }

            .rma-gov-wrap {
                --rma-bg: linear-gradient(150deg, #fcfdff 0%, #f2f7ff 100%);
                --rma-card: rgba(255, 255, 255, 0.86);
                --rma-border: rgba(255, 255, 255, 0.78);
                --rma-shadow: 0 24px 60px rgba(15, 23, 42, 0.11);
                --rma-text: #162538;
                --rma-muted: #5b6a7c;
                --rma-approve: #0f9f6f;
                --rma-reject: #ce3f4a;
                --rma-pending: #d78d11;
                margin-top: 14px;
                border-radius: 24px;
                padding: 28px;
                border: 1px solid rgba(255,255,255,0.9);
                background: var(--rma-bg);
                box-shadow: var(--rma-shadow);
                color: var(--rma-text);
            }

            .rma-gov-head { margin-bottom: 16px; }
            .rma-gov-head h1 { margin: 0 0 8px 0 !important; font-size: 30px !important; }
            .rma-gov-head p { margin: 0 !important; color: var(--rma-muted); }

            .rma-gov-notice {
                border-radius: 12px;
                padding: 12px 14px;
                margin: 12px 0 18px;
                border: 1px solid transparent;
            }
            .rma-gov-notice.success { background: rgba(15,159,111,.11); border-color: rgba(15,159,111,.3); color: #0d7d58; }
            .rma-gov-notice.error { background: rgba(206,63,74,.1); border-color: rgba(206,63,74,.24); color: #b2303c; }

            .rma-gov-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
                gap: 12px;
                margin-bottom: 22px;
            }
            .rma-gov-card {
                border-radius: 16px;
                padding: 14px;
                background: var(--rma-card);
                border: 1px solid var(--rma-border);
                box-shadow: 0 10px 30px rgba(15,23,42,0.07);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .rma-gov-card .icon {
                width: 30px;
                height: 30px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                border: 1px solid rgba(15,23,42,.08);
            }
            .rma-gov-card strong { display: block; font-size: 22px; line-height: 1.1; }
            .rma-muted { color: var(--rma-muted); font-size: 12px; }

            .rma-gov-table-wrap,
            .rma-gov-detail {
                background: var(--rma-card);
                border: 1px solid var(--rma-border);
                border-radius: 18px;
                box-shadow: 0 10px 30px rgba(15,23,42,.06);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
            .rma-gov-table-wrap { overflow: auto; margin-bottom: 18px; }

            table.rma-gov-table,
            table.rma-gov-nested {
                width: 100%;
                border-collapse: collapse;
                background: transparent !important;
            }
            .rma-gov-table th,
            .rma-gov-table td,
            .rma-gov-nested th,
            .rma-gov-nested td {
                border-bottom: 1px solid rgba(15,23,42,.08) !important;
                padding: 12px;
                text-align: left;
                vertical-align: top;
            }
            .rma-gov-table th,
            .rma-gov-nested th {
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--rma-muted);
            }

            .rma-badge {
                border-radius: 999px;
                padding: 6px 11px;
                font-size: 12px;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border: 1px solid transparent;
            }
            .rma-badge.aprovado { color: var(--rma-approve); background: rgba(15,159,111,.12); border-color: rgba(15,159,111,.25); }
            .rma-badge.recusado { color: var(--rma-reject); background: rgba(206,63,74,.12); border-color: rgba(206,63,74,.25); }
            .rma-badge.em_analise,
            .rma-badge.pendente { color: var(--rma-pending); background: rgba(215,141,17,.12); border-color: rgba(215,141,17,.25); }

            .rma-link {
                color: #1559d6 !important;
                text-decoration: none !important;
            }
            .rma-link:hover { text-decoration: underline !important; }

            .rma-gov-detail {
                padding: 18px;
                display: grid;
                gap: 14px;
            }
            .rma-gov-detail-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 12px;
            }
            .rma-detail-card {
                background: rgba(255,255,255,.9);
                border: 1px solid rgba(15,23,42,.08);
                border-radius: 14px;
                padding: 12px;
            }
            .rma-detail-card h3 { margin: 0 0 8px; font-size: 15px; }
            .rma-actions form { display: grid; gap: 8px; margin-bottom: 10px; }
            .rma-actions textarea,
            .rma-actions input[type="text"] {
                width: 100%;
                border: 1px solid rgba(15,23,42,.2);
                border-radius: 10px;
                padding: 8px 10px;
                background: #fff;
                font-size: 13px;
            }
            .rma-actions button {
                border: none;
                border-radius: 10px;
                padding: 9px 12px;
                color: #fff;
                cursor: pointer;
                width: fit-content;
            }
            .rma-btn-approve { background: linear-gradient(135deg, #0f9f6f 0%, #0d8a61 100%); }
            .rma-btn-reject { background: linear-gradient(135deg, #ce3f4a 0%, #b1323d 100%); }
            .rma-btn-resubmit { background: linear-gradient(135deg, #d78d11 0%, #bd7a0f 100%); }

            .rma-timeline { margin: 0; padding-left: 18px; }
            .rma-timeline li { margin: 0 0 8px 0; color: var(--rma-text); }
        </style>

        <div class="wrap rma-gov-wrap">
            <div class="rma-gov-head">
                <h1>Governança RMA • Premium Console</h1>
                <p>Tela com visual glass white, status operacionais e gestão prática de documentos e auditoria.</p>
            </div>

            <?php if ($notice !== '') : ?>
                <div class="rma-gov-notice <?php echo esc_attr($notice_type === 'success' ? 'success' : 'error'); ?>">
                    <?php echo esc_html($notice); ?>
                </div>
            <?php endif; ?>

            <div class="rma-gov-cards">
                <?php echo $this->summary_card('approve', 'Aprovadas', $summary['approved']); ?>
                <?php echo $this->summary_card('reject', 'Recusadas', $summary['rejected']); ?>
                <?php echo $this->summary_card('pending', 'Aguardando análise', $summary['pending']); ?>
                <?php echo $this->summary_card('document', 'Arquivos privados', $summary['documents']); ?>
            </div>

            <div class="rma-gov-table-wrap">
                <table class="rma-gov-table">
                    <thead>
                    <tr>
                        <th>Entidade</th>
                        <th>Status</th>
                        <th>Aceites</th>
                        <th>Documentos</th>
                        <th>Último evento</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)) : ?>
                        <tr><td colspan="6">Nenhuma entidade encontrada para governança.</td></tr>
                    <?php else : ?>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($row['title']); ?></strong><br>
                                    <span class="rma-muted">ID #<?php echo esc_html((string) $row['id']); ?></span>
                                </td>
                                <td>
                                    <span class="rma-badge <?php echo esc_attr($row['status']); ?>">
                                        <?php echo $this->status_icon($row['status']); ?>
                                        <?php echo esc_html($this->status_label($row['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html((string) $row['approvals_count']); ?>/3</td>
                                <td><?php echo esc_html((string) $row['documents_count']); ?></td>
                                <td><span class="rma-muted"><?php echo esc_html($row['last_audit'] !== '' ? $row['last_audit'] : '—'); ?></span></td>
                                <td>
                                    <a class="rma-link" href="<?php echo esc_url(get_edit_post_link($row['id'])); ?>">Editar</a> ·
                                    <a class="rma-link" href="<?php echo esc_url($this->build_admin_url(['entity_id' => $row['id']])); ?>">Gerenciar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($selected !== null) : ?>
                <div class="rma-gov-detail">
                    <div>
                        <h2 style="margin:0 0 6px;">Entidade selecionada: <?php echo esc_html($selected['title']); ?></h2>
                        <span class="rma-badge <?php echo esc_attr($selected['status']); ?>">
                            <?php echo $this->status_icon($selected['status']); ?>
                            <?php echo esc_html($this->status_label($selected['status'])); ?>
                        </span>
                        <?php if ($selected['rejection_reason'] !== '') : ?>
                            <p class="rma-muted" style="margin-top:8px;">Motivo da recusa: <?php echo esc_html($selected['rejection_reason']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="rma-gov-detail-grid">
                        <div class="rma-detail-card rma-actions">
                            <h3>Ações de governança</h3>
                            <?php if (in_array($selected['status'], ['pendente', 'em_analise'], true)) : ?>
                                <form method="post">
                                    <input type="hidden" name="rma_governance_action" value="approve">
                                    <input type="hidden" name="entity_id" value="<?php echo esc_attr((string) $selected['id']); ?>">
                                    <?php wp_nonce_field('rma_gov_action_' . $selected['id'] . '_approve'); ?>
                                    <textarea name="comment" rows="2" placeholder="Comentário opcional do aceite"></textarea>
                                    <button class="rma-btn-approve" type="submit">Registrar aceite</button>
                                </form>

                                <form method="post">
                                    <input type="hidden" name="rma_governance_action" value="reject">
                                    <input type="hidden" name="entity_id" value="<?php echo esc_attr((string) $selected['id']); ?>">
                                    <?php wp_nonce_field('rma_gov_action_' . $selected['id'] . '_reject'); ?>
                                    <input type="text" name="reason" required placeholder="Motivo obrigatório da recusa">
                                    <button class="rma-btn-reject" type="submit">Recusar entidade</button>
                                </form>
                            <?php elseif ($selected['status'] === 'recusado') : ?>
                                <form method="post">
                                    <input type="hidden" name="rma_governance_action" value="resubmit">
                                    <input type="hidden" name="entity_id" value="<?php echo esc_attr((string) $selected['id']); ?>">
                                    <?php wp_nonce_field('rma_gov_action_' . $selected['id'] . '_resubmit'); ?>
                                    <button class="rma-btn-resubmit" type="submit">Reenviar para análise</button>
                                </form>
                            <?php else : ?>
                                <p class="rma-muted">Entidade já aprovada. Nenhuma ação disponível.</p>
                            <?php endif; ?>
                        </div>

                        <div class="rma-detail-card">
                            <h3>Aceites registrados</h3>
                            <?php if (empty($selected['approvals'])) : ?>
                                <p class="rma-muted">Ainda não há aceites para esta entidade.</p>
                            <?php else : ?>
                                <ul class="rma-timeline">
                                    <?php foreach ($selected['approvals'] as $approval) : ?>
                                        <li>
                                            <?php echo esc_html($approval['user_name']); ?> · <?php echo esc_html($approval['datetime']); ?>
                                            <?php if ($approval['comment'] !== '') : ?>
                                                <br><span class="rma-muted"><?php echo esc_html($approval['comment']); ?></span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="rma-detail-card">
                        <h3>Documentos privados (<?php echo esc_html((string) count($selected['documents'])); ?>)</h3>
                        <?php if (empty($selected['documents'])) : ?>
                            <p class="rma-muted">Não há documentos enviados nesta entidade.</p>
                        <?php else : ?>
                            <table class="rma-gov-nested">
                                <thead>
                                <tr>
                                    <th>Arquivo</th>
                                    <th>Tipo</th>
                                    <th>Enviado em</th>
                                    <th>Ação</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($selected['documents'] as $doc) : ?>
                                    <tr>
                                        <td><?php echo esc_html($doc['name']); ?></td>
                                        <td><?php echo esc_html($doc['document_type'] !== '' ? $doc['document_type'] : 'geral'); ?></td>
                                        <td><?php echo esc_html($doc['uploaded_at'] !== '' ? $doc['uploaded_at'] : '—'); ?></td>
                                        <td><a class="rma-link" href="<?php echo esc_url($doc['download_url']); ?>" target="_blank" rel="noopener">Baixar</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <div class="rma-detail-card">
                        <h3>Trilha de auditoria</h3>
                        <?php if (empty($selected['audit_logs'])) : ?>
                            <p class="rma-muted">Sem eventos de auditoria.</p>
                        <?php else : ?>
                            <ul class="rma-timeline">
                                <?php foreach ($selected['audit_logs'] as $log) : ?>
                                    <li><?php echo esc_html($log['action']); ?> · <?php echo esc_html($log['datetime']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function handle_admin_actions(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page !== 'rma-governance-audit') {
            return;
        }

        $action = isset($_POST['rma_governance_action']) ? sanitize_key((string) wp_unslash($_POST['rma_governance_action'])) : '';
        $entity_id = isset($_POST['entity_id']) ? (int) $_POST['entity_id'] : 0;

        if ($entity_id <= 0 || ! in_array($action, ['approve', 'reject', 'resubmit'], true)) {
            $this->redirect_with_notice($entity_id, 'Ação inválida.', 'error');
        }

        $nonce = isset($_POST['_wpnonce']) ? (string) wp_unslash($_POST['_wpnonce']) : '';
        if (! wp_verify_nonce($nonce, 'rma_gov_action_' . $entity_id . '_' . $action)) {
            $this->redirect_with_notice($entity_id, 'Falha de segurança ao validar ação.', 'error');
        }

        $request = new WP_REST_Request('POST');
        $request->set_param('id', $entity_id);

        if ($action === 'approve') {
            $request->set_param('comment', sanitize_textarea_field((string) ($_POST['comment'] ?? '')));
            $response = $this->approve_entity($request);
        } elseif ($action === 'reject') {
            $request->set_param('reason', sanitize_text_field((string) ($_POST['reason'] ?? '')));
            $response = $this->reject_entity($request);
        } else {
            $response = $this->resubmit_entity($request);
        }

        $payload = $response instanceof WP_REST_Response ? $response->get_data() : [];
        $status = $response instanceof WP_REST_Response ? (int) $response->get_status() : 500;

        if ($status >= 200 && $status < 300) {
            $ok_message = 'Ação executada com sucesso.';
            if ($action === 'approve') {
                $ok_message = 'Aceite registrado com sucesso.';
            } elseif ($action === 'reject') {
                $ok_message = 'Entidade recusada com sucesso.';
            } elseif ($action === 'resubmit') {
                $ok_message = 'Entidade reenviada para análise.';
            }

            $this->redirect_with_notice($entity_id, $ok_message, 'success');
        }

        $error_message = is_array($payload) ? (string) ($payload['message'] ?? 'Não foi possível executar a ação.') : 'Não foi possível executar a ação.';
        $this->redirect_with_notice($entity_id, $error_message, 'error');
    }

    private function redirect_with_notice(int $entity_id, string $message, string $type): void {
        $args = [
            'entity_id' => $entity_id,
            'rma_notice' => rawurlencode($message),
            'rma_notice_type' => $type === 'success' ? 'success' : 'error',
        ];

        wp_safe_redirect($this->build_admin_url($args));
        exit;
    }

    private function build_admin_url(array $args = []): string {
        $base = [
            'post_type' => self::CPT,
            'page' => 'rma-governance-audit',
        ];

        return add_query_arg(array_merge($base, $args), admin_url('edit.php'));
    }

    private function load_entity_details(int $entity_id): ?array {
        if ($entity_id <= 0 || get_post_type($entity_id) !== self::CPT) {
            return null;
        }

        $approvals = get_post_meta($entity_id, 'governance_approvals', true);
        $approvals = is_array($approvals) ? $approvals : [];

        $documents = get_post_meta($entity_id, 'entity_documents', true);
        $documents = is_array($documents) ? $documents : [];

        $audit_logs = get_post_meta($entity_id, 'governance_audit_logs', true);
        $audit_logs = is_array($audit_logs) ? $audit_logs : [];
        $audit_logs = array_reverse($audit_logs);

        $normalized_approvals = [];
        foreach ($approvals as $approval) {
            $user_id = (int) ($approval['user_id'] ?? 0);
            $user = $user_id > 0 ? get_userdata($user_id) : null;
            $normalized_approvals[] = [
                'user_name' => $user ? (string) $user->display_name : 'Usuário #' . $user_id,
                'datetime' => (string) ($approval['datetime'] ?? ''),
                'comment' => (string) ($approval['comment'] ?? ''),
            ];
        }

        $normalized_documents = [];
        foreach ($documents as $doc) {
            $doc_id = sanitize_text_field((string) ($doc['id'] ?? ''));
            if ($doc_id === '') {
                continue;
            }

            $normalized_documents[] = [
                'name' => sanitize_file_name((string) ($doc['name'] ?? 'documento')),
                'document_type' => sanitize_key((string) ($doc['document_type'] ?? '')),
                'uploaded_at' => sanitize_text_field((string) ($doc['uploaded_at'] ?? '')),
                'download_url' => rest_url(sprintf('rma/v1/entities/%d/documents/%s', $entity_id, rawurlencode($doc_id))),
            ];
        }

        $normalized_logs = [];
        foreach ($audit_logs as $log) {
            $normalized_logs[] = [
                'action' => sanitize_key((string) ($log['action'] ?? 'evento')),
                'datetime' => sanitize_text_field((string) ($log['datetime'] ?? '')),
            ];
        }

        return [
            'id' => $entity_id,
            'title' => (string) get_the_title($entity_id),
            'status' => $this->normalized_governance_status($entity_id),
            'rejection_reason' => (string) get_post_meta($entity_id, 'governance_rejection_reason', true),
            'approvals' => $normalized_approvals,
            'documents' => $normalized_documents,
            'audit_logs' => array_slice($normalized_logs, 0, 20),
        ];
    }

    private function load_governance_rows(): array {
        $posts = get_posts([
            'post_type' => self::CPT,
            'post_status' => ['draft', 'publish', 'pending', 'private'],
            'posts_per_page' => 200,
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        if (empty($posts)) {
            return [];
        }

        $rows = [];

        foreach ($posts as $post) {
            $entity_id = (int) $post->ID;
            $approvals = get_post_meta($entity_id, 'governance_approvals', true);
            $approvals = is_array($approvals) ? $approvals : [];

            $documents = get_post_meta($entity_id, 'entity_documents', true);
            $documents = is_array($documents) ? $documents : [];

            $audit_logs = get_post_meta($entity_id, 'governance_audit_logs', true);
            $audit_logs = is_array($audit_logs) ? $audit_logs : [];
            $last_audit = '';

            if (! empty($audit_logs)) {
                $last_log = end($audit_logs);
                $action = (string) ($last_log['action'] ?? 'evento');
                $datetime = (string) ($last_log['datetime'] ?? '');
                $last_audit = trim($action . ' · ' . $datetime, " ·");
            }

            $rows[] = [
                'id' => $entity_id,
                'title' => (string) get_the_title($entity_id),
                'status' => $this->normalized_governance_status($entity_id),
                'approvals_count' => count($approvals),
                'documents_count' => count($documents),
                'last_audit' => $last_audit,
            ];
        }

        return $rows;
    }

    private function build_summary(array $rows): array {
        $summary = [
            'approved' => 0,
            'rejected' => 0,
            'pending' => 0,
            'documents' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'pendente');
            if ($status === 'aprovado') {
                $summary['approved']++;
            } elseif ($status === 'recusado') {
                $summary['rejected']++;
            } else {
                $summary['pending']++;
            }

            $summary['documents'] += (int) ($row['documents_count'] ?? 0);
        }

        return $summary;
    }

    private function summary_card(string $icon, string $label, int $value): string {
        return sprintf(
            '<div class="rma-gov-card"><span class="icon">%s</span><div><strong>%s</strong><span class="rma-muted">%s</span></div></div>',
            $this->svg_icon($icon),
            esc_html((string) $value),
            esc_html($label)
        );
    }

    private function status_label(string $status): string {
        $labels = [
            'aprovado' => 'Aprovado',
            'recusado' => 'Recusado',
            'em_analise' => 'Em análise',
            'pendente' => 'Aguardando',
        ];

        return $labels[$status] ?? 'Aguardando';
    }

    private function status_icon(string $status): string {
        if ($status === 'aprovado') {
            return $this->svg_icon('approve');
        }

        if ($status === 'recusado') {
            return $this->svg_icon('reject');
        }

        if ($status === 'em_analise') {
            return $this->svg_icon('analysis');
        }

        return $this->svg_icon('pending');
    }

    private function svg_icon(string $name): string {
        $icons = [
            'approve' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7L10 17l-5-5" stroke="#0f9f6f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'reject' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="#ce3f4a" stroke-width="2" stroke-linecap="round"/></svg>',
            'pending' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="#d78d11" stroke-width="2"/><path d="M12 8v5l3 2" stroke="#d78d11" stroke-width="2" stroke-linecap="round"/></svg>',
            'analysis' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 5h9M10 12h9M10 19h9" stroke="#d78d11" stroke-width="2" stroke-linecap="round"/><circle cx="6" cy="5" r="1.5" fill="#d78d11"/><circle cx="6" cy="12" r="1.5" fill="#d78d11"/><circle cx="6" cy="19" r="1.5" fill="#d78d11"/></svg>',
            'document' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z" stroke="#2351a3" stroke-width="2"/><path d="M14 3v5h5" stroke="#2351a3" stroke-width="2"/></svg>',
        ];

        return $icons[$name] ?? $icons['pending'];
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
