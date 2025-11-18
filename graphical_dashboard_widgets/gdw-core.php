<?php
/*
Plugin Name: Estatísticas Gráficas - Widgets do Painel
Plugin URI: http://codecanyon.net/user/themepassion/portfolio
Description: Adiciona diversos widgets estatísticos no painel do WordPress com gráficos e visual avançado.
Author: themepassion
Version: 1.6
Text Domain: gdwlang
Domain Path: /languages
Author URI: http://codecanyon.net/user/themepassion/portfolio
*/


/* --------------- Load Custom functions ---------------- */
require_once( trailingslashit(dirname( __FILE__ )) . 'settings/settings.php' );

/* --------------- Load Custom functions ---------------- */
require_once( trailingslashit(dirname( __FILE__ )) . 'lib/gdw-functions.php' );

/* --------------- Visitor Stats ---------------- */
// Disabled - In case of ajax call disable visitor script
//if (defined('DOING_AJAX') && DOING_AJAX) { //} else {
require_once( trailingslashit(dirname( __FILE__ )) . 'visitor-stats/index.php' );
//}
/* --------------- Site Stats ---------------- */
require_once( trailingslashit(dirname( __FILE__ )) . 'site-stats/index.php' );

/* ---------------- Dynamic CSS - after plugins loaded ------------------ */
add_action('plugins_loaded', 'gdw_core', 12);
//add_action('admin_menu', 'gdw_panel_settings', 12);


/* --------------- Registration Hook Library---------------- */
require_once( trailingslashit(dirname(__FILE__)) . 'lib/gdw-register-hook.php' );
register_activation_hook(__FILE__, 'gdw_admin_activation');
register_deactivation_hook(__FILE__, 'gdw_admin_deactivation');




/*
function gdw_dashboard_columns() {
    add_screen_option(
        'layout_columns',
        array(
            'max'     => 3,
            'default' => 2
        )
    );
}*/
//add_action( 'admin_head-index.php', 'gdw_dashboard_columns' );

?>