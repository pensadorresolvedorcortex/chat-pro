<?php
/*
 * UX Agência Privilege
 * @author   Agência Privilége
 * @url     https://studioprivilege.com.br
*/

defined('ABSPATH') || die;

add_action('admin_menu', 'wps_show_addons_menu');

function wps_show_addons_menu()
{
  /**
  * if WPSPowerbox plugin is active return
  **/
  if(defined('POWERBOX_PATH'))
    return;

  add_submenu_page( WPSHAPERE_MENU_SLUG , esc_html__('WPShapere Premium Addons', 'wps'), esc_html__('Premium Addons', 'wps'), 'manage_options', 'wps_addons_adv', 'wps_addons_adv_page' );
}

function wps_addons_adv_page () {
  global $aof_options;
  $wps_dir = WPSHAPERE_DIR_URI;
  ?>
  <div class="wrap wps-wrap">

    <div class="addons-heading wps-new-page-heading">
      <h1><?php esc_html_e( 'WPSPowerbox – Supercharge Your WPShapere Experience', 'wps' ) ?> <span>
        <?php esc_html_e('Unlock the full potential of WPShapere with WPSPowerbox — the all-in-one powerhouse packed with premium addons. Create dedicated menu sets for each user role, even for non-privileged admin users. Use Google Fonts to style your dashboard, hide unwanted plugins and users from the entire admin area, redirect users after login, and much more. All these features come in one simple, powerful plugin to take your WordPress admin to the next level.', 'wps'); ?></span></h1>
    </div>

    <div class="addons-content-wrap wps-new-content-wrap">

      <a target="_blank" class="addons-action-btn wps-addon-review-link" href="https://codecanyon.net/item/wpspowerbox-addon-for-wpshapere-wordpress-admin-theme/22169580">
        <?php echo esc_html__('Read more', 'wps') ?>
      </a>
      <a target="_blank" class="addons-action-btn wps-addon-purchase-link" href="https://codecanyon.net/cart/configure_before_adding/22169580?license=regular&size=source&support=bundle_12month&utm_source=wpshapereplugin">
        <?php echo esc_html__('Upgrade Your WPShapere Today →', 'wps') ?>
      </a>

      <img src="<?php echo esc_url($wps_dir); ?>assets/images/wps-powerbox-promo.jpg" alt="WPSPowerbox addon for WPShapere" />

    </div>

  </div>
  <?php
}
