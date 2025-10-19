<?php
/**
 * General helper functions.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function questoes_get_default_palette() {
    return array(
        'primary'   => '#242142',
        'secondary' => '#bf83ff',
        'light'     => '#c3e3f3',
        'neutral'   => '#f1f5f9',
        'neutral-2' => '#e5e7eb',
    );
}

function questoes_get_option( $key, $default = false ) {
    $options = get_option( 'questoes_settings', array() );

    if ( isset( $options[ $key ] ) ) {
        return $options[ $key ];
    }

    return $default;
}

function questoes_update_option( $key, $value ) {
    $options         = get_option( 'questoes_settings', array() );
    $options[ $key ] = $value;
    update_option( 'questoes_settings', $options );
}

function questoes_get_data() {
    $raw   = questoes_get_option( 'data', '' );
    $clean = Questoes_Schema::sanitize_json( $raw );

    if ( empty( $clean ) ) {
        return array();
    }

    return json_decode( $clean, true );
}

/**
 * Return safe asset version.
 *
 * @param string $relative_path Relative path from plugin directory.
 *
 * @return string|int
 */
function questoes_asset_version( $relative_path ) {
    $file = QUESTOES_PLUGIN_DIR . ltrim( $relative_path, '/' );

    if ( file_exists( $file ) ) {
        return filemtime( $file );
    }

    return '0.11.1';
}

function questoes_get_difficulty_label( $difficulty ) {
    switch ( $difficulty ) {
        case 'easy':
            return __( 'Fácil', 'questoes' );
        case 'medium':
            return __( 'Média', 'questoes' );
        case 'hard':
            return __( 'Difícil', 'questoes' );
        default:
            return __( 'Não informada', 'questoes' );
    }
}

function questoes_get_question_types() {
    return array(
        'multiple_choice' => __( 'Múltipla escolha', 'questoes' ),
        'true_false'      => __( 'Verdadeiro ou falso', 'questoes' ),
        'discursive'      => __( 'Discursiva', 'questoes' ),
        'fill_in_blank'   => __( 'Preenchimento de lacunas', 'questoes' ),
    );
}

function questoes_get_question_type_label( $type ) {
    $types = questoes_get_question_types();

    if ( isset( $types[ $type ] ) ) {
        return $types[ $type ];
    }

    return __( 'Não informado', 'questoes' );
}

function questoes_get_course_regions() {
    return array(
        'nacional'     => __( 'Nacional', 'questoes' ),
        'norte'        => __( 'Norte', 'questoes' ),
        'nordeste'     => __( 'Nordeste', 'questoes' ),
        'centro-oeste' => __( 'Centro-Oeste', 'questoes' ),
        'sudeste'      => __( 'Sudeste', 'questoes' ),
        'sul'          => __( 'Sul', 'questoes' ),
    );
}

function questoes_get_course_region_label( $region ) {
    $regions = questoes_get_course_regions();

    if ( isset( $regions[ $region ] ) ) {
        return $regions[ $region ];
    }

    return '';
}

function questoes_render_course_card( $course, $args = array() ) {
    if ( empty( $course ) || ! is_array( $course ) ) {
        return '';
    }

    $defaults = array(
        'context' => 'grid',
    );

    $args = wp_parse_args( $args, $defaults );

    $classes = array( 'questoes-course-card' );

    if ( ! empty( $args['context'] ) ) {
        $classes[] = 'questoes-course-card--' . sanitize_html_class( $args['context'] );
    }

    if ( ! empty( $course['featured'] ) ) {
        $classes[] = 'is-featured';
    }

    $region   = isset( $course['region'] ) ? sanitize_key( $course['region'] ) : 'nacional';
    $count    = isset( $course['count'] ) ? (int) $course['count'] : 0;
    $comments = isset( $course['comments'] ) ? (int) $course['comments'] : 0;
    $badge    = isset( $course['badge'] ) ? sanitize_text_field( $course['badge'] ) : '';
    $salary   = isset( $course['salary'] ) ? sanitize_text_field( $course['salary'] ) : '';
    $opps     = isset( $course['opportunities'] ) ? sanitize_text_field( $course['opportunities'] ) : '';
    $cta      = isset( $course['cta'] ) && ! empty( $course['cta'] ) ? $course['cta'] : __( 'Ver cursos disponíveis', 'questoes' );
    $link     = isset( $course['link'] ) ? esc_url( $course['link'] ) : '';
    $icon     = isset( $course['icon'] ) ? esc_url( $course['icon'] ) : '';
    $title    = isset( $course['name'] ) ? sanitize_text_field( $course['name'] ) : '';
    $highlight = isset( $course['highlight'] ) ? wp_kses_post( $course['highlight'] ) : '';
    $region_label = isset( $course['region_label'] ) ? sanitize_text_field( $course['region_label'] ) : '';

    $fallback_letter = '';

    if ( empty( $icon ) && ! empty( $title ) ) {
        $letter_source   = function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1 ) : substr( $title, 0, 1 );
        $fallback_letter = strtoupper( wp_strip_all_tags( $letter_source ) );
    }

    ob_start();
    ?>
    <article class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $classes ) ) ); ?>" data-region="<?php echo esc_attr( $region ); ?>" data-count="<?php echo esc_attr( $count ); ?>" data-priority="<?php echo esc_attr( isset( $course['priority'] ) ? (int) $course['priority'] : 0 ); ?>">
        <div class="questoes-course-card__main">
            <?php if ( $badge ) : ?>
                <span class="questoes-course-card__badge"><?php echo esc_html( $badge ); ?></span>
            <?php endif; ?>

            <div class="questoes-course-card__heading">
                <?php if ( $icon ) : ?>
                    <span class="questoes-course-card__icon">
                        <img src="<?php echo esc_url( $icon ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" />
                    </span>
                <?php elseif ( $fallback_letter ) : ?>
                    <span class="questoes-course-card__icon questoes-course-card__icon--fallback" aria-hidden="true"><?php echo esc_html( $fallback_letter ); ?></span>
                <?php endif; ?>

                <div class="questoes-course-card__titles">
                    <h3 class="questoes-course-card__title"><?php echo esc_html( $title ); ?></h3>
                    <?php if ( $region_label ) : ?>
                        <span class="questoes-course-card__region"><?php echo esc_html( $region_label ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( $highlight ) : ?>
                <p class="questoes-course-card__highlight"><?php echo $highlight; ?></p>
            <?php endif; ?>

            <div class="questoes-course-card__details">
                <?php if ( $salary ) : ?>
                    <p class="questoes-course-card__salary"><?php echo esc_html( $salary ); ?></p>
                <?php endif; ?>

                <?php if ( $opps ) : ?>
                    <p class="questoes-course-card__meta"><?php echo esc_html( $opps ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="questoes-course-card__aside">
            <ul class="questoes-course-card__stats" aria-label="<?php esc_attr_e( 'Resumo do curso', 'questoes' ); ?>">
                <li>
                    <strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
                    <span><?php esc_html_e( 'questões', 'questoes' ); ?></span>
                </li>
                <li>
                    <strong><?php echo esc_html( number_format_i18n( $comments ) ); ?></strong>
                    <span><?php esc_html_e( 'comentários', 'questoes' ); ?></span>
                </li>
            </ul>

            <?php if ( $link ) : ?>
                <a class="questoes-course-card__cta" href="<?php echo esc_url( $link ); ?>">
                    <span class="questoes-course-card__cta-label"><?php echo esc_html( $cta ); ?></span>
                    <span aria-hidden="true" class="questoes-course-card__cta-icon">&rarr;</span>
                </a>
            <?php endif; ?>
        </div>
    </article>
    <?php

    return trim( ob_get_clean() );
}

function questoes_comments_enabled() {
    return (int) questoes_get_option( 'comments_enabled', 1 ) === 1;
}

function questoes_get_answer_letter( $index ) {
    $index    = (int) $index;
    $alphabet = range( 'A', 'Z' );

    if ( isset( $alphabet[ $index ] ) ) {
        return $alphabet[ $index ];
    }

    $dividend = $index + 1;
    $letter   = '';

    while ( $dividend > 0 ) {
        $modulo   = ( $dividend - 1 ) % 26;
        $letter   = chr( 65 + $modulo ) . $letter;
        $dividend = (int) ( ( $dividend - $modulo ) / 26 );

        if ( $dividend > 0 ) {
            $dividend--;
        }
    }

    return $letter;
}

function questoes_render_question_comments( $question_id ) {
    if ( ! questoes_comments_enabled() || ! function_exists( 'comment_form' ) ) {
        return '';
    }

    $question_id = absint( $question_id );

    if ( ! $question_id ) {
        return '';
    }

    if ( 'open' !== get_post_field( 'comment_status', $question_id ) ) {
        return '';
    }

    $comments = get_comments(
        array(
            'post_id' => $question_id,
            'status'  => 'approve',
            'orderby' => 'comment_date_gmt',
            'order'   => 'ASC',
        )
    );

    $count        = get_comments_number( $question_id );
    $summary_text = $count > 0 ? sprintf( __( 'Comentários (%d)', 'questoes' ), $count ) : __( 'Comentários', 'questoes' );

    ob_start();
    ?>
    <details class="questoes-question-card__comments" aria-label="<?php echo esc_attr( $summary_text ); ?>">
        <summary><?php echo esc_html( $summary_text ); ?></summary>
        <div class="questoes-question-card__comments-body">
            <?php if ( ! empty( $comments ) ) : ?>
                <ul class="questoes-question-card__comments-list">
                    <?php foreach ( $comments as $comment ) : ?>
                        <li>
                            <div class="questoes-question-card__comment-meta">
                                <span class="questoes-question-card__comment-author"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>
                                <time datetime="<?php echo esc_attr( get_comment_date( 'c', $comment ) ); ?>" class="questoes-question-card__comment-date"><?php echo esc_html( get_comment_date( get_option( 'date_format' ), $comment ) ); ?></time>
                            </div>
                            <div class="questoes-question-card__comment-content"><?php echo wp_kses_post( wpautop( apply_filters( 'comment_text', $comment->comment_content, $comment ) ) ); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="questoes-question-card__comments-empty"><?php esc_html_e( 'Seja o primeiro a comentar esta resposta.', 'questoes' ); ?></p>
            <?php endif; ?>

            <?php
            comment_form(
                array(
                    'title_reply'          => '',
                    'comment_notes_before' => '',
                    'comment_notes_after'  => '',
                    'label_submit'         => __( 'Publicar comentário', 'questoes' ),
                    'class_submit'         => 'questoes-button questoes-question-card__comment-submit',
                    'id_form'              => 'questoes-comment-form-' . $question_id,
                    'id_submit'            => 'questoes-comment-submit-' . $question_id,
                    'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
                ),
                $question_id
            );
            ?>
        </div>
    </details>
    <?php

    return trim( ob_get_clean() );
}

function questoes_render_question_card( $question ) {
    if ( empty( $question ) || ! is_array( $question ) ) {
        return '';
    }

    $answers     = isset( $question['answers'] ) ? (array) $question['answers'] : array();
    $has_answers = ! empty( $answers );
    $show_meta   = ! empty( $question['categories'] ) || ! empty( $question['bancas'] ) || ! empty( $question['subjects'] ) || ! empty( $question['reference'] ) || ! empty( $question['source'] ) || ! empty( $question['source_url'] ) || ! empty( $question['estimated_time'] ) || ! empty( $question['question_type'] ) || ! empty( $question['year'] ) || ! empty( $question['video_url'] );
    $answers_id  = 'questoes-answers-' . absint( $question['id'] );

    ob_start();
    ?>
    <article class="questoes-question-card" data-question-id="<?php echo esc_attr( $question['id'] ); ?>">
        <header class="questoes-question-card__header">
            <h3 class="questoes-question-card__title"><?php echo esc_html( $question['title'] ); ?></h3>
            <?php if ( ! empty( $question['difficulty'] ) ) : ?>
                <span class="questoes-question-card__badge questoes-question-card__badge--<?php echo esc_attr( $question['difficulty'] ); ?>"><?php echo esc_html( questoes_get_difficulty_label( $question['difficulty'] ) ); ?></span>
            <?php endif; ?>
        </header>

        <div class="questoes-question-card__content"><?php echo wp_kses_post( $question['content'] ); ?></div>

        <?php if ( $has_answers ) : ?>
            <div class="questoes-question-card__answers" id="<?php echo esc_attr( $answers_id ); ?>">
                <p class="questoes-question-card__instruction"><?php esc_html_e( 'Selecione uma alternativa para conferir o gabarito.', 'questoes' ); ?></p>
                <ul role="list">
                    <?php foreach ( $answers as $index => $answer ) :
                        $is_correct = ! empty( $answer['is_correct'] );
                        ?>
                        <li>
                            <button type="button" class="questoes-question-card__answer" data-correct="<?php echo $is_correct ? '1' : '0'; ?>">
                                <span class="questoes-question-card__answer-letter"><?php echo esc_html( questoes_get_answer_letter( $index ) ); ?></span>
                                <span class="questoes-question-card__answer-text"><?php echo wp_kses_post( $answer['text'] ); ?></span>
                                <?php if ( $is_correct ) : ?>
                                    <span class="questoes-question-card__answer-badge"><?php esc_html_e( 'Correta', 'questoes' ); ?></span>
                                <?php endif; ?>
                            </button>
                            <?php if ( ! empty( $answer['feedback'] ) ) : ?>
                                <div class="questoes-question-card__feedback" hidden><?php echo wp_kses_post( wpautop( $answer['feedback'] ) ); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="questoes-question-card__result" role="status" aria-live="polite" hidden></div>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $question['explanation'] ) ) : ?>
            <details class="questoes-question-card__explanation">
                <summary><?php esc_html_e( 'Comentário do gabarito', 'questoes' ); ?></summary>
                <div><?php echo wp_kses_post( wpautop( $question['explanation'] ) ); ?></div>
            </details>
        <?php endif; ?>

        <?php if ( $show_meta ) : ?>
            <footer class="questoes-question-card__footer">
                <?php if ( ! empty( $question['categories'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Categorias:', 'questoes' ); ?></strong> <?php echo esc_html( implode( ', ', $question['categories'] ) ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['bancas'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Bancas:', 'questoes' ); ?></strong> <?php echo esc_html( implode( ', ', $question['bancas'] ) ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['subjects'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Assuntos:', 'questoes' ); ?></strong> <?php echo esc_html( implode( ', ', $question['subjects'] ) ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['reference'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Referência:', 'questoes' ); ?></strong> <?php echo esc_html( $question['reference'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['source'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Fonte:', 'questoes' ); ?></strong> <?php echo esc_html( $question['source'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['source_url'] ) ) : ?>
                    <a class="questoes-question-card__meta questoes-question-card__meta--link" href="<?php echo esc_url( $question['source_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver prova/edital original', 'questoes' ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $question['question_type'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Formato:', 'questoes' ); ?></strong> <?php echo esc_html( questoes_get_question_type_label( $question['question_type'] ) ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['year'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Ano:', 'questoes' ); ?></strong> <?php echo esc_html( $question['year'] ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['estimated_time'] ) ) : ?>
                    <span class="questoes-question-card__meta"><strong><?php esc_html_e( 'Tempo estimado:', 'questoes' ); ?></strong> <?php echo esc_html( absint( $question['estimated_time'] ) ); ?> <?php esc_html_e( 'min', 'questoes' ); ?></span>
                <?php endif; ?>
                <?php if ( ! empty( $question['video_url'] ) ) : ?>
                    <a class="questoes-question-card__meta questoes-question-card__meta--link" href="<?php echo esc_url( $question['video_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Assistir explicação em vídeo', 'questoes' ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $question['permalink'] ) ) : ?>
                    <a class="questoes-question-card__link" href="<?php echo esc_url( $question['permalink'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver questão completa', 'questoes' ); ?></a>
                <?php endif; ?>
            </footer>
        <?php endif; ?>

        <?php echo questoes_render_question_comments( $question['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </article>
    <?php

    return trim( ob_get_clean() );
}
