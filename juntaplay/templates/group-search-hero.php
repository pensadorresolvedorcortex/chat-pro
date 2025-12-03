<?php
/**
 * JuntaPlay hero search template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$categories = isset($hero_categories) ? (array) $hero_categories : \JuntaPlay\Data\Groups::get_category_labels();
$search     = isset($hero_search) ? (string) $hero_search : '';
$current_category = isset($hero_category) ? (string) $hero_category : '';
$action     = isset($hero_action) ? (string) $hero_action : home_url('/');
$button_label = isset($hero_button) ? (string) $hero_button : esc_html__('Explorar Grupos', 'juntaplay');
?>
<section class="juntaplay-search-hero">
    <form class="juntaplay-search-hero__form" action="<?php echo esc_url($action); ?>" method="get" role="search" data-jp-group-search>
        <div class="juntaplay-search-hero__field">
            <label for="juntaplay-search-query" class="screen-reader-text"><?php esc_html_e('O que você está procurando?', 'juntaplay'); ?></label>
            <span class="juntaplay-search-hero__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">
                    <path d="M14.29 13.17l4.27 4.27a1 1 0 01-1.42 1.42l-4.27-4.27a7 7 0 111.42-1.42zM8.5 14a5.5 5.5 0 100-11 5.5 5.5 0 000 11z" fill="currentColor" />
                </svg>
            </span>
            <input type="search" id="juntaplay-search-query" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('O que você está procurando?', 'juntaplay'); ?>" autocomplete="off" />
        </div>
        <div class="juntaplay-search-hero__field juntaplay-search-hero__field--select">
            <label for="juntaplay-search-category" class="screen-reader-text"><?php esc_html_e('Categoria', 'juntaplay'); ?></label>
            <span class="juntaplay-search-hero__icon" aria-hidden="true">
                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">
                    <path d="M3 5a2 2 0 012-2h10a2 2 0 012 2v1H3V5zm0 4h14v6a2 2 0 01-2 2H5a2 2 0 01-2-2V9zm4 2a1 1 0 100 2h6a1 1 0 100-2H7z" fill="currentColor" />
                </svg>
            </span>
            <select id="juntaplay-search-category" name="category">
                <option value=""><?php esc_html_e('Categoria', 'juntaplay'); ?></option>
                <?php foreach ($categories as $category_key => $category_label) : ?>
                    <option value="<?php echo esc_attr((string) $category_key); ?>" <?php selected($current_category, (string) $category_key); ?>><?php echo esc_html((string) $category_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="juntaplay-search-hero__actions">
            <button type="submit" class="juntaplay-search-hero__submit">
                <span><?php echo esc_html($button_label); ?></span>
                <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" focusable="false" aria-hidden="true">
                    <path d="M4 10a1 1 0 011-1h7.59L10.3 6.7a1 1 0 011.4-1.42l4 4a1 1 0 010 1.42l-4 4a1 1 0 01-1.4-1.42L12.59 11H5a1 1 0 01-1-1z" fill="currentColor" />
                </svg>
            </button>
        </div>
    </form>
</section>
