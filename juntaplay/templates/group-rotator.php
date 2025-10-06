<?php
/**
 * JuntaPlay group rotator template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$rotator_limit            = isset($rotator_limit) ? (int) $rotator_limit : 12;
$rotator_categories       = isset($rotator_categories) && is_array($rotator_categories) ? $rotator_categories : [];
$rotator_default_category = isset($rotator_default_category) ? (string) $rotator_default_category : '';

if ($rotator_default_category !== '' && !isset($rotator_categories[$rotator_default_category])) {
    $rotator_default_category = '';
}

$category_icons = [
    'video'      => 'video',
    'musica'     => 'music',
    'cursos'     => 'book',
    'leitura'    => 'reader',
    'escritorio' => 'briefcase',
    'jogos'      => 'game',
];

if (!function_exists('juntaplay_group_rotator_icon')) {
    function juntaplay_group_rotator_icon(string $icon): string
    {
        $paths = [
            'video'     => 'M15 10.5V6a1.5 1.5 0 0 0-1.5-1.5h-9A1.5 1.5 0 0 0 3 6v8a1.5 1.5 0 0 0 1.5 1.5h9A1.5 1.5 0 0 0 15 14v-4.5l4.22 2.53a.75.75 0 0 0 1.13-.64V7.1a.75.75 0 0 0-1.13-.64Z',
            'music'     => 'M9 3v10.56a3 3 0 1 0 1.5 2.62V7.5h6V3z',
            'book'      => 'M5 4.5A2.5 2.5 0 0 1 7.5 2h11A1.5 1.5 0 0 1 20 3.5v17a.5.5 0 0 1-.77.41L16 18.37l-3.23 2.54a.5.5 0 0 1-.77-.41v-2H7.5A2.5 2.5 0 0 1 5 15.99Z',
            'reader'    => 'M5 4a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v16l-7-3-7 3z',
            'briefcase' => 'M9 3a2 2 0 0 0-2 2v1H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a2 2 0 0 0-2-2Z',
            'game'      => 'M4 6a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4h-2l-1.34 2.23a1 1 0 0 1-1.72 0L12 18h-4a4 4 0 0 1-4-4Z',
            'default'   => 'M12 3a9 9 0 1 1-9 9a9 9 0 0 1 9-9Zm0 4a1 1 0 0 0-1 1v4.59l3 3a1 1 0 1 0 1.41-1.41L13 11.17V8a1 1 0 0 0-1-1Z',
        ];

        $path = $paths[$icon] ?? $paths['default'];

        return '<svg class="juntaplay-group-rotator__icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="' . esc_attr($path) . '"/></svg>';
    }
}
?>
<section class="juntaplay-group-rotator" data-group-rotator data-limit="<?php echo esc_attr((string) max(4, $rotator_limit)); ?>" data-default-category="<?php echo esc_attr($rotator_default_category); ?>">

    <?php if ($rotator_categories) : ?>
        <nav class="juntaplay-group-rotator__controls" aria-label="<?php esc_attr_e('Filtrar categorias de grupos', 'juntaplay'); ?>">
            <button type="button" class="juntaplay-group-rotator__nav is-disabled" data-rotator-nav="prev" aria-label="<?php esc_attr_e('Categorias anteriores', 'juntaplay'); ?>" aria-disabled="true" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.41 16.59 10.83 12l4.58-4.59L14 6l-6 6 6 6z"/></svg>
            </button>
            <div class="juntaplay-group-rotator__filters-wrapper">
                <div class="juntaplay-group-rotator__filters" data-rotator-track>
                    <button type="button" class="juntaplay-group-rotator__filter <?php echo $rotator_default_category === '' ? 'is-active' : ''; ?>" data-rotator-filter="" aria-selected="<?php echo $rotator_default_category === '' ? 'true' : 'false'; ?>">
                        <?php echo juntaplay_group_rotator_icon('default'); ?>
                        <span><?php esc_html_e('Todos', 'juntaplay'); ?></span>
                    </button>
                    <?php foreach ($rotator_categories as $category_key => $category_label) :
                        $category_key = (string) $category_key;
                        $is_active    = $rotator_default_category !== '' && $rotator_default_category === $category_key;
                        $icon_key     = $category_icons[$category_key] ?? 'default';
                        ?>
                        <button type="button" class="juntaplay-group-rotator__filter <?php echo $is_active ? 'is-active' : ''; ?>" data-rotator-filter="<?php echo esc_attr($category_key); ?>" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                            <?php echo juntaplay_group_rotator_icon($icon_key); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <span><?php echo esc_html((string) $category_label); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="button" class="juntaplay-group-rotator__nav" data-rotator-nav="next" aria-label="<?php esc_attr_e('Próximas categorias', 'juntaplay'); ?>" aria-disabled="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="m10 6 6 6-6 6-1.41-1.41L13.17 12 8.59 7.41z"/></svg>
            </button>
        </nav>
    <?php endif; ?>

    <div class="juntaplay-group-rotator__grid" data-rotator-grid></div>
    <p class="juntaplay-group-rotator__empty" data-rotator-empty hidden><?php esc_html_e('Nenhum grupo disponível para esta categoria no momento.', 'juntaplay'); ?></p>
</section>
