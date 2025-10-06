<?php
declare(strict_types=1);

namespace JuntaPlay\Woo;

use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use JuntaPlay\Admin\Settings;
use JuntaPlay\Data\CreditTransactions;
use JuntaPlay\Data\Quotas;

use function absint;
use function add_action;
use function add_filter;
use function array_map;
use function current_time;
use function get_option;
use function get_current_user_id;
use function get_user_meta;
use function implode;
use function in_array;
use function is_admin;
use function is_array;
use function is_numeric;
use function sanitize_text_field;
use function sprintf;
use function str_replace;
use function update_user_meta;
use function wc_add_notice;
use function wc_get_order;
use function wc_get_product;
use function wc_price;
use function wp_unslash;
use function __;

defined('ABSPATH') || exit;

class Hooks
{
    public function init(): void
    {
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 6);
        add_filter('woocommerce_add_cart_item_data', [$this, 'store_cart_data'], 10, 3);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_meta_to_item'], 10, 4);
        add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 10, 4);
        add_action('woocommerce_before_calculate_totals', [$this, 'enforce_price']);
    }

    private array $pending_item = [];

    public function validate_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = [], $cart_item_data = [])
    {
        $product = wc_get_product($product_id);

        if ($product instanceof WC_Product && $product->is_type('juntaplay_credit_topup')) {
            $amount = 0.0;

            if (isset($cart_item_data['juntaplay_deposit']) && is_array($cart_item_data['juntaplay_deposit'])) {
                $amount = isset($cart_item_data['juntaplay_deposit']['amount']) && is_numeric($cart_item_data['juntaplay_deposit']['amount'])
                    ? (float) $cart_item_data['juntaplay_deposit']['amount']
                    : 0.0;
            } elseif (isset($_POST['jp_deposit_amount'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $amount = (float) sanitize_text_field(wp_unslash($_POST['jp_deposit_amount'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            }

            if ($amount <= 0) {
                wc_add_notice(__('Informe um valor válido para a recarga de créditos.', 'juntaplay'), 'error');

                return false;
            }

            return $passed;
        }

        $pool_id = absint($_POST['jp_pool_id'] ?? 0);
        $numbers = array_map('intval', $_POST['jp_numbers'] ?? []);
        $user_id = get_current_user_id() ?: 0;

        if (!$pool_id || empty($numbers)) {
            wc_add_notice(__('Selecione pelo menos uma cota.', 'juntaplay'), 'error');

            return false;
        }

        $settings = get_option(Settings::OPTION_RESERVE, ['minutes' => 15]);
        $minutes  = (int) ($settings['minutes'] ?? 15);

        $reserved = Quotas::reserve($pool_id, $numbers, $user_id, $minutes);

        if (count($reserved) !== count($numbers)) {
            wc_add_notice(__('Algumas cotas escolhidas não estão mais disponíveis.', 'juntaplay'), 'error');

            return false;
        }

        $this->pending_item = ['pool_id' => $pool_id, 'numbers' => $numbers];

        return $passed;
    }

    public function store_cart_data($cart_item_data, $product_id, $variation_id)
    {
        if (!empty($cart_item_data['juntaplay_deposit'])) {
            return $cart_item_data;
        }

        if (!empty($this->pending_item)) {
            $cart_item_data['juntaplay'] = $this->pending_item;
            $this->pending_item          = [];
        }

        return $cart_item_data;
    }

    public function add_meta_to_item($item, $cart_item_key, $values, $order): void
    {
        if (!empty($values['juntaplay'])) {
            $data = $values['juntaplay'];
            $item->add_meta_data('JuntaPlay Pool', (int) $data['pool_id']);
            $item->add_meta_data('JuntaPlay Cotas', implode(', ', array_map('intval', $data['numbers'])));

            return;
        }

        if (empty($values['juntaplay_deposit'])) {
            return;
        }

        $deposit   = $values['juntaplay_deposit'];
        $amount    = isset($deposit['amount']) ? (float) $deposit['amount'] : 0.0;
        $display   = $amount > 0 ? wc_price($amount) : '';
        $reference = isset($deposit['reference']) ? (string) $deposit['reference'] : '';

        $item->add_meta_data('JuntaPlay Depósito', $display !== '' ? $display : $amount);
        $item->add_meta_data('_juntaplay_deposit_amount', $amount, true);

        if ($reference !== '') {
            $item->add_meta_data('_juntaplay_deposit_reference', $reference, true);
        }
    }

    public function on_order_status_changed($order_id, $from, $to, $order): void
    {
        if (!($order instanceof WC_Order)) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $deposit_amount = $item->get_meta('_juntaplay_deposit_amount', true);

            if ($deposit_amount !== '') {
                $this->handle_deposit_item($order, $item, $to);

                continue;
            }

            $pool_id = (int) $item->get_meta('JuntaPlay Pool');
            $numbers = $item->get_meta('JuntaPlay Cotas');

            if (!$pool_id || !$numbers) {
                continue;
            }

            $numbers = array_map('intval', explode(',', str_replace(' ', '', (string) $numbers)));

            if (in_array($to, ['processing', 'completed'], true)) {
                Quotas::pay($pool_id, $numbers, $order_id, (int) $order->get_user_id());
            }

            if (in_array($to, ['failed', 'cancelled', 'refunded'], true)) {
                Quotas::release_by_order($order_id);
            }
        }
    }

    public function enforce_price($cart): void
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (!empty($cart_item['juntaplay_deposit'])) {
                $amount = isset($cart_item['juntaplay_deposit']['amount']) ? (float) $cart_item['juntaplay_deposit']['amount'] : 0.0;
                $cart_item['data']->set_price($amount);

                continue;
            }

            if (empty($cart_item['juntaplay']['pool_id'])) {
                continue;
            }

            $pool = \JuntaPlay\Data\Pools::get((int) $cart_item['juntaplay']['pool_id']);

            if ($pool) {
                $cart_item['data']->set_price($pool->price);
            }
        }
    }

    private function handle_deposit_item(WC_Order $order, WC_Order_Item_Product $item, string $new_status): void
    {
        $order_id  = $order->get_id();
        $user_id   = (int) $order->get_user_id();
        $amount    = (float) $item->get_meta('_juntaplay_deposit_amount', true);
        $processed = (string) $item->get_meta('_juntaplay_deposit_processed', true);
        $reference = (string) $item->get_meta('_juntaplay_deposit_reference', true);

        if ($user_id <= 0 || $amount <= 0) {
            return;
        }

        if (in_array($new_status, ['processing', 'completed'], true)) {
            if ($processed === 'completed') {
                return;
            }

            $balance       = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
            $balance       = max(0.0, $balance);
            $balance_after = $balance + $amount;

            update_user_meta($user_id, 'juntaplay_credit_balance', number_format($balance_after, 2, '.', ''));
            update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));
            update_user_meta($user_id, 'juntaplay_credit_last_recharge', current_time('mysql'));

            CreditTransactions::create([
                'user_id'       => $user_id,
                'type'          => CreditTransactions::TYPE_DEPOSIT,
                'status'        => CreditTransactions::STATUS_COMPLETED,
                'amount'        => $amount,
                'balance_after' => $balance_after,
                'reference'     => $reference !== '' ? $reference : sprintf('JPD-%d', $order_id),
                'context'       => [
                    'order_id' => $order_id,
                    'item_id'  => $item->get_id(),
                ],
            ]);

            $item->update_meta_data('_juntaplay_deposit_processed', 'completed');
            $item->save();

            do_action('juntaplay/credits/deposit_completed', $user_id, [
                'amount'    => $amount,
                'reference' => $reference,
                'order_id'  => $order_id,
            ]);

            return;
        }

        if (in_array($new_status, ['failed', 'cancelled', 'refunded'], true) && $processed === 'completed') {
            $balance       = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
            $balance_after = max(0.0, $balance - $amount);

            update_user_meta($user_id, 'juntaplay_credit_balance', number_format($balance_after, 2, '.', ''));
            update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));

            CreditTransactions::create([
                'user_id'       => $user_id,
                'type'          => CreditTransactions::TYPE_ADJUSTMENT,
                'status'        => CreditTransactions::STATUS_FAILED,
                'amount'        => -$amount,
                'balance_after' => $balance_after,
                'reference'     => $reference !== '' ? $reference : sprintf('JPD-%d', $order_id),
                'context'       => [
                    'order_id' => $order_id,
                    'item_id'  => $item->get_id(),
                    'reason'   => 'deposit_reversed',
                ],
            ]);

            $item->update_meta_data('_juntaplay_deposit_processed', 'reversed');
            $item->save();

            do_action('juntaplay/credits/deposit_reversed', $user_id, [
                'amount'    => $amount,
                'reference' => $reference,
                'order_id'  => $order_id,
            ]);
        }
    }
}
