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
$total_questions     = isset( $results['total'] ) ? (int) $results['total'] : 0;
$category_count      = is_array( $categories ) ? count( $categories ) : 0;
$banca_count         = is_array( $bancas ) ? count( $bancas ) : 0;
$subject_count       = is_array( $subjects ) ? count( $subjects ) : 0;
$per_page            = isset( $config['perPage'] ) ? (int) $config['perPage'] : 0;
$hero_title          = ! empty( $title ) ? $title : __( 'Banco de questões comentadas', 'questoes' );
$hero_description    = sprintf(
    /* translators: 1: total questions, 2: total categories, 3: total bancas, 4: total subjects */
    esc_html__( 'Acesse %1$s questões organizadas em %2$s categorias, %3$s bancas e %4$s assuntos para aprofundar seus estudos.', 'questoes' ),
    esc_html( number_format_i18n( $total_questions ) ),
    esc_html( number_format_i18n( $category_count ) ),
    esc_html( number_format_i18n( $banca_count ) ),
    esc_html( number_format_i18n( $subject_count ) )
);

ob_start();
?>
    <div class="questoes-question-bank__content">
        <div class="questoes-question-bank__messages" role="status" aria-live="polite" hidden></div>

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
    </div>
<?php
$content_markup = ob_get_clean();
?>
<div class="questoes-stage questoes-question-bank" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
    <div class="questoes-shell questoes-shell--wide">
        <header class="questoes-hero">
            <div class="questoes-hero__content">
                <span class="questoes-hero__eyebrow"><?php esc_html_e( 'Banco Questões', 'questoes' ); ?></span>
                <h2 class="questoes-hero__title"><?php echo esc_html( $hero_title ); ?></h2>
                <p class="questoes-hero__description"><?php echo esc_html( $hero_description ); ?></p>
                <?php if ( $per_page > 0 ) : ?>
                    <span class="questoes-pill">
                        <span class="questoes-pill__dot" aria-hidden="true"></span>
                        <?php
                        printf(
                            /* translators: %s: questions per page */
                            esc_html__( '%s questões por página', 'questoes' ),
                            esc_html( number_format_i18n( $per_page ) )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="questoes-hero__stats">
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Questões disponíveis', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $total_questions ) ); ?></span>
                </div>
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Categorias mapeadas', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $category_count ) ); ?></span>
                </div>
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Bancas ativas', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $banca_count ) ); ?></span>
                </div>
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Assuntos detalhados', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $subject_count ) ); ?></span>
                </div>
            </div>
            <div class="questoes-hero__halo" aria-hidden="true"></div>
        </header>

        <div class="questoes-question-bank__surface questoes-surface questoes-surface--tinted">
            <?php if ( $show_filters ) : ?>
                <div class="questoes-question-bank__layout questoes-question-bank__layout--split">
                    <aside class="questoes-panel questoes-question-bank__panel" aria-label="<?php esc_attr_e( 'Filtrar questões', 'questoes' ); ?>">
                        <div>
                            <h3 class="questoes-panel__title"><?php esc_html_e( 'Filtros inteligentes', 'questoes' ); ?></h3>
                            <p class="questoes-panel__subtitle"><?php esc_html_e( 'Combine temas, bancas e níveis para personalizar seu treino.', 'questoes' ); ?></p>
                        </div>

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
                    </aside>

                    <?php echo $content_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php else : ?>
                <?php echo $content_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </div>
    </div>

    <script type="application/json" class="questoes-question-bank__initial">
        <?php echo wp_json_encode( $results ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </script>
</div>
