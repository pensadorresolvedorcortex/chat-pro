<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$shortcode_label = isset( $shortcode_tag ) && $shortcode_tag ? '[' . $shortcode_tag . ']' : '';
?>
<section class="lae-page lae-page-pratica">
    <div class="lae-page-hero lae-page-hero--pratica">
        <div class="lae-page-hero__content">
            <span class="lae-page-hero__badge"><?php esc_html_e( 'Mão na massa', 'login-academia-da-educacao' ); ?></span>
            <h2><?php esc_html_e( 'Prática', 'login-academia-da-educacao' ); ?></h2>
            <p><?php esc_html_e( 'Resolva questões comentadas, simulados e trilhas práticas para consolidar o aprendizado e acompanhar sua evolução em tempo real.', 'login-academia-da-educacao' ); ?></p>
        </div>
        <div class="lae-page-hero__art" aria-hidden="true"></div>
    </div>
    <div class="lae-page-body">
        <?php echo do_shortcode( '[lae_pratica_cursos show_empty="yes"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php if ( $shortcode_label ) : ?>
            <p class="lae-shortcode-hint"><?php printf( esc_html__( 'Shortcode disponível: %s', 'login-academia-da-educacao' ), esc_html( $shortcode_label ) ); ?></p>
        <?php endif; ?>
    </div>
</section>
