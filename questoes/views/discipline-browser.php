<?php
/**
 * Discipline browser template.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$disciplines       = isset( $disciplines ) ? $disciplines : array();
$areas             = isset( $areas ) ? $areas : array();
$total_disciplines = count( $disciplines );
$total_areas       = count( $areas );
$total_questions   = isset( $total_questions ) ? (int) $total_questions : 0;
$highlight         = isset( $highlight ) ? $highlight : '';
$title             = isset( $title ) ? $title : '';
$description       = isset( $description ) ? $description : '';
$search_id         = isset( $search_id ) ? $search_id : uniqid( 'questoes-discipline-search-', false );
$area_id           = isset( $area_id ) ? $area_id : uniqid( 'questoes-discipline-area-', false );
$total_comments    = 0;

foreach ( $disciplines as $entry ) {
    $total_comments += isset( $entry['comments'] ) ? (int) $entry['comments'] : 0;
}

$hero_title       = ! empty( $title ) ? $title : __( 'Disciplinas em destaque', 'questoes' );
$hero_description = ! empty( $description )
    ? $description
    : sprintf(
        /* translators: 1: total disciplines, 2: total areas, 3: total questions */
        esc_html__( 'Mapeamos %1$s disciplinas distribuídas em %2$s áreas com %3$s questões comentadas para orientar seus estudos.', 'questoes' ),
        esc_html( number_format_i18n( $total_disciplines ) ),
        esc_html( number_format_i18n( $total_areas ) ),
        esc_html( number_format_i18n( $total_questions ) )
    );
?>
<div class="questoes-stage questoes-discipline-browser" data-component="discipline-browser">
    <div class="questoes-shell questoes-shell--wide">
        <div class="questoes-discipline-browser__header">
            <div class="questoes-discipline-browser__headline">
                <span class="questoes-discipline-browser__eyebrow"><?php esc_html_e( 'Mapa de disciplinas', 'questoes' ); ?></span>
                <h2 class="questoes-discipline-browser__title"><?php echo esc_html( $hero_title ); ?></h2>
                <p class="questoes-discipline-browser__description"><?php echo esc_html( $hero_description ); ?></p>
            </div>
            <ul class="questoes-discipline-browser__stats">
                <li class="questoes-discipline-browser__stat">
                    <span class="questoes-discipline-browser__stat-label"><?php esc_html_e( 'Disciplinas catalogadas', 'questoes' ); ?></span>
                    <span class="questoes-discipline-browser__stat-value"><?php echo esc_html( number_format_i18n( $total_disciplines ) ); ?></span>
                </li>
                <li class="questoes-discipline-browser__stat">
                    <span class="questoes-discipline-browser__stat-label"><?php esc_html_e( 'Áreas de formação', 'questoes' ); ?></span>
                    <span class="questoes-discipline-browser__stat-value"><?php echo esc_html( number_format_i18n( $total_areas ) ); ?></span>
                </li>
                <li class="questoes-discipline-browser__stat">
                    <span class="questoes-discipline-browser__stat-label"><?php esc_html_e( 'Questões disponíveis', 'questoes' ); ?></span>
                    <span class="questoes-discipline-browser__stat-value"><?php echo esc_html( number_format_i18n( $total_questions ) ); ?></span>
                </li>
                <li class="questoes-discipline-browser__stat">
                    <span class="questoes-discipline-browser__stat-label"><?php esc_html_e( 'Comentários publicados', 'questoes' ); ?></span>
                    <span class="questoes-discipline-browser__stat-value"><?php echo esc_html( number_format_i18n( $total_comments ) ); ?></span>
                </li>
            </ul>
        </div>

        <div class="questoes-surface questoes-discipline-browser__surface">
            <div class="questoes-discipline-browser__panels">
                <form class="questoes-discipline-browser__filters questoes-panel" novalidate>
                    <div class="questoes-discipline-browser__filter questoes-discipline-browser__filter--search">
                        <label for="<?php echo esc_attr( $search_id ); ?>"><?php esc_html_e( 'Palavra-chave', 'questoes' ); ?></label>
                        <input type="search" id="<?php echo esc_attr( $search_id ); ?>" name="busca" placeholder="<?php esc_attr_e( 'Digite uma disciplina', 'questoes' ); ?>" />
                    </div>
                    <div class="questoes-discipline-browser__filter questoes-discipline-browser__filter--area">
                        <label for="<?php echo esc_attr( $area_id ); ?>"><?php esc_html_e( 'Área de Formação', 'questoes' ); ?></label>
                        <select id="<?php echo esc_attr( $area_id ); ?>" name="area">
                            <option value=""><?php esc_html_e( 'Todas as áreas', 'questoes' ); ?></option>
                            <?php foreach ( $areas as $area ) : ?>
                                <option value="<?php echo esc_attr( $area['slug'] ); ?>"><?php echo esc_html( $area['name'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="questoes-discipline-browser__filter questoes-discipline-browser__filter--submit">
                        <button type="submit" class="questoes-button questoes-button--primary"><?php esc_html_e( 'Filtrar', 'questoes' ); ?></button>
                    </div>
                </form>

                <?php if ( ! empty( $highlight ) ) : ?>
                    <div class="questoes-discipline-browser__highlight questoes-panel questoes-panel--accent">
                        <?php echo wp_kses_post( $highlight ); ?>
                    </div>
                <?php endif; ?>
            </div>

            <section class="questoes-discipline-browser__results" aria-live="polite">
                <header class="questoes-discipline-browser__intro">
                    <h3><?php esc_html_e( 'Disciplinas de Concursos Públicos mais procuradas', 'questoes' ); ?></h3>
                    <p>
                        <?php
                        printf(
                            esc_html__( '%1$s disciplinas organizadas em %2$s áreas de formação.', 'questoes' ),
                            number_format_i18n( $total_disciplines ),
                            number_format_i18n( $total_areas )
                        );
                        ?>
                    </p>
                    <p
                        class="questoes-discipline-browser__status"
                        data-total-disciplines="<?php echo esc_attr( $total_disciplines ); ?>"
                        data-total-questions="<?php echo esc_attr( $total_questions ); ?>"
                    >
                        <?php
                        printf(
                            esc_html__( '%1$s disciplinas encontradas com %2$s questões disponíveis.', 'questoes' ),
                            number_format_i18n( $total_disciplines ),
                            number_format_i18n( $total_questions )
                        );
                        ?>
                    </p>
                </header>

                <div class="questoes-discipline-browser__list" role="table">
                    <div class="questoes-discipline-browser__row questoes-discipline-browser__row--head" role="row">
                        <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--discipline" role="columnheader"><?php esc_html_e( 'Disciplina', 'questoes' ); ?></div>
                        <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--questions" role="columnheader"><?php esc_html_e( 'Questões', 'questoes' ); ?></div>
                        <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--comments" role="columnheader"><?php esc_html_e( 'Comentadas', 'questoes' ); ?></div>
                        <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--action" role="columnheader">
                            <span class="screen-reader-text"><?php esc_html_e( 'Ações', 'questoes' ); ?></span>
                        </div>
                    </div>
                    <?php foreach ( $disciplines as $entry ) :
                        $keywords_source = wp_strip_all_tags( implode( ' ', array_filter( array( $entry['name'], $entry['slug'], $entry['area_name'] ) ) ) );
                        if ( function_exists( 'mb_strtolower' ) ) {
                            $keywords = mb_strtolower( $keywords_source, 'UTF-8' );
                        } else {
                            $keywords = strtolower( $keywords_source );
                        }
                        $link     = ! empty( $entry['link'] ) ? $entry['link'] : '';
                        ?>
                        <article
                            class="questoes-discipline-browser__row"
                            role="row"
                            tabindex="0"
                            data-discipline="<?php echo esc_attr( $entry['slug'] ); ?>"
                            data-area="<?php echo esc_attr( $entry['area_slug'] ); ?>"
                            data-count="<?php echo esc_attr( $entry['count'] ); ?>"
                            data-comments="<?php echo esc_attr( $entry['comments'] ); ?>"
                            data-keywords="<?php echo esc_attr( $keywords ); ?>"
                        >
                            <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--discipline" role="cell">
                                <strong class="questoes-discipline-browser__name"><?php echo esc_html( $entry['name'] ); ?></strong>
                                <?php if ( ! empty( $entry['area_name'] ) && $entry['area_name'] !== $entry['name'] ) : ?>
                                    <span class="questoes-discipline-browser__area"><?php echo esc_html( $entry['area_name'] ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--questions" role="cell">
                                <span><?php echo esc_html( number_format_i18n( $entry['count'] ) ); ?></span>
                            </div>
                            <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--comments" role="cell">
                                <span><?php echo esc_html( number_format_i18n( $entry['comments'] ) ); ?></span>
                            </div>
                            <div class="questoes-discipline-browser__cell questoes-discipline-browser__cell--action" role="cell">
                                <?php if ( $link ) : ?>
                                    <a class="questoes-button questoes-button--ghost" href="<?php echo esc_url( $link ); ?>">
                                        <?php esc_html_e( 'Ver questões', 'questoes' ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <p class="questoes-discipline-browser__empty" hidden><?php esc_html_e( 'Nenhuma disciplina encontrada para os filtros selecionados.', 'questoes' ); ?></p>
            </section>
        </div>
    </div>
</div>
