<?php
declare(strict_types=1);

use JuntaPlay\Data\Pools;

$categories = Pools::get_category_labels();
$per_page   = 12;
?>
<div
    class="juntaplay-pool-catalog juntaplay-section"
    data-per-page="<?php echo esc_attr($per_page); ?>"
    data-order="desc"
    data-orderby="created_at"
>
    <header class="juntaplay-catalog__header">
        <div>
            <h2 class="juntaplay-catalog__title"><?php esc_html_e('Faça sua pesquisa', 'juntaplay'); ?></h2>
            <p class="juntaplay-catalog__subtitle"><?php esc_html_e('Refine por categoria, preço ou popularidade e encontre a campanha ideal para participar.', 'juntaplay'); ?></p>
        </div>
        <div class="juntaplay-catalog__meta" data-pool-meta></div>
    </header>

    <form class="juntaplay-pool-filters" novalidate>
        <div class="juntaplay-filters juntaplay-filters--catalog">
            <div class="juntaplay-filters__group">
                <label for="juntaplay-pool-search"><?php esc_html_e('Buscar campanha', 'juntaplay'); ?></label>
                <input id="juntaplay-pool-search" name="search" type="search" placeholder="<?php esc_attr_e('Nome, serviço ou palavra-chave', 'juntaplay'); ?>" />
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-pool-category"><?php esc_html_e('Categoria', 'juntaplay'); ?></label>
                <select id="juntaplay-pool-category" name="category">
                    <option value=""><?php esc_html_e('Todas as categorias', 'juntaplay'); ?></option>
                    <?php foreach ($categories as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-pool-orderby"><?php esc_html_e('Ordenar por', 'juntaplay'); ?></label>
                <select id="juntaplay-pool-orderby" name="orderby">
                    <option value="created_at"><?php esc_html_e('Mais recentes', 'juntaplay'); ?></option>
                    <option value="price"><?php esc_html_e('Menor preço', 'juntaplay'); ?></option>
                    <option value="quotas_paid"><?php esc_html_e('Maior adesão', 'juntaplay'); ?></option>
                </select>
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-pool-order"><?php esc_html_e('Direção', 'juntaplay'); ?></label>
                <select id="juntaplay-pool-order" name="order">
                    <option value="desc"><?php esc_html_e('Decrescente', 'juntaplay'); ?></option>
                    <option value="asc"><?php esc_html_e('Crescente', 'juntaplay'); ?></option>
                </select>
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-pool-min-price"><?php esc_html_e('Preço mínimo', 'juntaplay'); ?></label>
                <input id="juntaplay-pool-min-price" name="min_price" type="number" step="0.01" min="0" placeholder="0,00" />
            </div>
            <div class="juntaplay-filters__group">
                <label for="juntaplay-pool-max-price"><?php esc_html_e('Preço máximo', 'juntaplay'); ?></label>
                <input id="juntaplay-pool-max-price" name="max_price" type="number" step="0.01" min="0" placeholder="<?php esc_attr_e('Sem limite', 'juntaplay'); ?>" />
            </div>
            <div class="juntaplay-filters__actions juntaplay-filters__actions--stacked">
                <button type="submit" class="juntaplay-button juntaplay-button--secondary"><?php esc_html_e('Aplicar filtros', 'juntaplay'); ?></button>
            </div>
        </div>
    </form>

    <div class="juntaplay-catalog__results" data-pool-results></div>
    <p class="juntaplay-feedback" data-pool-empty></p>

    <div class="juntaplay-catalog__actions">
        <button type="button" class="juntaplay-button juntaplay-button--ghost" data-pools-load><?php esc_html_e('Carregar mais campanhas', 'juntaplay'); ?></button>
    </div>
</div>
