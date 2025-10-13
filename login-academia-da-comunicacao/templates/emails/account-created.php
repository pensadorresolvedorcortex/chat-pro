<?php
/**
 * Account created email.
 *
 * @package ADC\Login\Emails
 */

$user_name = $user->display_name ?: $user->user_login;
ob_start();
?>
<h1 style="font-size:28px;color:<?php echo esc_attr( $palette['primary'] ); ?>;margin:0 0 16px 0;">👋 <?php esc_html_e( 'Bem-vindo à Academia da Comunicação!', 'login-academia-da-comunicacao' ); ?></h1>
<p style="font-size:16px;line-height:1.6;margin:0 0 24px 0;color:<?php echo esc_attr( $palette['ink'] ); ?>;">
    <?php printf( esc_html__( 'Olá %s, sua nova conta está pronta para ser explorada. Prepare-se para quizzes envolventes, trilhas de estudo e recompensas especiais.', 'login-academia-da-comunicacao' ), esc_html( $user_name ) ); ?>
</p>
<p style="font-size:16px;line-height:1.6;margin:0 0 24px 0;">
    <?php esc_html_e( 'Use seu e-mail e senha para acessar e continue de onde parou. Caso precise redefinir a senha, clique no botão abaixo.', 'login-academia-da-comunicacao' ); ?>
</p>
<p style="text-align:center;margin:0 0 24px 0;">
    <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="display:inline-block;background:<?php echo esc_attr( $palette['primary'] ); ?>;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;">
        <?php esc_html_e( 'Redefinir senha', 'login-academia-da-comunicacao' ); ?>
    </a>
</p>
<p style="font-size:14px;line-height:1.6;margin:0;color:rgba(36,33,66,0.7);">
    <?php esc_html_e( 'Dica: adicione esta mensagem aos seus favoritos para acessar rapidamente sempre que precisar.', 'login-academia-da-comunicacao' ); ?>
</p>
<?php
$content = ob_get_clean();
echo $content;
