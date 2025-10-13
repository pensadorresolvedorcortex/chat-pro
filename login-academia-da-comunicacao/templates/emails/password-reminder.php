<?php
/**
 * Password reminder email.
 *
 * @package ADC\Login\Emails
 */

$user_name = $user->display_name ?: $user->user_login;
ob_start();
?>
<h1 style="font-size:28px;color:<?php echo esc_attr( $palette['primary'] ); ?>;margin:0 0 16px 0;">🔐 <?php esc_html_e( 'Vamos redefinir sua senha?', 'login-academia-da-comunicacao' ); ?></h1>
<p style="font-size:16px;line-height:1.6;margin:0 0 24px 0;color:<?php echo esc_attr( $palette['ink'] ); ?>;">
    <?php printf( esc_html__( 'Olá %s, recebemos um pedido para redefinir sua senha.', 'login-academia-da-comunicacao' ), esc_html( $user_name ) ); ?>
</p>
<p style="font-size:16px;line-height:1.6;margin:0 0 24px 0;">
    <?php esc_html_e( 'Clique no botão abaixo para criar uma nova senha. Se você não solicitou a mudança, ignore este e-mail.', 'login-academia-da-comunicacao' ); ?>
</p>
<p style="text-align:center;margin:0 0 24px 0;">
    <a href="<?php echo esc_url( $reset_url ); ?>" style="display:inline-block;background:<?php echo esc_attr( $palette['accent'] ); ?>;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;">
        <?php esc_html_e( 'Redefinir minha senha', 'login-academia-da-comunicacao' ); ?>
    </a>
</p>
<p style="font-size:13px;line-height:1.6;margin:0;color:rgba(36,33,66,0.6);">
    <?php esc_html_e( 'Este link é válido por 1 hora. Para sua segurança, não compartilhe este e-mail com ninguém.', 'login-academia-da-comunicacao' ); ?>
</p>
<?php
$content = ob_get_clean();
echo $content;
