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

    return '0.5.0';
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
            <button type="button" class="questoes-question-card__toggle" data-action="toggle-answer" aria-expanded="false" aria-controls="<?php echo esc_attr( $answers_id ); ?>"><?php esc_html_e( 'Mostrar resposta', 'questoes' ); ?></button>
            <div class="questoes-question-card__answers" id="<?php echo esc_attr( $answers_id ); ?>" hidden>
                <ul>
                    <?php foreach ( $answers as $answer ) :
                        $is_correct = ! empty( $answer['is_correct'] );
                        ?>
                        <li class="<?php echo $is_correct ? 'is-correct' : ''; ?>">
                            <span class="questoes-question-card__answer-text"><?php echo wp_kses_post( $answer['text'] ); ?></span>
                            <?php if ( $is_correct ) : ?>
                                <span class="questoes-question-card__answer-badge"><?php esc_html_e( 'Correta', 'questoes' ); ?></span>
                            <?php endif; ?>
                            <?php if ( ! empty( $answer['feedback'] ) ) : ?>
                                <div class="questoes-question-card__feedback"><?php echo wp_kses_post( wpautop( $answer['feedback'] ) ); ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
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
    </article>
    <?php

    return trim( ob_get_clean() );
}
