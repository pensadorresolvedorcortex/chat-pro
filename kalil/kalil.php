<?php
/*
Plugin Name: Kalil
Description: Area de membros com troca de arquivos, envio de videos e bate papo entre administrador e paciente.
Version: 1.2.1
Author: Kalil Dev
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-kalil.php';

function kalil_init() {
    \Kalil\Plugin::instance();
}
add_action( 'plugins_loaded', 'kalil_init' );

