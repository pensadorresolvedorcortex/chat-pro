<?php
/**
 * Two factor email template.
 *
 * @package ADC\Login\Emails
 */

$expires_in = human_time_diff( time(), $expires );
ob_start();
?>
<h1 style="font-size:36px;color:<?php echo esc_attr( $palette['primary'] ); ?>;margin:0 0 16px 0;text-align:center;letter-spacing:12px;"><?php echo esc_html( $code ); ?></h1>
<p style="font-size:16px;line-height:1.6;margin:0 0 24px 0;text-align:center;color:<?php echo esc_attr( $palette['ink'] ); ?>;">
    <?php esc_html_e( 'Use este código para finalizar seu login na Academia da Comunicação.', 'login-academia-da-comunicacao' ); ?>
</p>
<p style="font-size:15px;line-height:1.6;margin:0 0 24px 0;text-align:center;color:rgba(36,33,66,0.7);">
    <?php printf( esc_html__( 'Ele expira em %s. Se você não iniciou este acesso, altere sua senha imediatamente.', 'login-academia-da-comunicacao' ), esc_html( $expires_in ) ); ?>
</p>
<p style="text-align:center;margin:0;">
    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="display:inline-block;background:<?php echo esc_attr( $palette['accent'] ); ?>;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:600;">
        <?php esc_html_e( 'Proteger minha conta', 'login-academia-da-comunicacao' ); ?>
    </a>
</p>
<?php
$content = ob_get_clean();
echo $content;
