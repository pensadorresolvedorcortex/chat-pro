<?php
namespace Juntaplay\Notifications;

use WP_Post;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Responsável por enviar os e-mails transacionais do fluxo de grupos.
 */
class Mailer {
    /**
     * Envia o código de verificação em duas etapas para o usuário.
     *
     * @param WP_User $user   Usuário autenticado.
     * @param string  $code   Código de seis dígitos.
     * @param int     $post_id ID do grupo relacionado.
     *
     * @return bool True em caso de envio bem-sucedido, false em falhas.
     */
    public static function send_two_factor_code( WP_User $user, $code, $post_id ) {
        if ( empty( $user->user_email ) ) {
            return false;
        }

        $subject = __( 'Código de verificação Juntaplay', 'juntaplay' );
        $message_lines = array(
            __( 'Olá!', 'juntaplay' ),
            __( 'Enviamos um código de 6 dígitos para confirmar o envio do seu grupo para análise.', 'juntaplay' ),
            sprintf(
                /* translators: %s: verification code */
                __( 'Seu código é: %s', 'juntaplay' ),
                $code
            ),
            __( 'O código expira em 10 minutos. Se não foi você quem solicitou, ignore este e-mail.', 'juntaplay' ),
        );

        $message = implode( "\n\n", $message_lines );

        $sent = wp_mail( $user->user_email, $subject, $message, self::get_plain_headers() );

        if ( apply_filters( 'jplay_debug_2fa_echo', true, $code, $user, $post_id ) ) {
            error_log( sprintf( 'Juntaplay 2FA code for user %1$s (post %2$d): %3$s', $user->user_login, $post_id, $code ) );
        }

        return (bool) $sent;
    }

    /**
     * Envia o e-mail de confirmação para o autor do grupo após submissão.
     *
     * @param int     $post_id ID do grupo.
     * @param WP_User $user    Usuário autenticado.
     */
    public static function send_submission_confirmation( $post_id, WP_User $user ) {
        if ( empty( $user->user_email ) ) {
            return;
        }

        $post = get_post( $post_id );

        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $subject = __( 'Recebemos sua submissão Juntaplay', 'juntaplay' );

        $message_lines = array(
            __( '✅ Recebemos sua submissão.', 'juntaplay' ),
            __( 'A equipe Juntaplay vai avaliar se o grupo cumpre todos os requisitos.', 'juntaplay' ),
        );

        $permalink = get_permalink( $post );

        if ( $permalink ) {
            $message_lines[] = sprintf(
                /* translators: %s: group permalink */
                __( 'Você pode acompanhar o grupo aqui: %s', 'juntaplay' ),
                esc_url( $permalink )
            );
        }

        $message_lines[] = __( 'Você será notificado por e-mail após a revisão.', 'juntaplay' );

        $message = implode( "\n\n", $message_lines );

        wp_mail( $user->user_email, $subject, $message, self::get_plain_headers() );
    }

    /**
     * Envia um aviso para a equipe Juntaplay sobre a submissão do grupo.
     *
     * @param int     $post_id ID do grupo.
     * @param WP_User $user    Usuário autenticado.
     */
    public static function notify_team_submission( $post_id, WP_User $user ) {
        $team_email = self::get_team_email();

        if ( empty( $team_email ) ) {
            return;
        }

        $post = get_post( $post_id );

        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: group title */
            __( 'Novo grupo enviado para revisão: %s', 'juntaplay' ),
            $post->post_title
        );

        $edit_link = get_edit_post_link( $post );
        $message_lines = array(
            __( 'Um novo grupo foi enviado para revisão.', 'juntaplay' ),
            sprintf(
                /* translators: %s: group title */
                __( 'Título: %s', 'juntaplay' ),
                $post->post_title
            ),
        );

        if ( $user instanceof WP_User ) {
            $message_lines[] = sprintf(
                /* translators: 1: user display name, 2: user email */
                __( 'Autor: %1$s (%2$s)', 'juntaplay' ),
                $user->display_name,
                $user->user_email
            );
        }

        if ( $edit_link ) {
            $message_lines[] = sprintf(
                /* translators: %s: edit link */
                __( 'Revisar grupo: %s', 'juntaplay' ),
                esc_url( $edit_link )
            );
        }

        $message = implode( "\n\n", $message_lines );

        wp_mail( $team_email, $subject, $message, self::get_plain_headers() );
    }

    /**
     * Obtém o endereço da equipe responsável pela revisão.
     *
     * @return string
     */
    protected static function get_team_email() {
        $configured = get_option( 'jplay_team_email' );
        $configured = is_string( $configured ) ? sanitize_email( $configured ) : '';

        $default = is_email( $configured ) ? $configured : get_option( 'admin_email' );
        $default = is_string( $default ) ? sanitize_email( $default ) : '';

        /**
         * Permite alterar o e-mail que receberá as notificações da equipe Juntaplay.
         *
         * @param string $team_email E-mail padrão configurado.
         * @param string $configured E-mail salvo na opção jplay_team_email.
         */
        $team_email = apply_filters( 'jplay_team_notification_email', $default, $configured );

        return is_email( $team_email ) ? $team_email : '';
    }

    /**
     * Retorna os headers padrão para e-mails em texto puro.
     *
     * @return array
     */
    protected static function get_plain_headers() {
        return array( 'Content-Type: text/plain; charset=UTF-8' );
    }
}
