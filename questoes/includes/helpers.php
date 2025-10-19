<?php
/**
 * General helper functions.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function questoes_get_default_palette() {
    return array(
        'primary'   => '#242142',
        'secondary' => '#bf83ff',
        'light'     => '#c3e3f3',
        'neutral'   => '#f1f5f9',
        'neutral-2' => '#e5e7eb',
    );
}

function questoes_get_option( $key, $default = false ) {
    $options = get_option( 'questoes_settings', array() );

    if ( isset( $options[ $key ] ) ) {
        return $options[ $key ];
    }

    return $default;
}

function questoes_update_option( $key, $value ) {
    $options         = get_option( 'questoes_settings', array() );
    $options[ $key ] = $value;
    update_option( 'questoes_settings', $options );
}

function questoes_get_data() {
    $raw   = questoes_get_option( 'data', '' );
    $clean = Questoes_Schema::sanitize_json( $raw );

    if ( empty( $clean ) ) {
        return array();
    }

    return json_decode( $clean, true );
}

/**
 * Return safe asset version.
 *
 * @param string $relative_path Relative path from plugin directory.
 *
 * @return string|int
 */
function questoes_asset_version( $relative_path ) {
    $file = QUESTOES_PLUGIN_DIR . ltrim( $relative_path, '/' );

    if ( file_exists( $file ) ) {
        return filemtime( $file );
    }

    return '0.1.0';
}
