<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$pages          = isset( $pages ) && is_array( $pages ) ? $pages : array();
$menu_shortcode = isset( $menu_shortcode ) ? $menu_shortcode : '[lae_user_menu]';
?>
<div class="lae-admin-wrap">
    <div class="lae-admin-hero">
        <h1><?php esc_html_e( 'Login Academia da Educação', 'login-academia-da-educacao' ); ?></h1>
        <p><?php esc_html_e( 'Gerencie o menu de usuário e os atalhos criados automaticamente pelo plugin.', 'login-academia-da-educacao' ); ?></p>
    </div>

    <section class="lae-admin-section">
        <h2><?php esc_html_e( 'Shortcode do menu principal', 'login-academia-da-educacao' ); ?></h2>
        <p class="lae-admin-code"><?php echo esc_html( $menu_shortcode ); ?></p>
        <p class="lae-admin-note">
            <?php esc_html_e( 'Use este shortcode em qualquer página ou widget Elementor para exibir o menu com notificações e dropdown.', 'login-academia-da-educacao' ); ?>
        </p>
        <ul class="lae-admin-attrs">
            <li><strong>show_notifications</strong>: yes | no</li>
            <li><strong>notification_count</strong>: 0..N</li>
            <li><strong>greeting</strong>: <?php esc_html_e( 'Texto de saudação personalizado', 'login-academia-da-educacao' ); ?></li>
        </ul>
    </section>

    <section class="lae-admin-section">
        <h2><?php esc_html_e( 'Páginas criadas automaticamente', 'login-academia-da-educacao' ); ?></h2>
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
                        <p><a class="button button-secondary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver página', 'login-academia-da-educacao' ); ?></a></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="lae-admin-section">
        <h2><?php esc_html_e( 'Dicas rápidas', 'login-academia-da-educacao' ); ?></h2>
        <ul class="lae-admin-tips">
            <li><?php esc_html_e( 'Edite cada página com o Elementor e mantenha o shortcode correspondente no conteúdo.', 'login-academia-da-educacao' ); ?></li>
            <li><?php esc_html_e( 'Integre seu contador de notificações usando o filtro lae_notification_count.', 'login-academia-da-educacao' ); ?></li>
            <li><?php esc_html_e( 'Personalize saudação, avatar e itens do menu através dos filtros disponíveis.', 'login-academia-da-educacao' ); ?></li>
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
    background: linear-gradient(135deg, rgba(106, 90, 224, 0.18), rgba(191, 131, 255, 0.16));
    border: 1px solid rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    padding: 32px;
    box-shadow: 0 20px 45px rgba(106, 90, 224, 0.16);
    text-align: center;
    color: #1f1f35;
}

.lae-admin-hero h1 {
    margin: 0 0 8px;
    font-size: 28px;
}

.lae-admin-hero p {
    margin: 0;
    font-size: 15px;
    color: #4b4b6f;
}

.lae-admin-section {
    margin-top: 36px;
    background: rgba(255, 255, 255, 0.88);
    border-radius: 18px;
    padding: 28px;
    border: 1px solid rgba(106, 90, 224, 0.18);
    box-shadow: 0 12px 30px rgba(31, 31, 61, 0.12);
}

.lae-admin-section h2 {
    margin-top: 0;
    color: #2c2c58;
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
    background: rgba(255, 255, 255, 0.94);
    border-radius: 18px;
    padding: 20px;
    border: 1px solid rgba(106, 90, 224, 0.14);
    box-shadow: 0 12px 28px rgba(106, 90, 224, 0.12);
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
}
</style>
