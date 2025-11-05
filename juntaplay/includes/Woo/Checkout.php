<?php
declare(strict_types=1);

namespace JuntaPlay\Woo;

use JuntaPlay\Admin\Settings;
use WC_Cart;
use WC_Fee;
use WC_Order;
use WC_Product;

use function add_action;
use function add_filter;
use function apply_filters;
use function esc_attr;
use function esc_html__;
use function get_current_user_id;
use function get_user_meta;
use function is_admin;
use function number_format;
use function sprintf;
use function woocommerce_quantity_input;

defined('ABSPATH') || exit;

class Checkout
{
    private const SESSION_DEPOSIT_KEY    = 'juntaplay_group_deposit';
    private const SESSION_PROCESSING_KEY = 'juntaplay_processing_fee';
    private const SESSION_WALLET_KEY     = 'juntaplay_wallet_used';

    private const PROCESSING_FEE_LABEL = 'Custos de Processamento';
    private const DEPOSIT_LABEL        = 'Caução';
    private const WALLET_LABEL         = 'Uso de créditos';

    public function init(): void
    {
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_group_fees']);
        add_filter('woocommerce_quantity_input_args', [$this, 'configure_quantity'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'persist_checkout_meta'], 10, 2);
        add_filter('woocommerce_cart_totals_fee_html', [$this, 'format_fee_amount'], 10, 2);
        add_filter('woocommerce_cart_totals_fee_label', [$this, 'format_fee_label'], 10, 2);
        add_filter('woocommerce_checkout_cart_item_quantity', [$this, 'render_checkout_quantity'], 10, 3);
    }

    public function apply_group_fees(WC_Cart $cart): void
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        $has_group_items = false;
        $group_total     = 0.0;

        foreach ($cart->get_cart() as $cart_item) {
            if (empty($cart_item['juntaplay_group'])) {
                continue;
            }

            $has_group_items = true;
            $price = isset($cart_item['juntaplay_group']['price'])
                ? (float) $cart_item['juntaplay_group']['price']
                : 0.0;

            $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product
                ? $cart_item['data']
                : null;

            if ($price <= 0 && $product) {
                $product_price = (float) $product->get_price();

                if ($product_price > 0) {
                    $price = $product_price;
                }
            }

            $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;
            $quantity = max(1, $quantity);

            $group_total += $price * $quantity;
        }

        if (!$has_group_items || $group_total <= 0) {
            $this->clear_session_totals();

            return;
        }

        $deposit_total = $group_total;
        $processing_fee = Settings::get_processing_fee();

        $session = WC()->session;

        if (!is_object($session) || (!method_exists($session, 'set') && !method_exists($session, '__set'))) {
            return;
        }

        $session->set(self::SESSION_DEPOSIT_KEY, $this->format_decimal($deposit_total));
        $session->set(self::SESSION_PROCESSING_KEY, $this->format_decimal($processing_fee));

        if ($deposit_total > 0) {
            $cart->add_fee(self::DEPOSIT_LABEL, $deposit_total, false);
        }

        if ($processing_fee > 0) {
            $cart->add_fee(self::PROCESSING_FEE_LABEL, $processing_fee, false);
        }

        $wallet_applied = 0.0;
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            $balance = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
            $balance = max(0.0, $balance);

            if ($balance > 0) {
                $estimated_total = $cart->get_cart_contents_total() + $deposit_total + $processing_fee;
                $estimated_total = apply_filters('juntaplay/checkout/estimated_total', $estimated_total, $cart);

                if ($estimated_total > 0) {
                    $wallet_applied = min($balance, $estimated_total);
                }
            }
        }

        if ($wallet_applied > 0) {
            $cart->add_fee(self::WALLET_LABEL, -$wallet_applied, false);
            if (method_exists($session, 'set')) {
                $session->set(self::SESSION_WALLET_KEY, $this->format_decimal($wallet_applied));
            }
        } elseif (method_exists($session, '__unset')) {
            $session->__unset(self::SESSION_WALLET_KEY);
        }
    }

    public function configure_quantity(array $args, $product): array
    {
        if (!$product || !$product->get_id()) {
            return $args;
        }

        if ($product->get_type() !== 'simple') {
            return $args;
        }

        $is_checkout_group = false;
        if (isset(WC()->cart)) {
            foreach (WC()->cart->get_cart() as $item) {
                if (!empty($item['juntaplay_group']) && $item['data']->get_id() === $product->get_id()) {
                    $is_checkout_group = true;
                    break;
                }
            }
        }

        if (!$is_checkout_group) {
            return $args;
        }

        $args['min_value'] = 1;
        $args['step']      = 1;
        $args['inputmode'] = 'numeric';
        $args['pattern']   = '[0-9]*';

        return $args;
    }

    public function render_checkout_quantity(string $quantity_html, array $cart_item, string $cart_item_key): string
    {
        if (empty($cart_item['juntaplay_group'])) {
            return $quantity_html;
        }

        $product = isset($cart_item['data']) ? $cart_item['data'] : null;

        if (!$product instanceof WC_Product) {
            return $quantity_html;
        }

        $current_value = isset($cart_item['quantity']) ? max(1, (int) $cart_item['quantity']) : 1;

        $input_name = sprintf('cart[%s][qty]', esc_attr($cart_item_key));

        return woocommerce_quantity_input(
            [
                'input_name'  => $input_name,
                'input_value' => $current_value,
                'min_value'   => 1,
                'step'        => 1,
                'classes'     => ['input-text', 'qty', 'text', 'juntaplay-group-quantity'],
            ],
            $product,
            false
        );
    }

    public function persist_checkout_meta(WC_Order $order, $data): void
    {
        $session = WC()->session;

        if (!$session) {
            return;
        }

        $deposit    = (float) $session->get(self::SESSION_DEPOSIT_KEY, 0.0);
        $processing = (float) $session->get(self::SESSION_PROCESSING_KEY, 0.0);
        $wallet     = (float) $session->get(self::SESSION_WALLET_KEY, 0.0);

        if ($deposit > 0) {
            $order->update_meta_data('_juntaplay_group_deposit', $this->format_decimal($deposit));
        }

        if ($processing > 0) {
            $order->update_meta_data('_juntaplay_processing_fee', $this->format_decimal($processing));
        }

        if ($wallet > 0) {
            $order->update_meta_data('_juntaplay_wallet_used', $this->format_decimal($wallet));
            $order->update_meta_data('_juntaplay_wallet_status', 'pending');
        }
    }

    public function format_fee_amount(string $html, $fee): string
    {
        if (!$fee instanceof WC_Fee) {
            return $html;
        }

        if ($this->is_processing_fee($fee)) {
            return '<small class="juntaplay-processing-fee-amount">' . $html . '</small>';
        }

        return $html;
    }

    public function format_fee_label(string $label, $fee): string
    {
        if (!$fee instanceof WC_Fee) {
            return $label;
        }

        if ($this->is_processing_fee($fee)) {
            return '<span class="juntaplay-processing-fee-label">' . esc_html__('Custos de Processamento', 'juntaplay') . '</span>';
        }

        if ($this->is_deposit_fee($fee)) {
            return '<strong class="juntaplay-deposit-label">' . esc_html__('Caução', 'juntaplay') . '</strong>';
        }

        if ($this->is_wallet_fee($fee)) {
            return '<span class="juntaplay-wallet-label">' . esc_html__('Uso de créditos', 'juntaplay') . '</span>';
        }

        return $label;
    }

    private function clear_session_totals(): void
    {
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        $session = WC()->session;

        if (!is_object($session) || !method_exists($session, '__unset')) {
            return;
        }

        $session->__unset(self::SESSION_DEPOSIT_KEY);
        $session->__unset(self::SESSION_PROCESSING_KEY);
        $session->__unset(self::SESSION_WALLET_KEY);
    }

    private function is_processing_fee(WC_Fee $fee): bool
    {
        return $fee->name === self::PROCESSING_FEE_LABEL;
    }

    private function is_deposit_fee(WC_Fee $fee): bool
    {
        return $fee->name === self::DEPOSIT_LABEL;
    }

    private function is_wallet_fee(WC_Fee $fee): bool
    {
        return $fee->name === self::WALLET_LABEL;
    }

    private function format_decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
