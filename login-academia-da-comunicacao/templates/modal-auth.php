<?php
/**
 * Authentication modal template.
 *
 * @package ADC\Login\Templates
 */

use function ADC\Login\get_logo_url;
use function ADC\Login\get_onboarding_url;
use function ADC\Login\get_option_value;
use function ADC\Login\get_post_login_url;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$site_key = get_option_value( 'recaptcha_site_key', '' );
if ( $site_key ) {
    wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
}

$login_tab_label   = apply_filters( 'adc_login_modal_login_tab_label', __( 'Entrar', 'login-academia-da-comunicacao' ) );
$signup_tab_label  = apply_filters( 'adc_login_modal_signup_tab_label', __( 'Cadastre-se', 'login-academia-da-comunicacao' ) );
$login_title       = apply_filters( 'adc_login_login_card_title', __( 'Entrar', 'login-academia-da-comunicacao' ) );
$login_description = apply_filters( 'adc_login_login_card_description', __( 'Acesse sua conta para continuar aprendendo.', 'login-academia-da-comunicacao' ) );
$login_submit      = apply_filters( 'adc_login_login_submit_label', __( 'Entrar', 'login-academia-da-comunicacao' ) );
$login_footer      = apply_filters( 'adc_login_login_footer_link_label', __( 'Cadastre-se', 'login-academia-da-comunicacao' ) );
$login_prompt      = apply_filters( 'adc_login_login_footer_prompt', __( 'Não tem conta?', 'login-academia-da-comunicacao' ) );
$forgot_label      = apply_filters( 'adc_login_login_forgot_link_label', __( 'Esqueceu a senha?', 'login-academia-da-comunicacao' ) );
$remember_label    = apply_filters( 'adc_login_login_remember_label', __( 'Manter conectado', 'login-academia-da-comunicacao' ) );

$signup_title       = apply_filters( 'adc_login_signup_card_title', __( 'Criar conta', 'login-academia-da-comunicacao' ) );
$signup_description = apply_filters( 'adc_login_signup_card_description', __( 'Complete seus dados para personalizarmos sua experiência.', 'login-academia-da-comunicacao' ) );
$signup_submit      = apply_filters( 'adc_login_signup_submit_label', __( 'Criar conta', 'login-academia-da-comunicacao' ) );
$signup_prompt      = apply_filters( 'adc_login_signup_footer_prompt', __( 'Já tem conta?', 'login-academia-da-comunicacao' ) );
$signup_footer      = apply_filters( 'adc_login_signup_footer_link_label', __( 'Entrar', 'login-academia-da-comunicacao' ) );
$terms_text         = apply_filters( 'adc_login_signup_terms_text', __( 'Li e aceito os Termos, Política e eventuais taxas.', 'login-academia-da-comunicacao' ) );
$terms_link_label   = apply_filters( 'adc_login_signup_terms_link_label', __( 'Saiba mais', 'login-academia-da-comunicacao' ) );
$terms_url          = get_option_value( 'terms_url', '' );

?>
<div class="adc-modal" id="adc-auth-modal" aria-hidden="true">
    <div class="adc-modal__overlay" data-modal-close></div>
    <div class="adc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="adc-auth-modal-title-login">
        <button class="adc-modal__close" type="button" data-modal-close aria-label="<?php esc_attr_e( 'Fechar', 'login-academia-da-comunicacao' ); ?>">×</button>
        <header class="adc-modal__header">
            <img class="adc-modal__logo" src="<?php echo esc_url( get_logo_url() ); ?>" alt="<?php esc_attr_e( 'Logo da Academia da Comunicação', 'login-academia-da-comunicacao' ); ?>" loading="lazy" width="128" height="40" />
            <nav class="adc-modal__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Alternar entre login e cadastro', 'login-academia-da-comunicacao' ); ?>">
                <button class="adc-modal__tab is-active" type="button" role="tab" id="adc-auth-tab-login" data-modal-tab="login" aria-controls="adc-auth-panel-login" aria-selected="true"><?php echo esc_html( $login_tab_label ); ?></button>
                <button class="adc-modal__tab" type="button" role="tab" id="adc-auth-tab-signup" data-modal-tab="signup" aria-controls="adc-auth-panel-signup" aria-selected="false"><?php echo esc_html( $signup_tab_label ); ?></button>
            </nav>
        </header>

        <div class="adc-modal__body">
            <section class="adc-modal__panel is-active" id="adc-auth-panel-login" data-modal-panel="login" role="tabpanel" aria-labelledby="adc-auth-tab-login">
                <h2 id="adc-auth-modal-title-login" class="adc-modal__title"><?php echo esc_html( $login_title ); ?></h2>
                <p class="adc-modal__description"><?php echo esc_html( $login_description ); ?></p>
                <form class="adc-validate adc-modal-form" method="post">
                    <input type="hidden" name="adc_login_action" value="login" />
                    <?php wp_nonce_field( 'adc_login' ); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_post_login_url() ); ?>" />

                    <div class="adc-field">
                        <label for="adc-modal-login-email"><?php esc_html_e( 'E-mail', 'login-academia-da-comunicacao' ); ?></label>
                        <input type="email" id="adc-modal-login-email" name="adc_email" autocomplete="email" data-required="true" required />
                    </div>

                    <div class="adc-field">
                        <label for="adc-modal-login-password"><?php esc_html_e( 'Senha', 'login-academia-da-comunicacao' ); ?></label>
                        <div class="adc-password-field">
                            <input type="password" id="adc-modal-login-password" name="adc_password" autocomplete="current-password" data-required="true" required />
                            <button class="adc-toggle-password" type="button" aria-label="<?php esc_attr_e( 'Mostrar senha', 'login-academia-da-comunicacao' ); ?>">👁</button>
                        </div>
                        <a class="adc-link adc-link-inline" href="<?php echo esc_url( add_query_arg( 'step', 'forgot', get_onboarding_url() ) ); ?>"><?php echo esc_html( $forgot_label ); ?></a>
                    </div>

                    <label class="adc-checkbox">
                        <input type="checkbox" name="rememberme" value="1" />
                        <span><?php echo esc_html( $remember_label ); ?></span>
                    </label>

                    <?php if ( $site_key ) : ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
                    <?php endif; ?>

                    <button type="submit" class="adc-button adc-button-primary">&nbsp;<?php echo esc_html( $login_submit ); ?>&nbsp;</button>
                </form>
                <p class="adc-modal__switch">
                    <?php echo esc_html( $login_prompt ); ?>
                    <button class="adc-link adc-link-button" type="button" data-modal-tab="signup"><?php echo esc_html( $login_footer ); ?></button>
                </p>
            </section>

            <section class="adc-modal__panel" id="adc-auth-panel-signup" data-modal-panel="signup" role="tabpanel" aria-labelledby="adc-auth-tab-signup" aria-hidden="true">
                <h2 id="adc-auth-modal-title-signup" class="adc-modal__title"><?php echo esc_html( $signup_title ); ?></h2>
                <p class="adc-modal__description"><?php echo esc_html( $signup_description ); ?></p>
                <form class="adc-validate adc-modal-form" method="post">
                    <input type="hidden" name="adc_login_action" value="signup" />
                    <?php wp_nonce_field( 'adc_signup' ); ?>
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url( get_post_login_url() ); ?>" />

                    <div class="adc-field">
                        <label for="adc-modal-signup-name"><?php esc_html_e( 'Nome completo', 'login-academia-da-comunicacao' ); ?></label>
                        <input type="text" id="adc-modal-signup-name" name="adc_name" autocomplete="name" data-required="true" required />
                    </div>

                    <div class="adc-field">
                        <label for="adc-modal-signup-email"><?php esc_html_e( 'E-mail', 'login-academia-da-comunicacao' ); ?></label>
                        <input type="email" id="adc-modal-signup-email" name="adc_email" autocomplete="email" data-required="true" required />
                    </div>

                    <div class="adc-field">
                        <label for="adc-modal-signup-password"><?php esc_html_e( 'Senha', 'login-academia-da-comunicacao' ); ?></label>
                        <div class="adc-password-field">
                            <input type="password" id="adc-modal-signup-password" name="adc_password" autocomplete="new-password" data-required="true" required />
                            <button class="adc-toggle-password" type="button" aria-label="<?php esc_attr_e( 'Mostrar senha', 'login-academia-da-comunicacao' ); ?>">👁</button>
                        </div>
                    </div>

                    <div class="adc-field">
                        <label for="adc-modal-signup-password-confirm"><?php esc_html_e( 'Confirmar senha', 'login-academia-da-comunicacao' ); ?></label>
                        <input type="password" id="adc-modal-signup-password-confirm" name="adc_password_confirm" autocomplete="new-password" data-required="true" required />
                    </div>

                    <label class="adc-checkbox">
                        <input type="checkbox" name="adc_terms" value="1" required />
                        <span>
                            <?php echo esc_html( $terms_text ); ?>
                            <?php if ( $terms_url ) : ?>
                                <a class="adc-link" href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $terms_link_label ); ?></a>
                            <?php endif; ?>
                        </span>
                    </label>

                    <?php if ( $site_key ) : ?>
                        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
                    <?php endif; ?>

                    <button type="submit" class="adc-button adc-button-secondary">&nbsp;<?php echo esc_html( $signup_submit ); ?>&nbsp;</button>
                </form>
                <p class="adc-modal__switch">
                    <?php echo esc_html( $signup_prompt ); ?>
                    <button class="adc-link adc-link-button" type="button" data-modal-tab="login"><?php echo esc_html( $signup_footer ); ?></button>
                </p>
            </section>
        </div>
    </div>
</div>
