<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$shortcode_label = isset( $shortcode_tag ) && $shortcode_tag ? '[' . $shortcode_tag . ']' : '';
?>
<section class="lae-page lae-page-planos">
    <h2><?php esc_html_e( 'Planos', 'login-academia-da-educacao' ); ?></h2>
    <p><?php esc_html_e( 'Apresente ofertas, pacotes e opções de assinatura aos alunos.', 'login-academia-da-educacao' ); ?></p>
    <?php if ( $shortcode_label ) : ?>
        <p class="lae-shortcode-hint"><?php printf( esc_html__( 'Shortcode disponível: %s', 'login-academia-da-educacao' ), esc_html( $shortcode_label ) ); ?></p>
    <?php endif; ?>
</section>
