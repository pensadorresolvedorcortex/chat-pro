<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin_instance = class_exists( 'Introducao_Plugin' ) ? Introducao_Plugin::get_instance() : null;

if ( $plugin_instance && method_exists( $plugin_instance, 'enqueue_assets' ) ) {
    $plugin_instance->enqueue_assets();
}
$default_slides  = $plugin_instance ? $plugin_instance->get_default_slider_texts() : array(
    array(
        'title'       => __( 'Organize sua preparação', 'login-academia-da-educacao' ),
        'description' => __( 'Conheça o plano de estudos inteligente da Academia da Comunicação e centralize teoria, prática e simulados em um só lugar.', 'login-academia-da-educacao' ),
    ),
    array(
        'title'       => __( 'Acompanhe o seu progresso', 'login-academia-da-educacao' ),
        'description' => __( 'Receba lembretes personalizados, veja sua evolução em tempo real e desbloqueie recomendações alinhadas às suas metas.', 'login-academia-da-educacao' ),
    ),
    array(
        'title'       => __( 'Comece agora mesmo', 'login-academia-da-educacao' ),
        'description' => __( 'Crie sua conta ou acesse para liberar turmas, simulados e o apoio do nosso treinador virtual 24h.', 'login-academia-da-educacao' ),
    ),
);

$illustration_presets = array( 'planner', 'location', 'insight', 'success' );

$settings = $plugin_instance ? $plugin_instance->get_slider_settings() : array();

$slides = isset( $settings['slides'] ) && is_array( $settings['slides'] ) ? $settings['slides'] : array();

if ( empty( $slides ) ) {
    $slides = $default_slides;
}

$total_slides = count( $slides );

if ( $total_slides < 1 ) {
    $total_slides = count( $default_slides );
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

$initial_slide    = isset( $slides[0] ) ? $slides[0] : array();
$initial_title    = isset( $initial_slide['title'] ) ? $initial_slide['title'] : '';
/* translators: {current}: current step number, {total}: total number of steps, {title}: current step title. */
$progress_template = __( 'Passo {current} de {total} — {title}', 'login-academia-da-educacao' );
$initial_progress  = str_replace(
    array( '{current}', '{total}', '{title}' ),
    array( 1, max( 1, $total_slides ), $initial_title ),
    $progress_template
);

$brand_logo = isset( $settings['brand_logo'] ) && $settings['brand_logo'] ? esc_url_raw( $settings['brand_logo'] ) : 'https://www.agenciadigitalsaopaulo.com.br/app/wp-content/uploads/2024/05/logo-footer.png';

$generate_id = static function ( $prefix ) {
    if ( function_exists( 'wp_unique_id' ) ) {
        return wp_unique_id( $prefix );
    }

    return uniqid( $prefix );
};

$slider_id = $generate_id( 'lae-onboarding-' );

$modal_enabled           = ! is_user_logged_in();
$modal_markup            = '';
$modal_title_id          = '';
$introducao_form_action  = '';
$redirect_after_auth     = isset( $settings['redirect_to'] ) && $settings['redirect_to'] ? esc_url_raw( $settings['redirect_to'] ) : esc_url_raw( home_url( user_trailingslashit( 'home' ) ) );

if ( $modal_enabled ) {
    $modal_title_id = wp_unique_id( 'lae-login-modal-title-' );

    if ( class_exists( 'LAE_Plugin' ) ) {
        $lae_pages = LAE_Plugin::get_instance()->get_pages();

        if ( $lae_pages instanceof LAE_Pages ) {
            $introducao_form_action = $lae_pages->get_account_page_url();

            if ( ! $introducao_form_action ) {
                $introducao_form_action = $lae_pages->get_page_url( 'minha-conta-academia' );
            }
        }
    }

    if ( ! $introducao_form_action ) {
        $introducao_form_action = home_url( user_trailingslashit( 'dados-conta' ) );
    }

    $introducao_form_action = esc_url_raw( $introducao_form_action );

    if ( class_exists( 'Introducao_Plugin' ) ) {
        $modal_markup = Introducao_Plugin::get_instance()->render_template(
            'page-perfil.php',
            array(
                'shortcode_tag'  => 'lae_onboarding_slider',
                'form_action'    => $introducao_form_action,
                'render_context' => 'modal',
                'redirect_url'   => $redirect_after_auth,
            )
        );
    }
}
?>
<section
    class="lae-onboarding lae-onboarding--mobile"
    data-lae-slider
    data-lae-progress-template="<?php echo esc_attr( $progress_template ); ?>"
    aria-label="<?php esc_attr_e( 'Apresentação da Academia da Comunicação', 'login-academia-da-educacao' ); ?>"
>
    <div class="lae-onboarding__content">
        <?php if ( $brand_logo ) : ?>
            <div class="lae-onboarding__brand">
                <img
                    src="<?php echo esc_url( $brand_logo ); ?>"
                    alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"
                    loading="lazy"
                />
            </div>
        <?php endif; ?>
        <div class="lae-onboarding__progress" role="tablist" aria-label="<?php esc_attr_e( 'Passos de boas-vindas', 'login-academia-da-educacao' ); ?>">
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
                    <span class="lae-onboarding__step-index"><?php echo esc_html( $index + 1 ); ?></span>
                    <span class="lae-onboarding__step-label"><?php esc_html_e( 'Passo', 'login-academia-da-educacao' ); ?> <?php echo esc_html( $index + 1 ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <p class="lae-onboarding__status" data-lae-progress-label role="status" aria-live="polite">
            <?php echo esc_html( $initial_progress ); ?>
        </p>

        <div class="lae-onboarding__slides">
            <?php foreach ( $slides as $index => $slide ) :
                $panel_id = $slider_id . '-step-' . $index . '-panel';
                $illustration_key = isset( $slide['illustration'] ) && $slide['illustration'] ? sanitize_key( $slide['illustration'] ) : $illustration_presets[ $index % count( $illustration_presets ) ];
                ?>
                <article
                    class="lae-onboarding__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
                    data-lae-slide
                    data-lae-index="<?php echo esc_attr( $index ); ?>"
                    data-lae-title="<?php echo esc_attr( $slide['title'] ); ?>"
                    id="<?php echo esc_attr( $panel_id ); ?>"
                    role="tabpanel"
                    aria-labelledby="<?php echo esc_attr( $slider_id . '-step-' . $index ); ?>"
                    aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
                    <?php echo 0 === $index ? '' : 'hidden'; ?>
                >
                    <div class="lae-onboarding__visual" aria-hidden="true">
                        <div class="lae-onboarding__device">
                            <div class="lae-onboarding__device-shell">
                                <div class="lae-onboarding__device-screen">
                                    <span class="lae-onboarding__step-chip"><?php esc_html_e( 'Passo', 'login-academia-da-educacao' ); ?> <?php echo esc_html( $index + 1 ); ?></span>
                                    <span class="lae-onboarding__illustration lae-onboarding__illustration--<?php echo esc_attr( sanitize_html_class( $illustration_key ) ); ?>" data-lae-illustration="<?php echo esc_attr( $illustration_key ); ?>"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lae-onboarding__text">
                        <h2 class="lae-onboarding__title"><?php echo esc_html( $slide['title'] ); ?></h2>
                        <p class="lae-onboarding__description"><?php echo esc_html( $slide['description'] ); ?></p>

                        <?php if ( $index === count( $slides ) - 1 ) : ?>
                            <div class="lae-onboarding__actions" data-lae-finish hidden>
                                <div class="lae-onboarding__cta-group">
                                    <button type="button" class="lae-onboarding__cta" data-lae-login-trigger data-lae-login-tab="login">
                                        <?php esc_html_e( 'Entrar', 'login-academia-da-educacao' ); ?>
                                    </button>
                                    <button type="button" class="lae-onboarding__cta lae-onboarding__cta--ghost" data-lae-login-trigger data-lae-login-tab="register">
                                        <?php esc_html_e( 'Criar conta', 'login-academia-da-educacao' ); ?>
                                    </button>
                                </div>
                                <span class="lae-onboarding__cta-hint"><?php esc_html_e( 'Escolha a melhor opção para você e continue logado em toda a plataforma.', 'login-academia-da-educacao' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="lae-onboarding__controls">
            <button type="button" class="lae-onboarding__control lae-onboarding__control--prev" data-lae-prev disabled aria-disabled="true">
                <span aria-hidden="true">&larr;</span>
                <span><?php esc_html_e( 'Voltar', 'login-academia-da-educacao' ); ?></span>
            </button>
            <button type="button" class="lae-onboarding__control lae-onboarding__control--next" data-lae-next aria-disabled="false">
                <span><?php esc_html_e( 'Próximo', 'login-academia-da-educacao' ); ?></span>
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </div>

    <?php if ( $modal_enabled ) : ?>
        <div class="lae-login-modal lae-login-modal--introducao" data-lae-login-modal aria-hidden="true" data-lae-login-flow="login" data-lae-login-awaiting="0">
            <div class="lae-login-modal__overlay" data-lae-login-close></div>
            <div class="lae-login-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_title_id ); ?>">
                <button type="button" class="lae-login-modal__close" data-lae-login-close aria-label="<?php esc_attr_e( 'Fechar painel de acesso', 'login-academia-da-educacao' ); ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="lae-login-modal__body lae-login-modal__body--introducao">
                    <span class="screen-reader-text" id="<?php echo esc_attr( $modal_title_id ); ?>"><?php esc_html_e( 'Formulário de acesso à Academia da Comunicação', 'login-academia-da-educacao' ); ?></span>
                    <?php if ( $modal_markup ) : ?>
                        <?php echo $modal_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php else : ?>
                        <p class="lae-login-empty"><?php esc_html_e( 'Não foi possível carregar o formulário de acesso. Recarregue a página e tente novamente.', 'login-academia-da-educacao' ); ?></p>
                    <?php endif; ?>
                    <div class="lae-login-feedback" data-lae-login-message role="status" aria-live="polite"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
