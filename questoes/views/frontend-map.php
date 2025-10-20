<?php
/**
 * Mind map view.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $data['mindmap'] ) ) {
    return;
}

$mindmap_root = $data['mindmap'];

if ( ! function_exists( 'questoes_render_node' ) ) {
    /**
     * Render node recursively.
     *
     * @param array                 $node Node data.
     * @param Questoes_Accessibility $accessibility Accessibility helper.
     */
    function questoes_render_node( $node, $accessibility ) {
        ?>
        <div class="questoes-node" data-level="<?php echo esc_attr( $node['level'] ); ?>" tabindex="0" aria-label="<?php echo esc_attr( $accessibility->get_node_label( $node ) ); ?>">
            <strong><?php echo esc_html( $node['label'] ); ?></strong>
            <?php if ( ! empty( $node['meta'] ) ) : ?>
                <ul class="questoes-node-meta">
                    <?php foreach ( $node['meta'] as $key => $value ) : ?>
                        <li><span><?php echo esc_html( ucfirst( $key ) ); ?>:</span> <?php echo esc_html( $value ); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ( ! empty( $node['children'] ) ) : ?>
                <div class="questoes-children">
                    <?php foreach ( $node['children'] as $child ) : ?>
                        <?php questoes_render_node( $child, $accessibility ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

questoes_render_node( $mindmap_root, $accessibility );
