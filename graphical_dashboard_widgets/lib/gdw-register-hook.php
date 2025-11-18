<?php
/**
 * @Package: WordPress Plugin
 * @Subpackage: Graphical Statistics - Dashboard Widgets
 * @Since: Gdw 1.0
 * @WordPress Version: 4.0 or above
 * This file is part of Graphical Statistics - Dashboard Widgets Plugin.
 */

//Activation Code
function gdw_admin_activation() {
    
    global $wpdb;
    //add_option("gdw_admin_version", "1.0");
    
        $sql = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "gdwwid"
                 ."( UNIQUE KEY id (id),
          id int(100) NOT NULL AUTO_INCREMENT,
          session_id  VARCHAR( 255 )  NOT NULL,
          knp_date  DATE NOT NULL,
          knp_time  TIME NOT NULL,
          knp_ts  VARCHAR (50) NOT NULL,
          duration  TIME NOT NULL,
          userid  VARCHAR( 50 ) NOT NULL,
          event VARCHAR( 50 ) NOT NULL,
          browser VARCHAR( 50 ) NOT NULL,
          platform  VARCHAR( 50 ) NOT NULL,
          ip  VARCHAR( 20 ) NOT NULL,
          city  VARCHAR( 50 ) NOT NULL,
          region  VARCHAR( 50 ) NOT NULL,
          countryName VARCHAR( 50 ) NOT NULL,
          url_id  VARCHAR( 255 )  NOT NULL,
          url_term  VARCHAR( 255 )  NOT NULL,
          referer_doamin  VARCHAR( 255 )  NOT NULL,
          referer_url TEXT NOT NULL,
          screensize  VARCHAR( 50 ) NOT NULL,
          isunique  VARCHAR( 50 ) NOT NULL,
          landing VARCHAR( 10 ) NOT NULL

          )";

          require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql);

    
        $sql2 = "CREATE TABLE IF NOT EXISTS " . $wpdb->prefix . "gdwwid_online"
                 ."( UNIQUE KEY id (id),
          id int(100) NOT NULL AUTO_INCREMENT,
          session_id VARCHAR( 255 ) NOT NULL,
          knp_time  DATETIME NOT NULL,
          knp_ts  VARCHAR (50) NOT NULL,
          userid  VARCHAR( 50 ) NOT NULL,
          url_id  VARCHAR( 255 )  NOT NULL,
          url_term  VARCHAR( 255 )  NOT NULL,
          city  VARCHAR( 50 ) NOT NULL,
          region  VARCHAR( 50 ) NOT NULL,
          countryName VARCHAR( 50 ) NOT NULL,
          browser VARCHAR( 50 ) NOT NULL,
          platform  VARCHAR( 50 ) NOT NULL,
          referer_doamin  VARCHAR( 255 )  NOT NULL,
          referer_url TEXT NOT NULL
          )";
    //$wpdb->query($sql2);
    dbDelta($sql2);
    


    $settingsapi = new Gdw_Settings_API_Test;
    $fields = $settingsapi->get_settings_fields();
    $arr = array();
    foreach ($fields['gdwids_options'] as $key => $value) {
        $arr[$value['name']] = $value['default'];
    }
    add_option( 'gdwids_options', '');
    update_option("gdwids_options",$arr);


}

//Deactivation Code
function gdw_admin_deactivation() {

	delete_option( "gdwadmin_plugin_access");
	delete_option( "gdwadmin_plugin_page");
	delete_option( "gdwadmin_plugin_userid");
	delete_option( "gdwadmin_menumng_page");
	delete_option( "gdwadmin_admin_menumng_page");
	delete_option( "gdwadmin_admintheme_page");
	delete_option( "gdwadmin_logintheme_page");
	delete_option( "gdwadmin_master_theme");

       delete_option("gdwadmin_menuorder");
       delete_option("gdwadmin_submenuorder");
       delete_option("gdwadmin_menurename");
       delete_option("gdwadmin_submenurename");
       delete_option("gdwadmin_menudisable");
       delete_option("gdwadmin_submenudisable");


  delete_site_option( "gdwadmin_plugin_access");
  delete_site_option( "gdwadmin_plugin_page");
  delete_site_option( "gdwadmin_plugin_userid");
  delete_site_option( "gdwadmin_menumng_page");
  delete_site_option( "gdwadmin_admin_menumng_page");
  delete_site_option( "gdwadmin_admintheme_page");
  delete_site_option( "gdwadmin_logintheme_page");
  delete_site_option( "gdwadmin_master_theme");

       delete_site_option("gdwadmin_menuorder");
       delete_site_option("gdwadmin_submenuorder");
       delete_site_option("gdwadmin_menurename");
       delete_site_option("gdwadmin_submenurename");
       delete_site_option("gdwadmin_menudisable");
       delete_site_option("gdwadmin_submenudisable");

}

?>