<?php
/**
 * Checkout confirmation screen.
 *
 * @var string $customer_name
 * @var string $primary_item
 * @var string $order_number
 * @var string $order_date
 * @var string $payment_method
 * @var bool   $is_completed
 * @var bool   $is_processing
 * @var bool   $is_pending
 * @var array<int, array<string, mixed>> $items
 * @var array<int, array<string, string>> $totals
 * @var string $total_formatted
 * @var string $billing_email
 * @var string $profile_url
 */

defined('ABSPATH') || exit;

$primary_item = $primary_item ?: $customer_name;
$profile_url  = isset($profile_url) ? (string) $profile_url : '';
?>

<div class="jp-checkout-confirmation">
    <style>
        .jp-checkout-confirmation {
            background: #f5f7fb;
            padding: 48px 16px 96px;
        }

        .jp-checkout-confirmation__wrapper {
            max-width: 960px;
            margin: 0 auto;
        }

        .jp-checkout-confirmation__panel {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.08);
            padding: 48px;
            text-align: center;
        }

        .jp-checkout-confirmation__panel h1 {
            font-size: 2rem;
            margin-top: 24px;
            margin-bottom: 16px;
            color: #13141b;
        }

        .jp-checkout-confirmation__panel p {
            font-size: 1rem;
            color: #4b5563;
            margin: 0 auto 12px;
            max-width: 540px;
        }

        .jp-checkout-confirmation__status {
            width: 120px;
            height: 120px;
            margin: 0 auto;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .jp-checkout-confirmation__status--success {
            background: linear-gradient(135deg, var(--jp-primary, #ff5a5f), var(--jp-primary-dark, #e24e53));
        }

        .jp-checkout-confirmation__status--progress {
            background: linear-gradient(135deg, #ef4444, #f97316);
            position: relative;
            overflow: hidden;
        }

        .jp-checkout-confirmation__status--progress::after {
            content: "";
            width: 160px;
            height: 160px;
            background: rgba(255, 255, 255, 0.25);
            position: absolute;
            border-radius: 50%;
            animation: jp-confirmation-spin 1.6s linear infinite;
        }

        @keyframes jp-confirmation-spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .jp-checkout-confirmation__details {
            margin-top: 48px;
            display: grid;
            gap: 24px;
        }

        .jp-checkout-confirmation__summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
        }

        .jp-checkout-confirmation__summary-card {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .jp-checkout-confirmation__summary-card h2 {
            font-size: 1.125rem;
            margin: 0 0 16px;
            color: #111827;
        }

        .jp-checkout-confirmation__summary-card dl {
            margin: 0;
        }

        .jp-checkout-confirmation__summary-card dt {
            font-weight: 600;
            color: #1f2937;
        }

        .jp-checkout-confirmation__summary-card dd {
            margin: 0 0 16px;
            color: #4b5563;
        }

        .jp-checkout-confirmation__items {
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            text-align: left;
        }

        .jp-checkout-confirmation__items h2 {
            font-size: 1.125rem;
            margin: 0 0 16px;
        }

        .jp-checkout-confirmation__item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .jp-checkout-confirmation__item:last-child {
            border-bottom: none;
        }

        .jp-checkout-confirmation__item-name {
            font-weight: 600;
            color: #111827;
        }

        .jp-checkout-confirmation__item-qty {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .jp-checkout-confirmation__item-total {
            font-weight: 600;
            color: var(--jp-primary-dark, #e24e53);
        }

        .jp-checkout-confirmation__totals {
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
        }

        .jp-checkout-confirmation__total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .jp-checkout-confirmation__total-row strong {
            color: #111827;
        }

        .jp-checkout-confirmation__note {
            margin-top: 16px;
            color: #6b7280;
        }

        .jp-checkout-confirmation__note strong {
            color: #111827;
        }

        .jp-checkout-confirmation__actions {
            margin-top: 32px;
        }

        .jp-checkout-confirmation__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border-radius: 999px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--jp-primary, #ff5a5f), var(--jp-primary-dark, #e24e53));
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .jp-checkout-confirmation__cta:hover,
        .jp-checkout-confirmation__cta:focus {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(226, 78, 83, 0.35);
        }

        @media (max-width: 768px) {
            .jp-checkout-confirmation__panel {
                padding: 32px 24px;
            }

            .jp-checkout-confirmation__summary {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="jp-checkout-confirmation__wrapper">
        <div class="jp-checkout-confirmation__panel">
            <div class="jp-checkout-confirmation__status <?php echo $is_completed ? 'jp-checkout-confirmation__status--success' : 'jp-checkout-confirmation__status--progress'; ?>">
                <?php if ($is_completed) : ?>
                    <span aria-hidden="true" style="font-size:56px;">✓</span>
                <?php else : ?>
                    <span aria-hidden="true" style="font-size:56px;">⏳</span>
                <?php endif; ?>
            </div>

            <?php if ($is_completed) : ?>
                <h1><?php esc_html_e('Parabéns!', 'juntaplay'); ?></h1>
                <p>
                    <?php
                    printf(
                        /* translators: %1$s: customer name, %2$s: primary item */
                        esc_html__('Agora você faz parte do grupo %2$s. Bem-vindo(a), %1$s!', 'juntaplay'),
                        esc_html($customer_name ?: ''),
                        esc_html($primary_item ?: '')
                    );
                    ?>
                </p>
            <?php elseif ($is_processing) : ?>
                <h1><?php esc_html_e('Finalizando sua inscrição', 'juntaplay'); ?></h1>
                <p><?php esc_html_e('Estamos concluindo a sua assinatura. Assim que tudo estiver pronto você receberá um e-mail com a confirmação.', 'juntaplay'); ?></p>
            <?php else : ?>
                <h1><?php esc_html_e('Estamos quase lá!', 'juntaplay'); ?></h1>
                <p><?php esc_html_e('Estamos validando as informações do seu pedido. Fique de olho no seu e-mail para os próximos passos.', 'juntaplay'); ?></p>
            <?php endif; ?>

            <p><?php esc_html_e('Enviamos o resumo da compra para o seu e-mail e vamos avisar assim que tudo estiver liberado.', 'juntaplay'); ?></p>
            <?php if ($billing_email !== '') : ?>
                <p class="jp-checkout-confirmation__note">
                    <?php
                    printf(
                        wp_kses(
                            /* translators: %s: customer email */
                            __('Confira a caixa de entrada em <strong>%s</strong> e acompanhe qualquer atualização pelo seu painel.', 'juntaplay'),
                            ['strong' => []]
                        ),
                        esc_html($billing_email)
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="jp-checkout-confirmation__details">
            <div class="jp-checkout-confirmation__summary">
                <div class="jp-checkout-confirmation__summary-card">
                    <h2><?php esc_html_e('Resumo da assinatura', 'juntaplay'); ?></h2>
                    <dl>
                        <dt><?php esc_html_e('Número da assinatura', 'juntaplay'); ?></dt>
                        <dd><?php echo esc_html($order_number); ?></dd>

                        <dt><?php esc_html_e('Data', 'juntaplay'); ?></dt>
                        <dd><?php echo esc_html($order_date); ?></dd>

                        <dt><?php esc_html_e('Forma de pagamento', 'juntaplay'); ?></dt>
                        <dd><?php echo esc_html($payment_method); ?></dd>

                        <dt><?php esc_html_e('E-mail de contato', 'juntaplay'); ?></dt>
                        <dd><?php echo esc_html($billing_email); ?></dd>
                    </dl>
                </div>

                <div class="jp-checkout-confirmation__summary-card">
                    <h2><?php esc_html_e('Status da inscrição', 'juntaplay'); ?></h2>
                    <dl>
                        <dt><?php esc_html_e('Situação atual', 'juntaplay'); ?></dt>
                        <dd>
                            <?php
                            if ($is_completed) {
                                esc_html_e('Assinatura confirmada', 'juntaplay');
                            } elseif ($is_processing) {
                                esc_html_e('Processando pagamento', 'juntaplay');
                            } else {
                                esc_html_e('Aguardando confirmação', 'juntaplay');
                            }
                            ?>
                        </dd>
                        <dt><?php esc_html_e('Próximos passos', 'juntaplay'); ?></dt>
                        <dd>
                            <?php
                            if ($is_completed) {
                                esc_html_e('Você já pode acessar a página do grupo e começar a participar.', 'juntaplay');
                            } elseif ($is_processing) {
                                esc_html_e('Em instantes enviaremos um e-mail com as instruções finais.', 'juntaplay');
                            } else {
                                esc_html_e('Entraremos em contato assim que o pagamento for confirmado.', 'juntaplay');
                            }
                            ?>
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="jp-checkout-confirmation__items">
                <h2><?php esc_html_e('Detalhes da assinatura', 'juntaplay'); ?></h2>

                <?php foreach ($items as $item) : ?>
                    <div class="jp-checkout-confirmation__item">
                        <div>
                            <div class="jp-checkout-confirmation__item-name"><?php echo esc_html($item['name'] ?? ''); ?></div>
                            <div class="jp-checkout-confirmation__item-qty">
                                <?php
                                printf(
                                    /* translators: %s: quantity */
                                    esc_html__('Quantidade: %s', 'juntaplay'),
                                    esc_html((string) ($item['quantity'] ?? 1))
                                );
                                ?>
                            </div>
                        </div>
                        <div class="jp-checkout-confirmation__item-total"><?php echo wp_kses_post($item['total'] ?? ''); ?></div>
                    </div>
                <?php endforeach; ?>

                <?php if (!empty($totals)) : ?>
                    <div class="jp-checkout-confirmation__totals">
                        <?php foreach ($totals as $total) : ?>
                            <div class="jp-checkout-confirmation__total-row">
                                <span><?php echo esc_html($total['label'] ?? ''); ?></span>
                                <strong><?php echo wp_kses_post($total['value'] ?? ''); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($profile_url !== '') : ?>
            <div class="jp-checkout-confirmation__actions">
                <a class="jp-checkout-confirmation__cta" href="<?php echo esc_url($profile_url); ?>">
                    <?php esc_html_e('Ir para meu painel', 'juntaplay'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
