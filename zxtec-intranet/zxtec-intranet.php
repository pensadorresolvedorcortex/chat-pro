<?php
/*
Plugin Name: ZX Tec
Description: Sistema interno de gestao de clientes, servicos e colaboradores.
 Version: 2.2.0
Author: ZX Tec
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-zxtec-intranet.php';

function zxtec_intranet_init() {
    \ZXTEC_Intranet::instance();
}
add_action( 'plugins_loaded', 'zxtec_intranet_init' );

