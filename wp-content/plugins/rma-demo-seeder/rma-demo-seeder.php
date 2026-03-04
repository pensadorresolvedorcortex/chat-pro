<?php
/**
 * Plugin Name: RMA Demo Seeder
 * Description: Gera e limpa dados demonstrativos para homologação completa do CRM RMA.
 * Version: 0.1.0
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Demo_Seeder {
    private const CPT = 'rma_entidade';
    private const META_DEMO = 'rma_is_demo';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_post_rma_demo_seed', [$this, 'handle_admin_seed']);
        add_action('admin_post_rma_demo_cleanup', [$this, 'handle_admin_cleanup']);
        add_action('admin_post_rma_demo_setup_next', [$this, 'handle_admin_setup_next']);
        add_action('admin_post_rma_demo_setup_reset', [$this, 'handle_admin_setup_reset']);

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('rma seed-demo', [$this, 'cli_seed_demo']);
            \WP_CLI::add_command('rma clear-demo', [$this, 'cli_clear_demo']);
            \WP_CLI::add_command('rma setup-next', [$this, 'cli_setup_next']);
        }
    }

    public function register_admin_page(): void {
        add_menu_page(
            'RMA Dados Demonstrativos',
            'RMA Dados Demonstrativos',
            'manage_options',
            'rma-demo-seeder',
            [$this, 'render_admin_page'],
            'dashicons-database-add',
            59
        );
    }

    public function render_admin_page(): void {
        if (! current_user_can('manage_options')) {
            return;
        }

        $last = get_option('rma_demo_last_report', []);
        ?>
        <div class="wrap">
          <h1>RMA → Dados Demonstrativos</h1>
          <p>Gera ambiente completo de homologação sem sobrescrever dados reais.</p>
          <?php $setup = get_option('rma_demo_setup_ctx', ['step' => 0]); $step = (int) ($setup['step'] ?? 0); ?>
          <h2>Setup guiado (avançar)</h2>
          <p>Step atual: <strong><?php echo esc_html((string) $step); ?></strong> (0=limpo, 1=infra, 2=entidades, 3=finalizado)</p>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:8px;">
            <?php wp_nonce_field('rma_demo_setup_next'); ?>
            <input type="hidden" name="action" value="rma_demo_setup_next" />
            <label>Entidades: <input type="number" name="entities" min="40" max="60" value="60" /></label>
            <label style="margin-left:10px;"><input type="checkbox" name="simulate_emails" value="1" checked /> Simular e-mails</label>
            <?php submit_button('Avançar setup demo', 'secondary', 'submit', false); ?>
          </form>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:12px;">
            <?php wp_nonce_field('rma_demo_setup_reset'); ?>
            <input type="hidden" name="action" value="rma_demo_setup_reset" />
            <?php submit_button('Resetar setup guiado', 'secondary', 'submit', false); ?>
          </form>
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:12px;">
            <?php wp_nonce_field('rma_demo_seed'); ?>
            <input type="hidden" name="action" value="rma_demo_seed" />
            <label>Entidades: <input type="number" name="entities" min="40" max="60" value="60" /></label>
            <label style="margin-left:10px;"><input type="checkbox" name="simulate_emails" value="1" checked /> Simular e-mails</label>
            <?php submit_button('Gerar Dados', 'primary', 'submit', false); ?>
          </form>

          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('rma_demo_cleanup'); ?>
            <input type="hidden" name="action" value="rma_demo_cleanup" />
            <?php submit_button('Limpar Dados Demo', 'delete', 'submit', false); ?>
          </form>

          <?php if (is_array($last) && ! empty($last)) : ?>
            <h2>Último relatório</h2>
            <pre><?php echo esc_html(wp_json_encode($last, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
          <?php endif; ?>
        </div>
        <?php
    }

    public function handle_admin_seed(): void {
        if (! current_user_can('manage_options') || ! check_admin_referer('rma_demo_seed')) {
            wp_die('Sem permissão.');
        }

        $entities = max(40, min(60, absint((int) ($_POST['entities'] ?? 60))));
        $simulate = ! empty($_POST['simulate_emails']);
        $report = $this->seed_demo($entities, $simulate);
        update_option('rma_demo_last_report', $report, false);

        wp_safe_redirect(admin_url('admin.php?page=rma-demo-seeder'));
        exit;
    }

    public function handle_admin_cleanup(): void {
        if (! current_user_can('manage_options') || ! check_admin_referer('rma_demo_cleanup')) {
            wp_die('Sem permissão.');
        }

        $report = $this->clear_demo();
        update_option('rma_demo_last_report', $report, false);

        wp_safe_redirect(admin_url('admin.php?page=rma-demo-seeder'));
        exit;
    }

    public function handle_admin_setup_next(): void {
        if (! current_user_can('manage_options') || ! check_admin_referer('rma_demo_setup_next')) {
            wp_die('Sem permissão.');
        }

        $entities = max(40, min(60, absint((int) ($_POST['entities'] ?? 60))));
        $simulate = ! empty($_POST['simulate_emails']);
        $report = $this->run_setup_step($entities, $simulate);
        update_option('rma_demo_last_report', $report, false);

        wp_safe_redirect(admin_url('admin.php?page=rma-demo-seeder'));
        exit;
    }

    public function handle_admin_setup_reset(): void {
        if (! current_user_can('manage_options') || ! check_admin_referer('rma_demo_setup_reset')) {
            wp_die('Sem permissão.');
        }

        delete_option('rma_demo_setup_ctx');
        update_option('rma_demo_last_report', ['message' => 'Setup guiado resetado.'], false);
        wp_safe_redirect(admin_url('admin.php?page=rma-demo-seeder'));
        exit;
    }

    public function cli_seed_demo(array $args, array $assoc_args): void {
        $entities = isset($assoc_args['entities']) ? max(40, min(60, absint((int) $assoc_args['entities']))) : 60;
        $simulate = array_key_exists('simulate-emails', $assoc_args);
        $report = $this->seed_demo($entities, $simulate);
        \WP_CLI::success('Seeder concluído.');
        \WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function cli_clear_demo(): void {
        $report = $this->clear_demo();
        \WP_CLI::success('Limpeza concluída.');
        \WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function cli_setup_next(array $args, array $assoc_args): void {
        $entities = isset($assoc_args['entities']) ? max(40, min(60, absint((int) $assoc_args['entities']))) : 60;
        $simulate = array_key_exists('simulate-emails', $assoc_args);
        $report = $this->run_setup_step($entities, $simulate);
        \WP_CLI::success('Setup avançou uma etapa.');
        \WP_CLI::line(wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function run_setup_step(int $total_entities, bool $simulate_emails): array {
        $ctx = get_option('rma_demo_setup_ctx', ['step' => 0]);
        $step = (int) ($ctx['step'] ?? 0);

        if ($step <= 0) {
            $this->ensure_demo_tables();
            $cleanup = $this->clear_demo();
            $users = $this->ensure_demo_users();
            $product_id = $this->ensure_demo_product();

            $ctx = [
                'step' => 1,
                'entities_target' => $total_entities,
                'simulate_emails' => $simulate_emails,
                'users' => $users,
                'product_id' => $product_id,
                'cleanup' => $cleanup,
            ];
            update_option('rma_demo_setup_ctx', $ctx, false);

            return ['message' => 'Setup step 1 concluído: infraestrutura preparada.', 'setup' => $ctx];
        }

        if ($step === 1) {
            $users = is_array($ctx['users'] ?? null) ? $ctx['users'] : $this->ensure_demo_users();
            $entities_target = max(40, min(60, absint((int) ($ctx['entities_target'] ?? $total_entities))));

            $cities = $this->city_pool();
            $statuses = $this->build_status_plan($entities_target);
            shuffle($cities);

            $created_ids = [];
            $approved_ids = [];
            $i = 0;
            foreach ($statuses as $status) {
                $city = $cities[$i % count($cities)];
                $i++;
                $author = ($i % 2 === 0) ? $users['entidade_a'] : $users['entidade_b'];
                $entity_id = $this->create_demo_entity($i, $status, $city, $author);
                $created_ids[] = $entity_id;
                if ($status === 'aprovado') {
                    $approved_ids[] = $entity_id;
                    $this->seed_approvals($entity_id, $users);
                }
                $this->seed_documents($entity_id, rand(2, 3), $author);
            }
            $this->seed_mandates($created_ids);

            $ctx['step'] = 2;
            $ctx['created_ids'] = $created_ids;
            $ctx['approved_ids'] = $approved_ids;
            update_option('rma_demo_setup_ctx', $ctx, false);

            return ['message' => 'Setup step 2 concluído: entidades/governança/documentos/mandatos.', 'created' => count($created_ids), 'approved' => count($approved_ids)];
        }

        if ($step === 2) {
            $users = is_array($ctx['users'] ?? null) ? $ctx['users'] : $this->ensure_demo_users();
            $approved_ids = array_map('intval', is_array($ctx['approved_ids'] ?? null) ? $ctx['approved_ids'] : []);
            $created_ids = array_map('intval', is_array($ctx['created_ids'] ?? null) ? $ctx['created_ids'] : []);
            $product_id = absint((int) ($ctx['product_id'] ?? 0));
            $simulate = ! empty($ctx['simulate_emails']);

            $this->seed_financial($approved_ids, $product_id, $users['entidade_a']);
            $email_logs = $this->seed_email_logs($created_ids, $simulate);
            $checks = $this->run_validations($users);

            $ctx['step'] = 3;
            $ctx['email_logs'] = $email_logs;
            $ctx['validations'] = $checks;
            update_option('rma_demo_setup_ctx', $ctx, false);

            return ['message' => 'Setup step 3 concluído: financeiro/automações/validações.', 'email_logs' => $email_logs, 'validations' => $checks];
        }

        return ['message' => 'Setup já finalizado. Use reset para reiniciar.', 'setup' => $ctx];
    }

    private function seed_demo(int $total_entities, bool $simulate_emails): array {
        $this->ensure_demo_tables();
        $this->clear_demo();
        $users = $this->ensure_demo_users();
        $product_id = $this->ensure_demo_product();

        $cities = $this->city_pool();
        $statuses = $this->build_status_plan($total_entities);
        shuffle($cities);

        $created_ids = [];
        $approved_ids = [];
        $i = 0;
        foreach ($statuses as $status) {
            $city = $cities[$i % count($cities)];
            $i++;
            $author = ($i % 2 === 0) ? $users['entidade_a'] : $users['entidade_b'];
            $entity_id = $this->create_demo_entity($i, $status, $city, $author);
            $created_ids[] = $entity_id;
            if ($status === 'aprovado') {
                $approved_ids[] = $entity_id;
                $this->seed_approvals($entity_id, $users);
            }
            $this->seed_documents($entity_id, rand(2, 3), $author);
        }

        $this->seed_financial($approved_ids, $product_id, $users['entidade_a']);
        $this->seed_mandates($created_ids);
        $email_logs = $this->seed_email_logs($created_ids, $simulate_emails);
        $checks = $this->run_validations($users);

        $report = [
            'entities_created' => count($created_ids),
            'approved' => count($approved_ids),
            'users' => $users,
            'product_id' => $product_id,
            'email_logs' => $email_logs,
            'validations' => $checks,
            'simulate_emails' => $simulate_emails,
        ];

        error_log('[RMA DEMO] Seeder report: ' . wp_json_encode($report));
        return $report;
    }

    private function clear_demo(): array {
        $ids = get_posts([
            'post_type' => self::CPT,
            'post_status' => ['publish', 'draft', 'pending', 'private', 'trash'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => self::META_DEMO,
            'meta_value' => '1',
        ]);

        $order_ids = [];
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders([
                'limit' => -1,
                'return' => 'ids',
                'meta_key' => 'rma_is_demo',
                'meta_value' => '1',
            ]);
            $order_ids = is_array($orders) ? $orders : [];
            foreach ($order_ids as $oid) {
                wp_delete_post((int) $oid, true);
            }
        }

        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => self::META_DEMO,
            'meta_value' => '1',
        ]);
        foreach ($attachments as $aid) {
            wp_delete_attachment((int) $aid, true);
        }

        foreach ($ids as $id) {
            wp_delete_post((int) $id, true);
        }

        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}rma_approvals WHERE is_demo = 1");
        $wpdb->query("DELETE FROM {$wpdb->prefix}rma_dues WHERE is_demo = 1");
        $wpdb->query("DELETE FROM {$wpdb->prefix}rma_email_log WHERE is_demo = 1");
        delete_option('rma_demo_setup_ctx');

        return [
            'entities_removed' => count($ids),
            'orders_removed' => count($order_ids),
            'attachments_removed' => count($attachments),
        ];
    }

    private function ensure_demo_users(): array {
        $map = [
            'admin_rma_demo' => ['role' => 'administrator', 'email' => 'admin_rma_demo@example.org'],
            'aprovador_demo' => ['role' => 'editor', 'email' => 'aprovador_demo@example.org'],
            'entidade_demo_a' => ['role' => 'subscriber', 'email' => 'entidade_demo_a@example.org'],
            'entidade_demo_b' => ['role' => 'subscriber', 'email' => 'entidade_demo_b@example.org'],
        ];
        $result = [];
        foreach ($map as $login => $cfg) {
            $u = get_user_by('login', $login);
            if (! $u) {
                $uid = wp_insert_user([
                    'user_login' => $login,
                    'user_pass' => wp_generate_password(20, true),
                    'user_email' => $cfg['email'],
                    'role' => $cfg['role'],
                    'display_name' => ucwords(str_replace('_', ' ', $login)),
                ]);
                $result[$login] = (int) $uid;
            } else {
                $result[$login] = (int) $u->ID;
            }
        }

        return [
            'admin' => $result['admin_rma_demo'],
            'aprovador' => $result['aprovador_demo'],
            'entidade_a' => $result['entidade_demo_a'],
            'entidade_b' => $result['entidade_demo_b'],
        ];
    }

    private function ensure_demo_product(): int {
        if (! class_exists('WC_Product_Simple')) {
            return 0;
        }

        $existing = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => self::META_DEMO,
            'meta_value' => '1',
            's' => 'Anuidade RMA 2026',
        ]);
        if (! empty($existing)) {
            return (int) $existing[0];
        }

        $product = new WC_Product_Simple();
        $product->set_name('Anuidade RMA 2026');
        $product->set_regular_price('1200');
        $product->set_status('publish');
        $product_id = $product->save();
        update_post_meta($product_id, self::META_DEMO, '1');

        return (int) $product_id;
    }

    private function create_demo_entity(int $index, string $status, array $city, int $author_id): int {
        $cnpj = $this->generate_valid_cnpj($index);
        $title = sprintf('Instituto %s %02d', $city['cidade'], $index);
        $post_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_status' => in_array($status, ['aprovado'], true) ? 'publish' : 'draft',
            'post_title' => $title,
            'post_author' => $author_id,
            'post_content' => 'Entidade demonstrativa para homologação RMA.',
        ]);

        $finance_default = in_array($status, ['aprovado'], true) ? 'inadimplente' : 'pendente';
        $areas = $this->pick_areas();
        $lat = $city['lat'] + (mt_rand(-40, 40) / 1000);
        $lng = $city['lng'] + (mt_rand(-40, 40) / 1000);

        $meta = [
            self::META_DEMO => '1',
            'cnpj' => $cnpj,
            'razao_social' => $title . ' Associação Civil',
            'nome_fantasia' => $title,
            'cnae_principal' => '9493-6/00',
            'email_contato' => 'contato+' . $post_id . '@demo-rma.org',
            'telefone_contato' => '(11) 90000-0000',
            'cep' => $city['cep'],
            'logradouro' => $city['logradouro'],
            'numero' => (string) mt_rand(10, 999),
            'bairro' => $city['bairro'],
            'cidade' => $city['cidade'],
            'uf' => $city['uf'],
            'lat' => (string) $lat,
            'lng' => (string) $lng,
            'governance_status' => $status,
            'finance_status' => $finance_default,
            'documentos_status' => 'enviado',
            'consent_lgpd' => '1',
            'area_interesse' => $areas[0],
            'areas_interesse' => implode(', ', $areas),
        ];

        foreach ($meta as $k => $v) {
            update_post_meta($post_id, $k, $v);
        }

        return (int) $post_id;
    }

    private function seed_approvals(int $entity_id, array $users): void {
        $approvals = [];
        $ids = [$users['admin'], $users['aprovador'], 1];
        for ($i = 1; $i <= 3; $i++) {
            $uid = (int) $ids[$i - 1];
            $approvals[] = [
                'user_id' => $uid,
                'datetime' => gmdate('Y-m-d H:i:s', time() - (4 - $i) * DAY_IN_SECONDS),
                'ip' => '10.0.0.' . (20 + $i),
                'comment' => 'Aceite automático de homologação passo ' . $i,
            ];
            $this->insert_approval_row($entity_id, $i, $uid);
        }

        update_post_meta($entity_id, 'governance_approvals', $approvals);
        update_post_meta($entity_id, 'governance_audit_logs', [[
            'action' => 'approve',
            'datetime' => current_time('mysql', true),
            'data' => ['approvals_count' => 3, 'is_demo' => true],
        ]]);
    }

    private function seed_financial(array $approved_ids, int $product_id, int $customer_id): void {
        if (! function_exists('wc_create_order')) {
            return;
        }

        $target_adimplente = (int) floor(count($approved_ids) * 0.6);
        shuffle($approved_ids);
        $adimplentes = array_slice($approved_ids, 0, $target_adimplente);

        foreach ($approved_ids as $entity_id) {
            $is_paid = in_array($entity_id, $adimplentes, true);
            if ($is_paid) {
                $order = wc_create_order(['customer_id' => $customer_id]);
                if ($product_id > 0) {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $order->add_product($product, 1);
                    }
                }
                $order->update_meta_data('rma_entity_id', $entity_id);
                $order->update_meta_data('rma_is_demo', '1');
                $order->calculate_totals();
                $order->update_status((rand(0, 1) ? 'completed' : 'processing'));
                update_post_meta($entity_id, 'finance_status', 'adimplente');

                $this->insert_due_row($entity_id, (int) $order->get_id(), 'adimplente', (float) $order->get_total());
            } else {
                update_post_meta($entity_id, 'finance_status', 'inadimplente');
                $this->insert_due_row($entity_id, 0, 'inadimplente', 0.0);
            }
        }
    }

    private function seed_mandates(array $entity_ids): void {
        shuffle($entity_ids);
        $buckets = [
            '30' => array_slice($entity_ids, 0, 5),
            '7' => array_slice($entity_ids, 5, 5),
            'expired' => array_slice($entity_ids, 10, 5),
            'valid' => array_slice($entity_ids, 15),
        ];

        foreach ($buckets['30'] as $id) {
            update_post_meta($id, 'mandato_fim', gmdate('Y-m-d', strtotime('+30 days')));
        }
        foreach ($buckets['7'] as $id) {
            update_post_meta($id, 'mandato_fim', gmdate('Y-m-d', strtotime('+7 days')));
        }
        foreach ($buckets['expired'] as $id) {
            update_post_meta($id, 'mandato_fim', gmdate('Y-m-d', strtotime('-5 days')));
        }
        foreach ($buckets['valid'] as $id) {
            update_post_meta($id, 'mandato_fim', gmdate('Y-m-d', strtotime('+420 days')));
        }
    }

    private function seed_documents(int $entity_id, int $count, int $author_id): void {
        $upload = wp_upload_dir();
        $base = trailingslashit($upload['basedir']) . 'rma-private/' . $entity_id;
        wp_mkdir_p($base);

        $docs = [];
        for ($i = 1; $i <= $count; $i++) {
            $doc_id = wp_generate_uuid4();
            $name = 'demo-documento-' . $i . '.pdf';
            $path = trailingslashit($base) . $doc_id . '-' . $name;
            file_put_contents($path, "%PDF-1.4\n%RMA DEMO\n1 0 obj <<>>\nendobj\ntrailer<<>>\n%%EOF");
            $docs[] = [
                'id' => $doc_id,
                'name' => $name,
                'path' => $path,
                'size' => filesize($path),
                'uploaded_by' => $author_id,
                'uploaded_at' => current_time('mysql', true),
            ];

            $att_id = wp_insert_attachment([
                'post_title' => 'demo-attachment-' . $doc_id,
                'post_mime_type' => 'application/pdf',
                'post_status' => 'inherit',
            ], $path, $entity_id);
            if (! is_wp_error($att_id)) {
                update_post_meta($att_id, self::META_DEMO, '1');
            }
        }

        update_post_meta($entity_id, 'entity_documents', $docs);
        update_post_meta($entity_id, 'documentos_status', 'enviado');
    }

    private function seed_email_logs(array $entity_ids, bool $simulate): int {
        $count = 0;
        global $wpdb;
        foreach (array_slice($entity_ids, 0, 20) as $id) {
            $logs = get_post_meta($id, 'automation_logs', true);
            $logs = is_array($logs) ? $logs : [];
            $logs[] = [
                'context' => 'demo_simulation',
                'email' => 'entidade+' . $id . '@demo-rma.org',
                'subject' => 'Simulação de automação RMA',
                'sent' => $simulate ? false : true,
                'datetime' => current_time('mysql', true),
            ];
            update_post_meta($id, 'automation_logs', $logs);

            $wpdb->insert($wpdb->prefix . 'rma_email_log', [
                'entity_id' => $id,
                'context' => 'demo_simulation',
                'recipient' => 'entidade+' . $id . '@demo-rma.org',
                'subject' => 'Simulação de automação RMA',
                'sent' => $simulate ? 0 : 1,
                'created_at' => current_time('mysql', true),
                'is_demo' => 1,
            ]);
            $count++;
        }

        return $count;
    }

    private function run_validations(array $users): array {
        $approved = $this->count_entities(['governance_status' => 'aprovado']);
        $inadimplentes = $this->count_entities(['finance_status' => 'inadimplente', 'governance_status' => 'aprovado']);
        $mandato_vencendo = $this->count_mandato_window([7, 30]);

        $map_count = 0;
        $response = rest_do_request(new WP_REST_Request('GET', '/rma-public/v1/entities'));
        if ($response instanceof WP_REST_Response) {
            $data = $response->get_data();
            $map_count = is_array($data) ? count($data) : 0;
        }

        $orders = function_exists('wc_get_orders') ? wc_get_orders(['limit' => -1, 'return' => 'ids', 'meta_key' => 'rma_is_demo', 'meta_value' => '1']) : [];
        $orders = is_array($orders) ? $orders : [];
        $revenue = 0.0;
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $revenue += (float) $order->get_total();
            }
        }

        return [
            'approved_entities' => $approved,
            'inadimplentes_aprovadas' => $inadimplentes,
            'mandato_vencendo_7_30' => $mandato_vencendo,
            'map_rest_count' => $map_count,
            'woo_orders_demo' => count($orders),
            'woo_revenue_demo' => round($revenue, 2),
            'by_uf' => $this->count_by_uf(),
            'security_checks' => $this->security_check_matrix($users),
        ];
    }

    private function count_by_uf(): array {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT pm.meta_value AS uf, COUNT(*) AS total\n             FROM {$wpdb->postmeta} demo\n             INNER JOIN {$wpdb->posts} p ON p.ID = demo.post_id\n             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s\n             WHERE demo.meta_key = %s AND demo.meta_value = '1' AND p.post_type = %s\n             GROUP BY pm.meta_value",
            'uf', self::META_DEMO, self::CPT
        ));

        $out = [];
        foreach ((array) $rows as $r) {
            $uf = sanitize_text_field((string) ($r->uf ?? ''));
            if ($uf === '') {
                $uf = 'N/D';
            }
            $out[$uf] = (int) ($r->total ?? 0);
        }

        ksort($out);
        return $out;
    }

    private function security_check_matrix(array $users): array {
        $ids = get_posts([
            'post_type' => self::CPT,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => self::META_DEMO,
            'meta_value' => '1',
        ]);

        $a = $users['entidade_a'] ?? 0;
        $b = $users['entidade_b'] ?? 0;
        $ownA = 0;
        $ownB = 0;
        $crossA = 0;
        $crossB = 0;

        foreach ($ids as $id) {
            $author = (int) get_post_field('post_author', (int) $id);
            if ($author === $a) {
                $ownA++;
                $crossB++;
            } elseif ($author === $b) {
                $ownB++;
                $crossA++;
            }
        }

        return [
            'entidade_a_own_entities' => $ownA,
            'entidade_b_own_entities' => $ownB,
            'entidade_a_cross_entities_expected_denied' => $crossA,
            'entidade_b_cross_entities_expected_denied' => $crossB,
            'admin_expected_all_entities' => count($ids),
        ];
    }

    private function count_entities(array $meta): int {
        $meta_query = ['relation' => 'AND'];
        foreach ($meta as $k => $v) {
            $meta_query[] = ['key' => $k, 'value' => $v];
        }
        $meta_query[] = ['key' => self::META_DEMO, 'value' => '1'];

        $q = new WP_Query([
            'post_type' => self::CPT,
            'post_status' => ['publish', 'draft'],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'meta_query' => $meta_query,
        ]);

        return (int) $q->found_posts;
    }

    private function count_mandato_window(array $days): int {
        $ids = get_posts([
            'post_type' => self::CPT,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => self::META_DEMO,
            'meta_value' => '1',
        ]);
        $count = 0;
        $today = strtotime(gmdate('Y-m-d'));
        foreach ($ids as $id) {
            $target = strtotime((string) get_post_meta($id, 'mandato_fim', true));
            if (! $target) {
                continue;
            }
            $diff = (int) floor(($target - $today) / DAY_IN_SECONDS);
            if (in_array($diff, $days, true)) {
                $count++;
            }
        }
        return $count;
    }

    private function ensure_demo_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        dbDelta("CREATE TABLE {$wpdb->prefix}rma_approvals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_id BIGINT UNSIGNED NOT NULL,
            step TINYINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            ip VARCHAR(45) NOT NULL,
            comment_text TEXT NULL,
            created_at DATETIME NOT NULL,
            is_demo TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY entity_id (entity_id)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}rma_dues (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            due_year SMALLINT UNSIGNED NOT NULL,
            status VARCHAR(40) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            is_demo TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY entity_id (entity_id)
        ) {$charset};");

        dbDelta("CREATE TABLE {$wpdb->prefix}rma_email_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_id BIGINT UNSIGNED NOT NULL,
            context VARCHAR(60) NOT NULL,
            recipient VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL,
            sent TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            is_demo TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY entity_id (entity_id)
        ) {$charset};");
    }

    private function insert_approval_row(int $entity_id, int $step, int $user_id): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'rma_approvals', [
            'entity_id' => $entity_id,
            'step' => $step,
            'user_id' => $user_id,
            'ip' => '10.0.0.' . (30 + $step),
            'comment_text' => 'Aprovação demo passo ' . $step,
            'created_at' => current_time('mysql', true),
            'is_demo' => 1,
        ]);
    }

    private function insert_due_row(int $entity_id, int $order_id, string $status, float $amount): void {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'rma_dues', [
            'entity_id' => $entity_id,
            'order_id' => $order_id,
            'due_year' => 2026,
            'status' => $status,
            'amount' => $amount,
            'created_at' => current_time('mysql', true),
            'is_demo' => 1,
        ]);
    }

    private function build_status_plan(int $total): array {
        $plan = array_merge(
            array_fill(0, 20, 'aprovado'),
            array_fill(0, 10, 'pendente'),
            array_fill(0, 5, 'recusado'),
            array_fill(0, 5, 'em_analise')
        );

        $remaining = max(0, $total - count($plan));
        $pool = ['pendente', 'aprovado', 'recusado', 'em_analise'];
        for ($i = 0; $i < $remaining; $i++) {
            $plan[] = $pool[array_rand($pool)];
        }

        return $plan;
    }

    private function pick_areas(): array {
        $areas = ['Meio Ambiente', 'Educação', 'Direitos Humanos', 'Cultura', 'Juventude', 'Saúde'];
        shuffle($areas);
        return array_slice($areas, 0, rand(1, 3));
    }

    private function city_pool(): array {
        return [
            ['uf' => 'SP', 'cidade' => 'São Paulo', 'lat' => -23.5505, 'lng' => -46.6333, 'cep' => '01001-000', 'logradouro' => 'Rua Líbero Badaró', 'bairro' => 'Centro'],
            ['uf' => 'RJ', 'cidade' => 'Rio de Janeiro', 'lat' => -22.9068, 'lng' => -43.1729, 'cep' => '20040-020', 'logradouro' => 'Rua da Assembleia', 'bairro' => 'Centro'],
            ['uf' => 'MG', 'cidade' => 'Belo Horizonte', 'lat' => -19.9167, 'lng' => -43.9345, 'cep' => '30130-010', 'logradouro' => 'Avenida Afonso Pena', 'bairro' => 'Centro'],
            ['uf' => 'ES', 'cidade' => 'Vitória', 'lat' => -20.3155, 'lng' => -40.3128, 'cep' => '29010-935', 'logradouro' => 'Avenida Marechal Mascarenhas', 'bairro' => 'Centro'],
            ['uf' => 'PR', 'cidade' => 'Curitiba', 'lat' => -25.4284, 'lng' => -49.2733, 'cep' => '80010-000', 'logradouro' => 'Rua XV de Novembro', 'bairro' => 'Centro'],
            ['uf' => 'SC', 'cidade' => 'Florianópolis', 'lat' => -27.5949, 'lng' => -48.5482, 'cep' => '88010-400', 'logradouro' => 'Rua Felipe Schmidt', 'bairro' => 'Centro'],
            ['uf' => 'RS', 'cidade' => 'Porto Alegre', 'lat' => -30.0346, 'lng' => -51.2177, 'cep' => '90010-150', 'logradouro' => 'Rua dos Andradas', 'bairro' => 'Centro'],
            ['uf' => 'BA', 'cidade' => 'Salvador', 'lat' => -12.9777, 'lng' => -38.5016, 'cep' => '40020-000', 'logradouro' => 'Rua Chile', 'bairro' => 'Centro'],
            ['uf' => 'PE', 'cidade' => 'Recife', 'lat' => -8.0476, 'lng' => -34.8770, 'cep' => '50030-230', 'logradouro' => 'Rua do Bom Jesus', 'bairro' => 'Recife'],
            ['uf' => 'CE', 'cidade' => 'Fortaleza', 'lat' => -3.7319, 'lng' => -38.5267, 'cep' => '60060-330', 'logradouro' => 'Avenida Santos Dumont', 'bairro' => 'Aldeota'],
        ];
    }

    private function generate_valid_cnpj(int $seed): string {
        $n = str_pad((string) ($seed % 99999999), 8, '0', STR_PAD_LEFT) . '0001';
        $d1 = $this->cnpj_digit($n, [5,4,3,2,9,8,7,6,5,4,3,2]);
        $d2 = $this->cnpj_digit($n . $d1, [6,5,4,3,2,9,8,7,6,5,4,3,2]);
        return $n . $d1 . $d2;
    }

    private function cnpj_digit(string $base, array $weights): int {
        $sum = 0;
        for ($i = 0; $i < count($weights); $i++) {
            $sum += ((int) $base[$i]) * $weights[$i];
        }
        $mod = $sum % 11;
        return $mod < 2 ? 0 : 11 - $mod;
    }
}

new RMA_Demo_Seeder();
