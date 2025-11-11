<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$context  = isset($template_context) && is_array($template_context) ? $template_context : [];
$checkout = function_exists('WC') ? WC()->checkout() : null;

if (!$checkout) {
    echo '<div class="jp-credit-checkout"><div class="jp-credit-checkout__container">';
    echo '<p>' . esc_html__('Não foi possível carregar o checkout agora. Atualize a página e tente novamente.', 'juntaplay') . '</p>';
    echo '</div></div>';

    return;
}

$amount_display = isset($context['amount_display']) ? (string) $context['amount_display'] : '';
$reference      = isset($context['reference']) ? (string) $context['reference'] : '';
$product_name   = isset($context['product_name']) ? (string) $context['product_name'] : '';
$customer_name  = isset($context['customer_name']) ? (string) $context['customer_name'] : '';
$customer_email = isset($context['customer_email']) ? (string) $context['customer_email'] : '';
$cart_items     = isset($context['cart_items']) && is_array($context['cart_items']) ? $context['cart_items'] : [];
$cart_totals    = isset($context['cart_totals']) && is_array($context['cart_totals']) ? $context['cart_totals'] : [];
$support_email  = isset($context['support_email']) ? (string) $context['support_email'] : '';
$balance_label  = isset($context['balance_label']) ? (string) $context['balance_label'] : '';
$credits_url    = isset($context['credits_url']) ? (string) $context['credits_url'] : '';

wc_print_notices();
?>
<div class="jp-credit-checkout" data-jp-credit-checkout>
    <div class="jp-credit-checkout__container">
        <header class="jp-credit-checkout__header">
            <span class="jp-credit-checkout__eyebrow"><?php esc_html_e('Adicionar créditos', 'juntaplay'); ?></span>
            <h1 class="jp-credit-checkout__title"><?php esc_html_e('Finalize sua recarga de créditos', 'juntaplay'); ?></h1>
            <p class="jp-credit-checkout__lead">
                <?php
                if ($customer_name !== '') {
                    echo esc_html(sprintf(__('Olá, %s! Revise as informações da recarga e conclua o pagamento em um ambiente seguro.', 'juntaplay'), $customer_name));
                } else {
                    esc_html_e('Revise as informações da recarga e conclua o pagamento em um ambiente seguro.', 'juntaplay');
                }
                ?>
            </p>
            <?php if ($customer_email !== '') : ?>
                <p class="jp-credit-checkout__lead">
                    <?php echo esc_html(sprintf(__('Pagamento vinculado a %s', 'juntaplay'), $customer_email)); ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="jp-credit-checkout__layout">
            <section class="jp-credit-checkout__form" aria-label="<?php esc_attr_e('Formulário de checkout seguro', 'juntaplay'); ?>">
                <div class="woocommerce">
                    <?php
                    if (function_exists('woocommerce_checkout_form')) {
                        woocommerce_checkout_form();
                    } else {
                        wc_get_template('checkout/form-checkout.php', ['checkout' => $checkout]);
                    }
                    ?>
                </div>
            </section>

            <aside class="jp-credit-checkout__sidebar" aria-label="<?php esc_attr_e('Resumo da compra e suporte', 'juntaplay'); ?>">
                <section class="jp-credit-checkout__card">
                    <h2><?php esc_html_e('Resumo da recarga', 'juntaplay'); ?></h2>
                    <div class="jp-credit-checkout__meta">
                        <?php if ($product_name !== '') : ?>
                            <span><?php echo esc_html($product_name); ?></span>
                        <?php endif; ?>
                        <?php if ($amount_display !== '') : ?>
                            <strong><?php echo wp_kses_post($amount_display); ?></strong>
                        <?php endif; ?>
                    </div>
                    <?php if ($reference !== '') : ?>
                        <div class="jp-credit-checkout__meta">
                            <span><?php esc_html_e('Referência', 'juntaplay'); ?></span>
                            <strong><?php echo esc_html($reference); ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($cart_items) : ?>
                        <ul class="jp-credit-checkout__items">
                            <?php foreach ($cart_items as $item) :
                                $item_name  = isset($item['name']) ? (string) $item['name'] : '';
                                $item_total = isset($item['total']) ? (string) $item['total'] : '';
                                ?>
                                <li class="jp-credit-checkout__item">
                                    <span><?php echo esc_html($item_name); ?></span>
                                    <span><?php echo wp_kses_post($item_total); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($cart_totals['total'])) : ?>
                        <div class="jp-credit-checkout__meta">
                            <span><?php esc_html_e('Total a pagar', 'juntaplay'); ?></span>
                            <strong><?php echo wp_kses_post((string) $cart_totals['total']); ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($cart_totals['fees']) && is_array($cart_totals['fees'])) : ?>
                        <?php foreach ($cart_totals['fees'] as $fee) :
                            $fee_name  = isset($fee['name']) ? (string) $fee['name'] : '';
                            $fee_total = isset($fee['total']) ? (string) $fee['total'] : '';
                            ?>
                            <div class="jp-credit-checkout__meta">
                                <span><?php echo esc_html($fee_name); ?></span>
                                <strong><?php echo wp_kses_post($fee_total); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <p class="jp-credit-checkout__support">
                        <?php
                        if ($support_email !== '') {
                            printf(
                                esc_html__('Em caso de dúvidas, fale com nosso time em %s.', 'juntaplay'),
                                '<a href="mailto:' . esc_attr($support_email) . '">' . esc_html($support_email) . '</a>'
                            );
                        } else {
                            esc_html_e('Em caso de dúvidas, fale com nosso time de suporte.', 'juntaplay');
                        }
                        ?>
                    </p>
                </section>

                <section class="jp-credit-checkout__card">
                    <h3><?php esc_html_e('Saldo atual', 'juntaplay'); ?></h3>
                    <div class="jp-credit-checkout__balance">
                        <span><?php esc_html_e('Saldo disponível na carteira', 'juntaplay'); ?></span>
                        <?php if ($balance_label !== '') : ?>
                            <strong><?php echo wp_kses_post($balance_label); ?></strong>
                        <?php endif; ?>
                    </div>
                    <p class="jp-credit-checkout__hint">
                        <?php esc_html_e('Assim que o pagamento for aprovado, adicionaremos automaticamente os créditos na sua conta e enviaremos uma confirmação por e-mail.', 'juntaplay'); ?>
                    </p>
                    <?php if ($credits_url !== '') : ?>
                        <a class="jp-credit-checkout__link" href="<?php echo esc_url($credits_url); ?>">
                            <?php esc_html_e('Voltar para meus créditos', 'juntaplay'); ?>
                        </a>
                    <?php endif; ?>
                </section>
            </aside>
        </div>
    </div>
</div>
