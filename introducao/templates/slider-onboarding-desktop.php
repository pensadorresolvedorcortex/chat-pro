<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin_instance = class_exists( 'Introducao_Plugin' ) ? Introducao_Plugin::get_instance() : null;
$default_slides  = $plugin_instance ? $plugin_instance->get_default_slider_texts() : array(
    array(
        'title'       => __( 'Domine sua jornada', 'introducao' ),
        'description' => __( 'Transforme teoria, prática e simulados em um ciclo inteligente que acelera sua aprovação.', 'introducao' ),
    ),
    array(
        'title'       => __( 'Conecte-se com insights', 'introducao' ),
        'description' => __( 'Acompanhe métricas em tempo real, receba alertas personalizados e ajuste o ritmo com segurança.', 'introducao' ),
    ),
    array(
        'title'       => __( 'Comece agora mesmo', 'introducao' ),
        'description' => __( 'Crie sua conta ou faça login e ative a experiência completa da Academia da Comunicação.', 'introducao' ),
    ),
);

$settings = $plugin_instance ? $plugin_instance->get_slider_settings() : array();

$slides = isset( $settings['slides'] ) && is_array( $settings['slides'] ) ? $settings['slides'] : array();

if ( empty( $slides ) ) {
    $slides = $default_slides;
}

foreach ( $slides as $index => $slide ) {
    $fallback = isset( $default_slides[ $index ] ) ? $default_slides[ $index ] : array();

    if ( empty( $slide['title'] ) && isset( $fallback['title'] ) ) {
        $slides[ $index ]['title'] = $fallback['title'];
    }

    if ( empty( $slide['description'] ) && isset( $fallback['description'] ) ) {
        $slides[ $index ]['description'] = $fallback['description'];
    }
}

$brand_logo = isset( $settings['brand_logo'] ) && $settings['brand_logo'] ? esc_url_raw( $settings['brand_logo'] ) : 'https://www.agenciadigitalsaopaulo.com.br/app/wp-content/uploads/2024/05/logo-footer.png';

$generate_id = static function ( $prefix ) {
    if ( function_exists( 'wp_unique_id' ) ) {
        return wp_unique_id( $prefix );
    }

    return uniqid( $prefix );
};

$slider_id = $generate_id( 'introducao-onboarding-desktop-' );

$modal_enabled        = ! is_user_logged_in();
$modal_markup         = '';
$modal_title_id       = '';
$form_action_url      = '';
$redirect_after_auth  = isset( $settings['redirect_to'] ) && $settings['redirect_to'] ? esc_url_raw( $settings['redirect_to'] ) : esc_url_raw( home_url( user_trailingslashit( 'home' ) ) );

if ( $modal_enabled ) {
    $modal_title_id = wp_unique_id( 'lae-login-modal-title-' );

    if ( class_exists( 'LAE_Plugin' ) ) {
        $lae_plugin = LAE_Plugin::get_instance();

        if ( $lae_plugin ) {
            $lae_pages = $lae_plugin->get_pages();

            if ( $lae_pages instanceof LAE_Pages ) {
                $form_action_url = $lae_pages->get_account_page_url();

                if ( ! $form_action_url ) {
                    $form_action_url = $lae_pages->get_page_url( 'minha-conta-academia' );
                }
            }
        }
    }

    if ( ! $form_action_url ) {
        $form_action_url = home_url( user_trailingslashit( 'dados-conta' ) );
    }

    $form_action_url = esc_url_raw( $form_action_url );

    if ( class_exists( 'Introducao_Plugin' ) ) {
        $modal_markup = Introducao_Plugin::get_instance()->render_template(
            'page-perfil.php',
            array(
                'shortcode_tag'  => 'lae_onboarding_slider_desktop',
                'form_action'    => $form_action_url,
                'render_context' => 'modal',
                'redirect_url'   => $redirect_after_auth,
            )
        );
    }
}
?>
<section class="lae-onboarding lae-onboarding--desktop" data-lae-slider aria-label="<?php esc_attr_e( 'Apresentação interativa da Academia da Comunicação', 'introducao' ); ?>">
    <div class="lae-onboarding__layout">
        <aside class="lae-onboarding__sidebar" aria-hidden="false">
            <?php if ( $brand_logo ) : ?>
                <div class="lae-onboarding__brand">
                    <img
                        src="<?php echo esc_url( $brand_logo ); ?>"
                        alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"
                        loading="lazy"
                    />
                </div>
            <?php endif; ?>
            <p class="lae-onboarding__tagline"><?php esc_html_e( 'Sua central de estudos conectada', 'introducao' ); ?></p>
            <div class="lae-onboarding__progress" role="tablist" aria-label="<?php esc_attr_e( 'Etapas de apresentação', 'introducao' ); ?>">
                <?php foreach ( $slides as $index => $slide ) :
                    $step_id = $slider_id . '-step-' . $index;
                    ?>
                    <button
                        type="button"
                        class="lae-onboarding__step"
                        data-lae-step
                        data-lae-target="<?php echo esc_attr( $index ); ?>"
                        id="<?php echo esc_attr( $step_id ); ?>"
                        role="tab"
                        aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                        aria-controls="<?php echo esc_attr( $step_id . '-panel' ); ?>"
                        tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
                    >
                        <span class="lae-onboarding__step-index"><?php echo esc_html( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
                        <span class="lae-onboarding__step-label"><?php echo esc_html( $slide['title'] ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="lae-onboarding__controls">
                <button type="button" class="lae-onboarding__control lae-onboarding__control--prev" data-lae-prev disabled aria-disabled="true">
                    <span aria-hidden="true">&larr;</span>
                    <span><?php esc_html_e( 'Voltar', 'introducao' ); ?></span>
                </button>
                <button type="button" class="lae-onboarding__control lae-onboarding__control--next" data-lae-next aria-disabled="false">
                    <span><?php esc_html_e( 'Próximo', 'introducao' ); ?></span>
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </aside>

        <div class="lae-onboarding__main">
            <header class="lae-onboarding__header">
                <span class="lae-onboarding__eyebrow"><?php esc_html_e( 'Bem-vindo à Academia da Comunicação', 'introducao' ); ?></span>
                <h1 class="lae-onboarding__hero-title"><?php esc_html_e( 'Uma experiência imersiva para acelerar seus resultados', 'introducao' ); ?></h1>
            </header>

            <div class="lae-onboarding__slides">
                <?php foreach ( $slides as $index => $slide ) :
                    $panel_id = $slider_id . '-step-' . $index . '-panel';
                    ?>
                    <article
                        class="lae-onboarding__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
                        data-lae-slide
                        data-lae-index="<?php echo esc_attr( $index ); ?>"
                        id="<?php echo esc_attr( $panel_id ); ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr( $slider_id . '-step-' . $index ); ?>"
                        aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
                        <?php echo 0 === $index ? '' : 'hidden'; ?>
                    >
                        <h2 class="lae-onboarding__title"><?php echo esc_html( $slide['title'] ); ?></h2>
                        <p class="lae-onboarding__description"><?php echo esc_html( $slide['description'] ); ?></p>

                        <?php if ( $index === count( $slides ) - 1 ) : ?>
                            <div class="lae-onboarding__actions" data-lae-finish hidden>
                                <div class="lae-onboarding__cta-group">
                                    <button type="button" class="lae-onboarding__cta" data-lae-login-trigger data-lae-login-tab="login">
                                        <?php esc_html_e( 'Entrar', 'introducao' ); ?>
                                    </button>
                                    <button type="button" class="lae-onboarding__cta lae-onboarding__cta--ghost" data-lae-login-trigger data-lae-login-tab="register">
                                        <?php esc_html_e( 'Criar conta', 'introducao' ); ?>
                                    </button>
                                </div>
                                <span class="lae-onboarding__cta-hint"><?php esc_html_e( 'Escolha como acessar e continue sua jornada sem sair do fluxo.', 'introducao' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ( $modal_enabled ) : ?>
        <div class="lae-login-modal lae-login-modal--introducao" data-lae-login-modal aria-hidden="true" data-lae-login-flow="login" data-lae-login-awaiting="0">
            <div class="lae-login-modal__overlay" data-lae-login-close></div>
            <div class="lae-login-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_title_id ); ?>">
                <button type="button" class="lae-login-modal__close" data-lae-login-close aria-label="<?php esc_attr_e( 'Fechar painel de acesso', 'introducao' ); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="lae-login-modal__body lae-login-modal__body--introducao">
                    <span class="screen-reader-text" id="<?php echo esc_attr( $modal_title_id ); ?>"><?php esc_html_e( 'Formulário de acesso à Academia da Comunicação', 'introducao' ); ?></span>
                    <?php if ( $modal_markup ) : ?>
                        <?php echo $modal_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php else : ?>
                        <p class="lae-login-empty"><?php esc_html_e( 'Não foi possível carregar o formulário de acesso. Recarregue a página e tente novamente.', 'introducao' ); ?></p>
                    <?php endif; ?>
                    <div class="lae-login-feedback" data-lae-login-message role="status" aria-live="polite"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
