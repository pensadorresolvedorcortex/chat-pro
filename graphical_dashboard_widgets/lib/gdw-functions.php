<?php
/**
 * @Package: WordPress Plugin
 * @Subpackage: Graphical Statistics - Dashboard Widgets
 * @Since: Gdw 1.0
 * @WordPress Version: 4.0 or above
 * This file is part of Graphical Statistics - Dashboard Widgets Plugin.
 */

function gdw_core(){


            // Garante o carregamento da tradução pt-BR a partir do arquivo .po (sem binários)
            $gdw_pt_br_mo = gdw_generate_pt_br_mo();
            if ( $gdw_pt_br_mo ) {
                load_textdomain( 'gdwlang', $gdw_pt_br_mo );
            }

            load_plugin_textdomain( 'gdwlang', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages/' );
            $gdwadmin = gdwadmin_network();

            $GLOBALS['gdwadmin'] = $gdwadmin;

            add_action('admin_enqueue_scripts', 'gdw_scripts', 1);
            add_action('admin_enqueue_scripts', 'gdw_admin_neon_scripts', 20);

            global $pagenow;
            if($pagenow == "index.php"){
                add_action("admin_enqueue_scripts","gdwwid_init_scripts");
            }
            add_action("wp_enqueue_scripts","gdwwid_init_scripts_frontend");

            add_action('admin_init', 'gdw_load_dashboard_widgets', 1);

            add_action('admin_enqueue_scripts', 'gdw_admin_css', 99);

}


/**
 * Converte o arquivo .po em .mo em tempo de execução para evitar binários no repositório.
 */
function gdw_generate_pt_br_mo() {
    $po_file = trailingslashit( dirname( __FILE__ ) ) . '../languages/gdwlang-pt_BR.po';

    if ( ! file_exists( $po_file ) ) {
        return false;
    }

    $target_dir = trailingslashit( WP_LANG_DIR ) . 'plugins';
    $target_mo  = trailingslashit( $target_dir ) . 'gdwlang-pt_BR.mo';

    // Se o .mo já existe e é mais recente que o .po, reutilize
    if ( file_exists( $target_mo ) && filemtime( $target_mo ) >= filemtime( $po_file ) ) {
        return $target_mo;
    }

    if ( ! class_exists( 'PO' ) ) {
        require_once ABSPATH . WPINC . '/pomo/po.php';
    }

    if ( ! class_exists( 'MO' ) ) {
        require_once ABSPATH . WPINC . '/pomo/mo.php';
    }

    $po = new PO();

    if ( ! $po->import_from_file( $po_file ) ) {
        return false;
    }

    $mo = new MO();
    $mo->entries = $po->entries;
    $mo->set_headers( $po->headers );

    if ( ! function_exists( 'wp_mkdir_p' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    wp_mkdir_p( $target_dir );

    if ( ! $mo->export_to_file( $target_mo ) ) {
        return false;
    }

    return $target_mo;
}


function gdwadmin_network(){

        if(is_multisite() && gdw_network_active()){

                    global $blog_id;
                    $current_blog_id = $blog_id;
                    switch_to_blog(1);
                    $site_specific_gdwadmin = get_option("gdwids_options");
                    $gdwadmin = $site_specific_gdwadmin;
                    switch_to_blog($current_blog_id);
                    //echo "hello";
        } else {
            $gdwadmin = gdw_get_option("gdwids_options","");
        }


    if(isset($gdwadmin['dashboard-widget-colors'])){
        $exp = explode(",", $gdwadmin['dashboard-widget-colors']);
        $gdwadmin['dashboard-widget-colors'] = array_unique(array_filter($exp));
    }


        return $gdwadmin;
}



function gdw_scripts(){
   // global $gdwadmin;

    global $wp_version;
    $plug = trim(get_current_screen()->id);

    if (isset($plug) && $plug == "dashboard"){
        $url = plugins_url('/', __FILE__).'../js/echarts-all.js';
        wp_deregister_script('gdw-echarts-js');
        wp_register_script('gdw-echarts-js', $url);
        wp_enqueue_script('gdw-echarts-js','jquery');

    }

        wp_localize_script('gdw-scripts-js', 'gdw_vars', array(
            'gdw_nonce' => wp_create_nonce('gdw-nonce')
                )
        );

}


function gdw_admin_css(){

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $is_dashboard = $screen && $screen->id === 'dashboard';
    $is_settings_page = $screen && $screen->id === 'toplevel_page_gdw_settings';

    if ( $is_dashboard || $is_settings_page ) {
        wp_enqueue_style(
            'gdw-admin-neon',
            plugins_url( '../css/gdw-admin.css', __FILE__ ),
            array(),
            '1.0'
        );
    }
}


function gdw_admin_neon_scripts() {

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if ( $screen && $screen->id === 'dashboard' ) {
        wp_enqueue_script(
            'gdw-admin-ux',
            plugins_url( '../js/gdw-admin-ux.js', __FILE__ ),
            array( 'gdw-echarts-js', 'jquery' ),
            '1.0',
            true
        );
    }
}



function gdw_multisite_allsites(){

    $arr = array();
                        $blogs = get_sites();

                        if ( 0 < count( $blogs ) ) :
                            foreach( $blogs as $blog ) : 
                                $getblogid = $blog -> blog_id;
                               // echo "id:". $getblogid;
                            //die();
                                switch_to_blog( $getblogid );

                                if ( get_theme_mod( 'show_in_home', 'on' ) !== 'on' ) {
                                    continue;
                                }

                                $blog_details = get_blog_details( $getblogid );
                                //print_r($blog_details);
                                
                                $id = $getblogid;
                                $name = $blog_details->blogname;
                                $arr[$id] = $name;

                            endforeach;
                        endif;

                        return $arr;
}


function gdw_network_active(){

        if ( ! function_exists( 'is_plugin_active_for_network' ) ){
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }

        // Makes sure the plugin is defined before trying to use it
            if ( is_plugin_active_for_network( 'graphical_dashboard_widgets/gdw-core.php' )){
                return true;
            }

            return false;
}


function gdw_add_option($variable,$default){
    if(gdw_network_active()){
        add_site_option($variable,$default);
    } else {
        add_option($variable,$default);
    }
}

function gdw_get_option($variable,$default){
    if(gdw_network_active()){
        //echo "networkactive";
        return get_site_option($variable,$default);
    } else {
        //echo "individualactive";
        return get_option($variable,$default);
    }
}

function gdw_update_option($variable,$default){
    if(gdw_network_active()){
        update_site_option($variable,$default);
    } else {
        update_option($variable,$default);
    }
}

function gdw_load_dashboard_widgets(){

    //$gdwadmin = gdwadmin_network();   
    global $gdwadmin;
    //echo "<pre>"; print_r($gdwadmin); echo "</pre>"; die;

    $element = "dashboard-widgets";

    $widgetid = "gdw_visitors_type";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_today_visitors";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_user_type";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_browser_type";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_platform_type";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_country_type";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }


    $widgetid = "gdw_userstats_add_dashboard";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_catstats_add_dashboard";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_commentstats_add_dashboard";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_poststats_add_dashboard";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }

    $widgetid = "gdw_pagestats_add_dashboard";
    if( isset($gdwadmin[$element][$widgetid]) && $gdwadmin[$element][$widgetid] == $widgetid){
    add_action( 'wp_dashboard_setup', $widgetid );
    }


    $element = "dashboard-default-widgets";

    $widgetid = "welcome_panel";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_action( 'welcome_panel', 'wp_welcome_panel' );
    }

    $widgetid = "dashboard_primary";
    
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    }

    $widgetid = "dashboard_quick_press";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    }

    $widgetid = "dashboard_recent_drafts";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
    }

    $widgetid = "dashboard_recent_comments";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    }

    $widgetid = "dashboard_right_now";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
    }

    $widgetid = "dashboard_activity";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8
    }

    $widgetid = "dashboard_incoming_links";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
    }

    $widgetid = "dashboard_plugins";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
    }

    $widgetid = "dashboard_secondary";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
    }

    $widgetid = "e-dashboard-overview";
    if( !isset($gdwadmin[$element][$widgetid])){
        remove_meta_box( 'e-dashboard-overview', 'dashboard', 'normal');
    }

}



function gdw_dashboard_widget_color(){

    //$gdwadmin = gdwadmin_network();
    global $gdwadmin;
   
    $blue_colors = array();
    $blue_colors[0] = "#17F3FF";
    $blue_colors[1] = "#FF33CC";
    $blue_colors[2] = "#A259FF";
    $blue_colors[3] = "#7CFF6B";
    $blue_colors[4] = "#6EFFE0";
    $blue_colors[5] = "#FFD166";

    $red_colors = array();
    $red_colors[0] = "#FF6FB5";
    $red_colors[1] = "#FF9E5E";
    $red_colors[2] = "#FFC23E";
    $red_colors[3] = "#FF5F6D";
    $red_colors[4] = "#FF8DFF";
    $red_colors[5] = "#C8FF3A";

    $green_colors = array();
    $green_colors[0] = "#4CFFB2";
    $green_colors[1] = "#8CFFDA";
    $green_colors[2] = "#60F5FF";
    $green_colors[3] = "#7DF8A9";
    $green_colors[4] = "#4EFFEF";
    $green_colors[5] = "#E6FF7D";

    $getcolor = array();
    if(isset($gdwadmin['dashboard-widget-colors']) && sizeof($gdwadmin['dashboard-widget-colors']) > 5){
        $getcolor = $gdwadmin['dashboard-widget-colors'];
        //print_r($getcolor);
    } else {
        $getcolor = $blue_colors;
    }

   // print_r($getcolor);

    return $getcolor;

}


function gdw_localize_label( $key ) {

    $normalized = strtolower( (string) $key );
    $normalized = str_replace( ' ', '_', $normalized );

    $map = array(
        'publish'             => array( 'msgid' => 'Publish', 'fallback' => 'Publicado' ),
        'future'              => array( 'msgid' => 'Future', 'fallback' => 'Agendado' ),
        'draft'               => array( 'msgid' => 'Draft', 'fallback' => 'Rascunho' ),
        'pending'             => array( 'msgid' => 'Pending', 'fallback' => 'Pendente' ),
        'private'             => array( 'msgid' => 'Private', 'fallback' => 'Privado' ),
        'hired'               => array( 'msgid' => 'Hired', 'fallback' => 'Contratado' ),
        'denied'              => array( 'msgid' => 'Denied', 'fallback' => 'Negado' ),
        'cancelled'           => array( 'msgid' => 'Cancelled', 'fallback' => 'Cancelado' ),
        'completed'           => array( 'msgid' => 'Completed', 'fallback' => 'Concluído' ),
        'expired'             => array( 'msgid' => 'Expired', 'fallback' => 'Expirado' ),
        'pending_aprove'      => array( 'msgid' => 'Pending_aprove', 'fallback' => 'Aguardando aprovação' ),
        'pending_payment'     => array( 'msgid' => 'Pending_payment', 'fallback' => 'Pagamento pendente' ),
        'preview'             => array( 'msgid' => 'Preview', 'fallback' => 'Pré-visualização' ),
        'none'                => array( 'msgid' => 'None', 'fallback' => 'Nenhum' ),
        'administrator'       => array( 'msgid' => 'Administrator', 'fallback' => 'Administrador' ),
        'editor'              => array( 'msgid' => 'Editor', 'fallback' => 'Editor' ),
        'author'              => array( 'msgid' => 'Author', 'fallback' => 'Autor' ),
        'contributor'         => array( 'msgid' => 'Contributor', 'fallback' => 'Colaborador' ),
        'subscriber'          => array( 'msgid' => 'Subscriber', 'fallback' => 'Assinante' ),
        'others'              => array( 'msgid' => 'Others', 'fallback' => 'Outros' ),
        'request-confirmed'   => array( 'msgid' => 'Request-confirmed', 'fallback' => 'Solicitação confirmada' ),
        'request-failed'      => array( 'msgid' => 'Request-failed', 'fallback' => 'Solicitação falhou' ),
        'request-completed'   => array( 'msgid' => 'Request-completed', 'fallback' => 'Solicitação concluída' ),
        'wc-pending'          => array( 'msgid' => 'Wc-pending', 'fallback' => 'Pagamento pendente (WooCommerce)' ),
        'wc-processing'       => array( 'msgid' => 'Wc-processing', 'fallback' => 'Processando (WooCommerce)' ),
        'wc-on-hold'          => array( 'msgid' => 'Wc-on-hold', 'fallback' => 'Em espera (WooCommerce)' ),
        'wc-completed'        => array( 'msgid' => 'Wc-completed', 'fallback' => 'Concluído (WooCommerce)' ),
        'wc-cancelled'        => array( 'msgid' => 'Wc-cancelled', 'fallback' => 'Cancelado (WooCommerce)' ),
        'wc-refunded'         => array( 'msgid' => 'Wc-refunded', 'fallback' => 'Reembolsado (WooCommerce)' ),
        'wc-failed'           => array( 'msgid' => 'Wc-failed', 'fallback' => 'Falhou (WooCommerce)' ),
        'wc-checkout-draft'   => array( 'msgid' => 'Wc-checkout', 'fallback' => 'Checkout (WooCommerce)' ),
        'wc-checkout'         => array( 'msgid' => 'Wc-checkout', 'fallback' => 'Checkout (WooCommerce)' ),
        'high'                => array( 'msgid' => 'High', 'fallback' => 'Alto' ),
        'low'                 => array( 'msgid' => 'Low', 'fallback' => 'Baixo' ),
        'spam'                => array( 'msgid' => 'Spam', 'fallback' => 'Spam' ),
        'post-trashed'        => array( 'msgid' => 'Trashed with Post', 'fallback' => 'Enviados para a lixeira com o post' ),
        'moderated'           => array( 'msgid' => 'Pending', 'fallback' => 'Pendente' ),
    );

    if ( isset( $map[ $normalized ] ) ) {
        $entry     = $map[ $normalized ];
        $msgid     = is_array( $entry ) ? $entry['msgid'] : $entry;
        $fallback  = is_array( $entry ) ? $entry['fallback'] : $entry;
        $translated = __( $msgid, 'gdwlang' );

        if ( $translated !== $msgid ) {
            return $translated;
        }

        return $fallback;
    }

    $raw_label  = ucwords( str_replace( array( '-', '_' ), ' ', $normalized ) );
    $translated = __( $raw_label, 'gdwlang' );

    return $translated !== $raw_label ? $translated : $raw_label;
}

?>