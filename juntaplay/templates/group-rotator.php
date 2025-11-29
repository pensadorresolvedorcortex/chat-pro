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
    'boloes'      => 'ticket',
    'video'       => 'video',
    'music'       => 'music',
    'education'   => 'graduation',
    'reading'     => 'book-open',
    'office'      => 'briefcase',
    'software'    => 'code',
    'games'       => 'game',
    'ai'          => 'spark',
    'security'    => 'shield',
    'marketplace' => 'cart',
    'lifestyle'   => 'heart',
    'other'       => 'default',
];

if (!function_exists('juntaplay_group_rotator_icon')) {
    function juntaplay_group_rotator_icon(string $icon): string
    {
        $paths = [
            'ticket'     => 'M4 4h12a2 2 0 0 1 2 2v1a1.5 1.5 0 1 0 0 3v1a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-1a1.5 1.5 0 1 0 0-3V6a2 2 0 0 1 2-2zm4 3a1 1 0 1 0 0 2a1 1 0 0 0 0-2zm-3 0a1 1 0 1 0 0 2a1 1 0 0 0 0-2z',
            'video'      => 'M15 10.5V6a1.5 1.5 0 0 0-1.5-1.5h-9A1.5 1.5 0 0 0 3 6v8a1.5 1.5 0 0 0 1.5 1.5h9A1.5 1.5 0 0 0 15 14v-4.5l4.22 2.53a.75.75 0 0 0 1.13-.64V7.1a.75.75 0 0 0-1.13-.64Z',
            'music'      => 'M9 3v10.56a3 3 0 1 0 1.5 2.62V7.5h6V3z',
            'graduation' => 'M4 8l8-3l8 3l-8 3l-8-3zm2 4.2V15a1 1 0 0 0 .76.97L12 17.5l5.24-1.53A1 1 0 0 0 18 15v-2.8l-6 2.25l-6-2.25z',
            'book-open'  => 'M4.5 5A1.5 1.5 0 0 1 6 3.5h5A1.5 1.5 0 0 1 12.5 5v12.75l-4.5-1.8l-4.5 1.8V5zm9 0A1.5 1.5 0 0 1 15 3.5h4A1.5 1.5 0 0 1 20.5 5v12.75l-4.5-1.8l-2.5 1z',
            'briefcase'  => 'M9 3a2 2 0 0 0-2 2v1H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3V5a2 2 0 0 0-2-2Z',
            'code'       => 'M8.7 6.7L5.4 10l3.3 3.3l-1.4 1.4L2.6 10l4.7-5.7l1.4 1.4zm6.6 0l1.4-1.4l4.7 5.7l-4.7 5.7l-1.4-1.4l3.3-3.3l-3.3-3.3zM13 4l-2 16h-2l2-16h2z',
            'game'       => 'M4 6a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4h-2l-1.34 2.23a1 1 0 0 1-1.72 0L12 18h-4a4 4 0 0 1-4-4Z',
            'spark'      => 'M12 2l2.3 5.9L20 10l-5.7 2.1L12 18l-2.3-5.9L4 10l5.7-2.1z',
            'shield'     => 'M12 2l8 4v6c0 5-3.3 9.4-8 10c-4.7-.6-8-5-8-10V6z',
            'cart'       => 'M3 4h2l1 4h12a1 1 0 0 1 0 2H6.7l-.6 3H17a2 2 0 0 1 0 4H8a2 2 0 1 1-4 0a2 2 0 0 1 2-2h9.6a1 1 0 0 0 0-2H5.4a1 1 0 0 1-.98-.8L3 4zm3 13a1 1 0 1 0 0 2a1 1 0 0 0 0-2zm10 0a1 1 0 1 0 0 2a1 1 0 0 0 0-2z',
            'heart'      => 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5C2 6 3.99 4 6.5 4C8.04 4 9.5 4.99 10 6.28C10.5 4.99 11.96 4 13.5 4C16.01 4 18 6 18 8.5c0 3.78-3.4 6.86-8.45 11.53z',
            'default'    => 'M12 3a9 9 0 1 1-9 9a9 9 0 0 1 9-9Zm0 4a1 1 0 0 0-1 1v4.59l3 3a1 1 0 1 0 1.41-1.41L13 11.17V8a1 1 0 0 0-1-1Z',
        ];

        $path = $paths[$icon] ?? $paths['default'];

        return '<svg class="juntaplay-group-rotator__icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="' . esc_attr($path) . '"/></svg>';
    }
}
?>
<section
    class="juntaplay-group-rotator juntaplay-group-rotator--services"
    data-group-rotator
    data-limit="<?php echo esc_attr((string) max(4, $rotator_limit)); ?>"
    data-default-category="<?php echo esc_attr($rotator_default_category); ?>"
    data-login-url="<?php echo esc_url($rotator_login_url); ?>"
    data-redirect-param="<?php echo esc_attr($rotator_redirect_param); ?>"
    data-logged-in="<?php echo esc_attr($rotator_logged_in ? '1' : '0'); ?>"
>
    <div class="juntaplay-service-grid__header">
        <?php if ($rotator_title !== '') : ?>
            <p class="juntaplay-eyebrow"><?php echo esc_html__('Grupos em alta', 'juntaplay'); ?></p>
            <h4><?php echo esc_html($rotator_title); ?></h4>
        <?php endif; ?>

        <?php if ($rotator_description !== '') : ?>
            <p><?php echo esc_html($rotator_description); ?></p>
        <?php endif; ?>
    </div>

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

    <div class="juntaplay-group-rotator__grid juntaplay-service-grid" data-rotator-grid></div>
    <p class="juntaplay-group-rotator__empty" data-rotator-empty hidden><?php esc_html_e('Nenhum grupo disponível para esta categoria no momento.', 'juntaplay'); ?></p>

</section>

<?php
if (!defined('JUNTAPLAY_GROUP_MODAL_RENDERED')) {
    define('JUNTAPLAY_GROUP_MODAL_RENDERED', true);

    $ajax_endpoint = admin_url('admin-ajax.php');
    $ajax_nonce    = wp_create_nonce('jp_nonce');
    $rest_root     = rest_url('juntaplay/v1/');
    $rest_nonce    = wp_create_nonce('wp_rest');
    ?>
    <div
        id="juntaplay-group-modal"
        class="juntaplay-modal"
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        hidden
        data-ajax-endpoint="<?php echo esc_url($ajax_endpoint); ?>"
        data-ajax-nonce="<?php echo esc_attr($ajax_nonce); ?>"
        data-rest-root="<?php echo esc_url($rest_root); ?>"
        data-rest-nonce="<?php echo esc_attr($rest_nonce); ?>"
    >
        <div class="juntaplay-modal__overlay" data-group-modal-close></div>
        <div class="juntaplay-modal__dialog" role="document">
            <button type="button" class="juntaplay-modal__close" aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">&times;</button>
            <div class="juntaplay-modal__messages" data-modal-messages></div>
            <div class="juntaplay-modal__content"></div>
        </div>
    </div>
    <?php
}
?>
