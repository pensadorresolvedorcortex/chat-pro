<?php
/**
 * Plugin Name: RMA Woo Sync
 * Description: Sincroniza status financeiro da entidade com pedidos WooCommerce (anuidade via PIX).
 * Version: 0.7.0
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Woo_Sync {
    private const CPT = 'rma_entidade';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);

        add_action('woocommerce_order_status_completed', [$this, 'mark_adimplente']);
        add_action('woocommerce_order_status_processing', [$this, 'mark_adimplente']);
        add_action('woocommerce_order_status_failed', [$this, 'mark_inadimplente']);
        add_action('woocommerce_order_status_cancelled', [$this, 'mark_inadimplente']);
        add_action('woocommerce_order_status_refunded', [$this, 'mark_inadimplente']);

        add_action('init', [$this, 'schedule_annual_dues_cron']);
        add_action('rma_generate_annual_dues', [$this, 'generate_annual_dues']);
    }

    public function register_routes(): void {
        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/finance/payment-status', [
            'methods' => 'GET',
            'callback' => [$this, 'payment_status'],
            'permission_callback' => [$this, 'can_view_entity_finance'],
        ]);
    }

    public function can_view_entity_finance(WP_REST_Request $request): bool {
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

    public function payment_status(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $finance_status = (string) get_post_meta($entity_id, 'finance_status', true);
        $is_paid = $finance_status === 'adimplente';
        $latest_order = $this->get_latest_entity_order($entity_id);

        $payload = [
            'entity_id' => $entity_id,
            'finance_status' => $finance_status,
            'is_paid' => $is_paid,
            'should_redirect' => $is_paid,
            'redirect_url' => wc_get_page_permalink('myaccount'),
            'latest_order' => $latest_order,
        ];

        return new WP_REST_Response($payload);
    }

    public function mark_adimplente(int $order_id): void {
        $this->sync_financial_status($order_id, 'adimplente');
    }

    public function mark_inadimplente(int $order_id): void {
        $this->sync_financial_status($order_id, 'inadimplente');
    }

    private function sync_financial_status(int $order_id, string $target_status): void {
        if (! function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (! $order) {
            return;
        }

        $entity_id = (int) $order->get_meta('rma_entity_id');
        if ($entity_id <= 0 || get_post_type($entity_id) !== self::CPT) {
            return;
        }

        update_post_meta($entity_id, 'finance_status', $target_status);

        $history = get_post_meta($entity_id, 'finance_history', true);
        $history = is_array($history) ? $history : [];

        $event_key = $order_id . '|' . $order->get_status() . '|' . $target_status;
        $is_duplicate = false;
        foreach ($history as $event) {
            $existing_key = (int) ($event['order_id'] ?? 0) . '|' . (string) ($event['status'] ?? '') . '|' . (string) ($event['finance_status'] ?? '');
            if ($existing_key === $event_key) {
                $is_duplicate = true;
                break;
            }
        }

        if (! $is_duplicate) {
            $paid_date = $order->get_date_paid();
            $year = $paid_date ? $paid_date->date('Y') : gmdate('Y');

            $history[] = [
                'order_id' => $order_id,
                'year' => $year,
                'status' => $order->get_status(),
                'finance_status' => $target_status,
                'total' => (float) $order->get_total(),
                'paid_at' => current_time('mysql', true),
            ];

            $max_history = 500;
            if (count($history) > $max_history) {
                $history = array_slice($history, -1 * $max_history);
            }

            update_post_meta($entity_id, 'finance_history', $history);
        }

        do_action('rma/entity_finance_updated', $entity_id, $order_id, $history);
    }

    public function schedule_annual_dues_cron(): void {
        if (! wp_next_scheduled('rma_generate_annual_dues')) {
            wp_schedule_event(time() + 10 * MINUTE_IN_SECONDS, 'daily', 'rma_generate_annual_dues');
        }
    }

    public function generate_annual_dues(): void {
        if (! function_exists('wc_create_order')) {
            return;
        }

        $year = gmdate('Y');
        if (! $this->is_due_cycle_open()) {
            return;
        }

        $product_id = absint((int) get_option('rma_annual_dues_product_id', 0));
        $annual_value = (float) get_option('rma_annual_due_value', '0');

        $query = new WP_Query([
            'post_type' => self::CPT,
            'post_status' => ['publish', 'draft'],
            'posts_per_page' => 500,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'governance_status',
                    'value' => 'aprovado',
                ],
            ],
        ]);

        $created = 0;
        foreach ($query->posts as $entity_id) {
            $entity_id = (int) $entity_id;
            if ($this->has_due_order_for_year($entity_id, $year)) {
                continue;
            }

            $author_id = (int) get_post_field('post_author', $entity_id);
            $customer_id = $author_id > 0 ? $author_id : 0;

            $order = wc_create_order(['customer_id' => $customer_id]);
            if (is_wp_error($order) || ! $order) {
                continue;
            }

            $has_item = false;
            if ($product_id > 0) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $order->add_product($product, 1);
                    $has_item = true;
                }
            }

            if (! $has_item && $annual_value > 0) {
                $fee = new WC_Order_Item_Fee();
                $fee->set_name('Anuidade RMA ' . $year);
                $fee->set_amount($annual_value);
                $fee->set_total($annual_value);
                $order->add_item($fee);
            }

            $order->update_meta_data('rma_entity_id', $entity_id);
            $order->update_meta_data('rma_due_year', $year);
            $order->update_meta_data('rma_is_annual_due', '1');
            $order->calculate_totals();
            $order->save();

            update_post_meta($entity_id, 'finance_status', 'inadimplente');
            $created++;
        }

        wp_reset_postdata();
        update_option('rma_annual_dues_last_run', [
            'year' => $year,
            'created_orders' => $created,
            'ran_at' => current_time('mysql', true),
        ], false);
    }

    private function is_due_cycle_open(): bool {
        $day_month = (string) get_option('rma_due_day_month', '01-01');
        if (! preg_match('/^(\d{2})-(\d{2})$/', $day_month, $matches)) {
            $day_month = '01-01';
            $matches = ['01-01', '01', '01'];
        }

        $day = (int) $matches[1];
        $month = (int) $matches[2];
        if (! checkdate($month, $day, 2024)) {
            $day_month = '01-01';
        }

        $current_md = gmdate('m-d');
        $target_md = substr($day_month, 3, 2) . '-' . substr($day_month, 0, 2);

        return $current_md >= $target_md;
    }

    private function has_due_order_for_year(int $entity_id, string $year): bool {
        if (! function_exists('wc_get_orders')) {
            return false;
        }

        $orders = wc_get_orders([
            'limit' => 1,
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => 'rma_entity_id',
                    'value' => $entity_id,
                ],
                [
                    'key' => 'rma_due_year',
                    'value' => $year,
                ],
            ],
            'status' => ['pending', 'on-hold', 'processing', 'completed', 'cancelled', 'failed', 'refunded'],
        ]);

        return ! empty($orders);
    }

    private function get_latest_entity_order(int $entity_id): array {
        if (! function_exists('wc_get_orders')) {
            return [];
        }

        $orders = wc_get_orders([
            'limit' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => 'rma_entity_id',
            'meta_value' => $entity_id,
            'status' => ['pending', 'on-hold', 'processing', 'completed', 'cancelled', 'failed', 'refunded'],
        ]);

        if (empty($orders) || ! is_array($orders)) {
            return [];
        }

        $order = $orders[0];
        if (! $order instanceof WC_Order) {
            return [];
        }

        return [
            'order_id' => (int) $order->get_id(),
            'status' => (string) $order->get_status(),
            'total' => (float) $order->get_total(),
            'pay_url' => $order->get_checkout_payment_url(),
            'due_year' => (string) $order->get_meta('rma_due_year'),
        ];
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('rma_generate_annual_dues');
    }
}

function rma_contains_annual_dues_product(): bool {
    if (! function_exists('WC') || ! WC()->cart) {
        return false;
    }

    $dues_product_id = (int) get_option('rma_annual_dues_product_id', 0);
    if ($dues_product_id <= 0) {
        $dues_product_id = (int) get_option('rma_woo_product_id', 0);
    }
    if ($dues_product_id <= 0) {
        $dues_product_id = 3407;
    }

    foreach (WC()->cart->get_cart() as $item) {
        $product_id = (int) ($item['product_id'] ?? 0);
        if ($product_id === $dues_product_id) {
            return true;
        }
    }

    return false;
}

function rma_pix_build_payload(string $pix_key, string $amount, string $txid): string {
    $pix_key = preg_replace('/\s+/', '', sanitize_text_field($pix_key));
    $amount = number_format(max(0, (float) $amount), 2, '.', '');
    $txid = strtoupper(preg_replace('/[^A-Z0-9]/', '', sanitize_text_field($txid)));
    if ($txid === '') {
        $txid = 'RMA';
    }
    $txid = substr($txid, 0, 25);

    $merchant = 'RMA';
    $city = 'SAO PAULO';

    $gui = '0014BR.GOV.BCB.PIX';
    $keyField = sprintf('%02d%s', strlen($pix_key), $pix_key);
    $merchantAccount = '26' . sprintf('%02d%s', strlen($gui . '01' . $keyField), $gui . '01' . $keyField);

    $payload = '000201'; // payload format indicator
    $payload .= $merchantAccount;
    $payload .= '52040000';
    $payload .= '5303986'; // BRL
    $payload .= '54' . sprintf('%02d%s', strlen($amount), $amount);
    $payload .= '5802BR';
    $payload .= '59' . sprintf('%02d%s', strlen($merchant), $merchant);
    $payload .= '60' . sprintf('%02d%s', strlen($city), $city);
    $tx = '05' . sprintf('%02d%s', strlen($txid), $txid);
    $payload .= '62' . sprintf('%02d%s', strlen($tx), $tx);
    $payload .= '6304';

    $crc = strtoupper(dechex(rma_pix_crc16($payload)));
    $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);

    return $payload . $crc;
}

function rma_pix_crc16(string $payload): int {
    $polynomial = 0x1021;
    $result = 0xFFFF;
    $len = strlen($payload);

    for ($i = 0; $i < $len; $i++) {
        $result ^= (ord($payload[$i]) << 8);
        for ($bit = 0; $bit < 8; $bit++) {
            $result = ($result & 0x8000) ? (($result << 1) ^ $polynomial) : ($result << 1);
            $result &= 0xFFFF;
        }
    }

    return $result;
}

add_filter('woocommerce_checkout_fields', function (array $fields): array {
    if (! rma_contains_annual_dues_product()) {
        return $fields;
    }

    $fields['billing'] = [];
    $fields['shipping'] = [];

    if (isset($fields['order']['order_comments'])) {
        unset($fields['order']['order_comments']);
    }

    return $fields;
}, 999);

add_filter('woocommerce_enable_order_notes_field', function ($enabled) {
    if (rma_contains_annual_dues_product()) {
        return false;
    }
    return $enabled;
});

add_filter('woocommerce_cart_needs_shipping', function ($needs_shipping) {
    if (rma_contains_annual_dues_product()) {
        return false;
    }
    return $needs_shipping;
});

add_action('wp_enqueue_scripts', function (): void {
    if (! function_exists('is_checkout') || ! is_checkout()) {
        return;
    }
    if (! rma_contains_annual_dues_product()) {
        return;
    }

    wp_register_style('rma-woo-checkout-premium', false, [], '1.0.0');
    wp_enqueue_style('rma-woo-checkout-premium');
    wp_add_inline_style('rma-woo-checkout-premium', '
        .woocommerce-checkout{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:#f8fafc;padding:20px;border-radius:18px}
        .woocommerce-checkout #customer_details{display:none!important}
        .woocommerce-checkout #order_review,.woocommerce-checkout #payment{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:22px;box-shadow:0 10px 26px rgba(15,23,42,.06)}
        .woocommerce-checkout h3,.woocommerce-checkout h2{color:#1f2937}
        .woocommerce-checkout .payment_method_rma_pix label{font-weight:700;color:#1f2937}
        .rma-pix-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px;box-shadow:0 8px 20px rgba(15,23,42,.05);margin-bottom:14px}
        .rma-pix-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .rma-pix-copy{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:10px;color:#111827;background:#f8fafc}
        .rma-pix-copy-btn{background:linear-gradient(135deg,#7bad39,#5ddabb);color:#fff;border:none;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer}
        .rma-pix-copy-status{font-size:.88rem;color:#047857;margin-top:6px;display:none}
        .rma-pix-copy-status.is-visible{display:block}
        .rma-pix-qr{max-width:260px;border:8px solid #fff;box-shadow:0 8px 20px rgba(15,23,42,.12);border-radius:12px}
        @media (max-width:760px){.rma-pix-grid{grid-template-columns:1fr}}
    ');
}, 30);

add_filter('woocommerce_payment_gateways', function (array $gateways): array {
    if (class_exists('WC_Payment_Gateway')) {
        $gateways[] = 'RMA_WC_Gateway_PIX';
    }

    return $gateways;
});

add_action('plugins_loaded', function (): void {
    if (! class_exists('WC_Payment_Gateway')) {
        return;
    }

    class RMA_WC_Gateway_PIX extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'rma_pix';
            $this->method_title = 'PIX RMA';
            $this->method_description = 'Pagamento institucional via PIX para Anuidade RMA.';
            $this->has_fields = true;
            $this->title = 'PIX institucional RMA';
            $this->description = 'Finalize sua filiação com segurança via PIX.';
            $this->supports = ['products'];
            $this->enabled = 'yes';
        }

        public function is_available() {
            if (! parent::is_available()) {
                return false;
            }

            if (! rma_contains_annual_dues_product()) {
                return false;
            }

            $pix_key = (string) get_option('rma_pix_key', '');
            return $pix_key !== '';
        }

        public function payment_fields() {
            $pix_key = (string) get_option('rma_pix_key', '');
            if ($pix_key === '') {
                echo '<p><strong>Pagamento PIX indisponível no momento.</strong> Entre em contato com a equipe RMA.</p>';
                return;
            }

            $total = function_exists('WC') && WC()->cart ? (float) WC()->cart->get_total('edit') : 0;
            $payload = rma_pix_build_payload($pix_key, (string) $total, 'RMA-CHECKOUT');
            $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&data=' . rawurlencode($payload);

            echo '<div class="rma-pix-card">';
            echo '<h3>Pagamento da Anuidade RMA</h3>';
            echo '<p style="color:#4b5563;margin-top:0">Para concluir sua filiação, realize o pagamento via PIX. O acesso será ativado automaticamente após a compensação.</p>';
            echo '<div class="rma-pix-grid">';
            echo '<div>';
            echo '<img class="rma-pix-qr" src="' . esc_url($qr_url) . '" alt="QR Code PIX" />';
            echo '<ol style="color:#4b5563"><li>Abra o app do seu banco</li><li>Escolha PIX > QR Code ou Copiar e Colar</li><li>Confirme o pagamento e finalize o pedido</li></ol>';
            echo '</div>';
            echo '<div>';
            echo '<p><strong>Resumo do pedido</strong></p>';
            echo '<p style="color:#4b5563">Pagamento via PIX • Compensação conforme seu banco</p>';
            echo '<input readonly class="rma-pix-copy" id="rma-pix-copy-code" value="' . esc_attr($payload) . '" />';
            echo '<button type="button" class="rma-pix-copy-btn" id="rma-pix-copy-btn">Copiar código PIX</button>';
            echo '<div class="rma-pix-copy-status" id="rma-pix-copy-status">Copiado com sucesso.</div>';
            echo '<p style="color:#6b7280;font-size:.9rem">O prazo de compensação pode variar conforme o banco.</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<script>(function(){var b=document.getElementById("rma-pix-copy-btn");var i=document.getElementById("rma-pix-copy-code");var s=document.getElementById("rma-pix-copy-status");if(!b||!i){return;}b.addEventListener("click",function(){i.select();i.setSelectionRange(0,99999);var ok=false;try{ok=document.execCommand("copy");}catch(e){}if(navigator.clipboard){navigator.clipboard.writeText(i.value).then(function(){s&&s.classList.add("is-visible");});return;}if(ok&&s){s.classList.add("is-visible");}});})();</script>';
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            if (! $order) {
                return ['result' => 'fail'];
            }

            $order->update_status('on-hold', 'Aguardando pagamento PIX da Anuidade RMA.');
            $order->add_order_note('Pedido criado via PIX institucional RMA.');
            wc_reduce_stock_levels($order_id);
            WC()->cart->empty_cart();

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }
    }
});

add_action('woocommerce_thankyou_rma_pix', function (int $order_id): void {
    $order = wc_get_order($order_id);
    if (! $order) {
        return;
    }

    $pix_key = (string) get_option('rma_pix_key', '');
    if ($pix_key === '') {
        wc_print_notice('Pagamento PIX indisponível no momento. Entre em contato com a equipe RMA.', 'notice');
        return;
    }

    $total = (string) $order->get_total();
    $payload = rma_pix_build_payload($pix_key, $total, 'RMAORDER' . $order->get_id());
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&data=' . rawurlencode($payload);

    echo '<section class="rma-pix-card" style="margin-top:20px">';
    echo '<h2 style="margin-top:0">Pagamento da Anuidade RMA</h2>';
    echo '<p style="color:#4b5563">Seu pedido está em <strong>aguardando pagamento</strong>. Escaneie o QR Code ou copie o código PIX abaixo.</p>';
    echo '<img class="rma-pix-qr" src="' . esc_url($qr_url) . '" alt="QR Code PIX do pedido" />';
    echo '<input readonly class="rma-pix-copy" id="rma-pix-order-copy-code" value="' . esc_attr($payload) . '" />';
    echo '<button type="button" class="rma-pix-copy-btn" id="rma-pix-order-copy-btn">Copiar código PIX</button>';
    echo '<div class="rma-pix-copy-status" id="rma-pix-order-copy-status">Copiado com sucesso.</div>';
    echo '<p style="color:#6b7280;font-size:.9rem">Assim que houver compensação, seu acesso RMA será liberado automaticamente.</p>';
    echo '</section>';
    echo '<script>(function(){var b=document.getElementById("rma-pix-order-copy-btn");var i=document.getElementById("rma-pix-order-copy-code");var s=document.getElementById("rma-pix-order-copy-status");if(!b||!i){return;}b.addEventListener("click",function(){if(navigator.clipboard){navigator.clipboard.writeText(i.value).then(function(){s&&s.classList.add("is-visible");});return;}i.select();i.setSelectionRange(0,99999);document.execCommand("copy");s&&s.classList.add("is-visible");});})();</script>';
});

register_deactivation_hook(__FILE__, ['RMA_Woo_Sync', 'deactivate']);
new RMA_Woo_Sync();
