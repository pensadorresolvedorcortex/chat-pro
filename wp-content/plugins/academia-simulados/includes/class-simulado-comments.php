<?php
/**
 * Custom comment handlers for Simulados.
 *
 * @package Academia_Simulados
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Academia_Simulados_Comments {
    /**
     * Boot hooks.
     */
    public static function init() {
        add_filter( 'preprocess_comment', array( __CLASS__, 'prepare_comment' ) );
        add_action( 'comment_post', array( __CLASS__, 'save_comment_meta' ), 10, 3 );
    }

    /**
     * Prepare the comment before it is inserted.
     *
     * @param array $comment_data Data to be inserted.
     * @return array
     */
    public static function prepare_comment( $comment_data ) {
        if ( empty( $comment_data['comment_post_ID'] ) || 'simulado' !== get_post_type( $comment_data['comment_post_ID'] ) ) {
            return $comment_data;
        }

        if ( ! isset( $_POST['academia_simulado_comment_nonce'], $_POST['academia_simulado_question'] ) ) {
            wp_die( esc_html__( 'Envio de comentário inválido.', 'academia-simulados' ) );
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['academia_simulado_comment_nonce'] ) ), 'academia_simulado_comment' ) ) {
            wp_die( esc_html__( 'Falha na verificação de segurança. Recarregue a página e tente novamente.', 'academia-simulados' ) );
        }

        $question_index = absint( $_POST['academia_simulado_question'] );
        $questions      = get_post_meta( $comment_data['comment_post_ID'], Academia_Simulados_Meta_Box::META_KEY, true );

        if ( ! is_array( $questions ) || ! array_key_exists( $question_index, $questions ) ) {
            wp_die( esc_html__( 'A questão selecionada não existe mais.', 'academia-simulados' ) );
        }

        $comment_data['comment_type']    = 'simulado_resposta';
        $comment_data['comment_content'] = wp_kses_post( $comment_data['comment_content'] );
        $comment_data['comment_parent']  = 0;

        return $comment_data;
    }

    /**
     * Save meta for comment.
     *
     * @param int   $comment_ID Comment ID.
     * @param int   $comment_approved Approval status.
     * @param array $commentdata Comment data.
     */
    public static function save_comment_meta( $comment_ID, $comment_approved, $commentdata ) {
        if ( 'simulado_resposta' !== $commentdata['comment_type'] ) {
            return;
        }

        if ( isset( $_POST['academia_simulado_question'] ) ) {
            add_comment_meta( $comment_ID, 'academia_simulado_question', absint( $_POST['academia_simulado_question'] ) );
        }
    }
}
