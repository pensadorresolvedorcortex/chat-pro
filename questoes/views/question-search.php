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
$total_questions     = isset( $results['total'] ) ? (int) $results['total'] : 0;
$category_count      = is_array( $categories ) ? count( $categories ) : 0;
$banca_count         = is_array( $bancas ) ? count( $bancas ) : 0;
$subject_count       = is_array( $subjects ) ? count( $subjects ) : 0;
$per_page            = isset( $config['perPage'] ) ? (int) $config['perPage'] : 0;
$hero_title          = ! empty( $title ) ? $title : __( 'Pesquisar banco de questões', 'questoes' );
$hero_description    = __( 'Combine filtros avançados, pesquise por palavra-chave e visualize as questões certas para o seu treino.', 'questoes' );
$messages_hidden     = empty( $initial_notice ) ? ' hidden' : '';
$host_id             = get_the_ID();
$host_slug           = $host_id ? get_post_field( 'post_name', $host_id ) : 'global';
$knowledge_seed      = $hero_title . '|' . $host_slug . '|search';
$knowledge_key       = 'search-' . substr( md5( $knowledge_seed ), 0, 12 );

ob_start();
?>
    <div class="questoes-question-bank__content">
        <div class="questoes-question-bank__messages" role="status" aria-live="polite"<?php echo $messages_hidden; ?><?php echo ! empty( $initial_notice ) ? ' data-type="info"' : ''; ?>><?php echo esc_html( $initial_notice ); ?></div>

        <div class="questoes-question-bank__list"></div>

        <nav class="questoes-question-bank__pagination" aria-label="<?php esc_attr_e( 'Paginação', 'questoes' ); ?>" style="display: none;">
            <button type="button" class="questoes-button" data-page="prev" aria-label="<?php esc_attr_e( 'Página anterior', 'questoes' ); ?>">&larr;</button>
            <span class="questoes-question-bank__pagination-status"></span>
            <button type="button" class="questoes-button" data-page="next" aria-label="<?php esc_attr_e( 'Próxima página', 'questoes' ); ?>">&rarr;</button>
        </nav>
    </div>
<?php
$content_markup = ob_get_clean();
?>
<div class="questoes-stage questoes-question-bank questoes-question-bank--search" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" data-knowledge-key="<?php echo esc_attr( $knowledge_key ); ?>" data-title="<?php echo esc_attr( $hero_title ); ?>">
    <div class="questoes-shell questoes-shell--wide">
        <header class="questoes-hero">
            <div class="questoes-hero__content">
                <span class="questoes-hero__eyebrow"><?php esc_html_e( 'Busca avançada', 'questoes' ); ?></span>
                <h2 class="questoes-hero__title"><?php echo esc_html( $hero_title ); ?></h2>
                <p class="questoes-hero__description"><?php echo esc_html( $hero_description ); ?></p>
                <?php if ( $per_page > 0 ) : ?>
                    <span class="questoes-pill">
                        <span class="questoes-pill__dot" aria-hidden="true"></span>
                        <?php
                        printf(
                            /* translators: %s: questions per page */
                            esc_html__( '%s resultados por página', 'questoes' ),
                            esc_html( number_format_i18n( $per_page ) )
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="questoes-hero__stats">
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Questões indexadas', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $total_questions ) ); ?></span>
                </div>
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Categorias ativas', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $category_count ) ); ?></span>
                </div>
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Bancas monitoradas', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $banca_count ) ); ?></span>
                </div>
                <div class="questoes-hero__stat">
                    <span class="questoes-hero__stat-label"><?php esc_html_e( 'Assuntos disponíveis', 'questoes' ); ?></span>
                    <span class="questoes-hero__stat-value"><?php echo esc_html( number_format_i18n( $subject_count ) ); ?></span>
                </div>
            </div>
            <div class="questoes-hero__halo" aria-hidden="true"></div>
        </header>

        <div class="questoes-question-bank__surface questoes-surface questoes-surface--tinted">
            <?php if ( $show_filters ) : ?>
                <div class="questoes-question-bank__layout questoes-question-bank__layout--split">
                    <aside class="questoes-panel questoes-question-bank__panel" aria-label="<?php esc_attr_e( 'Refinar busca', 'questoes' ); ?>">
                        <div>
                            <h3 class="questoes-panel__title"><?php esc_html_e( 'Configure sua busca', 'questoes' ); ?></h3>
                            <p class="questoes-panel__subtitle"><?php esc_html_e( 'Defina palavra-chave, banca, formato e dificuldade antes de buscar.', 'questoes' ); ?></p>
                        </div>

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
                    </aside>

                    <?php echo $content_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php else : ?>
                <?php echo $content_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </div>
    </div>
</div>
