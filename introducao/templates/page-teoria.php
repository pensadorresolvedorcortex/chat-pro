<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$shortcode_label = isset( $shortcode_tag ) && $shortcode_tag ? '[' . $shortcode_tag . ']' : '';
?>
<section class="lae-page lae-page-teoria">
    <h2><?php esc_html_e( 'Teoria', 'introducao' ); ?></h2>
    <p><?php esc_html_e( 'Utilize esta página para reunir conteúdos teóricos e materiais de estudo.', 'introducao' ); ?></p>
    <?php if ( $shortcode_label ) : ?>
        <p class="lae-shortcode-hint"><?php printf( esc_html__( 'Shortcode disponível: %s', 'introducao' ), esc_html( $shortcode_label ) ); ?></p>
    <?php endif; ?>
</section>
