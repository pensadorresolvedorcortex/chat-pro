<?php
/**
 * JuntaPlay credits page shortcode template.
 */

declare(strict_types=1);

use JuntaPlay\Data\CreditTransactions;

if (!defined('ABSPATH')) {
    exit;
}

$profile = isset($context['profile']) && is_array($context['profile']) ? $context['profile'] : [];
$wallet  = isset($context['wallet']) && is_array($context['wallet']) ? $context['wallet'] : [];
$summary = isset($context['summary']) && is_array($context['summary']) ? array_filter($context['summary'], 'is_array') : [];

$profile_name  = isset($profile['name']) ? (string) $profile['name'] : '';
$profile_email = isset($profile['email']) ? (string) $profile['email'] : '';
$last_recharge = isset($profile['last_recharge']) ? (string) $profile['last_recharge'] : '';
$updated_at    = isset($profile['updated_at']) ? (string) $profile['updated_at'] : '';
$bonus_expires = isset($profile['bonus_expires']) ? (string) $profile['bonus_expires'] : '';

$wallet_transactions = isset($wallet['transactions']) && is_array($wallet['transactions']) ? $wallet['transactions'] : [];
$wallet_pagination  = isset($wallet['pagination']) && is_array($wallet['pagination']) ? $wallet['pagination'] : ['page' => 1, 'pages' => 1, 'total' => 0];
$wallet_withdrawals = isset($wallet['withdrawals']) && is_array($wallet['withdrawals']) ? $wallet['withdrawals'] : [];
$wallet_two_factor  = isset($wallet['two_factor']) && is_array($wallet['two_factor']) ? $wallet['two_factor'] : [];
$wallet_deposit     = isset($wallet['deposit']) && is_array($wallet['deposit']) ? $wallet['deposit'] : [];
$wallet_has_pix     = !empty($wallet['has_pix']);
$wallet_has_bank    = !empty($wallet['has_bank']);
$wallet_page        = isset($wallet_pagination['page']) ? (int) $wallet_pagination['page'] : 1;
$wallet_pages       = isset($wallet_pagination['pages']) ? (int) $wallet_pagination['pages'] : 1;
$wallet_total       = isset($wallet_pagination['total']) ? (int) $wallet_pagination['total'] : 0;
$two_factor_label   = isset($wallet_two_factor['label']) ? (string) $wallet_two_factor['label'] : '';
$two_factor_destination = isset($wallet_two_factor['destination']) ? (string) $wallet_two_factor['destination'] : '';
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

$hero_description = $profile_name !== ''
    ? sprintf(__('Olá, %s! Acompanhe saldos, recargas e retiradas em tempo real.', 'juntaplay'), $profile_name)
    : __('Acompanhe saldos, recargas e retiradas em tempo real.', 'juntaplay');
?>
<section class="juntaplay-profile__group juntaplay-profile__group--credits">
    <header class="juntaplay-profile__group-header">
        <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Meus créditos', 'juntaplay'); ?></h2>
        <p class="juntaplay-profile__group-description"><?php echo esc_html($hero_description); ?></p>
        <?php if ($profile_email !== '') : ?>
            <p class="juntaplay-profile__group-description"><?php echo esc_html(sprintf(__('Conta vinculada: %s', 'juntaplay'), $profile_email)); ?></p>
        <?php endif; ?>
    </header>

    <?php if ($summary) : ?>
        <div class="juntaplay-profile__summary">
            <?php foreach ($summary as $summary_item) :
                $summary_label = isset($summary_item['label']) ? (string) $summary_item['label'] : '';
                $summary_value = isset($summary_item['value']) ? (string) $summary_item['value'] : '';
                $summary_hint  = isset($summary_item['hint']) ? (string) $summary_item['hint'] : '';
                $summary_tone  = isset($summary_item['tone']) ? (string) $summary_item['tone'] : '';

                $summary_classes = ['juntaplay-profile__summary-item'];
                if ($summary_tone !== '') {
                    $summary_classes[] = 'juntaplay-profile__summary-item--' . sanitize_html_class($summary_tone);
                }
                ?>
                <article class="<?php echo esc_attr(implode(' ', $summary_classes)); ?>">
                    <?php if ($summary_label !== '') : ?>
                        <span class="juntaplay-profile__summary-label"><?php echo esc_html($summary_label); ?></span>
                    <?php endif; ?>
                    <?php if ($summary_value !== '') : ?>
                        <span class="juntaplay-profile__summary-value"><?php echo esc_html($summary_value); ?></span>
                    <?php endif; ?>
                    <?php if ($summary_hint !== '') : ?>
                        <span class="juntaplay-profile__summary-hint"><?php echo esc_html($summary_hint); ?></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($last_recharge !== '' || $bonus_expires !== '') : ?>
        <div class="juntaplay-profile__alerts">
            <?php if ($last_recharge !== '') : ?>
                <div class="juntaplay-alert juntaplay-alert--info">
                    <?php echo esc_html(sprintf(__('Última recarga em %s', 'juntaplay'), $last_recharge)); ?>
                </div>
            <?php endif; ?>
            <?php if ($bonus_expires !== '') : ?>
                <div class="juntaplay-alert juntaplay-alert--success">
                    <?php echo esc_html(sprintf(__('Bônus válido até %s', 'juntaplay'), $bonus_expires)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<div class="juntaplay-profile__panels juntaplay-profile__panels--credits">
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
        <div class="juntaplay-wallet__actions juntaplay-wallet__surface">
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

        <div class="juntaplay-wallet__content">
            <section class="juntaplay-wallet__withdraw">
                <h3><?php esc_html_e('Solicitar retirada', 'juntaplay'); ?></h3>
                <?php if ($requires_destination) : ?>
                    <div class="juntaplay-wallet__alert juntaplay-wallet__alert--warning">
                        <?php esc_html_e('Cadastre uma chave Pix ou uma conta bancária para habilitar os saques.', 'juntaplay'); ?>
                    </div>
                <?php endif; ?>
                <form method="post" class="juntaplay-wallet__form">
                    <?php
                    $profile_nonce_field = wp_nonce_field(
                        'juntaplay_profile',
                        'jp_profile_nonce',
                        true,
                        false
                    );
                    $profile_nonce_field = preg_replace(
                        '/id="jp_profile_nonce"/',
                        'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                        $profile_nonce_field
                    );
                    echo $profile_nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                    <div class="juntaplay-wallet__form-actions">
                        <button type="submit" class="juntaplay-button juntaplay-button--primary juntaplay-wallet__submit" <?php disabled($requires_destination); ?>>
                            <?php esc_html_e('Confirmar retirada', 'juntaplay'); ?>
                        </button>
                    </div>
                </form>
            </section>

            <section class="juntaplay-wallet__transactions" data-jp-credit-list>
                <header class="juntaplay-wallet__section-header">
                    <div class="juntaplay-wallet__section-title">
                        <h3><?php esc_html_e('Últimas movimentações', 'juntaplay'); ?></h3>
                        <span class="juntaplay-wallet__total juntaplay-profile__tab-label" data-jp-credit-total><?php echo esc_html(sprintf(_n('%d movimento', '%d movimentos', $wallet_total, 'juntaplay'), $wallet_total)); ?></span>
                    </div>
                    <p><?php esc_html_e('Visualize recargas, compras e ajustes realizados em sua carteira.', 'juntaplay'); ?></p>
                </header>

                <?php
                $transaction_filters = [
                    'all'                                 => __('Todos', 'juntaplay'),
                    CreditTransactions::TYPE_DEPOSIT      => __('Entradas', 'juntaplay'),
                    CreditTransactions::TYPE_PURCHASE     => __('Compras', 'juntaplay'),
                    CreditTransactions::TYPE_WITHDRAWAL   => __('Saques', 'juntaplay'),
                    CreditTransactions::TYPE_BONUS        => __('Bônus', 'juntaplay'),
                    CreditTransactions::TYPE_REFUND       => __('Reembolsos', 'juntaplay'),
                ];
                ?>
                <div class="juntaplay-wallet__toolbar">
                    <div class="juntaplay-wallet__filters" role="group" aria-label="<?php esc_attr_e('Filtrar por tipo de movimentação', 'juntaplay'); ?>">
                        <?php foreach ($transaction_filters as $filter_key => $filter_label) :
                            $is_active = $filter_key === 'all';
                            ?>
                            <button type="button" class="juntaplay-chip<?php echo $is_active ? ' is-active' : ''; ?>" data-jp-credit-filter="<?php echo esc_attr((string) $filter_key); ?>">
                                <?php echo esc_html($filter_label); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="juntaplay-wallet__controls">
                        <label class="juntaplay-wallet__range" for="jp-credit-range">
                            <span class="juntaplay-wallet__range-label-text"><?php esc_html_e('Período', 'juntaplay'); ?></span>
                            <select id="jp-credit-range" class="juntaplay-field__input juntaplay-wallet__range-select" data-jp-credit-range>
                                <option value="0"><?php esc_html_e('Todas as datas', 'juntaplay'); ?></option>
                                <option value="30"><?php esc_html_e('Últimos 30 dias', 'juntaplay'); ?></option>
                                <option value="90"><?php esc_html_e('Últimos 90 dias', 'juntaplay'); ?></option>
                                <option value="180"><?php esc_html_e('Últimos 180 dias', 'juntaplay'); ?></option>
                                <option value="365"><?php esc_html_e('Últimos 12 meses', 'juntaplay'); ?></option>
                            </select>
                        </label>
                        <div class="juntaplay-wallet__search">
                            <label class="screen-reader-text" for="jp-credit-search"><?php esc_html_e('Pesquisar movimentações por referência ou status', 'juntaplay'); ?></label>
                            <input id="jp-credit-search" type="search" class="juntaplay-field__input juntaplay-wallet__search-input" placeholder="<?php esc_attr_e('Pesquisar movimentações…', 'juntaplay'); ?>" data-jp-credit-search autocomplete="off" />
                        </div>
                        <button type="button" class="juntaplay-button juntaplay-button--ghost juntaplay-button--compact" data-jp-credit-refresh>
                            <?php esc_html_e('Atualizar', 'juntaplay'); ?>
                        </button>
                    </div>
                </div>

                <?php $has_transactions = false; ?>
                <ul class="juntaplay-wallet__list" role="list" data-jp-credit-items>
                    <?php foreach ($wallet_transactions as $transaction) :
                        if (!is_array($transaction)) {
                            continue;
                        }

                        $transaction_id       = isset($transaction['id']) ? (int) $transaction['id'] : 0;
                        $transaction_type     = isset($transaction['type']) ? (string) $transaction['type'] : '';
                        $transaction_type_lbl = isset($transaction['type_label']) ? (string) $transaction['type_label'] : '';
                        $transaction_icon     = isset($transaction['icon']) ? (string) $transaction['icon'] : '';
                        $transaction_status   = isset($transaction['status']) ? (string) $transaction['status'] : '';
                        $transaction_status_lbl = isset($transaction['status_label']) ? (string) $transaction['status_label'] : '';
                        $transaction_amount   = isset($transaction['amount']) ? (string) $transaction['amount'] : '';
                        $transaction_time     = isset($transaction['time']) ? (string) $transaction['time'] : '';
                        $transaction_reference = isset($transaction['reference']) ? (string) $transaction['reference'] : '';
                        $transaction_timestamp = isset($transaction['timestamp']) ? (int) $transaction['timestamp'] : 0;
                        $transaction_search   = isset($transaction['search']) ? (string) $transaction['search'] : '';

                        if ($transaction_search === '') {
                            $search_terms = trim($transaction_type_lbl . ' ' . $transaction_status_lbl . ' ' . $transaction_reference . ' ' . $transaction_time);
                            $transaction_search = $search_terms !== '' ? strtolower($search_terms) : '';
                        }

                        $has_transactions = true;
                        ?>
                        <li
                            class="juntaplay-wallet__item"
                            data-transaction="<?php echo esc_attr($transaction_id); ?>"
                            data-type="<?php echo esc_attr($transaction_type); ?>"
                            data-status="<?php echo esc_attr($transaction_status); ?>"
                            data-time="<?php echo esc_attr($transaction_timestamp); ?>"
                            data-search="<?php echo esc_attr($transaction_search); ?>"
                        >
                            <?php
                            $default_render = '';

                            if ($transaction_icon === '🔁') {
                                ob_start();
                                ?>
                                <div class="juntaplay-wallet__item-card">
                                    <strong class="juntaplay-wallet__item-title">
                                        <span class="juntaplay-wallet__item-icon"><?php echo esc_html($transaction_icon); ?></span>
                                        <?php echo esc_html($transaction_type_lbl); ?>
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
                                    <?php if ($transaction_status_lbl !== '') : ?>
                                        <div class="juntaplay-wallet__item-status"><?php echo esc_html(sprintf(__('Status: %s', 'juntaplay'), $transaction_status_lbl)); ?></div>
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
                                        <?php echo esc_html($transaction_type_lbl); ?>
                                    </strong>
                                    <?php if ($transaction_time !== '') : ?>
                                        <span class="juntaplay-wallet__item-meta"><?php echo esc_html($transaction_time); ?></span>
                                    <?php endif; ?>
                                    <?php if ($transaction_reference !== '') : ?>
                                        <span class="juntaplay-wallet__item-meta juntaplay-wallet__item-ref"><?php echo esc_html(sprintf(__('Referência: %s', 'juntaplay'), $transaction_reference)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="juntaplay-wallet__item-side">
                                    <?php if ($transaction_status_lbl !== '') : ?>
                                        <span class="juntaplay-wallet__item-status"><?php echo esc_html($transaction_status_lbl); ?></span>
                                    <?php endif; ?>
                                    <?php if ($transaction_amount !== '') : ?>
                                        <span class="juntaplay-wallet__item-amount"><?php echo esc_html($transaction_amount); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $default_render = (string) ob_get_clean();
                            }

                            $rendered_entry = apply_filters('juntaplay_wallet_transaction_render', $default_render, $transaction, $transaction_icon, $transaction_type_lbl);

                            echo wp_kses_post($rendered_entry);
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p
                    class="juntaplay-wallet__empty"
                    data-jp-credit-empty
                    data-default-message="<?php esc_attr_e('Nenhuma movimentação registrada ainda.', 'juntaplay'); ?>"
                    <?php if ($has_transactions) : ?>hidden<?php endif; ?>
                >
                    <?php esc_html_e('Nenhuma movimentação registrada ainda.', 'juntaplay'); ?>
                </p>

                <div class="juntaplay-wallet__pagination">
                    <button
                        type="button"
                        class="juntaplay-button juntaplay-button--ghost juntaplay-button--compact juntaplay-wallet__more"
                        data-jp-credit-load-more
                        <?php if ($wallet_page >= $wallet_pages) : ?>hidden<?php endif; ?>
                    >
                        <?php esc_html_e('Carregar mais movimentos', 'juntaplay'); ?>
                    </button>
                </div>
            </section>

            <aside class="juntaplay-wallet__history">
                <header class="juntaplay-wallet__section-header">
                    <div class="juntaplay-wallet__section-title">
                        <h3><?php esc_html_e('Solicitações de saque', 'juntaplay'); ?></h3>
                    </div>
                    <p><?php esc_html_e('Acompanhe o status das suas solicitações de retirada recentes.', 'juntaplay'); ?></p>
                </header>
                <?php if ($wallet_withdrawals) : ?>
                    <ul class="juntaplay-wallet__history-list" role="list">
                        <?php foreach ($wallet_withdrawals as $withdrawal) :
                            if (!is_array($withdrawal)) {
                                continue;
                            }

                            $withdraw_status = isset($withdrawal['status']) ? (string) $withdrawal['status'] : '';
                            $withdraw_amount = isset($withdrawal['amount']) ? (string) $withdrawal['amount'] : '';
                            $withdraw_time   = isset($withdrawal['time']) ? (string) $withdrawal['time'] : '';
                            $withdraw_dest   = isset($withdrawal['destination']) ? (string) $withdrawal['destination'] : '';
                            $withdraw_ref    = isset($withdrawal['reference']) ? (string) $withdrawal['reference'] : '';
                            ?>
                            <li class="juntaplay-wallet__history-item">
                                <div class="juntaplay-wallet__history-main">
                                    <strong><?php echo esc_html($withdraw_amount); ?></strong>
                                    <?php if ($withdraw_time !== '') : ?>
                                        <span class="juntaplay-wallet__history-meta"><?php echo esc_html($withdraw_time); ?></span>
                                    <?php endif; ?>
                                    <?php if ($withdraw_dest !== '') : ?>
                                        <span class="juntaplay-wallet__history-destination"><?php echo esc_html($withdraw_dest); ?></span>
                                    <?php endif; ?>
                                    <?php if ($withdraw_ref !== '') : ?>
                                        <span class="juntaplay-wallet__history-ref"><?php echo esc_html($withdraw_ref); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($withdraw_status !== '') : ?>
                                    <span class="juntaplay-wallet__history-status"><?php echo esc_html($withdraw_status); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p class="juntaplay-wallet__empty"><?php esc_html_e('Nenhuma solicitação de saque foi realizada.', 'juntaplay'); ?></p>
                <?php endif; ?>
            </aside>
        </div>

        <aside class="juntaplay-wallet__deposit" data-jp-credit-deposit hidden>
            <div class="juntaplay-wallet__deposit-card">
                <header class="juntaplay-wallet__deposit-header">
                    <h3><?php esc_html_e('Adicionar créditos', 'juntaplay'); ?></h3>
                    <button type="button" class="juntaplay-wallet__deposit-close" data-jp-credit-deposit-close aria-label="<?php esc_attr_e('Fechar', 'juntaplay'); ?>">&times;</button>
                </header>
                <form class="juntaplay-wallet__deposit-form" data-jp-credit-deposit-form>
                    <div class="juntaplay-field">
                        <label class="juntaplay-field__label" for="jp-credit-deposit-amount"><?php esc_html_e('Quanto você deseja adicionar?', 'juntaplay'); ?></label>
                        <div class="juntaplay-field__input-wrapper">
                            <span class="juntaplay-field__prefix">R$</span>
                            <input id="jp-credit-deposit-amount" type="number" step="0.01" min="0" name="jp_profile_deposit_amount" class="juntaplay-field__input" placeholder="0,00" />
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

    <div class="juntaplay-wallet__details" data-jp-credit-details hidden>
        <div class="juntaplay-wallet__details-card">
            <header class="juntaplay-wallet__details-header">
                <h4><?php esc_html_e('Detalhes da movimentação', 'juntaplay'); ?></h4>
                <button type="button" class="juntaplay-wallet__details-close" data-jp-credit-details-close>&times;</button>
            </header>
            <div class="juntaplay-wallet__details-body" data-jp-credit-details-body>
                <p class="juntaplay-wallet__empty"><?php esc_html_e('Selecione uma movimentação para visualizar os detalhes.', 'juntaplay'); ?></p>
            </div>
        </div>
    </div>
</div>
