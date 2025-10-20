<?php
/**
 * Tabs wrapper.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$available_views = array();

if ( in_array( $mode, array( 'ambos', 'both', 'mapa' ), true ) && ! empty( $data['mindmap'] ) ) {
    $available_views['mapa'] = __( 'Mapa Mental', 'questoes' );
}

if ( in_array( $mode, array( 'ambos', 'both', 'organograma' ), true ) && ! empty( $data['orgchart'] ) ) {
    $available_views['organograma'] = __( 'Organograma', 'questoes' );
}

if ( empty( $available_views ) ) {
    echo '<div class="questoes-empty">' . esc_html__( 'Nenhum dado disponível.', 'questoes' ) . '</div>';
    return;
}

$first_key        = key( $available_views );
$container_attrs  = $accessibility->get_container_attrs( 'tabs' );
$palette          = isset( $palette ) && is_array( $palette ) ? $palette : questoes_get_default_palette();
$style_attributes = sprintf(
    '--questoes-primary:%1$s;--questoes-secondary:%2$s;--questoes-light:%3$s;--questoes-neutral:%4$s;--questoes-neutral-strong:%5$s;',
    esc_attr( $palette['primary'] ),
    esc_attr( $palette['secondary'] ),
    esc_attr( $palette['light'] ),
    esc_attr( $palette['neutral'] ),
    esc_attr( $palette['neutral-2'] )
);
?>
<div class="questoes-component" style="<?php echo esc_attr( $style_attributes ); ?>" <?php foreach ( $container_attrs as $attr => $value ) { printf( '%s="%s" ', esc_attr( $attr ), esc_attr( $value ) ); } ?>>
    <header class="questoes-header">
        <h2><?php echo esc_html( $data['title'] ); ?></h2>
        <div class="questoes-controls">
            <button type="button" class="questoes-button" data-action="zoom-in"><?php esc_html_e( 'Zoom +', 'questoes' ); ?></button>
            <button type="button" class="questoes-button" data-action="zoom-out"><?php esc_html_e( 'Zoom –', 'questoes' ); ?></button>
            <button type="button" class="questoes-button" data-action="center"><?php esc_html_e( 'Centralizar', 'questoes' ); ?></button>
            <button type="button" class="questoes-button" data-action="print"><?php esc_html_e( 'Imprimir', 'questoes' ); ?></button>
        </div>
    </header>

    <?php if ( count( $available_views ) > 1 ) : ?>
        <div class="questoes-tabs" role="tablist">
            <?php foreach ( $available_views as $key => $label ) : ?>
                <button
                    type="button"
                    class="questoes-tab"
                    role="tab"
                    data-target="questoes-view-<?php echo esc_attr( $key ); ?>"
                    aria-selected="<?php echo $key === $first_key ? 'true' : 'false'; ?>">
                    <?php echo esc_html( $label ); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="questoes-views">
        <?php foreach ( $available_views as $key => $label ) :
            $attrs         = $accessibility->get_container_attrs( $key === 'mapa' ? 'mindmap' : 'orgchart' );
            $attrs['id']   = 'questoes-view-' . $key;
            $attrs['class'] = 'questoes-view' . ( $key === $first_key ? ' is-active' : '' );
            ?>
            <div <?php foreach ( $attrs as $attr => $value ) { printf( '%s="%s" ', esc_attr( $attr ), esc_attr( $value ) ); } ?>>
                <?php
                if ( 'mapa' === $key ) {
                    include QUESTOES_PLUGIN_DIR . 'views/frontend-map.php';
                } else {
                    include QUESTOES_PLUGIN_DIR . 'views/frontend-org.php';
                }
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
