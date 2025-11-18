<?php
// Security: Considered blocking direct access to PHP files by adding the following line. 
defined('ABSPATH') or die("Silence is golden :)");

/*Dashboard Widget Page Stats*/

function gdw_pagestats_add_dashboard() {
  wp_add_dashboard_widget( 'pagestats_wp_dashboard',  __('Contagem e tipo de páginas', 'gdwlang'), 'gdw_pagestats_dashboard_output' );
}

function gdw_pagestats_dashboard_output() {
  include('includes/pagestats-ajaxcall.php');
}

function gdwwid_pagestats(){  
  include('includes/gdw-stats-pages.php');
  wp_show_stats_pages();
  die();
}

add_action('wp_ajax_gdwwid_pagestats', 'gdwwid_pagestats');
add_action('wp_ajax_nopriv_gdwwid_pagestats', 'gdwwid_pagestats');


/*Dashboard Widget Post Stats*/

function gdw_poststats_add_dashboard() {
  wp_add_dashboard_widget( 'poststats_wp_dashboard', __('Estatísticas de posts', 'gdwlang') , 'gdw_poststats_dashboard_output' );
}

function gdw_poststats_dashboard_output() {
  include('includes/poststats-ajaxcall.php');
}

function gdwwid_poststats(){  
  include('includes/gdw-stats-posts.php');
  wp_show_stats_posts();
  die();
}

add_action('wp_ajax_gdwwid_poststats', 'gdwwid_poststats');
add_action('wp_ajax_nopriv_gdwwid_poststats', 'gdwwid_poststats');


/*Dashboard Widget Comment Stats*/

function gdw_commentstats_add_dashboard() {
  wp_add_dashboard_widget( 'commentstats_wp_dashboard', __('Comentários de usuários', 'gdwlang') , 'gdw_commentstats_dashboard_output' );
}

function gdw_commentstats_dashboard_output() {
  include('includes/commentstats-ajaxcall.php');
}

function gdwwid_commentstats(){  
  include('includes/gdw-stats-comments.php');
  wp_show_stats_comments();
  die();
}

add_action('wp_ajax_gdwwid_commentstats', 'gdwwid_commentstats');
add_action('wp_ajax_nopriv_gdwwid_commentstats', 'gdwwid_commentstats');






/*Dashboard Widget Category Stats*/

function gdw_catstats_add_dashboard() {
  wp_add_dashboard_widget( 'catstats_wp_dashboard', __('Estatísticas por categoria', 'gdwlang') , 'gdw_catstats_dashboard_output' );
}

function gdw_catstats_dashboard_output() {
  include('includes/catstats-ajaxcall.php');
}

function gdwwid_catstats(){
  include('includes/gdw-stats-categories.php');
  wp_show_stats_categories();
  die();
}

add_action('wp_ajax_gdwwid_catstats', 'gdwwid_catstats');
add_action('wp_ajax_nopriv_gdwwid_catstats', 'gdwwid_catstats');



/*Dashboard Widget User Stats*/

function gdw_userstats_add_dashboard() {
  wp_add_dashboard_widget( 'userstats_wp_dashboard', __('Estatísticas de usuários', 'gdwlang') , 'gdw_userstats_dashboard_output' );
}


function gdw_userstats_dashboard_output() {
  include('includes/userstats-ajaxcall.php');
}

function gdwwid_userstats(){  
  include('includes/gdw-stats-users.php');
  wp_show_stats_users();
  die();
}

add_action('wp_ajax_gdwwid_userstats', 'gdwwid_userstats');
add_action('wp_ajax_nopriv_gdwwid_userstats', 'gdwwid_userstats');


?>
