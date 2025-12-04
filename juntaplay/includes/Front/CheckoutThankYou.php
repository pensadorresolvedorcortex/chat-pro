<?php

declare(strict_types=1);

namespace JuntaPlay\Front;

use JuntaPlay\Notifications\EmailHelper;
use WC_Order;
use function absint;
use function add_action;
use function add_filter;
use function apply_filters;
use function array_filter;
use function array_values;
use function current_time;
use function get_bloginfo;
use function get_footer;
use function get_header;
use function get_option;
use function get_permalink;
use function get_query_var;
use function home_url;
use function in_array;
use function is_checkout;
use function is_order_received_page;
use function is_string;
use function is_numeric;
use function nocache_headers;
use function ob_get_clean;
use function ob_start;
use function sanitize_email;
use function sanitize_text_field;
use function status_header;
use function trailingslashit;
use function wc_get_order;
use function wc_price;
use function wp_kses_post;
use function wp_strip_all_tags;
use function wp_unslash;

if (!defined('ABSPATH')) {
    exit;
}

class CheckoutThankYou
{
    private const EMAIL_META_KEY = '_juntaplay_checkout_email_sent';
    private const CALLBACK_META_KEY = '_payment_via_mp_callback';

    public function init(): void
    {
        if (!function_exists('is_checkout')) {
            return;
        }

        add_action('woocommerce_thankyou', [$this, 'handle_thankyou'], 0, 1);
        add_action('woocommerce_order_status_changed', [$this, 'handle_status_transition'], 10, 4);
        add_action('template_redirect', [$this, 'intercept_thankyou_template'], 0);

        foreach (['customer_processing_order', 'customer_completed_order', 'customer_on_hold_order'] as $email_hook) {
            add_filter("woocommerce_email_enabled_{$email_hook}", [$this, 'disable_default_customer_email'], 10, 2);
        }
    }

    public function handle_thankyou(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order || !$this->should_override_order($order)) {
            return;
        }

        $this->maybe_send_custom_email($order);
    }

    public function handle_status_transition($order_id, $from, $to, $order): void
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order((int) $order_id);
        }

        if (!$order instanceof WC_Order || !$this->should_override_order($order)) {
            return;
        }

        $this->maybe_send_custom_email($order);
    }

    public function intercept_thankyou_template(): void
    {
        if (!is_checkout() || !is_order_received_page()) {
            return;
        }

        $order_id = absint(get_query_var('order-received'));

        if ($order_id <= 0) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order || !$this->should_override_order($order)) {
            return;
        }

        $order_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash((string) $_GET['key'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($order_key !== '' && $order->get_order_key() !== $order_key) {
            return;
        }

        nocache_headers();
        status_header(200);

        $context = $this->prepare_view_context($order);
        $this->maybe_send_custom_email($order);

        $this->render_template($context);
        exit;
    }

    /**
     * @param mixed $order
     */
    public function disable_default_customer_email(bool $enabled, $order): bool
    {
        if (!$enabled) {
            return false;
        }

        if ($order instanceof WC_Order && $this->should_override_order($order)) {
            return false;
        }

        return $enabled;
    }

    private function should_override_order(WC_Order $order): bool
    {
        $status        = $order->get_status();
        $via_callback  = $this->order_paid_via_callback($order);
        $eligible      = $via_callback && in_array($status, ['processing', 'completed'], true);

        /** @var bool $override */
        $override = apply_filters('juntaplay/checkout/override_thankyou', $eligible, $order);

        return $eligible && (bool) $override;
    }

    private function order_paid_via_callback(WC_Order $order): bool
    {
        $flag = $order->get_meta(self::CALLBACK_META_KEY, true);

        if (is_string($flag)) {
            $flag = strtolower(trim($flag));
        }

        return $flag === 'yes' || $flag === '1' || $flag === 'true' || $flag === 1 || $flag === true;
    }

    private function maybe_send_custom_email(WC_Order $order): void
    {
        if ($order->get_meta(self::EMAIL_META_KEY)) {
            return;
        }

        $recipient = $order->get_billing_email();

        if (!$recipient) {
            return;
        }

        $site_name = wp_strip_all_tags(get_bloginfo('name'));

        $context = $this->prepare_view_context($order);
        $blocks  = $this->build_email_blocks($context);

        if (!$blocks) {
            return;
        }

        $primary_item = isset($context['primary_item']) ? (string) $context['primary_item'] : '';
        $is_credit    = !empty($context['is_credit_topup']);

        if ($is_credit) {
            $subject = sprintf(
                /* translators: %s: site name */
                __('Sua recarga em %s está confirmada!', 'juntaplay'),
                $site_name
            );

            $headline = __('Sua recarga está confirmada!', 'juntaplay');
            $preheader = $primary_item !== ''
                ? sprintf(__('Resumo da sua recarga de %s', 'juntaplay'), $primary_item)
                : __('Resumo da sua recarga recente', 'juntaplay');
        } else {
            $subject = sprintf(
                /* translators: %s: site name */
                __('Sua assinatura em %s está confirmada!', 'juntaplay'),
                $site_name
            );

            $headline  = __('Sua assinatura está confirmada!', 'juntaplay');
            $preheader = $primary_item !== ''
                ? sprintf(__('Resumo do seu pedido de %s', 'juntaplay'), $primary_item)
                : __('Resumo do seu pedido recente', 'juntaplay');
        }

        $admin_email = sanitize_email((string) get_bloginfo('admin_email'));
        $footer_lines = array_filter([
            __('JuntaPlay • Transformando conhecimento em comunidades vibrantes.', 'juntaplay'),
            $admin_email !== ''
                ? sprintf(__('Precisa de ajuda? Fale com a gente em %s', 'juntaplay'), $admin_email)
                : '',
            sprintf(__('© %1$s %2$s. Todos os direitos reservados.', 'juntaplay'), current_time('Y'), $site_name),
        ]);

        $sent = EmailHelper::send(
            $recipient,
            $subject,
            $blocks,
            [
                'headline'  => $headline,
                'preheader' => $preheader,
                'footer'    => $footer_lines,
            ]
        );

        if ($sent) {
            $order->update_meta_data(self::EMAIL_META_KEY, current_time('mysql'));
            $order->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare_view_context(WC_Order $order): array
    {
        $status        = $order->get_status();
        $is_completed  = in_array($status, ['completed'], true);
        $is_processing = in_array($status, ['processing'], true);

        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'name'     => wp_strip_all_tags($item->get_name()),
                'quantity' => $item->get_quantity(),
                'total'    => wc_price($order->get_line_total($item, true)),
            ];
        }

        $totals = [];
        foreach ($order->get_order_item_totals() as $total) {
            $totals[] = [
                'label' => wp_strip_all_tags($total['label']),
                'value' => $total['value'],
            ];
        }

        $order_date    = $order->get_date_created();
        $customer_name = $order->get_formatted_billing_full_name();
        if ($customer_name === '') {
            $customer_name = $order->get_billing_first_name() ?: $order->get_billing_email();
        }

        $profile_url      = $this->get_profile_url();
        $groups_url       = $this->get_groups_url();
        $credits_url      = $this->get_credits_url();
        $is_credit_topup  = $this->order_contains_credit_topup($order);
        $redirect_url     = $groups_url !== '' ? $groups_url : $profile_url;

        if ($is_credit_topup && $credits_url !== '') {
            $redirect_url = $credits_url;
        }

        return [
            'order_id'        => $order->get_id(),
            'order_number'    => $order->get_order_number(),
            'order_date'      => $order_date ? $order_date->date_i18n(get_option('date_format')) : '',
            'status'          => $status,
            'is_completed'    => $is_completed,
            'is_processing'   => $is_processing && !$is_completed,
            'is_pending'      => !$is_completed && !$is_processing,
            'customer_name'   => $customer_name,
            'billing_email'   => $order->get_billing_email(),
            'payment_method'  => $order->get_payment_method_title(),
            'items'           => $items,
            'totals'          => $totals,
            'total_formatted' => $order->get_formatted_order_total(),
            'primary_item'    => $items[0]['name'] ?? '',
            'profile_url'     => $profile_url,
            'groups_url'      => $groups_url,
            'credits_url'     => $credits_url,
            'redirect_url'    => $redirect_url,
            'is_credit_topup' => $is_credit_topup,
            'flow_type'       => $is_credit_topup ? 'credit_topup' : 'subscription',
            'transition_delay' => (int) apply_filters('juntaplay/checkout/transition_delay', 3200, $order),
            'redirect_delay'   => (int) apply_filters('juntaplay/checkout/redirect_delay', 6500, $order),
            'success_image_url' => $this->get_success_image_url(),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render_template(array $context): void
    {
        $order_number   = $context['order_number'] ?? '';
        $order_date     = $context['order_date'] ?? '';
        $payment_method = $context['payment_method'] ?? '';
        $is_completed   = !empty($context['is_completed']);
        $is_processing  = !empty($context['is_processing']);
        $is_pending     = !empty($context['is_pending']);
        $items          = $context['items'] ?? [];
        $totals         = $context['totals'] ?? [];
        $customer_name  = $context['customer_name'] ?? '';
        $primary_item   = $context['primary_item'] ?? '';
        $total_formatted = $context['total_formatted'] ?? '';
        $billing_email  = $context['billing_email'] ?? '';
        $profile_url    = $context['profile_url'] ?? '';
        $groups_url     = $context['groups_url'] ?? '';
        $credits_url    = $context['credits_url'] ?? '';
        $redirect_url   = $context['redirect_url'] ?? '';
        $transition_delay = isset($context['transition_delay']) ? (int) $context['transition_delay'] : 3200;
        $redirect_delay   = isset($context['redirect_delay']) ? (int) $context['redirect_delay'] : 6500;
        $success_image_url = $context['success_image_url'] ?? '';
        $is_credit_topup = !empty($context['is_credit_topup']);
        $flow_type      = $context['flow_type'] ?? '';

        get_header();
        include JP_DIR . 'templates/checkout/thankyou.php';
        get_footer();
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, mixed>
     */
    private function build_email_blocks(array $context): array
    {
        $customer_name = $context['customer_name'] ?? '';
        $order_number  = $context['order_number'] ?? '';
        $order_date    = $context['order_date'] ?? '';
        $payment       = $context['payment_method'] ?? '';
        $profile_url   = $context['profile_url'] ?? '';
        $groups_url    = $context['groups_url'] ?? '';
        $credits_url   = $context['credits_url'] ?? '';
        $is_credit     = !empty($context['is_credit_topup']);
        $destination   = $groups_url !== '' ? $groups_url : $profile_url;

        if ($is_credit && $credits_url !== '') {
            $destination = $credits_url;
        }

        $greeting = $customer_name !== ''
            ? sprintf(__('Olá %s,', 'juntaplay'), $customer_name)
            : __('Olá!', 'juntaplay');

        $intro_message = $is_credit
            ? __('Recebemos a sua recarga e estamos atualizando o saldo da sua carteira.', 'juntaplay')
            : __('Recebemos o seu pagamento e preparamos os próximos passos da sua assinatura.', 'juntaplay');

        $blocks = [
            [
                'type'    => 'paragraph',
                'content' => $greeting . ' ' . $intro_message,
            ],
        ];

        if ($order_number !== '' || $order_date !== '') {
            $details = [];
            if ($order_number !== '') {
                $details[] = sprintf(__('Número do pedido: %s', 'juntaplay'), $order_number);
            }
            if ($order_date !== '') {
                $details[] = sprintf(__('Data da compra: %s', 'juntaplay'), $order_date);
            }
            if ($payment !== '') {
                $details[] = sprintf(__('Forma de pagamento: %s', 'juntaplay'), $payment);
            }

            $blocks[] = [
                'type'  => 'list',
                'items' => $details,
            ];
        }

        $summary = $this->render_email_order_summary($context);
        if ($summary !== '') {
            $blocks[] = [
                'type'    => 'html',
                'content' => $summary,
            ];
        }

        if ($destination !== '') {
            $button_label = $is_credit
                ? __('Ver meus créditos', 'juntaplay')
                : __('Acessar meu painel', 'juntaplay');

            $blocks[] = [
                'type'  => 'button',
                'label' => $button_label,
                'url'   => $destination,
            ];
        }

        $blocks[] = [
            'type'    => 'paragraph',
            'content' => __('Se precisar de ajuda, basta responder este e-mail ou falar com o suporte JuntaPlay.', 'juntaplay'),
        ];

        return array_values(array_filter($blocks));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render_email_order_summary(array $context): string
    {
        $items  = isset($context['items']) && is_array($context['items']) ? $context['items'] : [];
        $totals = isset($context['totals']) && is_array($context['totals']) ? $context['totals'] : [];

        if (!$items && !$totals) {
            return '';
        }

        ob_start();
        ?>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0 24px;border-collapse:collapse;">
            <?php if ($items) : ?>
                <thead>
                    <tr>
                        <th align="left" style="padding:12px 16px;background:#f8fafc;font-size:13px;text-transform:uppercase;letter-spacing:0.04em;color:#6b7280;">
                            <?php esc_html_e('Produto', 'juntaplay'); ?>
                        </th>
                        <th align="right" style="padding:12px 16px;background:#f8fafc;font-size:13px;text-transform:uppercase;letter-spacing:0.04em;color:#6b7280;">
                            <?php esc_html_e('Total', 'juntaplay'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) :
                        $name  = isset($item['name']) ? (string) $item['name'] : '';
                        $qty   = isset($item['quantity']) ? (int) $item['quantity'] : 0;
                        $total = isset($item['total']) ? (string) $item['total'] : '';
                        ?>
                        <tr>
                            <td style="padding:14px 16px;border-bottom:1px solid #e5e7eb;">
                                <strong style="display:block;color:#1f2937;"><?php echo esc_html($name); ?></strong>
                                <?php if ($qty > 0) : ?>
                                    <span style="color:#6b7280;font-size:13px;">&times; <?php echo esc_html((string) $qty); ?></span>
                                <?php endif; ?>
                            </td>
                            <td align="right" style="padding:14px 16px;border-bottom:1px solid #e5e7eb;color:#1f2937;">
                                <?php echo wp_kses_post($total); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endif; ?>

            <?php if ($totals) : ?>
                <tfoot>
                    <?php foreach ($totals as $total) :
                        $label = isset($total['label']) ? (string) $total['label'] : '';
                        $value = isset($total['value']) ? (string) $total['value'] : '';
                        ?>
                        <tr>
                            <th align="left" style="padding:8px 16px;font-weight:600;color:#1f2937;">
                                <?php echo esc_html($label); ?>
                            </th>
                            <td align="right" style="padding:8px 16px;font-weight:600;color:#1f2937;">
                                <?php echo wp_kses_post($value); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tfoot>
            <?php endif; ?>
        </table>
        <?php

        $html = ob_get_clean();

        return is_string($html) ? trim($html) : '';
    }

    private function order_contains_credit_topup(WC_Order $order): bool
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();

            if ($product && $product->is_type('juntaplay_credit_topup')) {
                return true;
            }

            $deposit_amount = $item->get_meta('_juntaplay_deposit_amount', true);

            if (is_numeric($deposit_amount) && (float) $deposit_amount > 0.0) {
                return true;
            }
        }

        return false;
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

    private function get_groups_url(): string
    {
        $groups_page_id = (int) get_option('juntaplay_page_meus-grupos');
        $url            = $groups_page_id ? (string) get_permalink($groups_page_id) : '';

        if ($url === '') {
            $url = home_url('/meus-grupos');
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

    private function get_success_image_url(): string
    {
        if (!defined('JP_URL')) {
            return '';
        }

        $base = trailingslashit(JP_URL);

        return $base . 'assets/images/agradecimento.gif';
    }
}
