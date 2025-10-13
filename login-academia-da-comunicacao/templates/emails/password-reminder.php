<?php
/**
 * Password reminder email.
 *
 * @package ADC\Login\Emails
 */

$user_name = $user->display_name ?: $user->user_login;
$copy      = isset( $copy ) && is_array( $copy ) ? $copy : array();

$headline  = isset( $copy['headline'] ) ? $copy['headline'] : __( 'Vamos redefinir sua senha?', 'login-academia-da-comunicacao' );
$intro     = isset( $copy['intro'] ) ? $copy['intro'] : sprintf( __( 'Olá %s, recebemos seu pedido de redefinição de senha.', 'login-academia-da-comunicacao' ), $user_name );
$body      = isset( $copy['body'] ) ? $copy['body'] : __( 'Clique no botão abaixo para criar uma nova senha segura. Este link é válido por tempo limitado.', 'login-academia-da-comunicacao' );
$cta_label = isset( $copy['cta_label'] ) ? $copy['cta_label'] : __( 'Criar nova senha', 'login-academia-da-comunicacao' );
$cta_url   = ! empty( $copy['cta_url'] ) ? $copy['cta_url'] : $reset_url;
$footer    = isset( $copy['footer'] ) ? $copy['footer'] : __( 'Se você não solicitou esta alteração, ignore este e-mail ou troque sua senha imediatamente.', 'login-academia-da-comunicacao' );
$hero      = isset( $copy['hero'] ) && is_array( $copy['hero'] ) ? $copy['hero'] : array();
$hero_src  = isset( $hero['src'] ) ? $hero['src'] : '';
$hero_alt  = isset( $hero['alt'] ) ? $hero['alt'] : '';

ob_start();
?>
<?php if ( $hero_src ) : ?>
    <p style="margin:0 0 24px 0;text-align:center;">
        <img src="<?php echo esc_url( $hero_src ); ?>" alt="<?php echo esc_attr( $hero_alt ); ?>" style="max-width:100%;height:auto;border-radius:18px;" />
    </p>
<?php endif; ?>

<?php if ( $headline ) : ?>
    <h1 style="font-size:26px;color:<?php echo esc_attr( $palette['primary'] ); ?>;margin:0 0 12px 0;">🔐 <?php echo esc_html( $headline ); ?></h1>
<?php endif; ?>

<?php if ( $intro ) :
    $intro_text = $intro;
    if ( false !== strpos( $intro_text, '%s' ) ) {
        $intro_text = sprintf( $intro_text, $user_name );
    }
    ?>
    <p style="font-size:16px;line-height:1.6;margin:0 0 16px 0;color:<?php echo esc_attr( $palette['ink'] ); ?>;">
        <?php echo esc_html( $intro_text ); ?>
    </p>
<?php endif; ?>

<?php if ( $body ) : ?>
    <p style="font-size:16px;line-height:1.6;margin:0 0 24px 0;">
        <?php echo esc_html( $body ); ?>
    </p>
<?php endif; ?>

<?php if ( $cta_label ) : ?>
    <p style="text-align:center;margin:0 0 24px 0;">
        <a href="<?php echo esc_url( $cta_url ); ?>" style="display:inline-block;background:<?php echo esc_attr( $palette['accent'] ); ?>;color:#fff;padding:14px 28px;border-radius:999px;text-decoration:none;font-weight:600;">
            <?php echo esc_html( $cta_label ); ?>
        </a>
    </p>
<?php endif; ?>

<?php if ( $footer ) : ?>
    <p style="font-size:13px;line-height:1.6;margin:0;color:rgba(36,33,66,0.6);">
        <?php echo esc_html( $footer ); ?>
    </p>
<?php endif; ?>
<?php
$content = ob_get_clean();
echo $content;
