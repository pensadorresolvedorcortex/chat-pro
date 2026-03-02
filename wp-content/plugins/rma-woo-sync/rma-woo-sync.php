<?php
/**
 * Plugin Name: RMA Woo Sync
 * Description: Sincroniza status financeiro da entidade com pedidos WooCommerce (anuidade via PIX).
 * Version: 0.6.0
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

register_deactivation_hook(__FILE__, ['RMA_Woo_Sync', 'deactivate']);
new RMA_Woo_Sync();
