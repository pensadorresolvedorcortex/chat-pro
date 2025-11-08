<?php
namespace Juntaplay\Ajax;

use Juntaplay\Notifications\Mailer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manipula o fluxo de verificação em duas etapas para envio à análise.
 */
class TwoFactor {
    const META_CODE_HASH   = 'jplay_2fa_code_hash';
    const META_CODE_EXPIRY = 'jplay_2fa_code_expiry';
    const META_CODE_POST   = 'jplay_2fa_code_post';

    /**
     * Registra as ações AJAX necessárias.
     */
    public static function register() {
        add_action( 'wp_ajax_jplay_send_2fa', array( __CLASS__, 'send_code' ) );
        add_action( 'wp_ajax_jplay_validate_2fa', array( __CLASS__, 'validate_code' ) );
    }

    /**
     * Envia o código 2FA para o e-mail do usuário logado.
     */
    public static function send_code() {
        check_ajax_referer( 'jplay_2fa_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Grupo inválido.', 'juntaplay' ) ), 400 );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Você não tem permissão para enviar este grupo para análise.', 'juntaplay' ) ), 403 );
        }

        $user = wp_get_current_user();

        if ( ! $user || ! $user->ID ) {
            wp_send_json_error( array( 'message' => __( 'Você precisa estar autenticado para continuar.', 'juntaplay' ) ), 401 );
        }

        self::clear_user_meta( $user->ID );

        $code = (string) random_int( 100000, 999999 );

        update_user_meta( $user->ID, self::META_CODE_HASH, password_hash( $code, PASSWORD_DEFAULT ) );
        update_user_meta( $user->ID, self::META_CODE_EXPIRY, time() + ( 10 * MINUTE_IN_SECONDS ) );
        update_user_meta( $user->ID, self::META_CODE_POST, $post_id );

        $sent = Mailer::send_two_factor_code( $user, $code, $post_id );

        if ( ! $sent ) {
            self::clear_user_meta( $user->ID );
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível enviar o código. Tente novamente em instantes.', 'juntaplay' ),
                ),
                500
            );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Enviamos um código de 6 dígitos para o seu e-mail. Digite-o para confirmar o envio para análise.', 'juntaplay' ),
            )
        );
    }

    /**
     * Valida o código 2FA e atualiza o status do post.
     */
    public static function validate_code() {
        check_ajax_referer( 'jplay_2fa_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $code    = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( ! $post_id || '' === $code ) {
            wp_send_json_error( array( 'message' => __( 'Código inválido ou expirado. Solicite um novo código.', 'juntaplay' ) ), 400 );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Você não tem permissão para enviar este grupo para análise.', 'juntaplay' ) ), 403 );
        }

        $user = wp_get_current_user();

        if ( ! $user || ! $user->ID ) {
            wp_send_json_error( array( 'message' => __( 'Você precisa estar autenticado para continuar.', 'juntaplay' ) ), 401 );
        }

        $expected_post = (int) get_user_meta( $user->ID, self::META_CODE_POST, true );

        if ( $expected_post !== $post_id ) {
            wp_send_json_error( array( 'message' => __( 'Código inválido ou expirado. Solicite um novo código.', 'juntaplay' ) ), 400 );
        }

        $hash    = get_user_meta( $user->ID, self::META_CODE_HASH, true );
        $expires = (int) get_user_meta( $user->ID, self::META_CODE_EXPIRY, true );

        if ( ! $hash || ! $expires || time() > $expires ) {
            self::clear_user_meta( $user->ID );
            wp_send_json_error( array( 'message' => __( 'Código inválido ou expirado. Solicite um novo código.', 'juntaplay' ) ), 400 );
        }

        if ( ! password_verify( $code, $hash ) ) {
            wp_send_json_error( array( 'message' => __( 'Código inválido ou expirado. Solicite um novo código.', 'juntaplay' ) ), 400 );
        }

        $update = wp_update_post(
            array(
                'ID'          => $post_id,
                'post_status' => 'pending',
            ),
            true
        );

        self::clear_user_meta( $user->ID );

        if ( is_wp_error( $update ) ) {
            wp_send_json_error( array( 'message' => __( 'Não foi possível atualizar o status do grupo.', 'juntaplay' ) ), 500 );
        }

        Mailer::send_submission_confirmation( $post_id, $user );
        Mailer::notify_team_submission( $post_id, $user );

        wp_send_json_success( array( 'message' => __( '✅ Recebemos sua submissão. A equipe Juntaplay vai avaliar se o grupo cumpre todos os requisitos e você será notificado por e-mail.', 'juntaplay' ) ) );
    }

    /**
     * Remove os metadados temporários do usuário.
     *
     * @param int $user_id ID do usuário.
     */
    protected static function clear_user_meta( $user_id ) {
        delete_user_meta( $user_id, self::META_CODE_HASH );
        delete_user_meta( $user_id, self::META_CODE_EXPIRY );
        delete_user_meta( $user_id, self::META_CODE_POST );
    }
}
