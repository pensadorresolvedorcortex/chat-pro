<?php
/**
 * Shortcode for rendering quizzes.
 *
 * @package Academia_Simulados
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Academia_Simulados_Shortcode {
    /**
     * Register shortcode.
     */
    public static function init() {
        add_shortcode( 'academia_simulado', array( __CLASS__, 'render_shortcode' ) );
    }

    /**
     * Render the shortcode output.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id'   => 0,
                'slug' => '',
            ),
            $atts,
            'academia_simulado'
        );

        $simulado             = null;
        $available_simulados  = array();
        $requested_simulado   = '';

        if ( $atts['id'] ) {
            $simulado = get_post( (int) $atts['id'] );
        } elseif ( $atts['slug'] ) {
            $simulado = get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, 'simulado' );
        }

        $available_simulados = get_posts(
            array(
                'post_type'      => 'simulado',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => array(
                    'menu_order' => 'ASC',
                    'title'      => 'ASC',
                ),
            )
        );

        if ( ! $simulado && empty( $available_simulados ) ) {
            return '<p>' . esc_html__( 'Nenhum simulado disponível no momento.', 'academia-simulados' ) . '</p>';
        }

        if ( ! $simulado ) {
            $requested_simulado = get_query_var( 'simulado' );

            if ( isset( $_GET['simulado'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $requested_simulado = sanitize_title( wp_unslash( $_GET['simulado'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( $requested_simulado ) {
                $requested_simulado = sanitize_title( $requested_simulado );
            }

            if ( $requested_simulado ) {
                foreach ( $available_simulados as $candidate ) {
                    if ( $requested_simulado === $candidate->post_name ) {
                        $simulado = $candidate;
                        break;
                    }
                }
            }

            if ( ! $simulado ) {
                $simulado = $available_simulados[0];
            }
        }

        if ( ! $simulado || 'simulado' !== $simulado->post_type ) {
            return '<p>' . esc_html__( 'Simulado não encontrado.', 'academia-simulados' ) . '</p>';
        }

        $questions = get_post_meta( $simulado->ID, Academia_Simulados_Meta_Box::META_KEY, true );
        if ( empty( $questions ) ) {
            return '<p>' . esc_html__( 'Este simulado ainda não possui questões cadastradas.', 'academia-simulados' ) . '</p>';
        }

        wp_enqueue_style( 'academia-simulados-frontend' );
        wp_enqueue_script( 'academia-simulados-frontend' );

        ob_start();

        self::render_simulado_selector( $available_simulados, $simulado->ID );
        ?>
        <div class="academia-simulados-wrapper" data-total="<?php echo esc_attr( count( $questions ) ); ?>">
            <h2 class="academia-simulados-title"><?php echo esc_html( get_the_title( $simulado ) ); ?></h2>
            <?php if ( ! empty( $simulado->post_content ) ) : ?>
                <div class="academia-simulados-description"><?php echo wpautop( wp_kses_post( $simulado->post_content ) ); ?></div>
            <?php endif; ?>
            <div class="academia-simulados-progress" role="group" aria-label="<?php esc_attr_e( 'Progresso do simulado', 'academia-simulados' ); ?>">
                <div class="academia-simulados-progress-header">
                    <p class="academia-simulados-progress-status" aria-live="polite"><?php echo esc_html( sprintf( __( 'Questões respondidas: %1$d de %2$d', 'academia-simulados' ), 0, count( $questions ) ) ); ?></p>
                    <p class="academia-simulados-score" aria-live="polite"><?php echo esc_html( sprintf( __( 'Acertos: %1$d de %2$d', 'academia-simulados' ), 0, count( $questions ) ) ); ?></p>
                </div>
                <div class="academia-simulados-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( count( $questions ) ); ?>" aria-valuenow="0">
                    <span class="academia-simulados-progress-fill" style="width:0%"></span>
                </div>
                <button type="button" class="academia-simulados-reset" hidden><?php esc_html_e( 'Refazer simulado', 'academia-simulados' ); ?></button>
                <span class="academia-simulados-feedback-global screen-reader-text" aria-live="polite" aria-atomic="true"></span>
            </div>
            <p class="academia-simulados-complete-message" data-template="<?php echo esc_attr( __( 'Você concluiu o simulado! Pontuação final: %1$d de %2$d.', 'academia-simulados' ) ); ?>" hidden></p>
            <div class="academia-simulados-questions">
                <?php foreach ( $questions as $index => $question ) :
                    $question_id = $index + 1;
                    $answers     = isset( $question['answers'] ) ? $question['answers'] : array();
                    $correct     = isset( $question['correct'] ) ? (int) $question['correct'] : 0;
                    ?>
                    <section class="academia-simulados-question" aria-labelledby="simulado-question-<?php echo esc_attr( $question_id ); ?>" data-question-index="<?php echo esc_attr( $index ); ?>">
                        <h3 id="simulado-question-<?php echo esc_attr( $question_id ); ?>"><?php echo esc_html( $question_id . '. ' . wp_strip_all_tags( $question['text'] ) ); ?></h3>
                        <div class="academia-simulados-options" role="group" aria-label="<?php esc_attr_e( 'Opções de resposta', 'academia-simulados' ); ?>">
                            <?php foreach ( $answers as $answer_index => $answer_text ) : ?>
                                <button type="button" data-correct="<?php echo esc_attr( $answer_index === $correct ? '1' : '0' ); ?>" data-correct-text="<?php echo esc_attr__( 'Resposta correta! Parabéns.', 'academia-simulados' ); ?>" data-incorrect-text="<?php echo esc_attr__( 'Resposta incorreta. Tente novamente!', 'academia-simulados' ); ?>">
                                    <span class="academia-simulados-answer-label"><?php echo esc_html( chr( 65 + $answer_index ) . ') ' . $answer_text ); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="academia-simulados-feedback" aria-live="off"></p>
                        <?php if ( ! empty( $question['hint'] ) ) : ?>
                            <div class="academia-simulados-hint" hidden>
                                <strong><?php esc_html_e( 'Explicação:', 'academia-simulados' ); ?></strong>
                                <?php echo wpautop( wp_kses_post( $question['hint'] ) ); ?>
                            </div>
                        <?php endif; ?>
                        <?php self::render_comments( $simulado->ID, $index ); ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render comments for a specific question.
     *
     * @param int $post_id Post ID.
     * @param int $question_index Question index.
     */
    protected static function render_comments( $post_id, $question_index ) {
        if ( ! comments_open( $post_id ) ) {
            return;
        }

        $comments = get_comments(
            array(
                'post_id' => $post_id,
                'status'  => 'approve',
                'type'    => 'simulado_resposta',
                'meta_key'   => 'academia_simulado_question',
                'meta_value' => $question_index,
                'orderby' => 'comment_date_gmt',
                'order'   => 'ASC',
            )
        );
        ?>
        <div class="academia-simulados-comments" id="simulado-question-comments-<?php echo esc_attr( $question_index ); ?>">
            <h4><?php esc_html_e( 'Comentários sobre esta resposta', 'academia-simulados' ); ?></h4>
            <?php if ( $comments ) : ?>
                <ol class="comment-list">
                    <?php foreach ( $comments as $comment ) : ?>
                        <li class="comment">
                            <div class="comment-meta">
                                <span class="comment-author"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>
                                <span class="comment-date"><?php echo esc_html( get_comment_date( '', $comment ) ); ?></span>
                            </div>
                            <div class="comment-content"><?php echo wpautop( wp_kses_post( $comment->comment_content ) ); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else : ?>
                <p><?php esc_html_e( 'Seja o primeiro a comentar sobre esta questão.', 'academia-simulados' ); ?></p>
            <?php endif; ?>
            <?php
            ob_start();
            comment_form(
                array(
                    'title_reply'          => __( 'Deixe seu comentário', 'academia-simulados' ),
                    'title_reply_to'       => __( 'Responder', 'academia-simulados' ),
                    'label_submit'         => __( 'Enviar comentário', 'academia-simulados' ),
                    'comment_notes_before' => '',
                    'comment_notes_after'  => '',
                    'class_submit'         => 'button',
                    'id_form'              => 'commentform-question-' . $question_index,
                    'comment_field'        => '<p class="comment-form-comment"><label for="comment-question-' . esc_attr( $question_index ) . '">' . __( 'Comentário', 'academia-simulados' ) . '</label><textarea id="comment-question-' . esc_attr( $question_index ) . '" name="comment" cols="45" rows="4" maxlength="65525" required></textarea></p>' . '<input type="hidden" name="academia_simulado_question" value="' . esc_attr( $question_index ) . '" />' . wp_nonce_field( 'academia_simulado_comment', 'academia_simulado_comment_nonce', true, false ),
                ),
                $post_id
            );
            $form_markup = ob_get_clean();
            $form_markup = str_replace( 'id="respond"', 'id="respond-question-' . esc_attr( $question_index ) . '"', $form_markup );
            echo $form_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
        <?php
    }

    /**
     * Render selector for available simulados.
     *
     * @param WP_Post[] $simulados Available simulados.
     * @param int       $current_id Current simulado ID.
     */
    protected static function render_simulado_selector( $simulados, $current_id ) {
        if ( empty( $simulados ) || count( $simulados ) < 2 ) {
            return;
        }

        $base_url = remove_query_arg( array( 'simulado', 'paged' ) );

        ?>
        <nav class="academia-simulados-selector" aria-label="<?php esc_attr_e( 'Escolha um simulado disponível', 'academia-simulados' ); ?>">
            <span class="academia-simulados-selector-label"><?php esc_html_e( 'Simulados disponíveis:', 'academia-simulados' ); ?></span>
            <ul>
                <?php foreach ( $simulados as $candidate ) :
                    $url       = add_query_arg( 'simulado', $candidate->post_name, $base_url );
                    $is_active = (int) $candidate->ID === (int) $current_id;
                    ?>
                    <li>
                        <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                            <?php echo esc_html( get_the_title( $candidate ) ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }
}
