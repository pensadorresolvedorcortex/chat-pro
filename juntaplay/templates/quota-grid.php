<?php
declare(strict_types=1);

use JuntaPlay\Data\Pools;

global $wpdb;

$pool = Pools::get($current_pool_id ?? 0);

if (!$pool) {
    echo '<p class="juntaplay-notice">' . esc_html__('Campanha não encontrada.', 'juntaplay') . '</p>';
    return;
}

$table  = "{$wpdb->prefix}jp_quotas";
$counts = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT status, COUNT(*) AS total FROM $table WHERE pool_id = %d GROUP BY status",
        (int) $pool->id
    ),
    ARRAY_A
);

$stats = [
    'available' => 0,
    'reserved'  => 0,
    'paid'      => 0,
    'canceled'  => 0,
    'expired'   => 0,
];

foreach ($counts as $row) {
    $status = isset($row['status']) ? (string) $row['status'] : '';
    if (isset($stats[$status])) {
        $stats[$status] = (int) ($row['total'] ?? 0);
    }
}

$total     = array_sum($stats);
$available = $stats['available'];
$reserved  = $stats['reserved'];
$paid      = $stats['paid'];
$progress  = $total > 0 ? min(100, (int) round(($paid / $total) * 100)) : 0;
$currency  = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'BRL';
$locale    = str_replace('_', '-', get_locale());
$price     = (float) $pool->price;
$cart_url  = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
$per_page  = isset($per_page) ? (int) $per_page : 120;
?>
<div
    class="juntaplay-quota-selector juntaplay-section"
    data-pool="<?php echo esc_attr((int) $pool->id); ?>"
    data-per-page="<?php echo esc_attr($per_page); ?>"
    data-status="available"
    data-sort="ASC"
>
    <header class="juntaplay-summary" aria-label="<?php echo esc_attr__('Resumo de disponibilidade das cotas', 'juntaplay'); ?>">
        <div class="juntaplay-summary__item">
            <span class="juntaplay-summary__label"><?php esc_html_e('Disponíveis', 'juntaplay'); ?></span>
            <span class="juntaplay-summary__value"><?php echo esc_html(number_format_i18n($available)); ?></span>
        </div>
        <div class="juntaplay-summary__item">
            <span class="juntaplay-summary__label"><?php esc_html_e('Reservadas', 'juntaplay'); ?></span>
            <span class="juntaplay-summary__value"><?php echo esc_html(number_format_i18n($reserved)); ?></span>
        </div>
        <div class="juntaplay-summary__item">
            <span class="juntaplay-summary__label"><?php esc_html_e('Pagas', 'juntaplay'); ?></span>
            <span class="juntaplay-summary__value"><?php echo esc_html(number_format_i18n($paid)); ?></span>
        </div>
        <div class="juntaplay-summary__item">
            <span class="juntaplay-summary__label"><?php esc_html_e('Total', 'juntaplay'); ?></span>
            <span class="juntaplay-summary__value"><?php echo esc_html(number_format_i18n($total)); ?></span>
        </div>
    </header>

    <div class="juntaplay-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress); ?>">
        <span class="juntaplay-progress__bar" style="width: <?php echo esc_attr((string) $progress); ?>%;"></span>
    </div>

    <div class="juntaplay-summary__legend" aria-hidden="true">
        <?php
        printf(
            /* translators: %s: percentage of paid quotas */
            esc_html__('%s%% das cotas já foram pagas.', 'juntaplay'),
            esc_html(number_format_i18n($progress))
        );
        ?>
    </div>

    <form class="juntaplay-quota-filter" novalidate>
        <div class="juntaplay-filters">
            <div class="juntaplay-filters__group">
                <label for="juntaplay-quota-status"><?php esc_html_e('Status', 'juntaplay'); ?></label>
                <select id="juntaplay-quota-status" name="status">
                    <option value="available"><?php esc_html_e('Disponíveis', 'juntaplay'); ?></option>
                    <option value="open"><?php esc_html_e('Disponíveis e reservadas', 'juntaplay'); ?></option>
                    <option value="reserved"><?php esc_html_e('Reservadas', 'juntaplay'); ?></option>
                    <option value="paid"><?php esc_html_e('Pagas', 'juntaplay'); ?></option>
                    <option value="all"><?php esc_html_e('Todos os números', 'juntaplay'); ?></option>
                </select>
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-quota-search"><?php esc_html_e('Buscar número ou intervalo', 'juntaplay'); ?></label>
                <input id="juntaplay-quota-search" type="text" name="search" placeholder="<?php esc_attr_e('Ex.: 10-50', 'juntaplay'); ?>" />
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-quota-sort"><?php esc_html_e('Ordenar', 'juntaplay'); ?></label>
                <select id="juntaplay-quota-sort" name="sort">
                    <option value="ASC"><?php esc_html_e('Menor número', 'juntaplay'); ?></option>
                    <option value="DESC"><?php esc_html_e('Maior número', 'juntaplay'); ?></option>
                </select>
            </div>
            <div class="juntaplay-filters__actions">
                <button type="submit" class="juntaplay-button juntaplay-button--secondary"><?php esc_html_e('Aplicar filtros', 'juntaplay'); ?></button>
            </div>
        </div>
    </form>

    <div class="juntaplay-grid-wrap">
        <div class="juntaplay-grid juntaplay-grid--quotas" data-quota-grid></div>
        <p class="juntaplay-feedback" data-quota-feedback></p>
        <div class="juntaplay-grid__actions">
            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-quota-load><?php esc_html_e('Ver mais números', 'juntaplay'); ?></button>
        </div>
    </div>

    <form
        class="juntaplay-quota-form"
        method="post"
        action="<?php echo esc_url($cart_url); ?>"
        data-message-empty="<?php echo esc_attr__('Selecione ao menos uma cota.', 'juntaplay'); ?>"
        data-price="<?php echo esc_attr((string) $price); ?>"
        data-currency="<?php echo esc_attr($currency); ?>"
        data-locale="<?php echo esc_attr($locale); ?>"
    >
        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr((int) $pool->product_id); ?>" />
        <input type="hidden" name="jp_pool_id" value="<?php echo esc_attr((int) $pool->id); ?>" />

        <div class="juntaplay-selected">
            <div class="juntaplay-selected__header">
                <div>
                    <strong><?php esc_html_e('Cotas selecionadas', 'juntaplay'); ?></strong>
                    <p class="juntaplay-summary__label"><?php esc_html_e('Clique para adicionar ou remover números.', 'juntaplay'); ?></p>
                </div>
                <div class="juntaplay-selected__count-wrapper">
                    <span class="juntaplay-badge"><span class="juntaplay-selected__count">0</span></span>
                    <span class="juntaplay-summary__label"><?php esc_html_e('itens', 'juntaplay'); ?></span>
                </div>
            </div>

            <div class="juntaplay-selected__numbers" data-empty="<?php echo esc_attr__('Nenhuma cota selecionada ainda.', 'juntaplay'); ?>"></div>

            <div class="juntaplay-selected__footer">
                <span class="juntaplay-selected__total-label"><?php esc_html_e('Total estimado', 'juntaplay'); ?></span>
                <span class="juntaplay-selected__total-value" data-empty="<?php echo esc_attr__('—', 'juntaplay'); ?>"><?php esc_html_e('—', 'juntaplay'); ?></span>
            </div>
        </div>

        <div style="margin-top:1.5rem; display:flex; justify-content:flex-end;">
            <button type="submit" class="juntaplay-button juntaplay-button--primary btn btn-theme"><?php esc_html_e('Reservar cotas', 'juntaplay'); ?></button>
        </div>
    </form>
</div>
