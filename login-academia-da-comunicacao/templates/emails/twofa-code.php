<?php
/**
 * Two factor email template.
 *
 * @package ADC\Login\Emails
 */

$expires_in = human_time_diff( time(), $expires );
$copy       = isset( $copy ) && is_array( $copy ) ? $copy : array();

$headline  = isset( $copy['headline'] ) ? $copy['headline'] : __( 'Seu código de verificação', 'login-academia-da-comunicacao' );
$intro     = isset( $copy['intro'] ) ? $copy['intro'] : __( 'Use o código abaixo para confirmar que é você acessando a Academia da Comunicação.', 'login-academia-da-comunicacao' );
$body      = isset( $copy['body'] ) ? $copy['body'] : __( 'Este código expira em {{expires}}. Se não foi você, recomendamos redefinir sua senha imediatamente.', 'login-academia-da-comunicacao' );
$body      = str_replace( '{{expires}}', $expires_in, $body );
$cta_label = isset( $copy['cta_label'] ) ? $copy['cta_label'] : __( 'Proteger minha conta', 'login-academia-da-comunicacao' );
$cta_url   = ! empty( $copy['cta_url'] ) ? $copy['cta_url'] : wp_lostpassword_url();
$footer    = isset( $copy['footer'] ) ? $copy['footer'] : __( 'Dica: ative o 2FA sempre que possível para manter sua conta mais segura.', 'login-academia-da-comunicacao' );
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
    <h2 style="font-size:22px;color:<?php echo esc_attr( $palette['primary'] ); ?>;margin:0 0 12px 0;text-align:center;">
        <?php echo esc_html( $headline ); ?>
    </h2>
<?php endif; ?>

<div style="font-size:36px;color:<?php echo esc_attr( $palette['primary'] ); ?>;margin:0 0 20px 0;text-align:center;letter-spacing:12px;font-weight:700;">
    <?php echo esc_html( $code ); ?>
</div>

<?php if ( $intro ) : ?>
    <p style="font-size:16px;line-height:1.6;margin:0 0 16px 0;text-align:center;color:<?php echo esc_attr( $palette['ink'] ); ?>;">
        <?php echo esc_html( $intro ); ?>
    </p>
<?php endif; ?>

<?php if ( $body ) : ?>
    <p style="font-size:15px;line-height:1.6;margin:0 0 24px 0;text-align:center;color:rgba(36,33,66,0.7);">
        <?php echo esc_html( $body ); ?>
    </p>
<?php endif; ?>

<?php if ( $cta_label ) : ?>
    <p style="text-align:center;margin:0 0 24px 0;">
        <a href="<?php echo esc_url( $cta_url ); ?>" style="display:inline-block;background:<?php echo esc_attr( $palette['accent'] ); ?>;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:600;">
            <?php echo esc_html( $cta_label ); ?>
        </a>
    </p>
<?php endif; ?>

<?php if ( $footer ) : ?>
    <p style="font-size:13px;line-height:1.6;margin:0;text-align:center;color:rgba(36,33,66,0.6);">
        <?php echo esc_html( $footer ); ?>
    </p>
<?php endif; ?>
<?php
$content = ob_get_clean();
echo $content;
