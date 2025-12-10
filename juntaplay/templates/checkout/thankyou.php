<?php
/**
 * Checkout confirmation screen with staged feedback.
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
 * @var string $groups_url
 * @var string $redirect_url
 * @var int    $transition_delay
 * @var int    $redirect_delay
 */

defined('ABSPATH') || exit;

$primary_item    = $primary_item ?: $customer_name;
$profile_url     = isset($profile_url) ? (string) $profile_url : '';
$groups_url      = isset($groups_url) ? (string) $groups_url : '';
$redirect_url    = isset($redirect_url) ? (string) $redirect_url : ($groups_url ?: $profile_url);
$transition_ms   = isset($transition_delay) ? max(0, (int) $transition_delay) : 3200;
$redirect_ms     = isset($redirect_delay) ? max(0, (int) $redirect_delay) : 6500;
$initial_state   = ($is_completed || $is_processing) ? 'success' : 'pending';
$auto_transition = $initial_state === 'pending' ? '1' : '0';
$should_redirect = $redirect_url !== '' ? '1' : '0';
$destination_url = $redirect_url ?: ($groups_url ?: $profile_url);
$is_credit_topup = !empty($is_credit_topup) || (isset($flow_type) && (string) $flow_type === 'credit_topup');
$credits_url     = isset($credits_url) ? (string) $credits_url : '';

if ($is_credit_topup && $destination_url === '') {
    $destination_url = $credits_url !== '' ? $credits_url : $profile_url;
}

$pending_leads = $is_credit_topup
    ? [
        __('Estamos validando os dados da sua recarga e atualizando o saldo da sua carteira.', 'juntaplay'),
        __('Isso leva apenas alguns instantes. Assim que tudo estiver confirmado nós mostraremos os próximos passos aqui mesmo.', 'juntaplay'),
    ]
    : [
        __('Estamos validando as informações do seu pedido e preparando a ativação da sua assinatura.', 'juntaplay'),
        __('Esse processo leva apenas alguns instantes. Assim que tudo estiver confirmado nós mostraremos os próximos passos aqui mesmo.', 'juntaplay'),
    ];

$customer_display_name = $customer_name !== '' ? $customer_name : '';
$primary_display_item  = $primary_item !== '' ? $primary_item : __('seu grupo', 'juntaplay');

$subscription_success_lead = sprintf(
    /* translators: %1$s: customer name, %2$s: primary item */
    __('Bem-vindo(a) ao grupo %2$s, %1$s! Agora é só acessar o painel para começar a participar.', 'juntaplay'),
    $customer_display_name,
    $primary_display_item
);

$credit_success_intro = $customer_display_name !== ''
    ? sprintf(__('Tudo pronto, %s! Assim que o pagamento for confirmado o valor aparecerá na sua carteira.', 'juntaplay'), $customer_display_name)
    : __('Tudo pronto! Assim que o pagamento for confirmado o valor aparecerá na sua carteira.', 'juntaplay');

$success_leads = $is_credit_topup
    ? [
        $credit_success_intro,
        __('Você pode acompanhar o histórico e solicitar novas recargas na página de créditos.', 'juntaplay'),
    ]
    : [$subscription_success_lead];

$pending_leads = array_values(array_filter($pending_leads, static function ($line): bool {
    return is_string($line) && trim($line) !== '';
}));

$success_leads = array_values(array_filter($success_leads, static function ($line): bool {
    return is_string($line) && trim($line) !== '';
}));

$success_title      = $is_credit_topup ? __('Parabéns, você adicionou um novo saldo!', 'juntaplay') : __('Parabéns! Sua assinatura foi ativada.', 'juntaplay');
$summary_title      = $is_credit_topup ? __('Resumo da recarga', 'juntaplay') : __('Resumo da assinatura', 'juntaplay');
$items_title        = $is_credit_topup ? __('Detalhes da recarga', 'juntaplay') : __('Itens confirmados', 'juntaplay');
$cta_label          = $is_credit_topup ? __('Ver meus créditos', 'juntaplay') : __('Ir para meus grupos', 'juntaplay');
$footer_hint_text   = $is_credit_topup ? __('Estamos atualizando seu saldo automaticamente…', 'juntaplay') : __('Estamos redirecionando automaticamente…', 'juntaplay');
$success_image_alt  = $is_credit_topup ? __('Confirmação da recarga de saldo JuntaPlay', 'juntaplay') : __('Confirmação da assinatura JuntaPlay', 'juntaplay');
?>

<div class="jp-checkout-flow" data-jp-checkout-flow
    data-initial-state="<?php echo esc_attr($initial_state); ?>"
    data-auto-transition="<?php echo esc_attr($auto_transition); ?>"
    data-transition-delay="<?php echo esc_attr((string) $transition_ms); ?>"
    data-redirect-delay="<?php echo esc_attr((string) $redirect_ms); ?>"
    data-redirect-url="<?php echo esc_attr($destination_url); ?>"
    data-should-redirect="<?php echo esc_attr($should_redirect); ?>">
    <style>
        .jp-checkout-flow {
            background: #f5f7fb;
            min-height: 100vh;
            padding: 48px 16px 96px;
        }

        .jp-checkout-flow__wrapper {
            max-width: 1040px;
            margin: 0 auto;
            display: grid;
            gap: 40px;
        }

        .jp-checkout-flow__stage {
            display: none;
        }

        .jp-checkout-flow__stage.is-active {
            display: block;
        }

        .jp-checkout-flow__card {
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 32px 64px rgba(15, 23, 42, 0.12);
            padding: clamp(32px, 5vw, 56px);
            text-align: center;
        }

        .jp-checkout-flow__icon {
            width: 112px;
            height: 112px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 56px;
            background: linear-gradient(135deg, #f97316, #ef4444);
            position: relative;
            overflow: hidden;
        }

        .jp-checkout-flow__icon::after {
            content: "";
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.22);
            animation: jp-checkout-pulse 1.8s linear infinite;
        }

        .jp-checkout-flow__stage--success .jp-checkout-flow__icon,
        .jp-checkout-flow__stage--success .jp-checkout-flow__icon::after {
            animation: none;
            background: linear-gradient(135deg, var(--jp-primary, #ff5a5f), var(--jp-primary-dark, #e24e53));
        }

        .jp-checkout-flow__stage--success .jp-checkout-flow__icon::after {
            opacity: 0.18;
        }

        @keyframes jp-checkout-pulse {
            0% {
                transform: scale(0.65) rotate(0deg);
            }
            100% {
                transform: scale(1) rotate(360deg);
            }
        }

        .jp-checkout-flow__title {
            font-size: clamp(2rem, 3vw, 2.5rem);
            margin-bottom: 16px;
            color: #111827;
        }

        .jp-checkout-flow__lead {
            font-size: 1.05rem;
            color: #4b5563;
            margin: 0 auto 12px;
            max-width: 560px;
        }

        .jp-checkout-flow__hint {
            color: #6b7280;
            margin: 16px auto 0;
            max-width: 520px;
        }

        .jp-checkout-flow__hint strong {
            color: #111827;
        }

        .jp-checkout-flow__success {
            display: grid;
            gap: 32px;
        }

        .jp-checkout-flow__success-inner {
            display: grid;
            gap: clamp(24px, 4vw, 40px);
            align-items: start;
        }

        .jp-checkout-flow__illustration {
            max-width: 320px;
            width: 100%;
            margin: 0 auto;
        }

        .jp-checkout-flow__illustration img {
            display: block;
            width: 100%;
            height: auto;
            border-radius: 20px;
        }

        .jp-checkout-flow__summary {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        .jp-checkout-flow__summary-card,
        .jp-checkout-flow__items {
            background: #fff;
            border-radius: 24px;
            padding: clamp(24px, 3vw, 32px);
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.12);
            text-align: left;
        }

        .jp-checkout-flow__summary-card h2,
        .jp-checkout-flow__items h2 {
            margin: 0 0 16px;
            font-size: 1.25rem;
            color: #111827;
        }

        .jp-checkout-flow__summary-card dl {
            margin: 0;
        }

        .jp-checkout-flow__summary-card dt {
            font-weight: 600;
            color: #1f2937;
        }

        .jp-checkout-flow__summary-card dd {
            margin: 0 0 14px;
            color: #4b5563;
        }

        .jp-checkout-flow__item {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .jp-checkout-flow__item:last-child {
            border-bottom: none;
        }

        .jp-checkout-flow__item-name {
            font-weight: 600;
            color: #111827;
        }

        .jp-checkout-flow__item-qty {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .jp-checkout-flow__item-total {
            font-weight: 700;
            color: var(--jp-primary-dark, #e24e53);
        }

        .jp-checkout-flow__totals {
            margin-top: 20px;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            padding-top: 16px;
        }

        .jp-checkout-flow__total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .jp-checkout-flow__total-row strong {
            color: #0f172a;
        }

        .jp-checkout-flow__cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 32px;
            border-radius: 999px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--jp-primary, #ff5a5f), var(--jp-primary-dark, #e24e53));
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .jp-checkout-flow__cta:hover,
        .jp-checkout-flow__cta:focus {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px rgba(226, 78, 83, 0.32);
        }

        .jp-checkout-flow__footer-hint {
            margin-top: 16px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .jp-checkout-flow__card {
                padding: 28px 22px;
            }

            .jp-checkout-flow__summary,
            .jp-checkout-flow__success-inner {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="jp-checkout-flow__wrapper">
        <section class="jp-checkout-flow__stage jp-checkout-flow__stage--pending<?php echo $initial_state === 'pending' ? ' is-active' : ''; ?>" data-jp-stage="pending" aria-live="polite">
            <div class="jp-checkout-flow__card">
                <div class="jp-checkout-flow__icon" aria-hidden="true">⏳</div>
                <h1 class="jp-checkout-flow__title"><?php esc_html_e('Estamos quase lá!', 'juntaplay'); ?></h1>
                <?php foreach ($pending_leads as $lead) : ?>
                    <p class="jp-checkout-flow__lead"><?php echo esc_html($lead); ?></p>
                <?php endforeach; ?>
                <?php if ($billing_email !== '') : ?>
                    <p class="jp-checkout-flow__hint">
                        <?php
                        printf(
                            wp_kses(
                                /* translators: %s: customer email */
                                __('Enquanto isso, fique de olho no e-mail <strong>%s</strong>. Nós também enviaremos a confirmação por lá.', 'juntaplay'),
                                ['strong' => []]
                            ),
                            esc_html($billing_email)
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <section class="jp-checkout-flow__stage jp-checkout-flow__stage--success<?php echo $initial_state === 'success' ? ' is-active' : ''; ?>" data-jp-stage="success" aria-live="polite">
            <div class="jp-checkout-flow__success">
                <div class="jp-checkout-flow__card">
                    <div class="jp-checkout-flow__icon" aria-hidden="true">✓</div>
                    <h1 class="jp-checkout-flow__title"><?php echo esc_html($success_title); ?></h1>
                    <?php foreach ($success_leads as $line) : ?>
                        <p class="jp-checkout-flow__lead"><?php echo esc_html($line); ?></p>
                    <?php endforeach; ?>
                    <?php if (!empty($success_image_url)) : ?>
                        <div class="jp-checkout-flow__illustration">
                            <img src="<?php echo esc_url($success_image_url); ?>" alt="<?php echo esc_attr($success_image_alt); ?>">
                        </div>
                    <?php endif; ?>
                    <?php if ($destination_url !== '') : ?>
                        <a class="jp-checkout-flow__cta" href="<?php echo esc_url($destination_url); ?>">
                            <?php echo esc_html($cta_label); ?>
                            <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                    <p class="jp-checkout-flow__footer-hint"><?php echo esc_html($footer_hint_text); ?></p>
                </div>

                <div class="jp-checkout-flow__success-inner">
                    <div class="jp-checkout-flow__summary-card">
                        <h2><?php echo esc_html($summary_title); ?></h2>
                        <?php
                        $order_number_label = $is_credit_topup
                            ? __('Número da recarga', 'juntaplay')
                            : __('Número da assinatura', 'juntaplay');
                        ?>
                        <dl>
                            <dt><?php echo esc_html($order_number_label); ?></dt>
                            <dd><?php echo esc_html($order_number); ?></dd>

                            <dt><?php esc_html_e('Data da compra', 'juntaplay'); ?></dt>
                            <dd><?php echo esc_html($order_date); ?></dd>

                            <dt><?php esc_html_e('Forma de pagamento', 'juntaplay'); ?></dt>
                            <dd><?php echo esc_html($payment_method); ?></dd>

                            <dt><?php esc_html_e('E-mail de contato', 'juntaplay'); ?></dt>
                            <dd><?php echo esc_html($billing_email); ?></dd>
                        </dl>
                    </div>

                    <div class="jp-checkout-flow__items">
                        <h2><?php echo esc_html($items_title); ?></h2>

                        <?php foreach ($items as $item) : ?>
                            <div class="jp-checkout-flow__item">
                                <div>
                                    <div class="jp-checkout-flow__item-name"><?php echo esc_html($item['name'] ?? ''); ?></div>
                                    <div class="jp-checkout-flow__item-qty">
                                        <?php
                                        printf(
                                            /* translators: %s: quantity */
                                            esc_html__('Quantidade: %s', 'juntaplay'),
                                            esc_html((string) ($item['quantity'] ?? 1))
                                        );
                                        ?>
                                    </div>
                                </div>
                                <div class="jp-checkout-flow__item-total"><?php echo wp_kses_post($item['total'] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!empty($totals)) : ?>
                            <div class="jp-checkout-flow__totals">
                                <?php foreach ($totals as $total) : ?>
                                    <div class="jp-checkout-flow__total-row">
                                        <span><?php echo esc_html($total['label'] ?? ''); ?></span>
                                        <strong><?php echo wp_kses_post($total['value'] ?? ''); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    (function () {
        var root = document.querySelector('[data-jp-checkout-flow]');

        if (!root) {
            return;
        }

        var pendingStage = root.querySelector('[data-jp-stage="pending"]');
        var successStage = root.querySelector('[data-jp-stage="success"]');
        var initialState = root.getAttribute('data-initial-state') || 'pending';
        var autoTransition = root.getAttribute('data-auto-transition') === '1';
        var transitionDelay = parseInt(root.getAttribute('data-transition-delay') || '0', 10) || 0;
        var redirectDelay = parseInt(root.getAttribute('data-redirect-delay') || '0', 10) || 0;
        var redirectUrl = root.getAttribute('data-redirect-url') || '';
        var shouldRedirect = root.getAttribute('data-should-redirect') === '1';

        function activate(stage) {
            if (!stage) {
                return;
            }

            [pendingStage, successStage].forEach(function (element) {
                if (!element) {
                    return;
                }

                element.classList.remove('is-active');
            });

            stage.classList.add('is-active');
        }

        function showSuccess() {
            activate(successStage);

            if (shouldRedirect && redirectUrl) {
                window.setTimeout(function () {
                    window.location.href = redirectUrl;
                }, Math.max(redirectDelay, 1500));
            }
        }

        if (initialState === 'success') {
            activate(successStage);
            if (shouldRedirect && redirectUrl) {
                window.setTimeout(function () {
                    window.location.href = redirectUrl;
                }, Math.max(redirectDelay, 1500));
            }

            return;
        }

        activate(pendingStage);

        if (!autoTransition) {
            return;
        }

        window.setTimeout(function () {
            showSuccess();
        }, Math.max(transitionDelay, 1500));
    })();
</script>
