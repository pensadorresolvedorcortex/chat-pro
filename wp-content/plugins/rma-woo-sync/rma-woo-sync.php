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

function rma_is_checkout_mode(): bool {
    if (! function_exists('is_checkout') || ! is_checkout()) {
        return false;
    }

    return rma_contains_annual_dues_product();
}

add_filter('body_class', function (array $classes): array {
    if (rma_is_checkout_mode()) {
        $classes[] = 'rma-checkout-mode';
    }

    return $classes;
});

add_filter('woocommerce_available_payment_gateways', function (array $gateways): array {
    if (! rma_is_checkout_mode()) {
        return $gateways;
    }

    if (isset($gateways['rma_pix'])) {
        return ['rma_pix' => $gateways['rma_pix']];
    }

    return $gateways;
});

add_filter('wc_add_to_cart_message_html', function (string $message): string {
    if (rma_is_checkout_mode()) {
        return '';
    }

    return $message;
}, 10, 1);

add_action('woocommerce_before_checkout_form', function (): void {
    if (! rma_is_checkout_mode() || ! function_exists('wc_get_notices')) {
        return;
    }

    $success = wc_get_notices('success');
    if (! empty($success)) {
        wc_clear_notices();
    }
}, 1);

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

function rma_get_current_user_entity_id(): int {
    $user_id = get_current_user_id();
    if ($user_id <= 0) {
        return 0;
    }

    if (function_exists('rma_get_entity_id_by_author')) {
        return (int) rma_get_entity_id_by_author($user_id);
    }

    $entity_id = (int) get_posts([
        'post_type' => 'rma_entidade',
        'post_status' => ['publish', 'draft'],
        'author' => $user_id,
        'fields' => 'ids',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
    ])[0] ?? 0;

    return max(0, $entity_id);
}

add_action('woocommerce_checkout_process', function (): void {
    if (! rma_contains_annual_dues_product()) {
        return;
    }

    if (rma_get_current_user_entity_id() <= 0) {
        wc_add_notice('Não foi possível vincular o pagamento a uma entidade RMA. Faça login com a conta da entidade e tente novamente.', 'error');
    }
});

add_action('woocommerce_checkout_create_order', function (WC_Order $order): void {
    if (! rma_contains_annual_dues_product()) {
        return;
    }

    $entity_id = rma_get_current_user_entity_id();
    if ($entity_id > 0) {
        $order->update_meta_data('rma_entity_id', $entity_id);
    }

    $order->update_meta_data('rma_is_annual_due', '1');
    $order->update_meta_data('rma_due_year', gmdate('Y'));
}, 20);

add_action('wp_enqueue_scripts', function (): void {
    if (! function_exists('is_checkout') || ! is_checkout()) {
        return;
    }
    if (! rma_contains_annual_dues_product()) {
        return;
    }

    wp_register_style('rma-woo-checkout-premium', false, [], '1.1.0');
    wp_enqueue_style('rma-woo-checkout-premium');
    wp_add_inline_style('rma-woo-checkout-premium', '
        .woocommerce-checkout{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;background:linear-gradient(180deg,#f8fafc 0%,#eef6f2 100%);padding:24px;border-radius:24px}.rma-checkout-mode .woocommerce-notices-wrapper{display:none!important}.rma-checkout-mode .woocommerce-message .button,.rma-checkout-mode a.wc-forward{display:none!important}.rma-checkout-mode .woocommerce form.checkout{max-width:1100px!important;margin:0 auto!important;display:block!important}.rma-checkout-mode .woocommerce-checkout .col2-set,.rma-checkout-mode .woocommerce-checkout #customer_details{display:none!important;height:0!important;margin:0!important;padding:0!important;overflow:hidden!important}.rma-checkout-mode .woocommerce-checkout #order_review_heading{display:none!important}.rma-checkout-mode .woocommerce-checkout #order_review,.rma-checkout-mode .woocommerce-checkout .woocommerce-checkout-review-order,.rma-checkout-mode .woocommerce-checkout .woocommerce-checkout-review-order-table{float:none!important;width:100%!important;max-width:980px!important;margin:0 auto!important;display:block!important}
        .woocommerce-checkout .col2-set,.woocommerce-checkout #customer_details{display:none!important}
        .woocommerce-checkout #order_review_heading{display:none!important}.woocommerce-checkout #order_review{float:none!important;width:100%!important;max-width:100%!important;margin:0 auto!important}
        .woocommerce-checkout #order_review{display:grid;gap:16px}
        .woocommerce-checkout #payment{background:#ffffff;border:1px solid #e5e7eb;border-radius:20px;padding:26px;box-shadow:0 16px 40px rgba(15,23,42,.09);width:100%!important;max-width:100%!important;margin:0 auto!important}
        .woocommerce-checkout h3,.woocommerce-checkout h2{color:#1f2937;letter-spacing:-.02em}
        .woocommerce-checkout-review-order-table{background:#fff;border:1px solid #e5e7eb;border-radius:16px;overflow:hidden;box-shadow:0 10px 26px rgba(15,23,42,.05)}
        .woocommerce-checkout-payment ul.payment_methods{padding:0!important;border:none!important;background:transparent!important}
        .woocommerce-checkout-payment .payment_method_rma_pix>label{font-weight:800;color:#1f2937;font-size:1.05rem}
        .rma-pix-card{background:#fff;border:1px solid #d9e3dc;border-radius:20px;padding:24px;box-shadow:0 14px 34px rgba(15,23,42,.08);margin:8px auto 0;max-width:1040px}
        .rma-pix-hero{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}
        .rma-pix-title{font-size:1.7rem;line-height:1.2;margin:0;color:#1f2937}
        .rma-pix-subtitle{margin:0;color:#4b5563}
        .rma-pix-badge{background:rgba(93,218,187,.16);color:#0f766e;border:1px solid rgba(15,118,110,.18);border-radius:999px;padding:6px 12px;font-size:.78rem;font-weight:700;white-space:nowrap}
        .rma-pix-grid{display:grid;grid-template-columns:minmax(380px,1.25fr) minmax(260px,.75fr);gap:18px;margin-top:16px;align-items:start}
        .rma-pix-panel{background:#f9fbfd;border:1px solid #e5e7eb;border-radius:16px;padding:16px}
        .rma-pix-panel h4{margin:0 0 8px;color:#1f2937;font-size:1.02rem}
        .rma-pix-qr-wrap{text-align:center}
        .rma-pix-qr{max-width:340px;width:100%;background:#fff;border:10px solid #fff;box-shadow:0 14px 30px rgba(15,23,42,.16);border-radius:14px}
        .rma-pix-copy{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;color:#111827;background:#fff;font-size:.92rem;margin:8px 0 10px;min-height:92px;resize:vertical;line-height:1.35}
        .rma-pix-copy-btn{background:linear-gradient(135deg,#7bad39,#5ddabb);color:#fff;border:none;border-radius:12px;padding:12px 16px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
        .rma-pix-copy-btn:hover{filter:brightness(1.03);transform:translateY(-1px)}
        .rma-pix-copy-status{font-size:.9rem;color:#047857;margin-top:8px;display:none}
        .rma-pix-copy-status.is-visible{display:block}
        .rma-pix-steps{margin:12px 0 0;padding-left:18px;color:#4b5563}
        .rma-pix-steps li{margin:6px 0}
        .rma-pix-warning{font-size:.9rem;color:#6b7280;margin-top:10px}
        .rma-pix-qr-fallback{display:none;color:#4b5563;font-size:.9rem;margin-top:8px}
        .rma-pix-qr-fallback.is-visible{display:block}
        @media (max-width:900px){.rma-pix-grid{grid-template-columns:1fr}.rma-pix-hero{flex-direction:column;align-items:flex-start}.rma-pix-title{font-size:1.45rem}.rma-pix-card{padding:18px}.woocommerce-checkout{padding:12px}}
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

            $cart_total = function_exists('WC') && WC()->cart ? (float) WC()->cart->total : 0;
            $payload = rma_pix_build_payload($pix_key, (string) $cart_total, 'RMA-CHECKOUT');
            $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&data=' . rawurlencode($payload);

            echo '<div class="rma-pix-card">';
            echo '<div class="rma-pix-hero">';
            echo '<div>';
            echo '<h3 class="rma-pix-title">Pagamento da Anuidade RMA</h3>';
            echo '<p class="rma-pix-subtitle">Finalize sua filiação com segurança.</p>';
            echo '</div>';
            echo '<span class="rma-pix-badge">Pagamento via PIX • Compensação conforme seu banco</span>';
            echo '</div>';
            echo '<p style="color:#4b5563;margin:0">Para concluir sua filiação, realize o pagamento via PIX. O acesso será ativado automaticamente após a compensação.</p>';
            echo '<div style="margin-top:12px;padding:10px 12px;border-radius:10px;background:#edf9ec;color:#166534;font-weight:600;">Anuidade adicionada. Finalize o pagamento via PIX para concluir sua filiação.</div>';
            echo '<div class="rma-pix-grid">';
            echo '<div class="rma-pix-panel">';
            echo '<h4>Escaneie o QR Code</h4>';
            echo '<div class="rma-pix-qr-wrap">';
            echo '<img class="rma-pix-qr" src="' . esc_url($qr_url) . '" alt="QR Code PIX" onerror="var n=this.nextElementSibling;this.style.display=\'none\';if(n){n.classList.add(\'is-visible\');}" />';
            echo '<div class="rma-pix-qr-fallback">Não foi possível carregar o QR Code agora. Use o código copia e cola abaixo.</div>';
            echo '</div>';
            echo '<ol class="rma-pix-steps"><li>Abra o app do seu banco</li><li>Escolha PIX > QR Code ou Copiar e Colar</li><li>Confirme o pagamento e finalize o pedido</li></ol>';
            echo '</div>';
            echo '<div class="rma-pix-panel">';
            echo '<h4>Resumo do pedido</h4>';
            echo '<p style="color:#4b5563;margin:0 0 6px">Total da anuidade: <strong style="color:#1f2937">' . wp_kses_post(wc_price($cart_total)) . '</strong></p>';
            echo '<textarea readonly class="rma-pix-copy" id="rma-pix-copy-code">' . esc_textarea($payload) . '</textarea>';
            echo '<button type="button" class="rma-pix-copy-btn" id="rma-pix-copy-btn">Copiar código PIX</button>';
            echo '<div class="rma-pix-copy-status" id="rma-pix-copy-status">Código PIX copiado com sucesso.</div>';
            echo '<p class="rma-pix-warning">O prazo de compensação pode variar conforme o banco.</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<script>(function(){var b=document.getElementById("rma-pix-copy-btn");var i=document.getElementById("rma-pix-copy-code");var s=document.getElementById("rma-pix-copy-status");if(!b||!i){return;}b.addEventListener("click",function(){var v=i.value||"";if(!v){return;}if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(v).then(function(){s&&s.classList.add("is-visible");});return;}i.focus();i.select();i.setSelectionRange(0,99999);try{document.execCommand("copy");s&&s.classList.add("is-visible");}catch(e){}});})();</script>';
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            if (! $order) {
                return ['result' => 'fail'];
            }

            $entity_id = (int) $order->get_meta('rma_entity_id');
            if ($entity_id <= 0) {
                $entity_id = rma_get_current_user_entity_id();
                if ($entity_id > 0) {
                    $order->update_meta_data('rma_entity_id', $entity_id);
                    $order->save();
                }
            }

            $order->update_status('on-hold', 'Aguardando pagamento PIX da Anuidade RMA.');
            $order->add_order_note('Pedido criado via PIX institucional RMA.');
            wc_reduce_stock_levels($order_id);
            if (function_exists('WC') && WC()->cart) {
                WC()->cart->empty_cart();
            }

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
    echo '<h2 style="margin-top:0;color:#1f2937">Pagamento da Anuidade RMA</h2>';
    echo '<p style="color:#4b5563">Seu pedido está em <strong>aguardando pagamento</strong>. Escaneie o QR Code ou copie o código PIX abaixo.</p>';
    echo '<img class="rma-pix-qr" src="' . esc_url($qr_url) . '" alt="QR Code PIX do pedido" onerror="this.style.display=\'none\'" />';
    echo '<textarea readonly class="rma-pix-copy" id="rma-pix-order-copy-code">' . esc_textarea($payload) . '</textarea>';
    echo '<button type="button" class="rma-pix-copy-btn" id="rma-pix-order-copy-btn">Copiar código PIX</button>';
    echo '<div class="rma-pix-copy-status" id="rma-pix-order-copy-status">Copiado com sucesso.</div>';
    echo '<p style="color:#6b7280;font-size:.9rem">Assim que houver compensação, seu acesso RMA será liberado automaticamente.</p>';
    echo '</section>';
    echo '<script>(function(){var b=document.getElementById("rma-pix-order-copy-btn");var i=document.getElementById("rma-pix-order-copy-code");var s=document.getElementById("rma-pix-order-copy-status");if(!b||!i){return;}b.addEventListener("click",function(){var v=i.value||"";if(!v){return;}if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(v).then(function(){s&&s.classList.add("is-visible");});return;}i.select();i.setSelectionRange(0,99999);document.execCommand("copy");s&&s.classList.add("is-visible");});})();</script>';
});

register_deactivation_hook(__FILE__, ['RMA_Woo_Sync', 'deactivate']);
new RMA_Woo_Sync();
