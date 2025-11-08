<?php
namespace Juntaplay\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode para selecionar a relação com o administrador.
 */
class GroupRelationship {
    /**
     * Slug do shortcode.
     */
    const SHORTCODE = 'juntaplay_group_relationship';

    /**
     * Opções permitidas de relacionamento.
     *
     * @return array
     */
    public static function get_relationship_options() {
        return array(
            'Moramos Juntos',
            'Família',
            'Amigos',
            'Colegas de Trabalho',
        );
    }

    /**
     * Registra o shortcode no WordPress.
     */
    public static function register() {
        add_shortcode( self::SHORTCODE, array( __CLASS__, 'render' ) );
    }

    /**
     * Renderiza o markup do shortcode.
     *
     * @return string
     */
    public static function render() {
        if ( wp_style_is( 'juntaplay-frontend', 'registered' ) ) {
            wp_enqueue_style( 'juntaplay-frontend' );
        }

        $options = self::get_relationship_options();

        ob_start();
        ?>
        <div class="jplay-field jplay-field--relationship">
            <label for="jplay_relationship_admin" class="jplay-label">
                <?php esc_html_e( 'Relação com o administrador', 'juntaplay' ); ?>
                <span class="jplay-required" aria-hidden="true">*</span>
            </label>
            <select id="jplay_relationship_admin" name="jplay_relationship_admin" required aria-required="true" class="jplay-input">
                <option value="">
                    <?php esc_html_e( 'Selecione…', 'juntaplay' ); ?>
                </option>
                <?php foreach ( $options as $option ) : ?>
                    <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php

        return ob_get_clean();
    }
}
