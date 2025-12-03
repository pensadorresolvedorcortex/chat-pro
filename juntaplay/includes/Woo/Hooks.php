<?php
declare(strict_types=1);

namespace JuntaPlay\Woo;

use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use JuntaPlay\Admin\Settings;
use JuntaPlay\Data\CreditTransactions;
use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\GroupMembershipEvents;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\Quotas;

use function absint;
use function add_action;
use function add_filter;
use function array_map;
use function current_time;
use function get_option;
use function get_permalink;
use function home_url;
use function get_current_user_id;
use function get_user_meta;
use function implode;
use function in_array;
use function is_admin;
use function is_array;
use function is_numeric;
use function is_string;
use function max;
use function sanitize_text_field;
use function sprintf;
use function str_replace;
use function update_user_meta;
use function wc_add_notice;
use function wc_get_order;
use function wc_get_template;
use function wc_get_product;
use function wc_price;
use function wc_date_format;
use function wc_format_datetime;
use function wp_strip_all_tags;
use function wp_unslash;
use function __;
use function _n;

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
        add_action('woocommerce_thankyou', [$this, 'render_thankyou_message'], 20);
        add_action('woocommerce_view_order', [$this, 'render_thankyou_message'], 20);
    }

    private array $pending_item = [];

    private bool $thankyou_rendered = false;

    /**
     * @var array<int, array<int, array<string, mixed>>>
     */
    private array $order_group_summaries = [];

    private function order_has_group_items(WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $group_id = (int) $item->get_meta('_juntaplay_group_id', true);

            if ($group_id > 0) {
                return true;
            }
        }

        return false;
    }

    private function order_paid_via_callback(WC_Order $order): bool
    {
        $flag = $order->get_meta('_payment_via_mp_callback', true);

        if (is_string($flag)) {
            $flag = strtolower(trim($flag));
        }

        return $flag === 'yes' || $flag === '1' || $flag === 'true' || $flag === 1 || $flag === true;
    }

    /**
     * @param array<string, mixed> $credentials
     */
    private function credentials_available(array $credentials): bool
    {
        $keys = [
            'access_url',
            'access_login',
            'access_password',
            'access_notes',
            'access_observations',
        ];

        foreach ($keys as $key) {
            if (!empty(trim((string) ($credentials[$key] ?? '')))) {
                return true;
            }
        }

        return false;
    }

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

        if (!empty($values['juntaplay_group'])) {
            $data       = $values['juntaplay_group'];
            $group_id   = isset($data['group_id']) ? (int) $data['group_id'] : 0;
            $group_name = isset($data['title']) ? (string) $data['title'] : '';
            $price      = isset($data['price']) ? (float) $data['price'] : 0.0;

            if ($group_name !== '') {
                $item->add_meta_data('JuntaPlay Grupo', $group_name);
            }

            if ($group_id > 0) {
                $item->add_meta_data('_juntaplay_group_id', $group_id, true);
            }

            if (!empty($data['group_slug'])) {
                $item->add_meta_data('_juntaplay_group_slug', (string) $data['group_slug'], true);
            }

            if ($price > 0) {
                $item->add_meta_data('_juntaplay_group_price', $price, true);
            }

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

        if (in_array($to, ['processing', 'completed'], true)) {
            $this->maybe_dispatch_group_order_email($order);
        }

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $group_id = (int) $item->get_meta('_juntaplay_group_id', true);
            if ($group_id > 0) {
                $this->handle_group_item($order, $item, $to);

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

        $this->handle_wallet_usage($order, $to);
    }

    public function enforce_price($cart): void
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product
                ? $cart_item['data']
                : null;

            if (!empty($cart_item['juntaplay_deposit'])) {
                $amount = isset($cart_item['juntaplay_deposit']['amount']) ? (float) $cart_item['juntaplay_deposit']['amount'] : 0.0;

                if ($product) {
                    $product->set_price($amount);
                }

                continue;
            }

            if (!empty($cart_item['juntaplay_group'])) {
                $price = isset($cart_item['juntaplay_group']['price']) ? (float) $cart_item['juntaplay_group']['price'] : 0.0;

                if ($price <= 0 && $product) {
                    $product_price = (float) $product->get_price();

                    if ($product_price > 0) {
                        $price = $product_price;
                    }
                }

                if ($product) {
                    $product->set_price($price);
                }

                continue;
            }

            if (empty($cart_item['juntaplay']['pool_id'])) {
                continue;
            }

            $pool = \JuntaPlay\Data\Pools::get((int) $cart_item['juntaplay']['pool_id']);

            if ($pool) {
                if ($product) {
                    $product->set_price($pool->price);
                }
            }
        }
    }

    private function maybe_dispatch_group_order_email(WC_Order $order): void
    {
        $order_id = $order->get_id();

        if ((string) $order->get_meta('_juntaplay_group_purchase_email_sent', true) === 'yes') {
            return;
        }

        $summaries = $this->get_order_group_summaries($order);

        if (!$summaries) {
            return;
        }

        $subscription_total = 0.0;
        $deposit_total      = 0.0;
        $processing_total   = 0.0;
        $wallet_total       = 0.0;
        $grand_total        = 0.0;

        foreach ($summaries as $summary) {
            $subscription_total += isset($summary['subscription_total']) ? (float) $summary['subscription_total'] : 0.0;
            $deposit_total      += isset($summary['deposit']) ? (float) $summary['deposit'] : 0.0;
            $processing_total   += isset($summary['processing_fee']) ? (float) $summary['processing_fee'] : 0.0;
            $wallet_total       += isset($summary['wallet_used']) ? (float) $summary['wallet_used'] : 0.0;
            $grand_total        += isset($summary['total']) ? (float) $summary['total'] : 0.0;
        }

        $meta_deposit = (float) $order->get_meta('_juntaplay_group_deposit', true);
        if ($meta_deposit > 0.0) {
            $deposit_total = $meta_deposit;
        }

        $meta_processing = (float) $order->get_meta('_juntaplay_processing_fee', true);
        if ($meta_processing > 0.0) {
            $processing_total = $meta_processing;
        }

        $meta_wallet = (float) $order->get_meta('_juntaplay_wallet_used', true);
        if ($meta_wallet > 0.0) {
            $wallet_total = $meta_wallet;
        }

        $totals = [
            'subscription_total' => $subscription_total,
            'deposit_total'      => $deposit_total,
            'processing_fee'     => $processing_total,
            'wallet_used'        => $wallet_total,
            'grand_total'        => max(0.0, $subscription_total + $deposit_total + $processing_total - $wallet_total),
        ];

        do_action('juntaplay/groups/order_purchase_email', $order, $summaries, $totals);

        $order->update_meta_data('_juntaplay_group_purchase_email_sent', 'yes');
        $order->save();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_order_group_summaries(WC_Order $order): array
    {
        $order_id = $order->get_id();

        if (!isset($this->order_group_summaries[$order_id])) {
            $base      = $this->get_group_item_summaries($order);
            $enriched  = $this->enrich_group_summaries($order, $base);
            $indexed   = [];

            foreach ($enriched as $summary) {
                $item_id = isset($summary['item_id']) ? (int) $summary['item_id'] : 0;

                if ($item_id <= 0) {
                    continue;
                }

                $indexed[$item_id] = $summary;
            }

            $this->order_group_summaries[$order_id] = $indexed;
        }

        return array_values($this->order_group_summaries[$order_id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get_group_item_summaries(WC_Order $order): array
    {
        $summaries = [];
        $user_id   = (int) $order->get_user_id();

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $group_id = (int) $item->get_meta('_juntaplay_group_id', true);

            if ($group_id <= 0) {
                continue;
            }

            $quantity            = max(1, (int) $item->get_quantity());
            $subscription_total  = (float) $item->get_total();
            $subscription_price  = $quantity > 0 ? $subscription_total / $quantity : $subscription_total;
            $group               = Groups::get($group_id);
            $title               = $group && isset($group->title) ? (string) $group->title : '';

            if ($title === '') {
                $title = (string) $item->get_meta('JuntaPlay Grupo');
            }

            if ($title === '') {
                $title = $item->get_name();
            }

            $summaries[] = [
                'group_id'           => $group_id,
                'user_id'            => $user_id,
                'quantity'           => $quantity,
                'subscription_price' => $subscription_price,
                'subscription_total' => $subscription_total,
                'title'              => $title,
                'order_id'           => $order->get_id(),
                'order_number'       => $order->get_order_number(),
                'item_id'            => $item->get_id(),
            ];
        }

        return $summaries;
    }

    /**
     * @param array<int, array<string, mixed>> $summaries
     *
     * @return array<int, array<string, mixed>>
     */
    private function enrich_group_summaries(WC_Order $order, array $summaries): array
    {
        if (!$summaries) {
            return [];
        }

        $subscription_total = 0.0;
        foreach ($summaries as $summary) {
            $subscription_total += isset($summary['subscription_total']) ? (float) $summary['subscription_total'] : 0.0;
        }

        $deposit_total    = (float) $order->get_meta('_juntaplay_group_deposit', true);
        $processing_total = (float) $order->get_meta('_juntaplay_processing_fee', true);
        $wallet_total     = (float) $order->get_meta('_juntaplay_wallet_used', true);

        if ($deposit_total <= 0.0) {
            $deposit_total = $subscription_total;
        }

        foreach ($summaries as &$summary) {
            $line_total = isset($summary['subscription_total']) ? (float) $summary['subscription_total'] : 0.0;
            $ratio      = $subscription_total > 0.0 ? $line_total / $subscription_total : 0.0;

            $summary['deposit']        = $deposit_total * $ratio;
            $summary['processing_fee'] = $processing_total * $ratio;
            $summary['wallet_used']    = $wallet_total * $ratio;
            $summary['total']          = max(0.0, $line_total + $summary['deposit'] + $summary['processing_fee'] - $summary['wallet_used']);
        }
        unset($summary);

        return $summaries;
    }

    private function get_group_summary_for_item(WC_Order $order, WC_Order_Item_Product $item): ?array
    {
        $order_id = $order->get_id();
        $item_id  = $item->get_id();

        if (!isset($this->order_group_summaries[$order_id][$item_id])) {
            $this->get_order_group_summaries($order);
        }

        return $this->order_group_summaries[$order_id][$item_id] ?? null;
    }

    private function handle_group_item(WC_Order $order, WC_Order_Item_Product $item, string $new_status): void
    {
        $group_id = (int) $item->get_meta('_juntaplay_group_id', true);
        $user_id  = (int) $order->get_user_id();

        if ($group_id <= 0 || $user_id <= 0) {
            return;
        }

        if (in_array($new_status, ['processing', 'completed'], true)) {
            if (!GroupMembers::user_has_membership($group_id, $user_id)) {
                GroupMembers::add($group_id, $user_id, 'member', 'active');
            } else {
                GroupMembers::update_status($group_id, $user_id, 'active');
            }

            GroupMembershipEvents::log(
                $group_id,
                $user_id,
                GroupMembershipEvents::TYPE_ORDER_JOIN,
                '',
                [
                    'order_id' => $order->get_id(),
                    'item_id'  => $item->get_id(),
                    'status'   => $new_status,
                ]
            );

            $summary = $this->get_group_summary_for_item($order, $item);

            if ($summary) {
                $email_flag = (string) $item->get_meta('_juntaplay_group_email_sent', true);

                if ($email_flag !== 'yes') {
                    do_action('juntaplay/groups/order_paid', $order, $item, $summary);
                    $item->update_meta_data('_juntaplay_group_email_sent', 'yes');
                    $item->save();
                }

                $access_flag = (string) $item->get_meta('_juntaplay_group_access_sent', true);
                if ($access_flag !== 'yes') {
                    $credentials = Groups::get_credentials($group_id);

                    if ($this->credentials_available($credentials)) {
                        do_action('juntaplay/groups/send_access', $group_id, $user_id, $credentials, [
                            'source'   => 'order',
                            'order_id' => $order->get_id(),
                            'item_id'  => $item->get_id(),
                        ]);

                        $item->update_meta_data('_juntaplay_group_access_sent', 'yes');
                        $item->save();
                    }
                }
            }

            return;
        }

        if (in_array($new_status, ['failed', 'cancelled', 'refunded'], true)) {
            GroupMembers::update_status($group_id, $user_id, 'canceled');

            GroupMembershipEvents::log(
                $group_id,
                $user_id,
                GroupMembershipEvents::TYPE_CANCEL,
                '',
                [
                    'order_id' => $order->get_id(),
                    'item_id'  => $item->get_id(),
                    'status'   => $new_status,
                ]
            );
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

    private function handle_wallet_usage(WC_Order $order, string $new_status): void
    {
        $wallet_used = (float) $order->get_meta('_juntaplay_wallet_used', true);

        if ($wallet_used <= 0) {
            return;
        }

        $wallet_status = (string) $order->get_meta('_juntaplay_wallet_status', true);
        $user_id       = (int) $order->get_user_id();
        $order_id      = $order->get_id();

        if ($user_id <= 0) {
            return;
        }

        if (in_array($new_status, ['processing', 'completed'], true)) {
            if ($wallet_status === 'captured') {
                return;
            }

            $balance       = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
            $balance       = max(0.0, $balance);
            $balance_after = max(0.0, $balance - $wallet_used);

            update_user_meta($user_id, 'juntaplay_credit_balance', number_format($balance_after, 2, '.', ''));
            update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));

            CreditTransactions::create([
                'user_id'       => $user_id,
                'type'          => CreditTransactions::TYPE_PURCHASE,
                'status'        => CreditTransactions::STATUS_COMPLETED,
                'amount'        => -$wallet_used,
                'balance_after' => $balance_after,
                'reference'     => sprintf('JPW-%d', $order_id),
                'context'       => [
                    'order_id' => $order_id,
                    'reason'   => 'checkout_wallet_capture',
                ],
            ]);

            $order->update_meta_data('_juntaplay_wallet_status', 'captured');
            $order->save();

            return;
        }

        if (in_array($new_status, ['failed', 'cancelled', 'refunded'], true) && $wallet_status === 'captured') {
            $balance       = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
            $balance_after = $balance + $wallet_used;

            update_user_meta($user_id, 'juntaplay_credit_balance', number_format($balance_after, 2, '.', ''));
            update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));

            CreditTransactions::create([
                'user_id'       => $user_id,
                'type'          => CreditTransactions::TYPE_REFUND,
                'status'        => CreditTransactions::STATUS_COMPLETED,
                'amount'        => $wallet_used,
                'balance_after' => $balance_after,
                'reference'     => sprintf('JPW-%d', $order_id),
                'context'       => [
                    'order_id' => $order_id,
                    'reason'   => 'checkout_wallet_refund',
                ],
            ]);

            $order->update_meta_data('_juntaplay_wallet_status', 'refunded');
            $order->save();
        }
    }

    public function render_thankyou_message($order_id): void
    {
        if ($this->thankyou_rendered) {
            return;
        }

        if ($order_id instanceof WC_Order) {
            $order = $order_id;
        } else {
            $order = wc_get_order($order_id);
        }

        if (!$order instanceof WC_Order) {
            return;
        }

        if (!$this->order_has_group_items($order)) {
            return;
        }

        $status = $order->get_status();

        if (!$order->is_paid() || !in_array($status, ['processing', 'completed'], true) || !$this->order_paid_via_callback($order)) {
            return;
        }

        $groups = [];
        $totals = [
            'subscription_total' => 0.0,
            'deposit_total'      => 0.0,
            'processing_fee'     => 0.0,
            'wallet_used'        => 0.0,
        ];

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $group_id = (int) $item->get_meta('_juntaplay_group_id', true);

            if ($group_id <= 0) {
                continue;
            }

            $summary = $this->get_group_summary_for_item($order, $item);

            if (!$summary) {
                continue;
            }

            $quantity           = isset($summary['quantity']) ? max(1, (int) $summary['quantity']) : max(1, (int) $item->get_quantity());
            $subscription_total = isset($summary['subscription_total']) ? (float) $summary['subscription_total'] : (float) $item->get_total();
            $subscription_price = isset($summary['subscription_price']) ? (float) $summary['subscription_price'] : ($quantity > 0 ? $subscription_total / $quantity : $subscription_total);
            $deposit            = isset($summary['deposit']) ? (float) $summary['deposit'] : 0.0;
            $processing_fee     = isset($summary['processing_fee']) ? (float) $summary['processing_fee'] : 0.0;
            $wallet_used        = isset($summary['wallet_used']) ? (float) $summary['wallet_used'] : 0.0;
            $total              = isset($summary['total']) ? (float) $summary['total'] : max(0.0, $subscription_total + $deposit + $processing_fee - $wallet_used);

            $totals['subscription_total'] += $subscription_total;
            $totals['deposit_total']      += $deposit;
            $totals['processing_fee']     += $processing_fee;
            $totals['wallet_used']        += $wallet_used;

            $group_title = isset($summary['title']) ? (string) $summary['title'] : $item->get_name();
            $quotas      = trim((string) $item->get_meta('JuntaPlay Cotas', true));

            $groups[] = [
                'title'                    => $group_title,
                'quantity_label'           => sprintf(_n('%d cota', '%d cotas', $quantity, 'juntaplay'), $quantity),
                'subscription_price_html'  => $subscription_price > 0 ? wc_price($subscription_price) : '',
                'subscription_total_html'  => $subscription_total > 0 ? wc_price($subscription_total) : '',
                'deposit_html'             => $deposit > 0 ? wc_price($deposit) : '',
                'processing_fee_html'      => $processing_fee > 0 ? wc_price($processing_fee) : '',
                'wallet_used_html'         => $wallet_used > 0 ? wc_price(-1 * $wallet_used) : '',
                'total_html'               => wc_price($total),
                'quotas'                   => $quotas,
            ];
        }

        if (!$groups) {
            return;
        }

        $grand_total = max(0.0, $totals['subscription_total'] + $totals['deposit_total'] + $totals['processing_fee'] - $totals['wallet_used']);

        $totals_display = [
            'subscription_total' => $totals['subscription_total'] > 0 ? wc_price($totals['subscription_total']) : '',
            'deposit_total'      => $totals['deposit_total'] > 0 ? wc_price($totals['deposit_total']) : '',
            'processing_fee'     => $totals['processing_fee'] > 0 ? wc_price($totals['processing_fee']) : '',
            'wallet_used'        => $totals['wallet_used'] > 0 ? wc_price(-1 * $totals['wallet_used']) : '',
            'grand_total'        => wc_price($grand_total),
        ];

        $totals_summary = [];
        if ($totals_display['subscription_total'] !== '') {
            $totals_summary[] = [
                'label' => __('Assinaturas', 'juntaplay'),
                'value' => $totals_display['subscription_total'],
            ];
        }

        if ($totals_display['deposit_total'] !== '') {
            $totals_summary[] = [
                'label' => __('Caução', 'juntaplay'),
                'value' => $totals_display['deposit_total'],
            ];
        }

        if ($totals_display['processing_fee'] !== '') {
            $totals_summary[] = [
                'label' => __('Custos de processamento', 'juntaplay'),
                'value' => $totals_display['processing_fee'],
            ];
        }

        if ($totals_display['wallet_used'] !== '') {
            $totals_summary[] = [
                'label' => __('Créditos utilizados', 'juntaplay'),
                'value' => $totals_display['wallet_used'],
            ];
        }

        $totals_summary[] = [
            'label'     => __('Total pago', 'juntaplay'),
            'value'     => $totals_display['grand_total'],
            'emphasis'  => true,
        ];

        $order_overview = [
            [
                'label' => __('Número da assinatura', 'juntaplay'),
                'value' => '#' . $order->get_order_number(),
            ],
        ];

        $created = $order->get_date_created();
        if ($created) {
            $order_overview[] = [
                'label' => __('Data', 'juntaplay'),
                'value' => wc_format_datetime($created, wc_date_format()),
            ];
        }

        $payment_method = $order->get_payment_method_title();
        if ($payment_method) {
            $order_overview[] = [
                'label' => __('Forma de pagamento', 'juntaplay'),
                'value' => $payment_method,
            ];
        }

        $billing_email = $order->get_billing_email();
        if ($billing_email !== '') {
            $order_overview[] = [
                'label' => __('E-mail de contato', 'juntaplay'),
                'value' => $billing_email,
            ];
        }

        $order_overview[] = [
            'label' => __('Valor total', 'juntaplay'),
            'value' => wp_strip_all_tags($order->get_formatted_order_total()),
        ];

        $customer_first_name = trim((string) $order->get_billing_first_name());
        if ($customer_first_name === '') {
            $customer_first_name = trim((string) $order->get_shipping_first_name());
        }

        $customer_name = trim($order->get_formatted_billing_full_name());
        if ($customer_name === '') {
            $customer_name = trim($order->get_formatted_shipping_full_name());
        }
        if ($customer_name === '') {
            $customer_name = $billing_email;
        }

        $this->thankyou_rendered = true;

        $my_groups_page_id = (int) get_option('juntaplay_page_meus-grupos');
        $my_groups_url     = $my_groups_page_id ? get_permalink($my_groups_page_id) : home_url('/meus-grupos/');

        $help_page_id = (int) get_option('juntaplay_page_regras');
        $help_url     = $help_page_id ? get_permalink($help_page_id) : home_url('/central-de-ajuda/');

        wc_get_template(
            'checkout/thankyou-groups.php',
            [
                'groups'            => $groups,
                'totals'            => $totals_display,
                'totals_summary'    => $totals_summary,
                'order_overview'    => $order_overview,
                'customer_name'     => $customer_name,
                'customer_first_name' => $customer_first_name,
                'my_groups_url'     => $my_groups_url,
                'help_url'          => $help_url,
                'illustration_url'  => JP_URL . 'assets/images/grupo.gif',
                'order_id'          => $order->get_id(),
                'redirect_delay'    => 6000,
            ],
            '',
            JP_DIR . 'templates/'
        );
    }
}
