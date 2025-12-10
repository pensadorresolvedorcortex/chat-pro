<?php
/**
 * JuntaPlay thank you message for group purchases.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$groups_raw       = isset($groups) && is_array($groups) ? $groups : [];
$order_overview   = isset($order_overview) && is_array($order_overview) ? $order_overview : [];
$totals_summary   = isset($totals_summary) && is_array($totals_summary) ? $totals_summary : [];
$customer_name    = isset($customer_name) ? (string) $customer_name : '';
$customer_first   = isset($customer_first_name) ? (string) $customer_first_name : '';
$my_groups_url    = isset($my_groups_url) ? (string) $my_groups_url : '';
$help_url         = isset($help_url) ? (string) $help_url : '';
$illustration_url = isset($illustration_url) ? (string) $illustration_url : '';
$order_id         = isset($order_id) ? (int) $order_id : 0;
$redirect_delay   = isset($redirect_delay) ? max(0, (int) $redirect_delay) : 0;

$groups = array_values(array_filter(array_map(
    static function ($group): ?array {
        if (!is_array($group)) {
            return null;
        }

        $title                   = isset($group['title']) ? (string) $group['title'] : '';
        $quantity_label          = isset($group['quantity_label']) ? (string) $group['quantity_label'] : '';
        $subscription_price_html = isset($group['subscription_price_html']) ? (string) $group['subscription_price_html'] : '';
        $subscription_total_html = isset($group['subscription_total_html']) ? (string) $group['subscription_total_html'] : '';
        $deposit_html            = isset($group['deposit_html']) ? (string) $group['deposit_html'] : '';
        $processing_fee_html     = isset($group['processing_fee_html']) ? (string) $group['processing_fee_html'] : '';
        $wallet_used_html        = isset($group['wallet_used_html']) ? (string) $group['wallet_used_html'] : '';
        $total_html              = isset($group['total_html']) ? (string) $group['total_html'] : '';
        $quotas                  = isset($group['quotas']) ? (string) $group['quotas'] : '';

        if ($title === '' && $total_html === '') {
            return null;
        }

        return [
            'title'                   => $title,
            'quantity_label'          => $quantity_label,
            'subscription_price_html' => $subscription_price_html,
            'subscription_total_html' => $subscription_total_html,
            'deposit_html'            => $deposit_html,
            'processing_fee_html'     => $processing_fee_html,
            'wallet_used_html'        => $wallet_used_html,
            'total_html'              => $total_html,
            'quotas'                  => $quotas,
        ];
    },
    $groups_raw
)));

$order_overview = array_values(array_filter(array_map(
    static function ($item): ?array {
        if (!is_array($item)) {
            return null;
        }

        $label = isset($item['label']) ? (string) $item['label'] : '';
        $value = isset($item['value']) ? (string) $item['value'] : '';

        if ($label === '' || $value === '') {
            return null;
        }

        return [
            'label' => $label,
            'value' => $value,
        ];
    },
    $order_overview
)));

$totals_summary = array_values(array_filter(array_map(
    static function ($item): ?array {
        if (!is_array($item)) {
            return null;
        }

        $label    = isset($item['label']) ? (string) $item['label'] : '';
        $value    = isset($item['value']) ? (string) $item['value'] : '';
        $emphasis = !empty($item['emphasis']);

        if ($label === '' || $value === '') {
            return null;
        }

        return [
            'label'    => $label,
            'value'    => $value,
            'emphasis' => $emphasis,
        ];
    },
    $totals_summary
)));

$redirect_seconds = ($redirect_delay > 0) ? max(1, (int) ceil($redirect_delay / 1000)) : 0;
$wrapper_id       = $order_id > 0 ? 'juntaplay-thankyou-order-' . $order_id : 'juntaplay-thankyou-order';
$headline_id      = 'juntaplay-thankyou-title';

$subtitle = $customer_first !== ''
    ? sprintf(
        /* translators: %s: customer first name */
        __('Tudo certo, %s! Recebemos o pagamento da sua assinatura.', 'juntaplay'),
        $customer_first
    )
    : __('Recebemos o pagamento da sua assinatura. Em instantes o administrador vai compartilhar os dados de acesso.', 'juntaplay');

$footnote_parts = [];
if ($my_groups_url !== '' && $redirect_seconds > 0) {
    $footnote_parts[] = sprintf(
        /* translators: %s: number of seconds */
        __('Você será redirecionado para “Meus grupos” em %s segundos.', 'juntaplay'),
        number_format_i18n($redirect_seconds)
    );
}

$footnote_parts[] = __('Se não encontrar o e-mail em alguns minutos, verifique também a caixa de spam.', 'juntaplay');

if ($help_url !== '') {
    $footnote_parts[] = sprintf(
        /* translators: %s: help center link */
        __('Precisa de ajuda? Acesse a %s.', 'juntaplay'),
        '<a class="juntaplay-link" href="' . esc_url($help_url) . '">' . esc_html__('Central de ajuda', 'juntaplay') . '</a>'
    );
}

$footnote_html = implode(' ', $footnote_parts);
?>
<?php if ($order_id > 0) : ?>
    <style>
        .woocommerce-order > *:not(#<?php echo esc_attr($wrapper_id); ?>) {
            display: none !important;
        }
    </style>
<?php endif; ?>
<section
    id="<?php echo esc_attr($wrapper_id); ?>"
    class="juntaplay-thankyou"
    aria-labelledby="<?php echo esc_attr($headline_id); ?>"
>
    <?php if ($illustration_url !== '') : ?>
        <div class="juntaplay-thankyou__media" aria-hidden="true">
            <img src="<?php echo esc_url($illustration_url); ?>" alt="" loading="lazy" />
        </div>
    <?php endif; ?>
    <div class="juntaplay-thankyou__content">
        <header class="juntaplay-thankyou__header">
            <h2 id="<?php echo esc_attr($headline_id); ?>"><?php esc_html_e('Você fez a assinatura!', 'juntaplay'); ?></h2>
            <p><?php echo esc_html($subtitle); ?></p>
        </header>

        <?php if ($order_overview) : ?>
            <h3><?php esc_html_e('Resumo do pedido', 'juntaplay'); ?></h3>
            <ul class="juntaplay-thankyou__list" role="list">
                <?php foreach ($order_overview as $line) : ?>
                    <li class="juntaplay-thankyou__item">
                        <span class="juntaplay-thankyou__item-name"><?php echo esc_html($line['label']); ?></span>
                        <span class="juntaplay-thankyou__item-meta"><?php echo esc_html($line['value']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($totals_summary) : ?>
            <h3><?php esc_html_e('Valores pagos', 'juntaplay'); ?></h3>
            <ul class="juntaplay-thankyou__list" role="list">
                <?php foreach ($totals_summary as $line) :
                    $emphasis = !empty($line['emphasis']);
                    ?>
                    <li class="juntaplay-thankyou__item">
                        <span class="juntaplay-thankyou__item-name"><?php echo esc_html($line['label']); ?></span>
                        <span class="juntaplay-thankyou__item-meta">
                            <?php if ($emphasis) : ?>
                                <strong><?php echo wp_kses_post($line['value']); ?></strong>
                            <?php else : ?>
                                <?php echo wp_kses_post($line['value']); ?>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($groups) : ?>
            <h3><?php esc_html_e('Grupos confirmados', 'juntaplay'); ?></h3>
            <ul class="juntaplay-thankyou__list" role="list">
                <?php foreach ($groups as $group) :
                    $title = $group['title'] !== '' ? $group['title'] : __('Grupo confirmado', 'juntaplay');
                    ?>
                    <li class="juntaplay-thankyou__item">
                        <span class="juntaplay-thankyou__item-name"><?php echo esc_html($title); ?></span>
                        <?php if ($group['quantity_label'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo esc_html(sprintf(__('Quantidade: %s', 'juntaplay'), $group['quantity_label'])); ?></span>
                        <?php endif; ?>
                        <?php if ($group['quotas'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo esc_html(sprintf(__('Cotas selecionadas: %s', 'juntaplay'), $group['quotas'])); ?></span>
                        <?php endif; ?>
                        <?php if ($group['subscription_price_html'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo wp_kses_post(sprintf(__('Assinatura por cota: %s', 'juntaplay'), $group['subscription_price_html'])); ?></span>
                        <?php endif; ?>
                        <?php if ($group['deposit_html'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo wp_kses_post(sprintf(__('Caução por participante: %s', 'juntaplay'), $group['deposit_html'])); ?></span>
                        <?php endif; ?>
                        <?php if ($group['processing_fee_html'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo wp_kses_post(sprintf(__('Custos de processamento: %s', 'juntaplay'), $group['processing_fee_html'])); ?></span>
                        <?php endif; ?>
                        <?php if ($group['wallet_used_html'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><?php echo wp_kses_post(sprintf(__('Créditos utilizados: %s', 'juntaplay'), $group['wallet_used_html'])); ?></span>
                        <?php endif; ?>
                        <?php if ($group['total_html'] !== '') : ?>
                            <span class="juntaplay-thankyou__item-meta"><strong><?php echo wp_kses_post(sprintf(__('Total neste grupo: %s', 'juntaplay'), $group['total_html'])); ?></strong></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($footnote_html !== '') : ?>
            <p class="juntaplay-thankyou__footnote"><?php echo wp_kses_post($footnote_html); ?></p>
        <?php endif; ?>

        <div class="juntaplay-thankyou__actions">
            <?php if ($my_groups_url !== '') : ?>
                <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($my_groups_url); ?>">
                    <?php esc_html_e('Ir para Meus Grupos', 'juntaplay'); ?>
                </a>
            <?php endif; ?>
            <?php if ($help_url !== '') : ?>
                <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($help_url); ?>">
                    <?php esc_html_e('Central de ajuda', 'juntaplay'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php if ($my_groups_url !== '' && $redirect_delay > 0) : ?>
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.location.href = <?php echo wp_json_encode($my_groups_url); ?>;
            }, <?php echo (int) $redirect_delay; ?>);
        });
    </script>
<?php endif; ?>
