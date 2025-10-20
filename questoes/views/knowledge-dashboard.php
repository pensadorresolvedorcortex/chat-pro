<?php
/**
 * Knowledge dashboard view.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$palette        = wp_parse_args( (array) $palette, questoes_get_default_palette() );
$knowledge_json = wp_json_encode( $knowledge_data );
$knowledge_json = $knowledge_json ? $knowledge_json : wp_json_encode( array() );
$stage_classes  = array( 'questoes-stage', 'questoes-knowledge' );

if ( ! empty( $palette['primary'] ) ) {
    $stage_classes[] = 'has-palette';
}
?>
<section class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $stage_classes ) ) ); ?>" data-component="knowledge" id="<?php echo esc_attr( $container_id ); ?>">
    <div class="questoes-shell questoes-shell--wide">
        <header class="questoes-knowledge__header">
            <span class="questoes-knowledge__eyebrow"><?php esc_html_e( 'Meus estudos', 'questoes' ); ?></span>
            <h2 class="questoes-knowledge__title"><?php echo esc_html( $title ); ?></h2>
            <?php if ( ! empty( $description ) ) : ?>
                <p class="questoes-knowledge__description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </header>

        <?php if ( ! empty( $extra_content ) ) : ?>
            <div class="questoes-knowledge__callout"><?php echo $extra_content; ?></div>
        <?php endif; ?>

        <div class="questoes-knowledge__surface questoes-surface">
            <div class="questoes-knowledge__sections">
                <section class="questoes-knowledge__section" data-type="questions">
                    <header class="questoes-knowledge__section-header">
                        <h3 class="questoes-knowledge__section-title"><?php esc_html_e( 'Questões em andamento', 'questoes' ); ?></h3>
                        <span class="questoes-knowledge__counter" data-role="knowledge-count" data-type="questions">0</span>
                    </header>
                    <div class="questoes-knowledge__list" data-role="knowledge-list" data-type="questions"></div>
                </section>
                <section class="questoes-knowledge__section" data-type="courses">
                    <header class="questoes-knowledge__section-header">
                        <h3 class="questoes-knowledge__section-title"><?php esc_html_e( 'Cursos em andamento', 'questoes' ); ?></h3>
                        <span class="questoes-knowledge__counter" data-role="knowledge-count" data-type="courses">0</span>
                    </header>
                    <div class="questoes-knowledge__list" data-role="knowledge-list" data-type="courses"></div>
                </section>
            </div>

            <p class="questoes-knowledge__empty" data-role="knowledge-empty"><?php echo esc_html( $empty_message ); ?></p>
        </div>
    </div>

    <script type="application/json" class="questoes-knowledge__initial">
        <?php echo $knowledge_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </script>
</section>
