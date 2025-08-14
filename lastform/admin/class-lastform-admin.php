<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://meydjer.com
 * @since      1.0.0
 *
 * @package    Lastform
 * @subpackage Lastform/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Lastform
 * @subpackage Lastform/admin
 * @author     Meydjer Windmüller <meydjer@gmail.com>
 */
class Lastform_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $_slug    The ID of this plugin.
	 */
	private $_slug;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $_version    The current version of this plugin.
	 */
	private $_version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $_slug       The name of this plugin.
	 * @param      string    $_version    The version of this plugin.
	 */
	public function __construct( $_slug, $_version ) {

		$this->_slug = $_slug;
		$this->_version = $_version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Lastform_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Lastform_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->_slug, plugin_dir_url( __FILE__ ) . 'css/lastform-admin.css', array(), $this->_version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Lastform_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Lastform_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->_slug, plugin_dir_url( __FILE__ ) . 'js/lastform-admin.js', array( 'jquery' ), $this->_version, false );

	}


	public function image_editor_field() {
	    ?>
	    <script type='text/javascript'>
        jQuery('.choices_setting')
          .on('input propertychange', '.field-choice-lf-img', function () {
            var
            $this = jQuery(this),
            i     = $this.closest('li.field-choice-row').data('index');

            field = GetSelectedField();
            field.choices[i].lfImg = $this.val();
          });
        gform.addFilter('gform_append_field_choice_option', function (str, field, i) {
          var
          inputType = GetInputType(field),
          lfImg    = field.choices[i].lfImg ? field.choices[i].lfImg : '';

          if (inputType.match('radio|checkbox')) {
            return "<input type='text' id='" + inputType + "_choice_lf_img_" + i + "' value='" + lfImg + "' class='field-choice-input field-choice-lf-img' placeholder='Image URL, like "+window.location.origin+"/image.png' />";
          } else {
          	return '';
          }
        });
	    </script>
	    <?php
	}


	public function custom_gf_tooltips($__gf_tooltips) {
		$lastform_info = '<br/><br/><h6><i class="fa fa-info-circle fa-lg"></i> ' . esc_attr__('Lastform version', 'lastform') . '</h6>' . esc_attr__('Since Lastform already has color options this will only affect pure "Gravity Forms" forms.', 'lastform');


		// Little hack to avoid duplications
		if (!strpos($__gf_tooltips['form_percentage_style'], $lastform_info)) {
			$__gf_tooltips['form_percentage_style'] = $__gf_tooltips['form_percentage_style'] . $lastform_info;
		}


		$lastform_info = '<br/><br/><h6><i class="fa fa-info-circle fa-lg"></i> ' . esc_attr__('Lastform version', 'lastform') . '</h6>' . esc_attr__('This option will be integrated with Lastform soon.', 'lastform');

		// Little hack to avoid duplications
		if (!strpos($__gf_tooltips['form_field_rich_text_editor'], $lastform_info)) {
			$__gf_tooltips['form_field_rich_text_editor'] = $__gf_tooltips['form_field_rich_text_editor'] . $lastform_info;
		}

		$lastform_info = '<br/><br/><h6><i class="fa fa-info-circle fa-lg"></i> ' . esc_attr__('Lastform version', 'lastform') . '</h6>' . esc_attr__("Lastform already has its own enhanced UI.", 'lastform');

		// Little hack to avoid duplications
		if (!strpos($__gf_tooltips['form_field_enable_enhanced_ui'], $lastform_info)) {
			$__gf_tooltips['form_field_enable_enhanced_ui'] = $__gf_tooltips['form_field_enable_enhanced_ui'] . $lastform_info;
		}

		$lastform_info = '<br/><br/><h6><i class="fa fa-info-circle fa-lg"></i> ' . esc_attr__('Lastform version', 'lastform') . '</h6>' . esc_attr__("Lastform already has its own calendar widget.", 'lastform');

		// Little hack to avoid duplications
		if (!strpos($__gf_tooltips['form_field_date_input_type'], $lastform_info)) {
			$__gf_tooltips['form_field_date_input_type'] = $__gf_tooltips['form_field_date_input_type'] . $lastform_info;
		}

		return $__gf_tooltips;
	}

}
