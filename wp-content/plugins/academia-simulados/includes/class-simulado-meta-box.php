<?php
/**
 * Meta boxes for simulados.
 *
 * @package Academia_Simulados
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Academia_Simulados_Meta_Box {
    const META_KEY = '_academia_simulado_questions';

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
        add_action( 'save_post_simulado', array( __CLASS__, 'save' ) );
    }

    /**
     * Add the custom meta box.
     */
    public static function add_meta_box() {
        add_meta_box(
            'academia-simulado-questions',
            __( 'Questões do Simulado', 'academia-simulados' ),
            array( __CLASS__, 'render' ),
            'simulado',
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box HTML.
     *
     * @param WP_Post $post The current post object.
     */
    public static function render( $post ) {
        wp_nonce_field( 'academia_simulado_save', 'academia_simulado_nonce' );

        $questions = get_post_meta( $post->ID, self::META_KEY, true );
        if ( ! is_array( $questions ) ) {
            $questions = array();
        }
        ?>
        <div id="academia-simulados-meta" class="academia-simulados-meta">
            <p class="description"><?php esc_html_e( 'Adicione questões ao seu simulado. Cada questão pode conter de duas a cinco alternativas.', 'academia-simulados' ); ?></p>
            <div class="academia-simulados-questions" data-count="<?php echo esc_attr( count( $questions ) ); ?>">
                <?php if ( empty( $questions ) ) : ?>
                    <?php self::render_empty_question(); ?>
                <?php else : ?>
                    <?php foreach ( $questions as $index => $question ) : ?>
                        <?php self::render_question( $index, $question ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <p><button type="button" class="button button-secondary" id="academia-simulados-add-question"><?php esc_html_e( 'Adicionar questão', 'academia-simulados' ); ?></button></p>
        </div>
        <?php
    }

    /**
     * Render a single question block.
     *
     * @param int   $index    Question index.
     * @param array $question Question data.
     */
    protected static function render_question( $index, $question = array() ) {
        $question_text = isset( $question['text'] ) ? $question['text'] : '';
        $question_hint = isset( $question['hint'] ) ? $question['hint'] : '';
        $answers       = isset( $question['answers'] ) && is_array( $question['answers'] ) ? $question['answers'] : array();
        $correct       = isset( $question['correct'] ) ? (int) $question['correct'] : 0;
        ?>
        <div class="academia-simulados-question" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="academia-simulados-question-header">
                <h3><?php printf( __( 'Questão %s', 'academia-simulados' ), '<span class="question-number">' . esc_html( $index + 1 ) . '</span>' ); ?></h3>
                <button type="button" class="button-link academia-remove-question" aria-label="<?php esc_attr_e( 'Remover questão', 'academia-simulados' ); ?>">&times;</button>
            </div>
            <p>
                <label>
                    <span class="screen-reader-text"><?php esc_html_e( 'Texto da questão', 'academia-simulados' ); ?></span>
                    <textarea class="widefat" name="academia_simulado_questions[<?php echo esc_attr( $index ); ?>][text]" rows="4" required><?php echo esc_textarea( $question_text ); ?></textarea>
                </label>
            </p>
            <p>
                <label>
                    <?php esc_html_e( 'Comentário ou explicação (opcional)', 'academia-simulados' ); ?>
                    <textarea class="widefat" name="academia_simulado_questions[<?php echo esc_attr( $index ); ?>][hint]" rows="3"><?php echo esc_textarea( $question_hint ); ?></textarea>
                </label>
            </p>
            <div class="academia-simulados-answers">
                <?php
                if ( empty( $answers ) ) {
                    $answers = array( '', '', '', '' );
                }
                foreach ( $answers as $answer_index => $answer_text ) :
                    ?>
                    <p class="academia-simulados-answer">
                        <label>
                            <span class="screen-reader-text"><?php esc_html_e( 'Alternativa', 'academia-simulados' ); ?></span>
                            <input type="text" class="widefat" name="academia_simulado_questions[<?php echo esc_attr( $index ); ?>][answers][<?php echo esc_attr( $answer_index ); ?>]" value="<?php echo esc_attr( $answer_text ); ?>" required />
                        </label>
                        <label class="academia-simulados-correct">
                            <input type="radio" name="academia_simulado_questions[<?php echo esc_attr( $index ); ?>][correct]" value="<?php echo esc_attr( $answer_index ); ?>" <?php checked( $correct, $answer_index ); ?> />
                            <?php esc_html_e( 'Resposta correta', 'academia-simulados' ); ?>
                        </label>
                        <button type="button" class="button-link remove-answer" aria-label="<?php esc_attr_e( 'Remover alternativa', 'academia-simulados' ); ?>">&times;</button>
                    </p>
                    <?php
                endforeach;
                ?>
            </div>
            <p><button type="button" class="button button-small add-answer"><?php esc_html_e( 'Adicionar alternativa', 'academia-simulados' ); ?></button></p>
            <hr />
        </div>
        <?php
    }

    /**
     * Render a blank question template.
     */
    protected static function render_empty_question() {
        self::render_question( 0 );
    }

    /**
     * Save handler for questions.
     *
     * @param int $post_id Post ID.
     */
    public static function save( $post_id ) {
        if ( ! isset( $_POST['academia_simulado_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['academia_simulado_nonce'] ) ), 'academia_simulado_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $questions = isset( $_POST['academia_simulado_questions'] ) ? wp_unslash( $_POST['academia_simulado_questions'] ) : array();

        $sanitised_questions = array();
        foreach ( $questions as $question ) {
            if ( empty( $question['text'] ) ) {
                continue;
            }

            $answers = isset( $question['answers'] ) && is_array( $question['answers'] ) ? $question['answers'] : array();
            $answers = array_values( array_filter( $answers, 'strlen' ) );

            if ( count( $answers ) < 2 ) {
                continue;
            }

            $correct = isset( $question['correct'] ) ? (int) $question['correct'] : 0;
            if ( ! isset( $answers[ $correct ] ) ) {
                $correct = 0;
            }

            $sanitised_questions[] = array(
                'text'    => wp_kses_post( $question['text'] ),
                'hint'    => isset( $question['hint'] ) ? wp_kses_post( $question['hint'] ) : '',
                'answers' => array_map( 'sanitize_text_field', $answers ),
                'correct' => $correct,
            );
        }

        update_post_meta( $post_id, self::META_KEY, $sanitised_questions );
    }
}
