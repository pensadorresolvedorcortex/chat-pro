<?php
/**
 * Two-factor authentication form template.
 *
 * @package ADC\Login\Templates
 */

use function ADC\Login\get_asset_url;
use function ADC\Login\get_flash_message;
use function ADC\Login\get_logo_url;

$error   = get_flash_message( 'error' );
$success = get_flash_message( 'success' );

$feature_defaults = array(
    __( 'Seus dados protegidos com verificação em dois passos.', 'login-academia-da-comunicacao' ),
    __( 'Códigos com validade de 10 minutos para mais segurança.', 'login-academia-da-comunicacao' ),
    __( 'Você pode reenviar o código quantas vezes precisar.', 'login-academia-da-comunicacao' ),
);

$features = apply_filters( 'adc_login_auth_features_twofa', $feature_defaults );
if ( ! is_array( $features ) ) {
    $features = $feature_defaults;
}

$features = apply_filters( 'adc_login_twofa_features', $features );

$badge            = apply_filters( 'adc_login_twofa_badge', __( 'Segurança reforçada', 'login-academia-da-comunicacao' ) );
$headline         = apply_filters( 'adc_login_twofa_headline', __( 'Confirme que é você.', 'login-academia-da-comunicacao' ) );
$subtitle         = apply_filters( 'adc_login_twofa_subtitle', __( 'Use o código enviado por e-mail para validar seu acesso e continuar aprendendo com tranquilidade.', 'login-academia-da-comunicacao' ) );
$card_kicker      = apply_filters( 'adc_login_twofa_card_kicker', __( 'Passo extra para proteger sua conta', 'login-academia-da-comunicacao' ) );
$card_title       = apply_filters( 'adc_login_twofa_card_title', __( 'Verificação em duas etapas', 'login-academia-da-comunicacao' ) );
$card_description = apply_filters( 'adc_login_twofa_card_description', __( 'Enviamos um código de 6 dígitos para o seu e-mail. Ele expira em 10 minutos.', 'login-academia-da-comunicacao' ) );
$submit_label     = apply_filters( 'adc_login_twofa_submit_label', __( 'Validar código', 'login-academia-da-comunicacao' ) );
$resend_label     = apply_filters( 'adc_login_twofa_resend_label', __( 'Reenviar código', 'login-academia-da-comunicacao' ) );

$illustration = apply_filters(
    'adc_login_twofa_illustration',
    array(
        'src' => get_asset_url( 'assets/img/auth-illustration.svg' ),
        'alt' => __( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ),
    )
);

$illustration_src = is_array( $illustration ) ? ( isset( $illustration['src'] ) ? $illustration['src'] : '' ) : $illustration;
$illustration_alt = is_array( $illustration ) ? ( isset( $illustration['alt'] ) ? $illustration['alt'] : '' ) : '';
?>
<div class="adc-auth-viewport">
    <div class="adc-auth-shell">
        <aside class="adc-auth-aside">
            <div class="adc-auth-brand">
                <img class="adc-auth-logo" src="<?php echo esc_url( get_logo_url() ); ?>" alt="<?php esc_attr_e( 'Logo da Academia da Comunicação', 'login-academia-da-comunicacao' ); ?>" loading="lazy" width="160" height="48" />
                <span class="adc-auth-badge"><?php echo esc_html( $badge ); ?></span>
            </div>
            <h1 class="adc-auth-headline"><?php echo esc_html( $headline ); ?></h1>
            <p class="adc-auth-subtitle"><?php echo esc_html( $subtitle ); ?></p>
            <?php if ( ! empty( $features ) ) : ?>
                <ul class="adc-auth-list">
                    <?php foreach ( $features as $feature ) : ?>
                        <li>
                            <span class="adc-auth-list-icon" aria-hidden="true">✓</span>
                            <span><?php echo esc_html( $feature ); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <figure class="adc-auth-illustration">
                <img src="<?php echo esc_url( $illustration_src ); ?>" alt="<?php echo esc_attr( $illustration_alt ); ?>" loading="lazy" width="360" height="280" />
            </figure>
        </aside>
        <div class="adc-auth-panel">
            <div class="adc-login-wrapper" id="adc-2fa">
                <header class="adc-card-header">
                    <span class="adc-card-kicker"><?php echo esc_html( $card_kicker ); ?></span>
                    <h2><?php echo esc_html( $card_title ); ?></h2>
                    <p class="adc-description"><?php echo esc_html( $card_description ); ?></p>
                </header>

                <?php if ( $error ) : ?>
                    <div class="adc-flash adc-flash-error" role="alert"><?php echo esc_html( $error ); ?></div>
                <?php elseif ( $success ) : ?>
                    <div class="adc-flash adc-flash-success" role="status"><?php echo esc_html( $success ); ?></div>
                <?php endif; ?>

                <div class="adc-card-body">
                    <form class="adc-validate" method="post">
                        <input type="hidden" name="adc_login_action" value="verify_2fa" />
                        <?php wp_nonce_field( 'adc_2fa' ); ?>

                        <div class="adc-field">
                            <label for="adc-2fa-code"><?php esc_html_e( 'Código de verificação', 'login-academia-da-comunicacao' ); ?></label>
                            <input type="text" id="adc-2fa-code" name="adc_code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" class="adc-twofa-code" data-required="true" required />
                        </div>

                        <button type="submit" class="adc-button"><?php echo esc_html( $submit_label ); ?></button>
                    </form>

                    <form method="post" class="adc-2fa-resend">
                        <input type="hidden" name="adc_login_action" value="resend_2fa" />
                        <?php wp_nonce_field( 'adc_2fa_resend' ); ?>
                        <button type="submit" class="adc-button adc-button-secondary"><?php echo esc_html( $resend_label ); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
