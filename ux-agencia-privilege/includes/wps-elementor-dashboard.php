<?php
/*
 * WPSHAPERE
 * @author   AcmeeDesign
 * @url     https://acmeedesign.com
*/

defined('ABSPATH') || die;

/**
 * Replace the welcome panel with Elementor template content
 */

if( !class_exists('WPS_ELEMENTOR_DASHBOARD') ) {

    class WPS_ELEMENTOR_DASHBOARD extends WPSHAPERE {

        function __construct()
        {
            $this->aof_options = parent::get_wps_option_data( WPSHAPERE_OPTIONS_SLUG );
            add_action( 'welcome_panel', [$this, 'wps_replace_welcome_panel'], 9 );
        }
        function wps_replace_welcome_panel() {
            
            //get template id
            if ( empty($this->aof_options['wps_ele_template_id']) ) {
                return;
            }

            $template_id = $this->aof_options['wps_ele_template_id'];

            // Ensure Elementor is loaded
            if ( did_action( 'elementor/loaded' ) ) {

            $elementor = Elementor\Plugin::$instance;

            echo '<style>.welcome-panel{background:transparent}.wpshapere-dashboard-template .e-con-inner {
            max-width: 100% !important;}#dashboard-widgets .empty-container, .welcome-panel-close{display:none}</style>';

                // Enqueue Elementor's frontend assets 
                $elementor->frontend->register_styles();
                $elementor->frontend->enqueue_styles();
                $elementor->frontend->register_scripts();
				$elementor->frontend->enqueue_scripts();

                // Get the raw rendered content (no wp_head dependencies)
                $content = \Elementor\Plugin::$instance->frontend->get_builder_content( $template_id, true );

                if ( ! empty( $content ) ) {
                    echo '<div class="wpshapere-dashboard-template">';
                    echo \Elementor\Plugin::$instance->frontend->get_builder_content( $template_id, true );
                    echo '</div>';
                } else {
                    echo '<p style="color:red;">' . esc_html__('Could not render the selected Elementor template. Try saving it once in Elementor editor.', 'wps') . '</p>';
                }

            } else {
                echo '<p style="color:red;">' . esc_html__('Elementor plugin is not active or not fully loaded.', 'wps') . '</p>';
            }
        }

    }
}
new WPS_ELEMENTOR_DASHBOARD();