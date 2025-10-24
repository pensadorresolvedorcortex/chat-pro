<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$shortcode_label = isset( $shortcode_tag ) && $shortcode_tag ? '[' . $shortcode_tag . ']' : '';
?>
<section class="lae-page lae-page-suporte">
    <h2><?php esc_html_e( 'Suporte', 'introducao' ); ?></h2>
    <p><?php esc_html_e( 'Disponibilize canais de contato, FAQ e tutoriais para auxiliar os alunos.', 'introducao' ); ?></p>
    <?php if ( $shortcode_label ) : ?>
        <p class="lae-shortcode-hint"><?php printf( esc_html__( 'Shortcode disponível: %s', 'introducao' ), esc_html( $shortcode_label ) ); ?></p>
    <?php endif; ?>
</section>
