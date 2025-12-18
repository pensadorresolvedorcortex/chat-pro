<?php
/**
 * Public groups directory template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$group_categories = isset($categories) && is_array($categories) ? $categories : \JuntaPlay\Data\Groups::get_category_labels();
$current_search = '';
if (isset($_GET['search'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $current_search = sanitize_text_field((string) wp_unslash($_GET['search'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$current_category = '';
if (isset($_GET['category'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $candidate = sanitize_key((string) wp_unslash($_GET['category'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($group_categories[$candidate])) {
        $current_category = $candidate;
    }
}

$allowed_orderby = ['created', 'price', 'members'];
$current_orderby = 'created';
if (isset($_GET['orderby'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $candidate = sanitize_key((string) wp_unslash($_GET['orderby'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (in_array($candidate, $allowed_orderby, true)) {
        $current_orderby = $candidate;
    }
}

$current_order = 'desc';
if (isset($_GET['order'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $candidate = strtolower(sanitize_text_field((string) wp_unslash($_GET['order']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (in_array($candidate, ['asc', 'desc'], true)) {
        $current_order = $candidate;
    }
}

$instant_access = '';
if (isset($_GET['instant'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $instant_access = (string) wp_unslash($_GET['instant']) === '1' ? '1' : '';
}
?>
<section class="juntaplay-groups" data-jp-groups data-page="1" data-pages="1" data-per-page="16" data-card-variant="service" data-default-search="<?php echo esc_attr($current_search); ?>" data-default-category="<?php echo esc_attr($current_category); ?>" data-default-orderby="<?php echo esc_attr($current_orderby); ?>" data-default-order="<?php echo esc_attr($current_order); ?>" data-default-instant="<?php echo esc_attr($instant_access); ?>">
    <header class="juntaplay-groups__header">
        <div>
            <h1><?php esc_html_e('Explore grupos e campanhas compartilhadas', 'juntaplay'); ?></h1>
            <p><?php esc_html_e('Encontre grupos ativos criados pela comunidade, compare valores e solicite participação com poucos cliques.', 'juntaplay'); ?></p>
        </div>
    </header>

    <form class="juntaplay-filters" data-jp-groups-filters>
        <div class="juntaplay-filters__group juntaplay-filters__group--search">
            <label class="juntaplay-filters__label" for="jp-groups-search"><?php esc_html_e('Buscar grupos', 'juntaplay'); ?></label>
            <input type="search" id="jp-groups-search" name="search" class="juntaplay-filters__input" value="<?php echo esc_attr($current_search); ?>" placeholder="<?php esc_attr_e('Procure por nome, serviço ou organizador', 'juntaplay'); ?>" />
        </div>

        <div class="juntaplay-filters__group">
            <label class="juntaplay-filters__label" for="jp-groups-category"><?php esc_html_e('Categoria', 'juntaplay'); ?></label>
            <select id="jp-groups-category" name="category" class="juntaplay-filters__input">
                <option value=""><?php esc_html_e('Todas as categorias', 'juntaplay'); ?></option>
                <?php foreach ($group_categories as $category_key => $category_label) : ?>
                    <option value="<?php echo esc_attr((string) $category_key); ?>" <?php selected($current_category, (string) $category_key); ?>><?php echo esc_html((string) $category_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="juntaplay-filters__group">
            <label class="juntaplay-filters__label" for="jp-groups-sort"><?php esc_html_e('Ordenar por', 'juntaplay'); ?></label>
            <select id="jp-groups-sort" name="orderby" class="juntaplay-filters__input">
                <option value="created" data-order="desc" <?php selected($current_orderby === 'created' && $current_order === 'desc'); ?>><?php esc_html_e('Mais recentes', 'juntaplay'); ?></option>
                <option value="price" data-order="asc" <?php selected($current_orderby === 'price' && $current_order === 'asc'); ?>><?php esc_html_e('Menor preço', 'juntaplay'); ?></option>
                <option value="price" data-order="desc" <?php selected($current_orderby === 'price' && $current_order === 'desc'); ?>><?php esc_html_e('Maior preço', 'juntaplay'); ?></option>
                <option value="members" data-order="desc" <?php selected($current_orderby === 'members'); ?>><?php esc_html_e('Mais participantes', 'juntaplay'); ?></option>
            </select>
        </div>

        <div class="juntaplay-filters__group juntaplay-filters__group--inline">
            <label class="juntaplay-checkbox">
                <input type="checkbox" name="instant" value="1" <?php checked($instant_access, '1'); ?> />
                <span><?php esc_html_e('Acesso imediato', 'juntaplay'); ?></span>
            </label>
        </div>

        <div class="juntaplay-filters__actions">
            <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php esc_html_e('Filtrar grupos', 'juntaplay'); ?></button>
            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-jp-groups-clear><?php esc_html_e('Limpar filtros', 'juntaplay'); ?></button>
        </div>
    </form>

    <div class="juntaplay-groups__body" data-jp-groups-body>
        <div class="juntaplay-groups__list">
            <div class="juntaplay-groups__cards juntaplay-service-grid" data-jp-groups-list data-group-list></div>
        </div>
        <p class="juntaplay-groups__empty" data-jp-groups-empty hidden><?php esc_html_e('Nenhum grupo corresponde aos filtros selecionados.', 'juntaplay'); ?></p>
    </div>

    <footer class="juntaplay-groups__footer">
        <span class="juntaplay-groups__total" data-jp-groups-total><?php esc_html_e('Carregando grupos...', 'juntaplay'); ?></span>
        <nav class="juntaplay-pagination" data-jp-groups-pagination aria-label="<?php esc_attr_e('Paginação de grupos', 'juntaplay'); ?>" hidden></nav>
    </footer>

    <noscript>
        <p class="juntaplay-groups__noscript"><?php esc_html_e('Ative o JavaScript para explorar e filtrar os grupos disponíveis.', 'juntaplay'); ?></p>
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
