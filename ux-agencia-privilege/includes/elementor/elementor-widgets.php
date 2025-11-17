<?php
/*
 * @package   WPSHAPERE
 * @author    AcmeeDesign
 * @link      https://acmeedesign.com
 * @since     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPS_Elementor_Widgets_base {

   private static $instance = null;

   public static function get_instance() {
      if ( ! self::$instance )
         self::$instance = new self;
      return self::$instance;
   }

   public function init(){
      add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
   }

   public function widgets_registered() {

      // We check if the Elementor plugin has been installed / activated.
      if(defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base')){

         foreach (glob(plugin_dir_path(__FILE__) . 'wps-elementor*.php') as $template_file)
         {
           if ( $template_file && is_readable( $template_file ) ) {
             include_once $template_file;
           }
         }
      }
   }

}

WPS_Elementor_Widgets_base::get_instance()->init();
