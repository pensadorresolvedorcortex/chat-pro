<?php
/**
 * JuntaPlay group rotator template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$rotator_title            = isset($rotator_title) ? (string) $rotator_title : '';
$rotator_description      = isset($rotator_description) ? (string) $rotator_description : '';
$rotator_button           = isset($rotator_button) ? (string) $rotator_button : '';
$rotator_limit            = isset($rotator_limit) ? (int) $rotator_limit : 12;
$rotator_categories       = isset($rotator_categories) && is_array($rotator_categories) ? $rotator_categories : [];
$rotator_default_category = isset($rotator_default_category) ? (string) $rotator_default_category : '';
$rotator_directory_url    = isset($rotator_directory_url) ? (string) $rotator_directory_url : home_url('/grupos');

if ($rotator_default_category !== '' && !isset($rotator_categories[$rotator_default_category])) {
    $rotator_default_category = '';
}
?>
<section class="juntaplay-group-rotator" data-group-rotator data-limit="<?php echo esc_attr((string) max(4, $rotator_limit)); ?>" data-default-category="<?php echo esc_attr($rotator_default_category); ?>" data-autoplay="6500">
    <header class="juntaplay-group-rotator__header">
        <div>
            <?php if ($rotator_title !== '') : ?>
                <h2><?php echo esc_html($rotator_title); ?></h2>
            <?php endif; ?>
            <?php if ($rotator_description !== '') : ?>
                <p><?php echo esc_html($rotator_description); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($rotator_button !== '') : ?>
            <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($rotator_directory_url); ?>"><?php echo esc_html($rotator_button); ?></a>
        <?php endif; ?>
    </header>

    <?php if ($rotator_categories) : ?>
        <nav class="juntaplay-group-rotator__filters" aria-label="<?php esc_attr_e('Filtrar categorias de grupos', 'juntaplay'); ?>">
            <button type="button" class="<?php echo $rotator_default_category === '' ? 'is-active' : ''; ?>" data-rotator-filter="" aria-selected="<?php echo $rotator_default_category === '' ? 'true' : 'false'; ?>"><?php esc_html_e('Todos', 'juntaplay'); ?></button>
            <?php foreach ($rotator_categories as $category_key => $category_label) :
                $category_key   = (string) $category_key;
                $is_active      = $rotator_default_category !== '' && $rotator_default_category === $category_key;
                ?>
                <button type="button" class="<?php echo $is_active ? 'is-active' : ''; ?>" data-rotator-filter="<?php echo esc_attr($category_key); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"><?php echo esc_html((string) $category_label); ?></button>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="juntaplay-group-rotator__viewport" data-rotator-viewport>
        <div class="juntaplay-group-rotator__track" data-rotator-track></div>
        <p class="juntaplay-group-rotator__empty" data-rotator-empty hidden><?php esc_html_e('Nenhum grupo disponível para esta categoria no momento.', 'juntaplay'); ?></p>
    </div>

    <div class="juntaplay-group-rotator__nav">
        <button type="button" data-rotator-prev aria-label="<?php esc_attr_e('Ver grupo anterior', 'juntaplay'); ?>">&#10094;</button>
        <button type="button" data-rotator-next aria-label="<?php esc_attr_e('Ver próximo grupo', 'juntaplay'); ?>">&#10095;</button>
    </div>
</section>
