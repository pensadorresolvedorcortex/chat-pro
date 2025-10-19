<?php
/**
 * Question search view.
 *
 * @var array  $config
 * @var array  $categories
 * @var array  $bancas
 * @var array  $subjects
 * @var string $title
 * @var array  $palette
 * @var array  $results
 * @var string $search_placeholder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$show_filters        = ! empty( $config['showFilter'] );
$palette             = wp_parse_args( (array) $palette, questoes_get_default_palette() );
$selected_categories = array_filter( array_map( 'trim', explode( ',', isset( $config['category'] ) ? $config['category'] : '' ) ) );
$selected_bancas     = array_filter( array_map( 'trim', explode( ',', isset( $config['banca'] ) ? $config['banca'] : '' ) ) );
$selected_subjects   = array_filter( array_map( 'trim', explode( ',', isset( $config['subject'] ) ? $config['subject'] : '' ) ) );
$selected_type       = isset( $config['type'] ) ? $config['type'] : '';
$selected_year       = ! empty( $config['year'] ) ? (int) $config['year'] : '';
$selected_search     = isset( $config['search'] ) ? $config['search'] : '';
$selected_difficulty = isset( $config['difficulty'] ) ? $config['difficulty'] : '';
$initial_notice      = isset( $config['initialNotice'] ) ? $config['initialNotice'] : '';
$question_types      = questoes_get_question_types();
?>
<div class="questoes-question-bank questoes-question-bank--search" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
    <?php if ( ! empty( $title ) ) : ?>
        <h2 class="questoes-question-bank__title" style="color: <?php echo esc_attr( $palette['primary'] ); ?>;">
            <?php echo esc_html( $title ); ?>
        </h2>
    <?php endif; ?>

    <?php if ( $show_filters ) : ?>
        <form class="questoes-question-bank__filters" aria-label="<?php esc_attr_e( 'Filtros de busca do banco de questões', 'questoes' ); ?>">
            <div class="questoes-question-bank__filter questoes-question-bank__filter--search">
                <label for="questoes-search-term"><?php esc_html_e( 'Palavra-chave', 'questoes' ); ?></label>
                <input type="search" id="questoes-search-term" name="busca" value="<?php echo esc_attr( $selected_search ); ?>" placeholder="<?php echo esc_attr( $search_placeholder ); ?>" />
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-search-category"><?php esc_html_e( 'Categoria', 'questoes' ); ?></label>
                <select id="questoes-search-category" name="categoria">
                    <option value=""><?php esc_html_e( 'Todas', 'questoes' ); ?></option>
                    <?php foreach ( $categories as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( in_array( $term->slug, $selected_categories, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-search-banca"><?php esc_html_e( 'Banca', 'questoes' ); ?></label>
                <select id="questoes-search-banca" name="banca">
                    <option value=""><?php esc_html_e( 'Todas', 'questoes' ); ?></option>
                    <?php foreach ( $bancas as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( in_array( $term->slug, $selected_bancas, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-search-subject"><?php esc_html_e( 'Assunto', 'questoes' ); ?></label>
                <select id="questoes-search-subject" name="assunto">
                    <option value=""><?php esc_html_e( 'Todos', 'questoes' ); ?></option>
                    <?php foreach ( $subjects as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( in_array( $term->slug, $selected_subjects, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-search-difficulty"><?php esc_html_e( 'Dificuldade', 'questoes' ); ?></label>
                <select id="questoes-search-difficulty" name="dificuldade">
                    <option value=""><?php esc_html_e( 'Todas', 'questoes' ); ?></option>
                    <option value="easy" <?php selected( 'easy' === $selected_difficulty ); ?>><?php esc_html_e( 'Fácil', 'questoes' ); ?></option>
                    <option value="medium" <?php selected( 'medium' === $selected_difficulty ); ?>><?php esc_html_e( 'Média', 'questoes' ); ?></option>
                    <option value="hard" <?php selected( 'hard' === $selected_difficulty ); ?>><?php esc_html_e( 'Difícil', 'questoes' ); ?></option>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-search-type"><?php esc_html_e( 'Formato', 'questoes' ); ?></label>
                <select id="questoes-search-type" name="tipo">
                    <option value=""><?php esc_html_e( 'Todos', 'questoes' ); ?></option>
                    <?php foreach ( $question_types as $type_key => $type_label ) : ?>
                        <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $selected_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-search-year"><?php esc_html_e( 'Ano', 'questoes' ); ?></label>
                <input type="number" id="questoes-search-year" name="ano" value="<?php echo esc_attr( $selected_year ); ?>" min="1900" max="2100" />
            </div>
            <div class="questoes-question-bank__filter questoes-question-bank__filter--submit">
                <button type="submit" class="questoes-button"><?php esc_html_e( 'Pesquisar', 'questoes' ); ?></button>
            </div>
        </form>
    <?php endif; ?>

    <div class="questoes-question-bank__messages" role="status" aria-live="polite"<?php echo ! empty( $initial_notice ) ? ' data-type="info"' : ''; ?>>
        <?php echo esc_html( $initial_notice ); ?>
    </div>

    <div class="questoes-question-bank__list"></div>

    <nav class="questoes-question-bank__pagination" aria-label="<?php esc_attr_e( 'Paginação', 'questoes' ); ?>" style="display: none;">
        <button type="button" class="questoes-button" data-page="prev" aria-label="<?php esc_attr_e( 'Página anterior', 'questoes' ); ?>">&larr;</button>
        <span class="questoes-question-bank__pagination-status"></span>
        <button type="button" class="questoes-button" data-page="next" aria-label="<?php esc_attr_e( 'Próxima página', 'questoes' ); ?>">&rarr;</button>
    </nav>
</div>
