<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$slides = array(
    array(
        'title'       => __( 'Organize sua preparação', 'introducao' ),
        'description' => __( 'Conheça o plano de estudos inteligente da Academia da Comunicação e centralize teoria, prática e simulados em um só lugar.', 'introducao' ),
    ),
    array(
        'title'       => __( 'Acompanhe o seu progresso', 'introducao' ),
        'description' => __( 'Receba lembretes personalizados, veja sua evolução em tempo real e desbloqueie recomendações alinhadas às suas metas.', 'introducao' ),
    ),
    array(
        'title'       => __( 'Comece agora mesmo', 'introducao' ),
        'description' => __( 'Crie sua conta ou acesse para liberar turmas, simulados e o apoio do nosso treinador virtual 24h.', 'introducao' ),
    ),
);

$generate_id = static function ( $prefix ) {
    if ( function_exists( 'wp_unique_id' ) ) {
        return wp_unique_id( $prefix );
    }

    return uniqid( $prefix );
};

$slider_id = $generate_id( 'introducao-onboarding-' );

$modal_enabled        = ! is_user_logged_in();
$modal_markup         = '';
$modal_title_id       = '';
$form_action_url      = '';
$redirect_after_auth  = esc_url_raw( home_url( user_trailingslashit( 'home' ) ) );

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
                'shortcode_tag'  => 'lae_onboarding_slider',
                'form_action'    => $form_action_url,
                'render_context' => 'modal',
                'redirect_url'   => $redirect_after_auth,
            )
        );
    }
}
?>
<section class="lae-onboarding" data-lae-slider aria-label="<?php esc_attr_e( 'Apresentação da Academia da Comunicação', 'introducao' ); ?>">
    <div class="lae-onboarding__content">
        <div class="lae-onboarding__brand">
            <img
                src="<?php echo esc_url( 'https://www.agenciadigitalsaopaulo.com.br/app/wp-content/uploads/2024/05/logo-footer.png' ); ?>"
                alt="<?php esc_attr_e( 'Agência Digital São Paulo', 'introducao' ); ?>"
                loading="lazy"
            />
        </div>
        <div class="lae-onboarding__progress" role="tablist" aria-label="<?php esc_attr_e( 'Passos de boas-vindas', 'introducao' ); ?>">
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
                    <span class="lae-onboarding__step-label"><?php esc_html_e( 'Passo', 'introducao' ); ?> <?php echo esc_html( $index + 1 ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>

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
                >
                    <h2 class="lae-onboarding__title"><?php echo esc_html( $slide['title'] ); ?></h2>
                    <p class="lae-onboarding__description"><?php echo esc_html( $slide['description'] ); ?></p>

                    <?php if ( 2 === $index ) : ?>
                        <div class="lae-onboarding__actions" data-lae-finish>
                            <button type="button" class="lae-onboarding__cta" data-lae-login-trigger>
                                <?php esc_html_e( 'Entrar ou criar conta', 'introducao' ); ?>
                            </button>
                            <span class="lae-onboarding__cta-hint"><?php esc_html_e( 'Leva menos de 1 minuto para começar.', 'introducao' ); ?></span>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="lae-onboarding__controls">
            <button type="button" class="lae-onboarding__control lae-onboarding__control--prev" data-lae-prev>
                <span aria-hidden="true">&larr;</span>
                <span><?php esc_html_e( 'Voltar', 'introducao' ); ?></span>
            </button>
            <button type="button" class="lae-onboarding__control lae-onboarding__control--next" data-lae-next>
                <span><?php esc_html_e( 'Próximo', 'introducao' ); ?></span>
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </div>

    <div class="lae-onboarding__visual" aria-hidden="true">
        <div class="lae-onboarding__visual-glow"></div>
        <svg class="lae-onboarding__visual-owl" viewBox="0 0 320 340" role="img" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="lae-owl-gradient" x1="50%" x2="50%" y1="0%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.95" />
                    <stop offset="100%" stop-color="#d7ccff" stop-opacity="0.75" />
                </linearGradient>
                <linearGradient id="lae-owl-accent" x1="0%" x2="100%" y1="0%" y2="100%">
                    <stop offset="0%" stop-color="#6a5ae0" />
                    <stop offset="100%" stop-color="#bf83ff" />
                </linearGradient>
            </defs>
            <g fill="none" fill-rule="evenodd">
                <path d="M160 32c68 0 124 52 124 116 0 56-40 90-88 136-20 20-52 20-72 0-48-46-88-80-88-136 0-64 56-116 124-116Z" fill="url(#lae-owl-gradient)" />
                <path d="M160 52c-48 0-86 36-86 80 0 40 32 72 72 72h28c40 0 72-32 72-72 0-44-38-80-86-80Z" fill="#ffffff" opacity="0.82" />
                <g transform="translate(78 104)">
                    <circle cx="42" cy="42" r="42" fill="#6a5ae0" opacity="0.9" />
                    <circle cx="122" cy="42" r="42" fill="#6a5ae0" opacity="0.9" />
                    <circle cx="42" cy="42" r="20" fill="#fff" />
                    <circle cx="122" cy="42" r="20" fill="#fff" />
                    <circle cx="48" cy="44" r="10" fill="#1f1f35" />
                    <circle cx="116" cy="44" r="10" fill="#1f1f35" />
                    <path d="M82 28c10 0 18 8 18 18s-8 18-18 18-18-8-18-18 8-18 18-18Z" fill="#bf83ff" opacity="0.7" />
                    <path d="M80 94c18 0 34 10 40 26 4 10-2 20-12 20H52c-10 0-16-10-12-20 6-16 22-26 40-26Z" fill="url(#lae-owl-accent)" />
                </g>
                <path d="M94 210c16 42 40 74 66 74s50-32 66-74" stroke="url(#lae-owl-accent)" stroke-width="18" stroke-linecap="round" opacity="0.75" />
            </g>
        </svg>
        <div class="lae-onboarding__visual-base"></div>
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
