<?php
/**
 * JuntaPlay group search results template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$search_term      = isset($search_term) ? (string) $search_term : '';
$search_category  = isset($search_category) ? (string) $search_category : '';
$category_labels  = isset($search_categories) && is_array($search_categories)
    ? $search_categories
    : \JuntaPlay\Data\Groups::get_category_labels();
$category_name    = $search_category !== '' && isset($category_labels[$search_category])
    ? (string) $category_labels[$search_category]
    : '';
$has_query        = $search_term !== '' || $category_name !== '';

$headline_parts = [];
if ($search_term !== '') {
    $headline_parts[] = sprintf(
        /* translators: %s: search term */
        esc_html__('Resultados para "%s"', 'juntaplay'),
        esc_html($search_term)
    );
}
if ($category_name !== '') {
    $headline_parts[] = sprintf(
        /* translators: %s: category label */
        esc_html__('Categoria: %s', 'juntaplay'),
        esc_html($category_name)
    );
}

if (!$headline_parts) {
    $headline_parts[] = esc_html__('Todos os grupos encontrados', 'juntaplay');
}

?>
<section
    class="juntaplay-groups juntaplay-search-results"
    data-jp-groups
    data-page="1"
    data-pages="1"
    data-per-page="16"
    data-card-variant="compact"
    data-default-search="<?php echo esc_attr($search_term); ?>"
    data-default-category="<?php echo esc_attr($search_category); ?>"
    data-default-orderby="created"
    data-default-order="desc"
    data-default-instant=""
>
    <header class="juntaplay-groups__header">
        <div>
            <h2><?php echo implode(' — ', $headline_parts); ?></h2>
            <?php if ($has_query) : ?>
                <p><?php esc_html_e('Confira abaixo os grupos que combinam com a sua pesquisa e escolha qual participar.', 'juntaplay'); ?></p>
            <?php else : ?>
                <p><?php esc_html_e('Use os filtros acima para encontrar grupos de acordo com seus interesses.', 'juntaplay'); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <div class="juntaplay-groups__body" data-jp-groups-body>
        <div class="juntaplay-groups__list" data-jp-groups-list></div>
        <p class="juntaplay-groups__empty" data-jp-groups-empty hidden><?php esc_html_e('Nenhum grupo corresponde à sua pesquisa no momento.', 'juntaplay'); ?></p>
    </div>

    <footer class="juntaplay-groups__footer">
        <span class="juntaplay-groups__total" data-jp-groups-total><?php esc_html_e('Buscando grupos...', 'juntaplay'); ?></span>
        <nav class="juntaplay-pagination" data-jp-groups-pagination aria-label="<?php esc_attr_e('Paginação dos resultados de grupos', 'juntaplay'); ?>" hidden></nav>
    </footer>

    <noscript>
        <p class="juntaplay-groups__noscript"><?php esc_html_e('Ative o JavaScript para visualizar os resultados da pesquisa de grupos.', 'juntaplay'); ?></p>
    </noscript>
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
