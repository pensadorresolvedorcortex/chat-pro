<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/** @var WC_Order $statement_order */
/** @var array<int, array{pool_id:int,title:string,link:string,numbers:array<int,int>,statuses:array<int,string>,line_total:float,quantity:int,line_subtotal:float}> $statement_items */
/** @var float $statement_balance */

$format_datetime = static function ($datetime) {
    if (!$datetime) {
        return '';
    }

    if (function_exists('wc_format_datetime')) {
        return wc_format_datetime($datetime);
    }

    return $datetime->date_i18n(get_option('date_format') . ' ' . get_option('time_format'));
};

$created_at = $statement_order->get_date_created();
$updated_at = $statement_order->get_date_modified() ?: $created_at;
$order_number = $statement_order->get_order_number();
$order_status = $statement_order->get_status();
$status_label = function_exists('wc_get_order_status_name') ? wc_get_order_status_name($order_status) : ucfirst((string) $order_status);
$total_paid = function_exists('wc_price') ? wc_price($statement_order->get_total()) : number_format_i18n((float) $statement_order->get_total(), 2);
$subtotal = function_exists('wc_price') ? wc_price($statement_order->get_subtotal()) : number_format_i18n((float) $statement_order->get_subtotal(), 2);
$payment_method = $statement_order->get_payment_method_title() ?: $statement_order->get_payment_method();
$payment_method = $payment_method ?: __('Não informado', 'juntaplay');
$updated_at_display = $format_datetime($updated_at);
$created_at_display = $format_datetime($created_at);
$balance_display = function_exists('wc_price') ? wc_price($statement_balance) : number_format_i18n($statement_balance, 2);
$campaigns_id = (int) get_option('juntaplay_page_campanhas');
$campaigns_link = $campaigns_id ? get_permalink($campaigns_id) : home_url('/');

$status_labels = [
    'available' => __('Disponível', 'juntaplay'),
    'reserved'  => __('Reservada', 'juntaplay'),
    'paid'      => __('Paga', 'juntaplay'),
    'canceled'  => __('Cancelada', 'juntaplay'),
    'expired'   => __('Expirada', 'juntaplay'),
];

$orders_url = wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'));
?>
<section class="juntaplay-statement juntaplay-section" aria-labelledby="juntaplay-statement-title">
    <header class="juntaplay-statement__header">
        <div>
            <p class="juntaplay-statement__eyebrow"><?php esc_html_e('Extrato', 'juntaplay'); ?></p>
            <h1 id="juntaplay-statement-title">#<?php echo esc_html((string) $order_number); ?></h1>
        </div>
        <div class="juntaplay-statement__header-meta">
            <?php if ($created_at_display) : ?>
                <span><?php echo esc_html(sprintf(__('Criado em %s', 'juntaplay'), $created_at_display)); ?></span>
            <?php endif; ?>
            <?php if ($updated_at_display && $updated_at_display !== $created_at_display) : ?>
                <span><?php echo esc_html(sprintf(__('Atualizado em %s', 'juntaplay'), $updated_at_display)); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="juntaplay-statement__grid">
        <article class="juntaplay-card juntaplay-card--highlight juntaplay-statement__card" aria-labelledby="juntaplay-statement-info">
            <header class="juntaplay-card__header">
                <h2 id="juntaplay-statement-info"><?php esc_html_e('Informações do pedido', 'juntaplay'); ?></h2>
                <span class="juntaplay-status juntaplay-status--<?php echo esc_attr(sanitize_html_class((string) $order_status)); ?>"><?php echo esc_html($status_label); ?></span>
            </header>
            <div class="juntaplay-card__body">
                <ul class="juntaplay-statement__list">
                    <li>
                        <span class="juntaplay-statement__label"><?php esc_html_e('Método de pagamento', 'juntaplay'); ?></span>
                        <span class="juntaplay-statement__value"><?php echo esc_html($payment_method); ?></span>
                    </li>
                    <li>
                        <span class="juntaplay-statement__label"><?php esc_html_e('Subtotal', 'juntaplay'); ?></span>
                        <span class="juntaplay-statement__value"><?php echo wp_kses_post($subtotal); ?></span>
                    </li>
                    <li>
                        <span class="juntaplay-statement__label"><?php esc_html_e('Total pago', 'juntaplay'); ?></span>
                        <span class="juntaplay-statement__value juntaplay-statement__value--total"><?php echo wp_kses_post($total_paid); ?></span>
                    </li>
                    <li>
                        <span class="juntaplay-statement__label"><?php esc_html_e('Saldo disponível', 'juntaplay'); ?></span>
                        <span class="juntaplay-statement__value"><?php echo wp_kses_post($balance_display); ?></span>
                    </li>
                </ul>
            </div>
            <footer class="juntaplay-card__footer">
                <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($campaigns_link); ?>">
                    <?php esc_html_e('Comprar cotas', 'juntaplay'); ?>
                </a>
                <a class="juntaplay-button" href="<?php echo esc_url($orders_url); ?>">
                    <?php esc_html_e('Voltar para pedidos', 'juntaplay'); ?>
                </a>
            </footer>
        </article>

        <div class="juntaplay-statement__details">
            <?php foreach ($statement_items as $item) :
                $item_numbers = $item['numbers'];
                $item_total_display = function_exists('wc_price') ? wc_price($item['line_total']) : number_format_i18n((float) $item['line_total'], 2);
                $pool_link = $item['link'];
                ?>
                <article class="juntaplay-card juntaplay-card--compact">
                    <header class="juntaplay-card__header">
                        <div>
                            <h3>
                                <?php if ($pool_link) : ?>
                                    <a class="juntaplay-link" href="<?php echo esc_url($pool_link); ?>"><?php echo esc_html($item['title']); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($item['title']); ?>
                                <?php endif; ?>
                            </h3>
                            <p class="juntaplay-card__meta"><?php echo esc_html(sprintf(_n('%s cota selecionada', '%s cotas selecionadas', count($item_numbers), 'juntaplay'), number_format_i18n(count($item_numbers)))); ?></p>
                        </div>
                        <div class="juntaplay-card__price">
                            <?php echo wp_kses_post($item_total_display); ?>
                        </div>
                    </header>
                    <div class="juntaplay-card__body">
                        <div class="juntaplay-statement__numbers" aria-label="<?php esc_attr_e('Cotas adquiridas', 'juntaplay'); ?>">
                            <?php foreach ($item_numbers as $number) :
                                $status_key = $item['statuses'][$number] ?? '';
                                $status_label_quota = $status_key && isset($status_labels[$status_key]) ? $status_labels[$status_key] : ($status_key ? ucfirst($status_key) : __('N/A', 'juntaplay'));
                                ?>
                                <span class="juntaplay-bubble">
                                    <strong><?php echo esc_html(number_format_i18n($number)); ?></strong>
                                    <?php if ($status_key) : ?>
                                        <small class="juntaplay-status juntaplay-status--<?php echo esc_attr(sanitize_html_class($status_key)); ?>"><?php echo esc_html($status_label_quota); ?></small>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
