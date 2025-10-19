<?php
/**
 * Question bank view.
 *
 * @var array $results
 * @var array $config
 * @var array $categories
 * @var array $bancas
 * @var array $subjects
 * @var string $title
 * @var array $palette
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
$question_types      = questoes_get_question_types();
?>
<div class="questoes-question-bank" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
    <?php if ( ! empty( $title ) ) : ?>
        <h2 class="questoes-question-bank__title" style="color: <?php echo esc_attr( $palette['primary'] ); ?>;">
            <?php echo esc_html( $title ); ?>
        </h2>
    <?php endif; ?>

    <?php if ( $show_filters ) : ?>
        <form class="questoes-question-bank__filters" aria-label="<?php esc_attr_e( 'Filtros do banco de questões', 'questoes' ); ?>">
            <div class="questoes-question-bank__filter">
                <label for="questoes-bank-category"><?php esc_html_e( 'Categoria', 'questoes' ); ?></label>
                <select id="questoes-bank-category" name="categoria">
                    <option value=""><?php esc_html_e( 'Todas', 'questoes' ); ?></option>
                    <?php foreach ( $categories as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( in_array( $term->slug, $selected_categories, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-bank-banca"><?php esc_html_e( 'Banca', 'questoes' ); ?></label>
                <select id="questoes-bank-banca" name="banca">
                    <option value=""><?php esc_html_e( 'Todas', 'questoes' ); ?></option>
                    <?php foreach ( $bancas as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( in_array( $term->slug, $selected_bancas, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-bank-subject"><?php esc_html_e( 'Assunto', 'questoes' ); ?></label>
                <select id="questoes-bank-subject" name="assunto">
                    <option value=""><?php esc_html_e( 'Todos', 'questoes' ); ?></option>
                    <?php foreach ( $subjects as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( in_array( $term->slug, $selected_subjects, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-bank-difficulty"><?php esc_html_e( 'Dificuldade', 'questoes' ); ?></label>
                <select id="questoes-bank-difficulty" name="dificuldade">
                    <option value=""><?php esc_html_e( 'Todas', 'questoes' ); ?></option>
                    <option value="easy" <?php selected( 'easy' === $config['difficulty'] ); ?>><?php esc_html_e( 'Fácil', 'questoes' ); ?></option>
                    <option value="medium" <?php selected( 'medium' === $config['difficulty'] ); ?>><?php esc_html_e( 'Média', 'questoes' ); ?></option>
                    <option value="hard" <?php selected( 'hard' === $config['difficulty'] ); ?>><?php esc_html_e( 'Difícil', 'questoes' ); ?></option>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-bank-type"><?php esc_html_e( 'Formato', 'questoes' ); ?></label>
                <select id="questoes-bank-type" name="tipo">
                    <option value=""><?php esc_html_e( 'Todos', 'questoes' ); ?></option>
                    <?php foreach ( $question_types as $type_key => $type_label ) : ?>
                        <option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $selected_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="questoes-question-bank__filter">
                <label for="questoes-bank-year"><?php esc_html_e( 'Ano', 'questoes' ); ?></label>
                <input type="number" id="questoes-bank-year" name="ano" value="<?php echo esc_attr( $selected_year ); ?>" min="1900" max="2100" />
            </div>
            <div class="questoes-question-bank__filter questoes-question-bank__filter--submit">
                <button type="submit" class="questoes-button"><?php esc_html_e( 'Aplicar filtros', 'questoes' ); ?></button>
            </div>
        </form>
    <?php endif; ?>

    <div class="questoes-question-bank__messages" role="status" aria-live="polite"></div>

    <div class="questoes-question-bank__list">
        <?php if ( ! empty( $results['items'] ) ) : ?>
            <?php foreach ( $results['items'] as $question ) : ?>
                <?php echo $question['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="questoes-question-bank__empty"><?php esc_html_e( 'Nenhuma questão encontrada.', 'questoes' ); ?></p>
        <?php endif; ?>
    </div>

    <nav class="questoes-question-bank__pagination" aria-label="<?php esc_attr_e( 'Paginação', 'questoes' ); ?>">
        <?php if ( ! empty( $results['pages'] ) && $results['pages'] > 1 ) : ?>
            <button type="button" class="questoes-button" data-page="prev" aria-label="<?php esc_attr_e( 'Página anterior', 'questoes' ); ?>">&larr;</button>
            <span class="questoes-question-bank__pagination-status"><?php printf( esc_html__( 'Página %1$d de %2$d', 'questoes' ), 1, (int) $results['pages'] ); ?></span>
            <button type="button" class="questoes-button" data-page="next" aria-label="<?php esc_attr_e( 'Próxima página', 'questoes' ); ?>">&rarr;</button>
        <?php endif; ?>
    </nav>

    <script type="application/json" class="questoes-question-bank__initial">
        <?php echo wp_json_encode( $results ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </script>
</div>
