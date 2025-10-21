<?php
/**
 * Forgot password form template.
 *
 * @package ADC\Login\Templates
 */

use function ADC\Login\get_asset_url;
use function ADC\Login\get_flash_message;
use function ADC\Login\get_logo_url;
use function ADC\Login\get_onboarding_url;

$error   = get_flash_message( 'error' );
$success = get_flash_message( 'success' );

$feature_defaults = array(
    __( 'Receba o passo a passo por e-mail em poucos segundos.', 'login-academia-da-comunicacao' ),
    __( 'Proteção reforçada com 2FA e verificações inteligentes.', 'login-academia-da-comunicacao' ),
    __( 'Equipe de suporte pronta para ajudar quando precisar.', 'login-academia-da-comunicacao' ),
);

$features = apply_filters( 'adc_login_auth_features_forgot', $feature_defaults );
if ( ! is_array( $features ) ) {
    $features = $feature_defaults;
}

$features = apply_filters( 'adc_login_forgot_features', $features );

$badge            = apply_filters( 'adc_login_forgot_badge', __( 'Tudo sob controle', 'login-academia-da-comunicacao' ) );
$headline         = apply_filters( 'adc_login_forgot_headline', __( 'Vamos recuperar seu acesso.', 'login-academia-da-comunicacao' ) );
$subtitle         = apply_filters( 'adc_login_forgot_subtitle', __( 'Não se preocupe, enviaremos um link seguro para você redefinir a senha em instantes.', 'login-academia-da-comunicacao' ) );
$card_kicker      = apply_filters( 'adc_login_forgot_card_kicker', __( 'Esqueceu a senha?', 'login-academia-da-comunicacao' ) );
$card_title       = apply_filters( 'adc_login_forgot_card_title', __( 'Recuperar senha', 'login-academia-da-comunicacao' ) );
$card_description = apply_filters( 'adc_login_forgot_card_description', __( 'Informe seu e-mail para receber um link de redefinição.', 'login-academia-da-comunicacao' ) );
$submit_label     = apply_filters( 'adc_login_forgot_submit_label', __( 'Enviar instruções', 'login-academia-da-comunicacao' ) );
$footer_link      = apply_filters( 'adc_login_forgot_footer_link_label', __( 'Voltar ao login', 'login-academia-da-comunicacao' ) );

$illustration = apply_filters(
    'adc_login_forgot_illustration',
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
            <div class="adc-login-wrapper" id="adc-forgot">
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
                        <input type="hidden" name="adc_login_action" value="forgot_password" />
                        <?php wp_nonce_field( 'adc_forgot_password' ); ?>

                        <div class="adc-field">
                            <label for="adc-forgot-email"><?php esc_html_e( 'E-mail', 'login-academia-da-comunicacao' ); ?></label>
                            <input type="email" id="adc-forgot-email" name="adc_email" autocomplete="email" data-required="true" required />
                        </div>

                        <button type="submit" class="adc-button adc-button-secondary"><?php echo esc_html( $submit_label ); ?></button>
                    </form>
                </div>

                <footer class="adc-card-footer">
                    <p class="adc-form-footer">
                        <a class="adc-link" href="<?php echo esc_url( add_query_arg( 'step', 'login', get_onboarding_url() ) ); ?>"><?php echo esc_html( $footer_link ); ?></a>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</div>
