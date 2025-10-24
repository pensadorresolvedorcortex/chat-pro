<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$pages               = isset( $pages ) && is_array( $pages ) ? $pages : array();
$menu_shortcode      = isset( $menu_shortcode ) ? $menu_shortcode : '[introducao_user_menu]';
$slider_settings     = isset( $slider_settings ) && is_array( $slider_settings ) ? $slider_settings : array();
$default_slider_copy = isset( $default_slider_copy ) && is_array( $default_slider_copy ) ? $default_slider_copy : array();

$brand_logo  = isset( $slider_settings['brand_logo'] ) ? $slider_settings['brand_logo'] : '';
$redirect_to = isset( $slider_settings['redirect_to'] ) ? $slider_settings['redirect_to'] : '';
$slides      = isset( $slider_settings['slides'] ) && is_array( $slider_settings['slides'] ) ? $slider_settings['slides'] : array();
?>
<div class="lae-admin-wrap">
    <div class="lae-admin-hero">
        <h1><?php esc_html_e( 'Introdução Academia da Educação', 'introducao' ); ?></h1>
        <p><?php esc_html_e( 'Gerencie o menu de usuário, o onboarding e as páginas criadas automaticamente pelo plugin.', 'introducao' ); ?></p>
    </div>

    <section class="lae-admin-section lae-admin-section--slider">
        <h2><?php esc_html_e( 'Onboarding Slider', 'introducao' ); ?></h2>
        <p class="lae-admin-note">
            <?php esc_html_e( 'Personalize a experiência mobile-first exibida antes do login. Todas as alterações impactam também o popup de entrada sincronizado com o restante do site.', 'introducao' ); ?>
        </p>

        <form method="post" action="options.php" class="lae-admin-form">
            <?php settings_fields( 'introducao_slider' ); ?>
            <div class="lae-admin-fields">
                <div class="lae-admin-field">
                    <label for="introducao-slider-logo"><?php esc_html_e( 'Logo principal', 'introducao' ); ?></label>
                    <input type="url" id="introducao-slider-logo" name="<?php echo esc_attr( Introducao_Plugin::SLIDER_OPTION ); ?>[brand_logo]" value="<?php echo esc_attr( $brand_logo ); ?>" placeholder="https://..." class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Use uma imagem em PNG ou SVG com fundo transparente para o cabeçalho.', 'introducao' ); ?></p>
                </div>

                <div class="lae-admin-field">
                    <label for="introducao-slider-redirect"><?php esc_html_e( 'Redirecionamento pós-login', 'introducao' ); ?></label>
                    <input type="url" id="introducao-slider-redirect" name="<?php echo esc_attr( Introducao_Plugin::SLIDER_OPTION ); ?>[redirect_to]" value="<?php echo esc_attr( $redirect_to ); ?>" placeholder="<?php echo esc_attr( home_url( '/home/' ) ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'URL para onde o usuário é enviado após concluir login ou cadastro pelo slider.', 'introducao' ); ?></p>
                </div>
            </div>

            <div class="lae-admin-slides">
                <?php foreach ( $slides as $index => $slide ) :
                    $default = isset( $default_slider_copy[ $index ] ) ? $default_slider_copy[ $index ] : array();
                    $title = isset( $slide['title'] ) ? $slide['title'] : '';
                    $description = isset( $slide['description'] ) ? $slide['description'] : '';
                    ?>
                    <fieldset class="lae-admin-slide">
                        <legend><?php printf( esc_html__( 'Passo %d', 'introducao' ), $index + 1 ); ?></legend>
                        <label for="introducao-slide-title-<?php echo esc_attr( $index ); ?>" class="screen-reader-text"><?php esc_html_e( 'Título', 'introducao' ); ?></label>
                        <input
                            type="text"
                            id="introducao-slide-title-<?php echo esc_attr( $index ); ?>"
                            name="<?php echo esc_attr( Introducao_Plugin::SLIDER_OPTION ); ?>[slides][<?php echo esc_attr( $index ); ?>][title]"
                            value="<?php echo esc_attr( $title ); ?>"
                            placeholder="<?php echo esc_attr( isset( $default['title'] ) ? $default['title'] : '' ); ?>"
                            class="regular-text"
                        />
                        <label for="introducao-slide-description-<?php echo esc_attr( $index ); ?>" class="screen-reader-text"><?php esc_html_e( 'Descrição', 'introducao' ); ?></label>
                        <textarea
                            id="introducao-slide-description-<?php echo esc_attr( $index ); ?>"
                            name="<?php echo esc_attr( Introducao_Plugin::SLIDER_OPTION ); ?>[slides][<?php echo esc_attr( $index ); ?>][description]"
                            rows="3"
                        ><?php echo esc_textarea( $description ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Sugestão padrão:', 'introducao' ); ?>
                            <em><?php echo esc_html( isset( $default['description'] ) ? $default['description'] : '' ); ?></em>
                        </p>
                    </fieldset>
                <?php endforeach; ?>
            </div>

            <?php submit_button( __( 'Salvar alterações', 'introducao' ) ); ?>
        </form>
    </section>

    <section class="lae-admin-section">
        <h2><?php esc_html_e( 'Shortcode do menu principal', 'introducao' ); ?></h2>
        <p class="lae-admin-code"><?php echo esc_html( $menu_shortcode ); ?></p>
        <p class="lae-admin-note">
            <?php esc_html_e( 'Use este shortcode em qualquer página ou widget Elementor para exibir o menu com notificações e dropdown.', 'introducao' ); ?>
        </p>
        <ul class="lae-admin-attrs">
            <li><strong>show_notifications</strong>: yes | no</li>
            <li><strong>notification_count</strong>: 0..N</li>
            <li><strong>greeting</strong>: <?php esc_html_e( 'Texto de saudação personalizado', 'introducao' ); ?></li>
        </ul>
    </section>

    <section class="lae-admin-section">
        <h2><?php esc_html_e( 'Páginas criadas automaticamente', 'introducao' ); ?></h2>
        <div class="lae-admin-grid">
            <?php foreach ( $pages as $page ) :
                $title     = isset( $page['title'] ) ? $page['title'] : '';
                $shortcode = isset( $page['shortcode'] ) ? $page['shortcode'] : '';
                $url       = isset( $page['url'] ) ? $page['url'] : '';
                ?>
                <article class="lae-admin-card">
                    <h3><?php echo esc_html( $title ); ?></h3>
                    <p class="lae-admin-code"><?php echo esc_html( $shortcode ); ?></p>
                    <?php if ( $url ) : ?>
                        <p><a class="button button-secondary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver página', 'introducao' ); ?></a></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lae-admin-section">
        <h2><?php esc_html_e( 'Dicas rápidas', 'introducao' ); ?></h2>
        <ul class="lae-admin-tips">
            <li><?php esc_html_e( 'Edite cada página com o Elementor e mantenha o shortcode correspondente no conteúdo.', 'introducao' ); ?></li>
            <li><?php esc_html_e( 'Integre seu contador de notificações usando o filtro introducao_notification_count.', 'introducao' ); ?></li>
            <li><?php esc_html_e( 'Personalize saudação, avatar e itens do menu através dos filtros disponíveis.', 'introducao' ); ?></li>
        </ul>
    </section>
</div>

<style>
.lae-admin-wrap {
    max-width: 960px;
    margin: 32px auto;
    padding: 0 16px 48px;
    font-family: "Inter", "Segoe UI", Roboto, sans-serif;
}


.lae-admin-hero {
    background: linear-gradient(135deg, #6a5ae0, #bf83ff);
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 18px 40px rgba(106, 90, 224, 0.22);
    text-align: center;
    color: #ffffff;
}

.lae-admin-hero h1 {
    margin: 0 0 8px;
    font-size: 28px;
}

.lae-admin-hero p {
    margin: 0;
    font-size: 15px;
    color: rgba(255, 255, 255, 0.86);
}


.lae-admin-section {
    margin-top: 36px;
    background: #ffffff;
    border-radius: 18px;
    padding: 28px;
    border: 1px solid rgba(106, 90, 224, 0.15);
    box-shadow: 0 12px 28px rgba(106, 90, 224, 0.14);
}

.lae-admin-section--slider {
    border: 1px solid rgba(111, 80, 240, 0.25);
    background: rgba(255, 255, 255, 0.98);
}

.lae-admin-section h2 {
    margin-top: 0;
    color: #2c2c58;
}

.lae-admin-form {
    margin-top: 22px;
    display: flex;
    flex-direction: column;
    gap: 28px;
}

.lae-admin-fields {
    display: grid;
    gap: 22px;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.lae-admin-field label {
    font-weight: 600;
    color: #25254a;
    display: block;
    margin-bottom: 6px;
}

.lae-admin-field input[type='url'],
.lae-admin-slide input[type='text'],
.lae-admin-slide textarea {
    width: 100%;
    border-radius: 14px;
    border: 1px solid rgba(111, 98, 241, 0.28);
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: inset 0 1px 2px rgba(20, 20, 43, 0.06);
    font-size: 14px;
    color: #1f1f35;
}

.lae-admin-field input[type='url']:focus,
.lae-admin-slide input[type='text']:focus,
.lae-admin-slide textarea:focus {
    outline: none;
    border-color: #6a5ae0;
    box-shadow: 0 0 0 3px rgba(106, 90, 224, 0.2);
}

.lae-admin-slides {
    display: grid;
    gap: 18px;
}

.lae-admin-slide {
    border: 1px solid rgba(106, 90, 224, 0.18);
    border-radius: 18px;
    padding: 18px 20px 22px;
    background: rgba(248, 246, 255, 0.8);
    box-shadow: 0 14px 26px rgba(106, 90, 224, 0.12);
}

.lae-admin-slide legend {
    font-weight: 600;
    color: #2c2c58;
    padding: 0 8px;
}

.lae-admin-slide textarea {
    min-height: 96px;
    resize: vertical;
    margin-top: 12px;
}


.lae-admin-code {
    display: inline-block;
    background: rgba(106, 90, 224, 0.12);
    color: #6a5ae0;
    padding: 6px 12px;
    border-radius: 999px;
    font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
    font-size: 13px;
    margin: 8px 0;
}

.lae-admin-note {
    color: #4b4b6f;
    margin-bottom: 18px;
}

.lae-admin-attrs,
.lae-admin-tips {
    margin: 0;
    padding-left: 18px;
    color: #3a3960;
}


.lae-admin-grid {
    display: grid;
    gap: 18px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.lae-admin-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 20px;
    border: 1px solid rgba(106, 90, 224, 0.14);
    box-shadow: 0 10px 24px rgba(106, 90, 224, 0.14);
}

.lae-admin-card h3 {
    margin-top: 0;
    color: #29275c;
}

.lae-admin-card .button {
    margin-top: 12px;
}

@media (max-width: 600px) {
    .lae-admin-hero {
        padding: 24px;
    }

    .lae-admin-section {
        padding: 22px;
    }

    .lae-admin-fields {
        grid-template-columns: 1fr;
    }

    .lae-admin-slide {
        padding: 16px;
    }
}
</style>
