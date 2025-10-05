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
$title      = isset($hero_title) ? (string) $hero_title : esc_html__('Assine e economize até 85% nos serviços!', 'juntaplay');
$description = isset($hero_description) ? (string) $hero_description : '';
$button_label = isset($hero_button) ? (string) $hero_button : esc_html__('Buscar', 'juntaplay');
?>
<section class="juntaplay-search-hero">
    <div class="juntaplay-search-hero__content">
        <div class="juntaplay-search-hero__copy">
            <span class="juntaplay-search-hero__tagline"><?php esc_html_e('JuntaPlay', 'juntaplay'); ?></span>
            <h1 class="juntaplay-search-hero__title"><?php echo esc_html($title); ?></h1>
            <?php if ($description !== '') : ?>
                <p class="juntaplay-search-hero__description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            <form class="juntaplay-search-hero__form" action="<?php echo esc_url($action); ?>" method="get" role="search" data-jp-group-search>
                <div class="juntaplay-search-hero__field">
                    <label for="juntaplay-search-query" class="screen-reader-text"><?php esc_html_e('O que você está procurando?', 'juntaplay'); ?></label>
                    <input type="search" id="juntaplay-search-query" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('O que você está procurando?', 'juntaplay'); ?>" autocomplete="off" />
                </div>
                <div class="juntaplay-search-hero__field juntaplay-search-hero__field--select">
                    <label for="juntaplay-search-category" class="screen-reader-text"><?php esc_html_e('Categoria', 'juntaplay'); ?></label>
                    <select id="juntaplay-search-category" name="category">
                        <option value=""><?php esc_html_e('Categoria', 'juntaplay'); ?></option>
                        <?php foreach ($categories as $category_key => $category_label) : ?>
                            <option value="<?php echo esc_attr((string) $category_key); ?>" <?php selected($current_category, (string) $category_key); ?>><?php echo esc_html((string) $category_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="juntaplay-search-hero__actions">
                    <button type="submit" class="juntaplay-button juntaplay-button--primary juntaplay-search-hero__submit"><?php echo esc_html($button_label); ?></button>
                </div>
            </form>
            <dl class="juntaplay-search-hero__stats">
                <div>
                    <dt><?php esc_html_e('960M+', 'juntaplay'); ?></dt>
                    <dd><?php esc_html_e('Serviços compartilhados', 'juntaplay'); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('850M', 'juntaplay'); ?></dt>
                    <dd><?php esc_html_e('Cotas aprovadas', 'juntaplay'); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('98M', 'juntaplay'); ?></dt>
                    <dd><?php esc_html_e('Usuários satisfeitos', 'juntaplay'); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('250M', 'juntaplay'); ?></dt>
                    <dd><?php esc_html_e('Projetos acompanhados', 'juntaplay'); ?></dd>
                </div>
            </dl>
        </div>
        <div class="juntaplay-search-hero__media" aria-hidden="true">
            <div class="juntaplay-search-hero__card">
                <span class="juntaplay-search-hero__badge"><?php esc_html_e('Qualidade comprovada', 'juntaplay'); ?></span>
                <p><?php esc_html_e('Campanhas verificadas diariamente pela equipe JuntaPlay.', 'juntaplay'); ?></p>
            </div>
            <div class="juntaplay-search-hero__illustration"></div>
        </div>
    </div>
</section>
