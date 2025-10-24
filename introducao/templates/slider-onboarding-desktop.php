<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$plugin_instance = class_exists( 'Introducao_Plugin' ) ? Introducao_Plugin::get_instance() : null;
$default_slides  = array(
    array(
        'title'       => __( 'Planeje cada etapa com clareza', 'introducao' ),
        'description' => __( 'Visualize o caminho completo de aprendizagem com trilhas, notificações e metas ajustadas ao seu tempo.', 'introducao' ),
        'art'         => 'journey',
        'image'       => '',
    ),
    array(
        'title'       => __( 'Treine com apoio personalizado', 'introducao' ),
        'description' => __( 'Combine aulas, simulados e mentorias em um único painel que evolui conforme seus resultados.', 'introducao' ),
        'art'         => 'focus',
        'image'       => '',
    ),
    array(
        'title'       => __( 'Conecte-se à comunidade certa', 'introducao' ),
        'description' => __( 'Entre ou crie sua conta para participar dos eventos ao vivo, fóruns e dashboards em tempo real.', 'introducao' ),
        'art'         => 'community',
        'image'       => '',
    ),
);

$settings = $plugin_instance ? $plugin_instance->get_slider_settings() : array();
$slides   = isset( $settings['slides'] ) && is_array( $settings['slides'] ) ? $settings['slides'] : array();

if ( empty( $slides ) ) {
    $slides = $default_slides;
}

foreach ( $slides as $index => $slide ) {
    $fallback = isset( $default_slides[ $index ] ) ? $default_slides[ $index ] : $default_slides[ array_key_first( $default_slides ) ];

    if ( empty( $slide['title'] ) && isset( $fallback['title'] ) ) {
        $slides[ $index ]['title'] = $fallback['title'];
    }

    if ( empty( $slide['description'] ) && isset( $fallback['description'] ) ) {
        $slides[ $index ]['description'] = $fallback['description'];
    }

    if ( empty( $slide['art'] ) && isset( $fallback['art'] ) ) {
        $slides[ $index ]['art'] = $fallback['art'];
    }

    if ( empty( $slide['image'] ) && isset( $fallback['image'] ) ) {
        $slides[ $index ]['image'] = $fallback['image'];
    }
}

$total_slides     = count( $slides );
$initial_slide    = isset( $slides[0] ) ? $slides[0] : array();
$initial_title    = isset( $initial_slide['title'] ) ? $initial_slide['title'] : '';
/* translators: {current}: current step number, {total}: total number of steps, {title}: current step title. */
$progress_template = __( 'Passo {current} de {total} — {title}', 'introducao' );
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
<section
    class="lae-onboarding lae-onboarding--desktop"
    data-lae-slider
    data-lae-progress-template="<?php echo esc_attr( $progress_template ); ?>"
    aria-label="<?php esc_attr_e( 'Introdução à Academia da Comunicação', 'introducao' ); ?>"
>
    <div class="lae-onboarding__surface">
        <header class="lae-onboarding__header">
            <?php if ( $brand_logo ) : ?>
                <span class="lae-onboarding__brand">
                    <img
                        src="<?php echo esc_url( $brand_logo ); ?>"
                        alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"
                        loading="lazy"
                    />
                </span>
            <?php endif; ?>
        </header>

        <p class="lae-onboarding__status" data-lae-progress-label role="status" aria-live="polite">
            <?php echo esc_html( $initial_progress ); ?>
        </p>

        <div class="lae-onboarding__viewport">
            <?php foreach ( $slides as $index => $slide ) :
                $panel_id      = $slider_id . '-step-' . $index . '-panel';
                $control_id    = $slider_id . '-step-' . $index;
                $art_preset    = isset( $slide['art'] ) && $slide['art'] ? sanitize_html_class( $slide['art'] ) : 'journey';
                $image_url     = isset( $slide['image'] ) && $slide['image'] ? esc_url( $slide['image'] ) : '';
                $is_active     = 0 === $index;
                $title         = isset( $slide['title'] ) ? $slide['title'] : '';
                $description   = isset( $slide['description'] ) ? $slide['description'] : '';
                ?>
                <article
                    class="lae-onboarding__slide<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-lae-slide
                    data-lae-index="<?php echo esc_attr( $index ); ?>"
                    data-lae-title="<?php echo esc_attr( $title ); ?>"
                    id="<?php echo esc_attr( $panel_id ); ?>"
                    role="tabpanel"
                    aria-labelledby="<?php echo esc_attr( $control_id ); ?>"
                    aria-hidden="<?php echo $is_active ? 'false' : 'true'; ?>"
                    <?php echo $is_active ? '' : 'hidden'; ?>
                >
                    <div class="lae-onboarding__media" aria-hidden="true">
                        <?php if ( $image_url ) : ?>
                            <img
                                src="<?php echo esc_url( $image_url ); ?>"
                                alt=""
                                class="lae-onboarding__image"
                                loading="lazy"
                            />
                        <?php else : ?>
                            <span class="lae-onboarding__placeholder lae-onboarding__placeholder--<?php echo esc_attr( $art_preset ); ?>"></span>
                        <?php endif; ?>
                    </div>

                    <div class="lae-onboarding__body">
                        <h2 class="lae-onboarding__title"><?php echo esc_html( $title ); ?></h2>
                        <p class="lae-onboarding__description"><?php echo esc_html( $description ); ?></p>

                        <?php if ( $index === $total_slides - 1 ) : ?>
                            <div class="lae-onboarding__finish" data-lae-finish hidden aria-hidden="true">
                                <div class="lae-onboarding__cta-group">
                                    <button type="button" class="lae-onboarding__cta" data-lae-login-trigger data-lae-login-tab="login">
                                        <?php esc_html_e( 'Entrar', 'introducao' ); ?>
                                    </button>
                                    <button type="button" class="lae-onboarding__cta lae-onboarding__cta--ghost" data-lae-login-trigger data-lae-login-tab="register">
                                        <?php esc_html_e( 'Criar conta', 'introducao' ); ?>
                                    </button>
                                </div>
                                <p class="lae-onboarding__hint"><?php esc_html_e( 'Ao concluir, abriremos o painel para você continuar exatamente de onde parou.', 'introducao' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <footer class="lae-onboarding__footer">
            <button
                type="button"
                class="lae-onboarding__control lae-onboarding__control--prev"
                data-lae-prev
                aria-label="<?php esc_attr_e( 'Etapa anterior', 'introducao' ); ?>"
                disabled
                aria-disabled="true"
            >
                <?php esc_html_e( 'Voltar', 'introducao' ); ?>
            </button>

            <div class="lae-onboarding__dots" role="tablist" aria-label="<?php esc_attr_e( 'Controle de etapas', 'introducao' ); ?>">
                <?php foreach ( $slides as $index => $slide ) :
                    $control_id = $slider_id . '-step-' . $index;
                    $panel_id   = $slider_id . '-step-' . $index . '-panel';
                    $is_active  = 0 === $index;
                    ?>
                    <button
                        type="button"
                        class="lae-onboarding__dot<?php echo $is_active ? ' is-active' : ''; ?>"
                        data-lae-step
                        data-lae-target="<?php echo esc_attr( $index ); ?>"
                        id="<?php echo esc_attr( $control_id ); ?>"
                        role="tab"
                        aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                        tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
                    >
                        <span class="screen-reader-text"><?php printf( esc_html__( 'Ir para o passo %d', 'introducao' ), $index + 1 ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <button
                type="button"
                class="lae-onboarding__control lae-onboarding__control--next"
                data-lae-next
                aria-label="<?php esc_attr_e( 'Próxima etapa', 'introducao' ); ?>"
            >
                <?php esc_html_e( 'Próximo', 'introducao' ); ?>
            </button>
        </footer>
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
