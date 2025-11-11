<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

use WC;
use WC_Product;
use WP_User;

use function add_action;
use function add_filter;
use function apply_filters;
use function __;
use function class_exists;
use function function_exists;
use function get_current_user_id;
use function get_option;
use function get_permalink;
use function get_userdata;
use function get_user_meta;
use function get_footer;
use function get_header;
use function home_url;
use function is_checkout;
use function is_order_received_page;
use function is_wc_endpoint_url;
use function nocache_headers;
use function status_header;
use function trailingslashit;
use function wc_price;
use function wp_add_inline_style;
use function wp_style_is;

use const JP_DIR;

class CheckoutDeposit
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $deposit_context = null;

    private bool $rendering = false;

    public function init(): void
    {
        add_filter('template_include', [$this, 'filter_template_include'], 99);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 30);
        add_filter('body_class', [$this, 'filter_body_class']);
        add_filter('woocommerce_checkout_fields', [$this, 'filter_checkout_fields']);
    }

    /**
     * @return array<string, mixed>
     */
    private function get_deposit_context(): array
    {
        if ($this->deposit_context !== null) {
            return $this->deposit_context;
        }

        if (!class_exists('\\WooCommerce') || !function_exists('WC')) {
            $this->deposit_context = [];

            return $this->deposit_context;
        }

        $wc = WC();

        if (!$wc || !isset($wc->cart) || !$wc->cart) {
            $this->deposit_context = [];

            return $this->deposit_context;
        }

        foreach ($wc->cart->get_cart() as $cart_item) {
            if (empty($cart_item['juntaplay_deposit']) || !is_array($cart_item['juntaplay_deposit'])) {
                continue;
            }

            $deposit = $cart_item['juntaplay_deposit'];
            $amount  = isset($deposit['amount']) ? (float) $deposit['amount'] : 0.0;

            if ($amount <= 0.0) {
                continue;
            }

            $display = isset($deposit['display']) ? (string) $deposit['display'] : '';

            if ($display === '') {
                $display = wc_price($amount);
            }

            $reference    = isset($deposit['reference']) ? (string) $deposit['reference'] : '';
            $product_name = '';

            if (isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product) {
                $product_name = $cart_item['data']->get_name();
            }

            $this->deposit_context = [
                'amount'      => $amount,
                'display'     => $display,
                'reference'   => $reference,
                'product'     => $product_name,
            ];

            return $this->deposit_context;
        }

        $this->deposit_context = [];

        return $this->deposit_context;
    }

    private function is_deposit_checkout(): bool
    {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return false;
        }

        if ((function_exists('is_order_received_page') && is_order_received_page())
            || (function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('order-pay') || is_wc_endpoint_url('view-order')))
        ) {
            return false;
        }

        $context = $this->get_deposit_context();

        return !empty($context);
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function filter_body_class(array $classes): array
    {
        if ($this->is_deposit_checkout()) {
            $classes[] = 'jp-credit-checkout-page';
        }

        return $classes;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function filter_checkout_fields(array $fields): array
    {
        if (!$this->is_deposit_checkout()) {
            return $fields;
        }

        unset($fields['order']);

        return $fields;
    }

    public function enqueue_assets(): void
    {
        if (!$this->is_deposit_checkout()) {
            return;
        }

        if (!wp_style_is('juntaplay', 'enqueued')) {
            return;
        }

        $css = <<<'CSS'
.jp-credit-checkout{padding:clamp(32px,5vw,80px) 0;background:linear-gradient(180deg,#f5f7fb 0%,#ffffff 100%);} 
.jp-credit-checkout__container{width:min(1080px,94vw);margin:0 auto;display:flex;flex-direction:column;gap:32px;} 
.jp-credit-checkout__header{display:flex;flex-direction:column;gap:12px;} 
.jp-credit-checkout__eyebrow{font-size:12px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;color:#64748b;} 
.jp-credit-checkout__title{font-size:clamp(26px,3.4vw,36px);font-weight:700;color:#0f172a;margin:0;} 
.jp-credit-checkout__lead{font-size:16px;line-height:1.6;color:#334155;max-width:62ch;margin:0;} 
.jp-credit-checkout__layout{display:flex;flex-wrap:wrap;gap:28px;} 
.jp-credit-checkout__form{flex:1 1 60%;min-width:min(560px,100%);} 
.jp-credit-checkout__form .woocommerce{background:#ffffff;border-radius:24px;padding:32px;box-shadow:0 24px 60px rgba(15,23,42,0.12);} 
.jp-credit-checkout__form .woocommerce form.checkout{border:none;padding:0;margin:0;} 
.jp-credit-checkout__form .woocommerce form.checkout .col2-set{display:grid;gap:24px;margin:0;} 
.jp-credit-checkout__form .woocommerce form.checkout .col2-set .col-1,
.jp-credit-checkout__form .woocommerce form.checkout .col2-set .col-2{float:none;width:100%;margin:0;padding:0;} 
.jp-credit-checkout__form .woocommerce form.checkout .col2-set .col-1 > h3,
.jp-credit-checkout__form .woocommerce form.checkout .col2-set .col-2 > h3{font-size:18px;color:#0f172a;margin-bottom:12px;} 
.jp-credit-checkout__form .woocommerce form.checkout .col2-set .form-row{margin-bottom:16px;} 
.jp-credit-checkout__form .woocommerce #order_review{border-top:1px solid rgba(148,163,184,0.25);padding-top:24px;margin-top:8px;} 
.jp-credit-checkout__form .woocommerce #order_review_heading{font-size:18px;color:#0f172a;margin-bottom:16px;} 
.jp-credit-checkout__form .woocommerce table.shop_table{border:1px solid rgba(148,163,184,0.3);border-radius:16px;overflow:hidden;} 
.jp-credit-checkout__form .woocommerce table.shop_table th,
.jp-credit-checkout__form .woocommerce table.shop_table td{padding:14px 18px;} 
.jp-credit-checkout__form .woocommerce .woocommerce-checkout-payment{background:#f8fafc;border-radius:16px;padding:20px;} 
.jp-credit-checkout__sidebar{flex:1 1 2%;min-width:min(230px,100%);display:flex;flex-direction:column;gap:24px;}
.jp-credit-checkout__card{background:#ffffff;border-radius:24px;padding:24px 26px;box-shadow:0 22px 50px rgba(15,23,42,0.08);} 
.jp-credit-checkout__card h2,
.jp-credit-checkout__card h3{margin:0 0 16px;font-size:18px;color:#0f172a;} 
.jp-credit-checkout__meta{display:flex;flex-direction:column;gap:6px;font-size:15px;color:#475569;} 
.jp-credit-checkout__meta strong{font-size:24px;color:#0f172a;} 
.jp-credit-checkout__items{margin:16px 0 0;padding:0;list-style:none;display:flex;flex-direction:column;gap:12px;} 
.jp-credit-checkout__item{display:flex;justify-content:space-between;font-size:15px;color:#0f172a;} 
.jp-credit-checkout__item span:last-child{font-weight:600;} 
.jp-credit-checkout__support{font-size:14px;line-height:1.6;color:#475569;margin-top:16px;} 
.jp-credit-checkout__support a{color:var(--jp-primary,#ff5a5f);font-weight:600;text-decoration:none;} 
.jp-credit-checkout__balance{display:flex;flex-direction:column;gap:6px;font-size:15px;color:#475569;} 
.jp-credit-checkout__balance strong{font-size:28px;color:#0f172a;} 
.jp-credit-checkout__hint{font-size:14px;color:#64748b;margin-top:12px;} 
.jp-credit-checkout__link{display:inline-flex;align-items:center;gap:8px;margin-top:18px;font-weight:600;color:var(--jp-primary,#ff5a5f);text-decoration:none;} 
.jp-credit-checkout__link::after{content:'→';font-size:14px;} 
@media (max-width:1024px){
    .jp-credit-checkout__form{min-width:100%;}
}
@media (max-width:720px){
    .jp-credit-checkout{padding:32px 0 48px;}
    .jp-credit-checkout__container{gap:24px;}
    .jp-credit-checkout__form .woocommerce{padding:24px;border-radius:20px;}
    .jp-credit-checkout__sidebar{flex:1 1 100%;min-width:100%;}
}
CSS;

        wp_add_inline_style('juntaplay', $css);
    }

    public function filter_template_include(string $template): string
    {
        if ($this->rendering || !$this->is_deposit_checkout()) {
            return $template;
        }

        if (!function_exists('WC')) {
            return $template;
        }

        $checkout = WC()->checkout();

        if (!$checkout) {
            return $template;
        }

        $context = $this->build_template_context();
        $context = apply_filters('juntaplay/credits/deposit_checkout_context', $context);

        $this->rendering = true;

        status_header(200);
        nocache_headers();

        $GLOBALS['juntaplay_deposit_template_context'] = $context;

        return JP_DIR . 'templates/checkout/deposit-page.php';
    }

    /**
     * @return array<string, mixed>
     */
    private function build_template_context(): array
    {
        $deposit = $this->get_deposit_context();
        $amount  = isset($deposit['amount']) ? (float) $deposit['amount'] : 0.0;
        $display = isset($deposit['display']) ? (string) $deposit['display'] : '';

        if ($display === '' && $amount > 0.0) {
            $display = wc_price($amount);
        }

        $product = isset($deposit['product']) ? (string) $deposit['product'] : '';
        if ($product === '') {
            $product = __('Recarga de Créditos', 'juntaplay');
        }

        $reference = isset($deposit['reference']) ? (string) $deposit['reference'] : '';

        $user_id = get_current_user_id();
        $user    = $user_id > 0 ? get_userdata($user_id) : null;

        $customer_name  = '';
        $customer_email = '';

        if ($user instanceof WP_User) {
            $customer_email = (string) $user->user_email;
            $name_parts     = [
                trim((string) $user->first_name),
                trim((string) $user->last_name),
            ];
            $customer_name = trim(implode(' ', array_filter($name_parts)));

            if ($customer_name === '') {
                $customer_name = (string) $user->display_name;
            }
        }

        $balance = $user_id > 0 ? (float) get_user_meta($user_id, 'juntaplay_credit_balance', true) : 0.0;
        $balance = max(0.0, $balance);
        $balance_label = wc_price($balance);

        $cart_items = [];
        $cart_totals = [
            'total'    => $display,
            'subtotal' => '',
            'fees'     => [],
        ];

        $wc = WC();
        $cart = $wc && isset($wc->cart) ? $wc->cart : null;

        if ($cart) {
            $totals = $cart->get_totals();

            if (isset($totals['subtotal'])) {
                $cart_totals['subtotal'] = wc_price((float) $totals['subtotal']);
            }

            if (isset($totals['total'])) {
                $cart_totals['total'] = wc_price((float) $totals['total']);
            }

            foreach ($cart->get_cart() as $item) {
                $product_obj = isset($item['data']) && $item['data'] instanceof WC_Product ? $item['data'] : null;
                $quantity    = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;
                $name        = $product_obj ? $product_obj->get_name() : $product;

                $line_total = $product_obj ? $cart->get_product_subtotal($product_obj, $quantity) : $display;

                $cart_items[] = [
                    'name'     => $name,
                    'quantity' => $quantity,
                    'total'    => $line_total,
                ];
            }

            foreach ($cart->get_fees() as $fee) {
                $cart_totals['fees'][] = [
                    'name'  => $fee->name,
                    'total' => wc_price($fee->amount),
                ];
            }
        }

        $support_email = apply_filters('juntaplay/credits/support_email', 'suporte@juntaplay.com.br');

        return [
            'amount'         => $amount,
            'amount_display' => $display,
            'product_name'   => $product,
            'reference'      => $reference,
            'customer_name'  => $customer_name,
            'customer_email' => $customer_email,
            'balance_label'  => $balance_label,
            'cart_items'     => $cart_items,
            'cart_totals'    => $cart_totals,
            'support_email'  => (string) $support_email,
            'credits_url'    => $this->get_credits_url(),
            'profile_url'    => $this->get_profile_url(),
        ];
    }

    private function get_profile_url(): string
    {
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        $url             = $profile_page_id ? (string) get_permalink($profile_page_id) : '';

        if ($url === '') {
            $url = home_url('/perfil');
        }

        if ($url === '') {
            $url = home_url('/');
        }

        return trailingslashit($url);
    }

    private function get_credits_url(): string
    {
        $credits_page_id = (int) get_option('juntaplay_page_creditos');
        $url             = $credits_page_id ? (string) get_permalink($credits_page_id) : '';

        if ($url === '') {
            $url = home_url('/creditos');
        }

        if ($url === '') {
            $url = $this->get_profile_url();
        }

        return trailingslashit($url);
    }
}
