<?php
/**
 * Signup form template.
 *
 * @package ADC\Login\Templates
 */

use function ADC\Login\get_asset_url;
use function ADC\Login\get_flash_message;
use function ADC\Login\get_logo_url;
use function ADC\Login\get_onboarding_url;
use function ADC\Login\get_onboarding_signup_url;
use function ADC\Login\get_option_value;

$site_key = get_option_value( 'recaptcha_site_key', '' );
if ( $site_key ) {
    wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
}

$error   = get_flash_message( 'error' );
$success = get_flash_message( 'success' );
$terms   = get_option_value( 'terms_url', '#' );

$feature_defaults = array(
    __( 'Aulas ao vivo e gravadas em um só lugar.', 'login-academia-da-comunicacao' ),
    __( 'Metas semanais para manter sua motivação.', 'login-academia-da-comunicacao' ),
    __( 'Suporte da comunidade e mentores certificados.', 'login-academia-da-comunicacao' ),
);

$features = apply_filters( 'adc_login_auth_features_signup', $feature_defaults );
if ( ! is_array( $features ) ) {
    $features = $feature_defaults;
}

$features = apply_filters( 'adc_login_signup_features', $features );

$badge            = apply_filters( 'adc_login_signup_badge', __( 'Jornada personalizada', 'login-academia-da-comunicacao' ) );
$headline         = apply_filters( 'adc_login_signup_headline', __( 'Crie sua conta e desbloqueie o melhor da comunicação.', 'login-academia-da-comunicacao' ) );
$subtitle         = apply_filters( 'adc_login_signup_subtitle', __( 'Domine apresentações, storytelling e técnicas de influência com roteiros feitos para você.', 'login-academia-da-comunicacao' ) );
$card_kicker      = apply_filters( 'adc_login_signup_card_kicker', __( 'Vamos começar', 'login-academia-da-comunicacao' ) );
$card_title       = apply_filters( 'adc_login_signup_card_title', __( 'Criar conta', 'login-academia-da-comunicacao' ) );
$card_description = apply_filters( 'adc_login_signup_card_description', __( 'Complete seus dados para personalizarmos sua experiência.', 'login-academia-da-comunicacao' ) );
$submit_label     = apply_filters( 'adc_login_signup_submit_label', __( 'Criar conta', 'login-academia-da-comunicacao' ) );
$footer_prompt    = apply_filters( 'adc_login_signup_footer_prompt', __( 'Já tem conta?', 'login-academia-da-comunicacao' ) );
$footer_link      = apply_filters( 'adc_login_signup_footer_link_label', __( 'Entrar', 'login-academia-da-comunicacao' ) );
$terms_text       = apply_filters( 'adc_login_signup_terms_text', __( 'Li e aceito os Termos, Política e eventuais taxas.', 'login-academia-da-comunicacao' ) );
$terms_link_label = apply_filters( 'adc_login_signup_terms_link_label', __( 'Saiba mais', 'login-academia-da-comunicacao' ) );

$illustration = apply_filters(
    'adc_login_signup_illustration',
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
            <div class="adc-login-wrapper" id="adc-signup">
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
                        <input type="hidden" name="adc_login_action" value="signup" />
                        <?php wp_nonce_field( 'adc_signup' ); ?>
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_onboarding_signup_url() ); ?>" />

                        <div class="adc-field">
                            <label for="adc-signup-name"><?php esc_html_e( 'Nome completo', 'login-academia-da-comunicacao' ); ?></label>
                            <input type="text" id="adc-signup-name" name="adc_name" autocomplete="name" data-required="true" required />
                        </div>

                        <div class="adc-field">
                            <label for="adc-signup-email"><?php esc_html_e( 'E-mail', 'login-academia-da-comunicacao' ); ?></label>
                            <input type="email" id="adc-signup-email" name="adc_email" autocomplete="email" data-required="true" required />
                        </div>

                        <div class="adc-field">
                            <label for="adc-signup-password"><?php esc_html_e( 'Senha', 'login-academia-da-comunicacao' ); ?></label>
                            <div class="adc-password-field">
                                <input type="password" id="adc-signup-password" name="adc_password" autocomplete="new-password" data-required="true" required />
                                <button class="adc-toggle-password" type="button" aria-label="<?php esc_attr_e( 'Mostrar senha', 'login-academia-da-comunicacao' ); ?>">👁</button>
                            </div>
                        </div>

                        <div class="adc-field">
                            <label for="adc-signup-password-confirm"><?php esc_html_e( 'Confirmar senha', 'login-academia-da-comunicacao' ); ?></label>
                            <input type="password" id="adc-signup-password-confirm" name="adc_password_confirm" autocomplete="new-password" data-required="true" required />
                        </div>

                        <div class="adc-field">
                            <label for="adc-signup-gender"><?php esc_html_e( 'Gênero (opcional)', 'login-academia-da-comunicacao' ); ?></label>
                            <select id="adc-signup-gender" name="adc_gender">
                                <option value=""><?php esc_html_e( 'Prefiro não informar', 'login-academia-da-comunicacao' ); ?></option>
                                <option value="male"><?php esc_html_e( 'Masculino', 'login-academia-da-comunicacao' ); ?></option>
                                <option value="female"><?php esc_html_e( 'Feminino', 'login-academia-da-comunicacao' ); ?></option>
                                <option value="other"><?php esc_html_e( 'Outro', 'login-academia-da-comunicacao' ); ?></option>
                            </select>
                        </div>

                        <label class="adc-checkbox">
                            <input type="checkbox" name="adc_terms" value="1" required />
                            <span>
                                <?php echo esc_html( $terms_text ); ?>
                                <?php if ( $terms && '#' !== $terms ) : ?>
                                    <a class="adc-link" href="<?php echo esc_url( $terms ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $terms_link_label ); ?></a>
                                <?php endif; ?>
                            </span>
                        </label>

                        <?php if ( $site_key ) : ?>
                            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
                        <?php endif; ?>

                        <button type="submit" class="adc-button adc-button-secondary"><?php echo esc_html( $submit_label ); ?></button>
                    </form>
                </div>

                <footer class="adc-card-footer">
                    <p class="adc-form-footer">
                        <?php echo esc_html( $footer_prompt ); ?>
                        <a class="adc-link" href="<?php echo esc_url( add_query_arg( 'step', 'login', get_onboarding_url() ) ); ?>"><?php echo esc_html( $footer_link ); ?></a>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</div>
