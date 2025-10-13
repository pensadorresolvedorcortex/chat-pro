<?php
/**
 * Email templates and dispatchers.
 *
 * @package ADC\Login\Email
 */

namespace ADC\Login\Email;

use WP_Error;
use WP_User;
use function ADC\Login\get_asset_url;
use function ADC\Login\get_logo_url;
use function ADC\Login\get_option_value;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Emails
 */
class Emails {

    /**
     * Bootstrap hooks.
     */
    public function init() {
        add_action( 'admin_post_adc_login_email_preview', array( $this, 'handle_preview' ) );
        add_action( 'admin_post_adc_login_email_test', array( $this, 'handle_test' ) );
    }

    /**
     * Send account created email.
     *
     * @param int $user_id User ID.
     */
    public function send_account_created( $user_id ) {
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return;
        }

        $email = $this->build_email( 'account-created', $user );

        if ( is_wp_error( $email ) ) {
            return;
        }

        $this->deliver( $user->user_email, $email['subject'], $email['body'] );
    }

    /**
     * Send password reminder email.
     *
     * @param int    $user_id  User ID.
     * @param string $reset_url Password reset URL.
     */
    public function send_password_reminder( $user_id, $reset_url ) {
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return;
        }

        $email = $this->build_email(
            'password-reminder',
            $user,
            array(
                'reset_url' => $reset_url,
            )
        );

        if ( is_wp_error( $email ) ) {
            return;
        }

        $this->deliver( $user->user_email, $email['subject'], $email['body'] );
    }

    /**
     * Send 2FA email with code.
     *
     * @param int    $user_id User ID.
     * @param string $code    Code.
     * @param int    $expires Expiration timestamp.
     */
    public function send_twofa_code( $user_id, $code, $expires ) {
        $user = get_user_by( 'id', $user_id );

        if ( ! $user ) {
            return;
        }

        $email = $this->build_email(
            'twofa-code',
            $user,
            array(
                'code'    => $code,
                'expires' => $expires,
            )
        );

        if ( is_wp_error( $email ) ) {
            return;
        }

        $this->deliver( $user->user_email, $email['subject'], $email['body'] );
    }

    /**
     * Send email message.
     *
     * @param string $to      Recipient.
     * @param string $subject Subject.
     * @param string $body    HTML body.
     *
     * @return bool
     */
    protected function deliver( $to, $subject, $body ) {
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $from    = get_option_value( 'email_from_address' );
        $name    = get_option_value( 'email_from_name' );

        if ( ! empty( $from ) ) {
            $headers[] = 'From: ' . sprintf( '%s <%s>', $name, $from );
        }

        return wp_mail( $to, $subject, $this->wrap_body( $body ), $headers );
    }

    /**
     * Wrap email body with base template.
     *
     * @param string $content Content.
     *
     * @return string
     */
    protected function wrap_body( $content ) {
        $logo = get_logo_url();
        $palette = array(
            'primary' => get_option_value( 'color_primary' ),
            'accent'  => get_option_value( 'color_accent' ),
            'ink'     => get_option_value( 'color_ink' ),
        );

        ob_start();
        include ADC_LOGIN_PLUGIN_DIR . 'templates/emails/base.php';

        return ob_get_clean();
    }

    /**
     * Render body template.
     *
     * @param string $template Template filename.
     * @param array  $vars     Variables.
     *
     * @return string
     */
    protected function render_template( $template, $vars ) {
        $vars = array_merge(
            array(
                'palette' => array(
                    'primary' => get_option_value( 'color_primary' ),
                    'accent'  => get_option_value( 'color_accent' ),
                    'ink'     => get_option_value( 'color_ink' ),
                ),
            ),
            $vars
        );

        ob_start();
        extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        include ADC_LOGIN_PLUGIN_DIR . 'templates/emails/' . $template;

        return ob_get_clean();
    }

    /**
     * Build subject/body payload for a given template.
     *
     * @param string  $type Email type identifier.
     * @param WP_User $user Reference user.
     * @param array   $args Extra context.
     *
     * @return array|WP_Error
     */
    protected function build_email( $type, WP_User $user, $args = array() ) {
        switch ( $type ) {
            case 'account-created':
                $copy = apply_filters(
                    'adc_login_email_account_created_content',
                    array(
                        'headline' => __( 'Bem-vindo à Academia da Comunicação!', 'login-academia-da-comunicacao' ),
                        'intro'    => __( 'Olá %s, sua conta está ativa e pronta para turbinar seus estudos.', 'login-academia-da-comunicacao' ),
                        'body'     => __( 'Use o botão abaixo para acessar a plataforma ou, se preferir, faça login pelo aplicativo quando quiser.', 'login-academia-da-comunicacao' ),
                        'cta_label' => __( 'Acessar minha conta', 'login-academia-da-comunicacao' ),
                        'cta_url'   => '',
                        'footer'   => __( 'Precisa de ajuda? Responda este e-mail e nossa equipe retorna rapidinho.', 'login-academia-da-comunicacao' ),
                        'hero'     => array(
                            'src' => get_asset_url( 'assets/img/auth-illustration.svg' ),
                            'alt' => __( 'Equipe celebrando a criação de uma nova conta.', 'login-academia-da-comunicacao' ),
                        ),
                    ),
                    $user,
                    $args
                );

                return array(
                    'subject' => get_option_value( 'subject_account_created' ),
                    'body'    => $this->render_template(
                        'account-created.php',
                        array(
                            'user' => $user,
                            'copy' => $copy,
                        )
                    ),
                );

            case 'password-reminder':
                $reset = isset( $args['reset_url'] ) ? esc_url_raw( $args['reset_url'] ) : home_url( '/redefinir-senha' );

                $copy = apply_filters(
                    'adc_login_email_password_reminder_content',
                    array(
                        'headline' => __( 'Vamos redefinir sua senha?', 'login-academia-da-comunicacao' ),
                        'intro'    => __( 'Olá %s, recebemos seu pedido para redefinir a senha.', 'login-academia-da-comunicacao' ),
                        'body'     => __( 'Clique no botão abaixo para criar uma nova senha segura. Este link é válido por tempo limitado.', 'login-academia-da-comunicacao' ),
                        'cta_label' => __( 'Criar nova senha', 'login-academia-da-comunicacao' ),
                        'cta_url'   => '',
                        'footer'   => __( 'Se você não solicitou esta alteração, ignore este e-mail ou altere sua senha imediatamente.', 'login-academia-da-comunicacao' ),
                        'hero'     => array(
                            'src' => get_asset_url( 'assets/img/onboarding-knowledge.svg' ),
                            'alt' => __( 'Pessoa redefinindo senha em um laptop.', 'login-academia-da-comunicacao' ),
                        ),
                    ),
                    $user,
                    $args
                );

                return array(
                    'subject' => get_option_value( 'subject_password_reminder' ),
                    'body'    => $this->render_template(
                        'password-reminder.php',
                        array(
                            'user'      => $user,
                            'reset_url' => $reset,
                            'copy'      => $copy,
                        )
                    ),
                );

            case 'twofa-code':
                $code    = isset( $args['code'] ) ? sanitize_text_field( $args['code'] ) : '123456';
                $expires = isset( $args['expires'] ) ? (int) $args['expires'] : ( current_time( 'timestamp' ) + ( 10 * MINUTE_IN_SECONDS ) );

                $copy = apply_filters(
                    'adc_login_email_twofa_content',
                    array(
                        'headline' => __( 'Seu código de verificação', 'login-academia-da-comunicacao' ),
                        'intro'    => __( 'Use o código abaixo para confirmar que é você acessando a Academia da Comunicação.', 'login-academia-da-comunicacao' ),
                        'body'     => __( 'Este código expira em {{expires}}. Se não foi você, recomendamos redefinir sua senha imediatamente.', 'login-academia-da-comunicacao' ),
                        'cta_label' => __( 'Proteger minha conta', 'login-academia-da-comunicacao' ),
                        'cta_url'   => '',
                        'footer'   => __( 'Dica: ative o 2FA sempre que possível para manter sua conta mais segura.', 'login-academia-da-comunicacao' ),
                        'hero'     => array(
                            'src' => get_asset_url( 'assets/img/onboarding-rewards.svg' ),
                            'alt' => __( 'Escudo representando segurança da conta.', 'login-academia-da-comunicacao' ),
                        ),
                    ),
                    $user,
                    $args
                );

                return array(
                    'subject' => get_option_value( 'subject_twofa_code' ),
                    'body'    => $this->render_template(
                        'twofa-code.php',
                        array(
                            'user'    => $user,
                            'code'    => $code,
                            'expires' => $expires,
                            'copy'    => $copy,
                        )
                    ),
                );
        }

        return new WP_Error( 'adc_email_unknown_template', __( 'Modelo de e-mail inválido.', 'login-academia-da-comunicacao' ) );
    }

    /**
     * Handle admin preview of email templates.
     */
    public function handle_preview() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para visualizar este conteúdo.', 'login-academia-da-comunicacao' ) );
        }

        $type = isset( $_GET['type'] ) ? $this->resolve_type( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ( empty( $type ) ) {
            wp_die( esc_html__( 'Modelo de e-mail inválido.', 'login-academia-da-comunicacao' ) );
        }

        check_admin_referer( 'adc_login_email_preview_' . $type );

        $user  = wp_get_current_user();
        $email = $this->build_email( $type, $user, $this->sample_context( $type ) );

        if ( is_wp_error( $email ) ) {
            wp_die( esc_html( $email->get_error_message() ) );
        }

        nocache_headers();
        header( 'Content-Type: text/html; charset=UTF-8' );
        echo $this->wrap_body( $email['body'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    /**
     * Handle sending of test emails to the current administrator.
     */
    public function handle_test() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para executar esta ação.', 'login-academia-da-comunicacao' ) );
        }

        $type = isset( $_GET['type'] ) ? $this->resolve_type( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

        if ( empty( $type ) ) {
            add_settings_error( 'adc_login_emails', 'adc_email_test_invalid', __( 'Modelo de e-mail inválido.', 'login-academia-da-comunicacao' ) );
            $this->redirect_back();
        }

        check_admin_referer( 'adc_login_email_test_' . $type );

        $user       = wp_get_current_user();
        $recipient  = $user && $user->user_email ? $user->user_email : get_bloginfo( 'admin_email' );
        $email      = $this->build_email( $type, $user, $this->sample_context( $type ) );
        $notice_key = 'adc_login_emails';

        if ( is_wp_error( $email ) ) {
            add_settings_error( $notice_key, 'adc_email_test_error', $email->get_error_message() );
            $this->redirect_back();
        }

        $sent = $this->deliver( $recipient, $email['subject'], $email['body'] );

        if ( $sent ) {
            add_settings_error(
                $notice_key,
                'adc_email_test_success',
                sprintf(
                    /* translators: %s: email address */
                    __( 'E-mail de teste enviado para %s.', 'login-academia-da-comunicacao' ),
                    $recipient
                ),
                'updated'
            );
        } else {
            add_settings_error(
                $notice_key,
                'adc_email_test_failed',
                __( 'Não foi possível enviar o e-mail de teste. Verifique a configuração do servidor de e-mail.', 'login-academia-da-comunicacao' )
            );
        }

        $this->redirect_back();
    }

    /**
     * Provide placeholder context for previews/tests.
     *
     * @param string $type Email type.
     *
     * @return array
     */
    protected function sample_context( $type ) {
        switch ( $type ) {
            case 'password-reminder':
                return array(
                    'reset_url' => home_url( '/redefinir-senha?token=demo' ),
                );

            case 'twofa-code':
                return array(
                    'code'    => '123456',
                    'expires' => current_time( 'timestamp' ) + ( 10 * MINUTE_IN_SECONDS ),
                );
        }

        return array();
    }

    /**
     * Redirect back to the settings screen.
     */
    protected function redirect_back() {
        $referer = wp_get_referer();
        $target  = $referer ? $referer : admin_url( 'options-general.php?page=adc-login-hub' );

        wp_safe_redirect( $target );
        exit;
    }

    /**
     * Sanitize and validate email type identifiers.
     *
     * @param string $raw Raw identifier.
     *
     * @return string
     */
    protected function resolve_type( $raw ) {
        $type   = sanitize_text_field( $raw );
        $types  = array( 'account-created', 'password-reminder', 'twofa-code' );

        return in_array( $type, $types, true ) ? $type : '';
    }
}
