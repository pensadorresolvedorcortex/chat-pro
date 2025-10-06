<?php
declare(strict_types=1);

use JuntaPlay\Data\Pools;

global $product, $wpdb;

$pool = isset($current_pool_id) && $current_pool_id ? Pools::get((int) $current_pool_id) : null;

if (!$pool && isset($product)) {
    $pool = Pools::get((int) get_post_meta($product->get_id(), '_juntaplay_pool_id', true));
}

if ($pool) {
    $stats = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT 
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) AS reserved,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid,
                COUNT(*) AS total
             FROM {$wpdb->prefix}jp_quotas
             WHERE pool_id = %d",
            (int) $pool->id
        ),
        ARRAY_A
    ) ?: [];

    $total     = (int) ($stats['total'] ?? ($pool->quotas_total ?? 0));
    $available = (int) ($stats['available'] ?? max(0, $total - ($pool->quotas_paid ?? 0)));
    $reserved  = (int) ($stats['reserved'] ?? 0);
    $paid      = (int) ($stats['paid'] ?? ($pool->quotas_paid ?? 0));
    $progress  = $total > 0 ? min(100, (int) round(($paid / $total) * 100)) : 0;
    $price_display = function_exists('wc_price') ? wc_price($pool->price) : number_format_i18n((float) $pool->price, 2);
}
?>
<?php if ($pool) : ?>
    <section class="juntaplay-pool-single juntaplay-card juntaplay-card--single juntaplay-section" aria-labelledby="juntaplay-pool-title">
        <header class="juntaplay-pool-single__header">
            <span class="juntaplay-badge"><?php esc_html_e('Campanha ativa', 'juntaplay'); ?></span>
            <h1 id="juntaplay-pool-title"><?php echo esc_html($pool->title); ?></h1>
            <p class="juntaplay-card__price"><?php echo wp_kses_post(sprintf(__('Cada cota custa %s', 'juntaplay'), $price_display)); ?></p>
        </header>

        <div class="juntaplay-summary" aria-label="<?php echo esc_attr__('Painel da campanha', 'juntaplay'); ?>">
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
                <span class="juntaplay-summary__label"><?php esc_html_e('Total de cotas', 'juntaplay'); ?></span>
                <span class="juntaplay-summary__value"><?php echo esc_html(number_format_i18n($total)); ?></span>
            </div>
        </div>

        <div class="juntaplay-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress); ?>">
            <span class="juntaplay-progress__bar" style="width: <?php echo esc_attr((string) $progress); ?>%;"></span>
        </div>

        <div class="juntaplay-summary__legend" aria-hidden="true">
            <?php
            printf(
                /* translators: %s: percentage of paid quotas */
                esc_html__('Progresso das cotas pagas: %s%% concluído', 'juntaplay'),
                esc_html(number_format_i18n($progress))
            );
            ?>
        </div>

        <div class="juntaplay-pool-single__content">
            <?php echo apply_filters('the_content', get_post_field('post_content', (int) $pool->product_id)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </section>
<?php else : ?>
    <p class="juntaplay-notice"><?php esc_html_e('Campanha não encontrada.', 'juntaplay'); ?></p>
<?php endif; ?>
