<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$shortcode_label = isset( $shortcode_tag ) && $shortcode_tag ? '[' . $shortcode_tag . ']' : '';
?>
<section class="lae-page lae-page-teoria">
    <div class="lae-page-hero lae-page-hero--teoria">
        <div class="lae-page-hero__content">
            <span class="lae-page-hero__badge"><?php esc_html_e( 'Trilhas de estudo', 'login-academia-da-educacao' ); ?></span>
            <h2><?php esc_html_e( 'Teoria', 'login-academia-da-educacao' ); ?></h2>
            <p><?php esc_html_e( 'Aprenda com aulas estruturadas, resumos e materiais selecionados para dominar cada disciplina antes de partir para os simulados.', 'login-academia-da-educacao' ); ?></p>
        </div>
        <div class="lae-page-hero__art" aria-hidden="true"></div>
    </div>
    <div class="lae-page-body">
        <?php echo do_shortcode( '[lae_teoria_cursos show_empty="yes"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php if ( $shortcode_label ) : ?>
            <p class="lae-shortcode-hint"><?php printf( esc_html__( 'Shortcode disponível: %s', 'login-academia-da-educacao' ), esc_html( $shortcode_label ) ); ?></p>
        <?php endif; ?>
    </div>
</section>
