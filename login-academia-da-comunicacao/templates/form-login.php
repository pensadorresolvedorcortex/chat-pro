<?php
/**
 * Login form template.
 *
 * @package ADC\Login\Templates
 */

use function ADC\Login\get_asset_url;
use function ADC\Login\get_flash_message;
use function ADC\Login\get_logo_url;
use function ADC\Login\get_onboarding_url;
use function ADC\Login\get_option_value;
use function ADC\Login\get_post_login_url;

$site_key = get_option_value( 'recaptcha_site_key', '' );
if ( $site_key ) {
    wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
}

$error   = get_flash_message( 'error' );
$success = get_flash_message( 'success' );

$feature_defaults = array(
    __( 'Planos de estudo personalizados em minutos.', 'login-academia-da-comunicacao' ),
    __( 'Aulas mobile-first para aprender onde estiver.', 'login-academia-da-comunicacao' ),
    __( 'Desafios gamificados com recompensas reais.', 'login-academia-da-comunicacao' ),
);

$features = apply_filters( 'adc_login_auth_features_login', $feature_defaults );
if ( ! is_array( $features ) ) {
    $features = $feature_defaults;
}

$social_providers = array();

if ( get_option_value( 'enable_social_google', 0 ) ) {
    $social_providers[] = array(
        'provider' => 'google',
        'label'    => __( 'Continuar com Google', 'login-academia-da-comunicacao' ),
        'url'      => apply_filters( 'adc_login_social_google_url', '' ),
    );
}

if ( get_option_value( 'enable_social_apple', 0 ) ) {
    $social_providers[] = array(
        'provider' => 'apple',
        'label'    => __( 'Continuar com Apple', 'login-academia-da-comunicacao' ),
        'url'      => apply_filters( 'adc_login_social_apple_url', '' ),
    );
}

$social = apply_filters( 'adc_login_social_buttons', $social_providers, array( 'context' => 'login_form' ) );

if ( ! is_array( $social ) ) {
    $social = array();
}
?>
<div class="adc-auth-viewport">
    <div class="adc-auth-shell">
        <aside class="adc-auth-aside">
            <div class="adc-auth-brand">
                <img class="adc-auth-logo" src="<?php echo esc_url( get_logo_url() ); ?>" alt="<?php esc_attr_e( 'Logo da Academia da Comunicação', 'login-academia-da-comunicacao' ); ?>" loading="lazy" width="160" height="48" />
                <span class="adc-auth-badge"><?php esc_html_e( 'Aprenda sem limites', 'login-academia-da-comunicacao' ); ?></span>
            </div>
            <h1 class="adc-auth-headline"><?php esc_html_e( 'Bem-vindo de volta! 💜', 'login-academia-da-comunicacao' ); ?></h1>
            <p class="adc-auth-subtitle"><?php esc_html_e( 'Retome seus cursos exatamente de onde parou e acompanhe seu progresso em tempo real.', 'login-academia-da-comunicacao' ); ?></p>
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
                <img src="<?php echo esc_url( get_asset_url( 'assets/img/auth-illustration.svg' ) ); ?>" alt="<?php esc_attr_e( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ); ?>" loading="lazy" width="360" height="280" />
            </figure>
        </aside>
        <div class="adc-auth-panel">
            <div class="adc-login-wrapper" id="adc-login">
                <header class="adc-card-header">
                    <span class="adc-card-kicker"><?php esc_html_e( 'Bem-vindo de volta', 'login-academia-da-comunicacao' ); ?></span>
                    <h2><?php esc_html_e( 'Entrar', 'login-academia-da-comunicacao' ); ?></h2>
                    <p class="adc-description"><?php esc_html_e( 'Acesse sua conta para continuar aprendendo.', 'login-academia-da-comunicacao' ); ?></p>
                </header>

                <?php if ( $error ) : ?>
                    <div class="adc-flash adc-flash-error" role="alert"><?php echo esc_html( $error ); ?></div>
                <?php elseif ( $success ) : ?>
                    <div class="adc-flash adc-flash-success" role="status"><?php echo esc_html( $success ); ?></div>
                <?php endif; ?>

                <div class="adc-card-body">
                    <form class="adc-validate" method="post">
                        <input type="hidden" name="adc_login_action" value="login" />
                        <?php wp_nonce_field( 'adc_login' ); ?>
                        <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_post_login_url() ); ?>" />

                        <div class="adc-field">
                            <label for="adc-login-email"><?php esc_html_e( 'E-mail', 'login-academia-da-comunicacao' ); ?></label>
                            <input type="email" id="adc-login-email" name="adc_email" autocomplete="email" data-required="true" required />
                        </div>

                        <div class="adc-field">
                            <label for="adc-login-password"><?php esc_html_e( 'Senha', 'login-academia-da-comunicacao' ); ?></label>
                            <div class="adc-password-field">
                                <input type="password" id="adc-login-password" name="adc_password" autocomplete="current-password" data-required="true" required />
                                <button class="adc-toggle-password" type="button" aria-label="<?php esc_attr_e( 'Mostrar senha', 'login-academia-da-comunicacao' ); ?>">👁</button>
                            </div>
                            <a class="adc-link adc-link-inline" href="<?php echo esc_url( add_query_arg( 'step', 'forgot', get_onboarding_url() ) ); ?>"><?php esc_html_e( 'Esqueceu a senha?', 'login-academia-da-comunicacao' ); ?></a>
                        </div>

                        <label class="adc-checkbox">
                            <input type="checkbox" name="rememberme" value="1" />
                            <span><?php esc_html_e( 'Manter conectado', 'login-academia-da-comunicacao' ); ?></span>
                        </label>

                        <?php if ( $site_key ) : ?>
                            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
                        <?php endif; ?>

                        <button type="submit" class="adc-button"><?php esc_html_e( 'Entrar', 'login-academia-da-comunicacao' ); ?></button>
                    </form>

                    <?php if ( ! empty( $social ) ) : ?>
                        <div class="adc-social-buttons" aria-label="<?php esc_attr_e( 'Login social', 'login-academia-da-comunicacao' ); ?>">
                            <?php foreach ( $social as $button ) : ?>
                                <?php
                                $url = isset( $button['url'] ) ? $button['url'] : '';
                                if ( empty( $url ) ) {
                                    continue;
                                }
                                $provider = isset( $button['provider'] ) ? sanitize_key( $button['provider'] ) : 'external';
                                ?>
                                <a class="adc-social-button adc-social-<?php echo esc_attr( $provider ); ?>" href="<?php echo esc_url( $url ); ?>">
                                    <span class="adc-social-icon" aria-hidden="true">
                                        <?php if ( 'google' === $provider ) : ?>
                                            <img src="<?php echo esc_url( get_asset_url( 'assets/img/icon-google.svg' ) ); ?>" alt="" width="20" height="20" />
                                        <?php elseif ( 'apple' === $provider ) : ?>
                                            <img src="<?php echo esc_url( get_asset_url( 'assets/img/icon-apple.svg' ) ); ?>" alt="" width="20" height="20" />
                                        <?php else : ?>
                                            <span class="adc-social-glyph" aria-hidden="true">★</span>
                                        <?php endif; ?>
                                    </span>
                                    <span><?php echo esc_html( $button['label'] ); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <footer class="adc-card-footer">
                    <p class="adc-form-footer">
                        <?php esc_html_e( 'Não tem conta?', 'login-academia-da-comunicacao' ); ?>
                        <a class="adc-link" href="<?php echo esc_url( add_query_arg( 'step', 'signup', get_onboarding_url() ) ); ?>"><?php esc_html_e( 'Cadastre-se', 'login-academia-da-comunicacao' ); ?></a>
                    </p>
                </footer>
            </div>
        </div>
    </div>
</div>
