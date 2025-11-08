<?php
/**
 * Plugin Name: Juntaplay
 * Description: Funcionalidades principais do fluxo de criação de grupos Juntaplay.
 * Version: 0.3.0
 * Author: Equipe Juntaplay
 * Text Domain: juntaplay
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'JPLAY_PLUGIN_PATH' ) ) {
    define( 'JPLAY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'JPLAY_PLUGIN_URL' ) ) {
    define( 'JPLAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Carrega arquivos necessários do plugin.
 */
function jplay_load_dependencies() {
    $files = array(
        'includes/Shortcodes/GroupRelationship.php',
        'includes/Forms/CreateGroupForm.php',
        'includes/Security/Crypto.php',
        'includes/Hooks/SaveGroupMeta.php',
        'includes/Notifications/Mailer.php',
        'includes/Ajax/TwoFactor.php',
    );

    foreach ( $files as $relative_path ) {
        $file = JPLAY_PLUGIN_PATH . $relative_path;

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
add_action( 'plugins_loaded', 'jplay_load_dependencies' );

/**
 * Registra estilos front-end do plugin.
 */
function jplay_register_assets() {
    wp_register_style(
        'juntaplay-frontend',
        JPLAY_PLUGIN_URL . 'assets/css/juntaplay.css',
        array(),
        '0.3.0'
    );

    wp_register_script(
        'juntaplay-form',
        JPLAY_PLUGIN_URL . 'assets/js/juntaplay-form.js',
        array(),
        '0.3.0',
        true
    );

    wp_register_script(
        'juntaplay-2fa',
        JPLAY_PLUGIN_URL . 'assets/js/juntaplay-2fa.js',
        array(),
        '0.3.0',
        true
    );
}
add_action( 'init', 'jplay_register_assets' );

/**
 * Registra shortcodes do plugin.
 */
function jplay_register_shortcodes() {
    if ( class_exists( '\\Juntaplay\\Shortcodes\\GroupRelationship' ) ) {
        \Juntaplay\Shortcodes\GroupRelationship::register();
    }

    if ( class_exists( '\\Juntaplay\\Forms\\CreateGroupForm' ) ) {
        \Juntaplay\Forms\CreateGroupForm::register_shortcodes();
    }
}
add_action( 'init', 'jplay_register_shortcodes', 20 );

/**
 * Registra formulários e manipuladores.
 */
function jplay_register_forms() {
    if ( class_exists( '\\Juntaplay\\Forms\\CreateGroupForm' ) ) {
        \Juntaplay\Forms\CreateGroupForm::register_handlers();
    }
}
add_action( 'init', 'jplay_register_forms', 25 );

/**
 * Registra hooks de salvamento de metadados.
 */
function jplay_register_hooks() {
    if ( class_exists( '\\Juntaplay\\Hooks\\SaveGroupMeta' ) ) {
        \Juntaplay\Hooks\SaveGroupMeta::register();
    }
}
add_action( 'init', 'jplay_register_hooks', 30 );

/**
 * Registra handlers AJAX.
 */
function jplay_register_ajax() {
    if ( class_exists( '\\Juntaplay\\Ajax\\TwoFactor' ) ) {
        \Juntaplay\Ajax\TwoFactor::register();
    }
}
add_action( 'init', 'jplay_register_ajax', 35 );
