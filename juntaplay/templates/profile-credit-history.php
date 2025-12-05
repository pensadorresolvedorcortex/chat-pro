<?php
/**
 * JuntaPlay profile credit history template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$wallet_transactions = isset($context['transactions']) && is_array($context['transactions']) ? $context['transactions'] : [];
$wallet_pagination  = isset($context['pagination']) && is_array($context['pagination']) ? $context['pagination'] : ['page' => 1, 'pages' => 1, 'total' => 0];
$wallet_withdrawals = isset($context['withdrawals']) && is_array($context['withdrawals']) ? $context['withdrawals'] : [];
$wallet_two_factor  = isset($context['two_factor']) && is_array($context['two_factor']) ? $context['two_factor'] : [];
$wallet_deposit     = isset($context['deposit']) && is_array($context['deposit']) ? $context['deposit'] : [];
$wallet_has_pix     = !empty($context['has_pix']);
$wallet_has_bank    = !empty($context['has_bank']);
$wallet_balance     = isset($context['balance_label']) ? (string) $context['balance_label'] : '';
$wallet_reserved    = isset($context['reserved_label']) ? (string) $context['reserved_label'] : '';
$wallet_bonus       = isset($context['bonus_label']) ? (string) $context['bonus_label'] : '';
$wallet_pending     = isset($context['withdraw_pending']) ? (string) $context['withdraw_pending'] : '';
$wallet_page        = isset($wallet_pagination['page']) ? (int) $wallet_pagination['page'] : 1;
$wallet_pages       = isset($wallet_pagination['pages']) ? (int) $wallet_pagination['pages'] : 1;
$wallet_total       = isset($wallet_pagination['total']) ? (int) $wallet_pagination['total'] : 0;
$two_factor_method  = isset($wallet_two_factor['method']) ? (string) $wallet_two_factor['method'] : 'email';
$two_factor_label   = isset($wallet_two_factor['label']) ? (string) $wallet_two_factor['label'] : '';
$two_factor_destination = isset($wallet_two_factor['destination']) ? (string) $wallet_two_factor['destination'] : '';
$two_factor_expires = isset($wallet_two_factor['code_expires']) ? (string) $wallet_two_factor['code_expires'] : '';
$two_factor_remaining = isset($wallet_two_factor['code_remaining']) ? (int) $wallet_two_factor['code_remaining'] : 0;
$requires_destination = (!$wallet_has_pix && !$wallet_has_bank);
$deposit_enabled   = !empty($wallet_deposit['enabled']);
$deposit_min_label = isset($wallet_deposit['min']) ? (string) $wallet_deposit['min'] : '';
$deposit_min_value = isset($wallet_deposit['min_raw']) ? (float) $wallet_deposit['min_raw'] : 0.0;
$deposit_max_label = isset($wallet_deposit['max']) ? (string) $wallet_deposit['max'] : '';
$deposit_max_value = isset($wallet_deposit['max_raw']) ? (float) $wallet_deposit['max_raw'] : 0.0;
$deposit_suggestions = [];

if (!empty($wallet_deposit['suggestions']) && is_array($wallet_deposit['suggestions'])) {
    foreach ($wallet_deposit['suggestions'] as $suggestion) {
        if (!is_array($suggestion)) {
            continue;
        }

        $value = isset($suggestion['value']) ? (float) $suggestion['value'] : 0.0;
        $label = isset($suggestion['label']) ? (string) $suggestion['label'] : '';

        if ($value > 0 && $label !== '') {
            $deposit_suggestions[] = ['value' => $value, 'label' => $label];
        }
    }
}
?>
<div
    class="juntaplay-wallet"
    data-jp-wallet
    data-page="<?php echo esc_attr($wallet_page); ?>"
    data-pages="<?php echo esc_attr($wallet_pages); ?>"
    data-total="<?php echo esc_attr($wallet_total); ?>"
    data-deposit-enabled="<?php echo esc_attr($deposit_enabled ? '1' : '0'); ?>"
    data-deposit-min="<?php echo esc_attr($deposit_min_value); ?>"
    data-deposit-max="<?php echo esc_attr($deposit_max_value); ?>"
>
    <header class="juntaplay-wallet__header">
        <div class="juntaplay-wallet__summary">
            <article class="juntaplay-wallet__card">
                <span class="juntaplay-wallet__card-label"><?php esc_html_e('Saldo disponível', 'juntaplay'); ?></span>
                <strong class="juntaplay-wallet__card-value"><?php echo esc_html($wallet_balance); ?></strong>
            </article>
            <article class="juntaplay-wallet__card">
                <span class="juntaplay-wallet__card-label"><?php esc_html_e('Reservado em pedidos', 'juntaplay'); ?></span>
                <span class="juntaplay-wallet__card-value juntaplay-wallet__card-value--muted"><?php echo esc_html($wallet_reserved); ?></span>
            </article>
            <article class="juntaplay-wallet__card">
                <span class="juntaplay-wallet__card-label"><?php esc_html_e('Bônus disponível', 'juntaplay'); ?></span>
                <span class="juntaplay-wallet__card-value juntaplay-wallet__card-value--accent"><?php echo esc_html($wallet_bonus); ?></span>
            </article>
            <article class="juntaplay-wallet__card">
                <span class="juntaplay-wallet__card-label"><?php esc_html_e('Saques em análise', 'juntaplay'); ?></span>
                <span class="juntaplay-wallet__card-value juntaplay-wallet__card-value--warning"><?php echo esc_html($wallet_pending); ?></span>
            </article>
        </div>
        <div class="juntaplay-wallet__actions">
            <div class="juntaplay-wallet__action-buttons">
                <button type="button" class="juntaplay-button juntaplay-button--primary" data-jp-credit-topup <?php disabled(!$deposit_enabled); ?>>
                    <?php esc_html_e('Adicionar créditos', 'juntaplay'); ?>
                </button>
                <button type="button" class="juntaplay-button juntaplay-button--ghost juntaplay-button--compact" data-jp-credit-send-code>
                    <?php esc_html_e('Enviar código de confirmação', 'juntaplay'); ?>
                </button>
            </div>
            <div class="juntaplay-wallet__action-info">
                <span class="juntaplay-wallet__hint" data-jp-credit-destination>
                    <?php
                    if ($two_factor_label !== '') {
                        echo esc_html(sprintf('%1$s · %2$s', $two_factor_label, $two_factor_destination !== '' ? $two_factor_destination : __('verifique seu e-mail cadastrado', 'juntaplay')));
                    }
                    ?>
                </span>
                <?php if ($deposit_min_label !== '') : ?>
                    <span class="juntaplay-wallet__hint juntaplay-wallet__hint--cta" data-jp-credit-deposit-hint>
                        <?php
                        if ($deposit_max_label !== '') {
                            echo esc_html(sprintf(__('Valores entre %1$s e %2$s', 'juntaplay'), $deposit_min_label, $deposit_max_label));
                        } else {
                            echo esc_html(sprintf(__('Valor mínimo: %s', 'juntaplay'), $deposit_min_label));
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="juntaplay-wallet__content">
        <div class="juntaplay-wallet__main">
            <section class="juntaplay-wallet__transactions" data-jp-credit-list>
                <header class="juntaplay-wallet__section-header">
                    <div class="juntaplay-wallet__section-title">
                        <h3><?php esc_html_e('Movimentações recentes', 'juntaplay'); ?></h3>
                        <span class="juntaplay-wallet__total juntaplay-profile__tab-label" data-jp-credit-total><?php echo esc_html(sprintf(_n('%d movimento', '%d movimentos', $wallet_total, 'juntaplay'), $wallet_total)); ?></span>
                    </div>
                    <p><?php esc_html_e('Acompanhe entradas, saídas e ajustes da sua carteira.', 'juntaplay'); ?></p>
                </header>
                <?php if ($wallet_transactions) : ?>
                    <ul class="juntaplay-wallet__list" role="list">
                        <?php foreach ($wallet_transactions as $transaction) :
                            $transaction_id    = isset($transaction['id']) ? (int) $transaction['id'] : 0;
                            $transaction_title = isset($transaction['type_label']) ? (string) $transaction['type_label'] : '';
                            $transaction_icon  = isset($transaction['icon']) ? (string) $transaction['icon'] : '';
                            $transaction_status = isset($transaction['status_label']) ? (string) $transaction['status_label'] : '';
                            $transaction_amount = isset($transaction['amount']) ? (string) $transaction['amount'] : '';
                            $transaction_time  = isset($transaction['time']) ? (string) $transaction['time'] : '';
                            $transaction_reference = isset($transaction['reference']) ? (string) $transaction['reference'] : '';

                            ?>
                            <li class="juntaplay-wallet__item" data-transaction="<?php echo esc_attr($transaction_id); ?>">
                                <?php
                                $default_render = '';

                                if ($transaction_icon === '🔁') {
                                    ob_start();
                                    ?>
                                    <div class="juntaplay-wallet__item-card">
                                        <strong class="juntaplay-wallet__item-title">
                                            <span class="juntaplay-wallet__item-icon"><?php echo esc_html($transaction_icon); ?></span>
                                            <?php echo esc_html($transaction_title); ?>
                                        </strong>
                                        <?php if ($transaction_time !== '') : ?>
                                            <div class="juntaplay-wallet__item-meta"><?php echo esc_html(sprintf(__('Data: %s', 'juntaplay'), $transaction_time)); ?></div>
                                        <?php endif; ?>
                                        <?php if ($transaction_reference !== '') : ?>
                                            <div class="juntaplay-wallet__item-meta juntaplay-wallet__item-ref"><?php echo esc_html(sprintf(__('Referência: %s', 'juntaplay'), $transaction_reference)); ?></div>
                                        <?php endif; ?>
                                        <?php if ($transaction_amount !== '') : ?>
                                            <div class="juntaplay-wallet__item-amount"><?php echo esc_html(sprintf(__('Valor: %s', 'juntaplay'), $transaction_amount)); ?></div>
                                        <?php endif; ?>
                                        <?php if ($transaction_status !== '') : ?>
                                            <div class="juntaplay-wallet__item-status"><?php echo esc_html(sprintf(__('Status: %s', 'juntaplay'), $transaction_status)); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    $default_render = (string) ob_get_clean();
                                }

                                if ($default_render === '') {
                                    ob_start();
                                    ?>
                                    <div class="juntaplay-wallet__item-main">
                                        <strong class="juntaplay-wallet__item-title">
                                            <?php if ($transaction_icon !== '') : ?>
                                                <span class="juntaplay-wallet__item-icon"><?php echo esc_html($transaction_icon); ?></span>
                                            <?php endif; ?>
                                            <?php echo esc_html($transaction_title); ?>
                                        </strong>
                                        <span class="juntaplay-wallet__item-meta">
                                            <?php echo esc_html($transaction_time); ?>
                                            <?php if ($transaction_reference !== '') : ?>
                                                · <?php echo esc_html($transaction_reference); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="juntaplay-wallet__item-side">
                                        <span class="juntaplay-wallet__item-status"><?php echo esc_html($transaction_status); ?></span>
                                        <span class="juntaplay-wallet__item-amount"><?php echo esc_html($transaction_amount); ?></span>
                                    </div>
                                    <?php
                                    $default_render = (string) ob_get_clean();
                                }

                                $rendered_entry = apply_filters('juntaplay_wallet_transaction_render', $default_render, $transaction, $transaction_icon, $transaction_title);

                                echo wp_kses_post($rendered_entry);
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($wallet_page < $wallet_pages) : ?>
                        <button type="button" class="juntaplay-button juntaplay-button--ghost juntaplay-wallet__more" data-jp-credit-load-more>
                            <?php esc_html_e('Carregar mais movimentações', 'juntaplay'); ?>
                        </button>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="juntaplay-wallet__empty" data-jp-credit-empty><?php esc_html_e('Nenhuma movimentação registrada ainda.', 'juntaplay'); ?></p>
                <?php endif; ?>
            </section>

            <aside class="juntaplay-wallet__history">
                <header class="juntaplay-wallet__section-header">
                    <div class="juntaplay-wallet__section-title">
                        <h3><?php esc_html_e('Solicitações recentes', 'juntaplay'); ?></h3>
                    </div>
                    <p><?php esc_html_e('Acompanhe o status das suas retiradas.', 'juntaplay'); ?></p>
                </header>
                <?php if ($wallet_withdrawals) : ?>
                    <ul class="juntaplay-wallet__history-list" role="list">
                        <?php foreach ($wallet_withdrawals as $withdrawal) :
                            $withdraw_id = isset($withdrawal['id']) ? (int) $withdrawal['id'] : 0;
                            $withdraw_status = isset($withdrawal['status_label']) ? (string) $withdrawal['status_label'] : '';
                            $withdraw_amount = isset($withdrawal['amount']) ? (string) $withdrawal['amount'] : '';
                            $withdraw_time   = isset($withdrawal['time']) ? (string) $withdrawal['time'] : '';
                            $withdraw_dest   = isset($withdrawal['destination']) ? (string) $withdrawal['destination'] : '';
                            $withdraw_ref    = isset($withdrawal['reference']) ? (string) $withdrawal['reference'] : '';
                            ?>
                            <li class="juntaplay-wallet__history-item">
                                <div>
                                    <strong><?php echo esc_html($withdraw_amount); ?></strong>
                                    <span class="juntaplay-wallet__history-meta"><?php echo esc_html($withdraw_time); ?></span>
                                    <?php if ($withdraw_dest !== '') : ?>
                                        <span class="juntaplay-wallet__history-destination"><?php echo esc_html($withdraw_dest); ?></span>
                                    <?php endif; ?>
                                    <?php if ($withdraw_ref !== '') : ?>
                                        <span class="juntaplay-wallet__history-ref"><?php echo esc_html($withdraw_ref); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="juntaplay-wallet__history-status"><?php echo esc_html($withdraw_status); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="juntaplay-wallet__empty"><?php esc_html_e('Nenhuma solicitação de saque foi realizada.', 'juntaplay'); ?></p>
                <?php endif; ?>
            </aside>
        </div>

        <div class="juntaplay-wallet__sidebar">
            <section class="juntaplay-wallet__withdraw">
                <h3><?php esc_html_e('Solicitar retirada', 'juntaplay'); ?></h3>
                <?php if ($requires_destination) : ?>
                    <div class="juntaplay-wallet__alert juntaplay-wallet__alert--warning">
                        <?php esc_html_e('Cadastre uma chave Pix ou uma conta bancária para habilitar os saques.', 'juntaplay'); ?>
                    </div>
                <?php endif; ?>
                <form method="post" class="juntaplay-wallet__form">
                    <?php
                    $history_nonce_field = wp_nonce_field(
                        'juntaplay_profile',
                        'jp_profile_nonce',
                        true,
                        false
                    );
                    $history_nonce_field = preg_replace(
                        '/id="jp_profile_nonce"/',
                        'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                        $history_nonce_field
                    );
                    echo $history_nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                    <input type="hidden" name="jp_profile_section" value="credit_withdrawal" />
                    <div class="juntaplay-field">
                        <label class="juntaplay-field__label" for="jp-profile-withdraw-amount"><?php esc_html_e('Valor do saque', 'juntaplay'); ?></label>
                        <input id="jp-profile-withdraw-amount" type="number" step="0.01" min="0" name="jp_profile_withdraw_amount" class="juntaplay-field__input" placeholder="0,00" <?php disabled($requires_destination); ?> />
                    </div>
                    <div class="juntaplay-field">
                        <label class="juntaplay-field__label" for="jp-profile-withdraw-method"><?php esc_html_e('Forma de recebimento', 'juntaplay'); ?></label>
                        <select id="jp-profile-withdraw-method" class="juntaplay-field__input" name="jp_profile_withdraw_method" <?php disabled($requires_destination); ?>>
                            <option value="pix" <?php selected(!$wallet_has_bank); ?> <?php disabled(!$wallet_has_pix); ?>><?php esc_html_e('Pix cadastrado', 'juntaplay'); ?></option>
                            <option value="bank" <?php selected($wallet_has_bank && !$wallet_has_pix); ?> <?php disabled(!$wallet_has_bank); ?>><?php esc_html_e('Conta bancária', 'juntaplay'); ?></option>
                        </select>
                    </div>
                    <div class="juntaplay-field">
                        <label class="juntaplay-field__label" for="jp-profile-withdraw-code"><?php esc_html_e('Código de confirmação', 'juntaplay'); ?></label>
                        <input id="jp-profile-withdraw-code" type="text" name="jp_profile_withdraw_code" class="juntaplay-field__input" placeholder="000000" autocomplete="one-time-code" <?php disabled($requires_destination); ?> />
                        <p class="juntaplay-field__hint" data-jp-credit-countdown>
                            <?php
                            if ($two_factor_destination !== '') {
                                echo esc_html(sprintf(__('Código enviado para %s', 'juntaplay'), $two_factor_destination));
                            } else {
                                esc_html_e('Solicite um novo código antes de confirmar a retirada.', 'juntaplay');
                            }
                            ?>
                        </p>
                    </div>
                    <button type="submit" class="juntaplay-button juntaplay-button--primary" <?php disabled($requires_destination); ?>>
                        <?php esc_html_e('Confirmar retirada', 'juntaplay'); ?>
                    </button>
                </form>
            </section>

            <aside class="juntaplay-wallet__deposit" data-jp-credit-deposit hidden>
                <div class="juntaplay-wallet__deposit-card">
                    <header class="juntaplay-wallet__deposit-header">
                        <h3><?php esc_html_e('Adicionar créditos', 'juntaplay'); ?></h3>
                        <button type="button" class="juntaplay-wallet__deposit-close" data-jp-credit-deposit-close aria-label="<?php esc_attr_e('Fechar', 'juntaplay'); ?>">&times;</button>
                    </header>
                    <form class="juntaplay-wallet__deposit-form" data-jp-credit-deposit-form>
                        <div class="juntaplay-field">
                            <label class="juntaplay-field__label" for="jp-profile-deposit-amount"><?php esc_html_e('Quanto você deseja adicionar?', 'juntaplay'); ?></label>
                            <div class="juntaplay-field__input-wrapper">
                                <span class="juntaplay-field__prefix">R$</span>
                                <input id="jp-profile-deposit-amount" type="number" step="0.01" min="0" name="jp_profile_deposit_amount" class="juntaplay-field__input" placeholder="0,00" />
                            </div>
                            <p class="juntaplay-field__hint">
                                <?php
                                if ($deposit_max_label !== '') {
                                    echo esc_html(sprintf(__('Valores entre %1$s e %2$s serão direcionados ao checkout seguro do WooCommerce.', 'juntaplay'), $deposit_min_label, $deposit_max_label));
                                } else {
                                    echo esc_html(sprintf(__('Valor mínimo de recarga: %s', 'juntaplay'), $deposit_min_label));
                                }
                                ?>
                            </p>
                        </div>
                        <?php if ($deposit_suggestions) : ?>
                            <div class="juntaplay-wallet__deposit-suggestions" role="group" aria-label="<?php esc_attr_e('Sugestões rápidas', 'juntaplay'); ?>">
                                <?php foreach ($deposit_suggestions as $suggestion) :
                                    $suggest_value = isset($suggestion['value']) ? (float) $suggestion['value'] : 0.0;
                                    $suggest_label = isset($suggestion['label']) ? (string) $suggestion['label'] : '';
                                    ?>
                                    <button type="button" class="juntaplay-chip" data-jp-credit-suggestion="<?php echo esc_attr($suggest_value); ?>">
                                        <?php echo esc_html($suggest_label); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="juntaplay-wallet__deposit-actions">
                            <button type="submit" class="juntaplay-button juntaplay-button--primary" data-jp-credit-deposit-submit>
                                <?php esc_html_e('Ir para o pagamento', 'juntaplay'); ?>
                            </button>
                            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-jp-credit-deposit-close>
                                <?php esc_html_e('Cancelar', 'juntaplay'); ?>
                            </button>
                        </div>
                        <p class="juntaplay-wallet__alert juntaplay-wallet__alert--warning" data-jp-credit-deposit-error hidden></p>
                    </form>
                </div>
            </aside>
        </div>
    </div>

    <div class="juntaplay-wallet__details" data-jp-credit-details hidden>
        <div class="juntaplay-wallet__details-card">
            <header class="juntaplay-wallet__details-header">
                <h4 data-jp-credit-details-title><?php esc_html_e('Detalhes da movimentação', 'juntaplay'); ?></h4>
                <button type="button" class="juntaplay-wallet__details-close" data-jp-credit-details-close>&times;</button>
            </header>
            <div class="juntaplay-wallet__details-body" data-jp-credit-details-body>
                <p class="juntaplay-wallet__empty"><?php esc_html_e('Selecione uma movimentação para visualizar os detalhes.', 'juntaplay'); ?></p>
            </div>
        </div>
    </div>
</div>
