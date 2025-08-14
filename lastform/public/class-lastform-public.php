<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://meydjer.com
 * @since      1.0.0
 *
 * @package    Lastform
 * @subpackage Lastform/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Lastform
 * @subpackage Lastform/public
 * @author     Meydjer Windmüller <meydjer@gmail.com>
 */
class Lastform_Public {

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
	 * @param      string    $_slug       The name of the plugin.
	 * @param      string    $_version    The version of this plugin.
	 */
	public function __construct( $_slug, $_version ) {
		$this->_slug = $_slug;
		$this->_version = $_version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function register_styles() {
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_style( $this->_slug, plugin_dir_url( __FILE__ ) . "css/lastform-public{$min}.css", array(), $this->_version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($form) {

		/**
		 * Exit if it's not called from the Lastform template
		 */
		if (!get_query_var('lastform')) {
			return false;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	}


	public function gform_processed($form, $page_number = null, $source_page_number = null) {
		if (empty($_POST['is_lastform'])) return null;

		global $lf_gform_processed;

		$lf_gform_processed = $form;
	}

	/**
	 * Check if the correspondent select2 i18n file exists
	 * @since     1.0.0
	 * @param     string    $lang    language code
	 * @return    boolean
	 */
	public function select2_i18n_file_exists($lang) {
		return file_exists(plugin_dir_path( __FILE__ ).'../includes/vendor/select2/dist/js/i18n/'.$lang.'.js');
	}


	/**
	 * Processes pages that are not loaded directly within WordPress
	 *
	 * @access public
	 * @static
	 * @see GFCommon
	 */
	public function process_form_page() {
		$lastform_query = get_query_var('lastform');

		if ( empty($lastform_query) ) {
			return;
		} else {
			require_once( plugin_dir_path( __FILE__ ) . 'partials/lastform-public-display.php' );
			exit();
		}
	}


	/**
	 * Create Lastform route
	 * @since     1.0.0
	 */
	public function add_route(){
		$setting = lastform_addon()->get_plugin_setting('form_url_slug');
		$slug = (!empty($setting)) ? $setting : 'lastform';
	    add_rewrite_rule(
	        $slug . '/([0-9]+)/?$',
	        'index.php?lastform=$matches[1]',
	        'top' );
	}


	/**
	 * Add 'lastform' param to the query
	 * @since     1.0.0
	 * @param     array    $query_vars    All current query vars
	 * @return    array                   New query cars
	 */
	public function query_vars( $query_vars ){
	    $query_vars[] = 'lastform';
	    return $query_vars;
	}


	/**
	 * Check if field has duplicate value
	 * @since     2.0.0
	 * @return    boolean
	 */
	public function is_duplicate() {
		if (!wp_verify_nonce($_POST['_wpnonce'], 'is_duplicate-'.$_POST['form_id']))
			wp_die();

		$form = GFFormsModel::get_form_meta( $_POST['form_id'] );
		$field = GFFormsModel::get_field( $form, $_POST['field_id'] );

		echo RGFormsModel::is_duplicate( $_POST['form_id'], $field, $_POST['value'] );

		wp_die();
	}


	/**
	 * Get 'save_email_input' options
	 * @since  2.0.0
	 * @param  string $text Confirmation message
	 * @return array        Button Text and Validation Message vals
	 */
	public static function get_save_email_input_options($text) {
		$resume_submit_button_text       = esc_html__( 'Send Email', 'gravityforms' );
		$resume_email_validation_message = esc_html__( 'Please enter a valid email address.', 'gravityforms' );

		preg_match_all( '/\{save_email_input:(.*?)\}/', $text, $matches, PREG_SET_ORDER );

		if ( is_array( $matches ) && isset( $matches[0] ) && isset( $matches[0][1] ) ) {
			$options_string = isset( $matches[0][1] ) ? $matches[0][1] : '';
			$options        = shortcode_parse_atts( $options_string );
			if ( isset( $options['button_text'] ) ) {
				$resume_submit_button_text = $options['button_text'];
			}
			if ( isset( $options['validation_message'] ) ) {
				$resume_email_validation_message = $options['validation_message'];
			}
			$full_tag = $matches[0][0];
			$text     = str_replace( $full_tag, '{save_email_input}', $text );
		}

		return array(
			'button_text'        => $resume_submit_button_text,
			'validation_message' => $resume_email_validation_message
		);
	}

	/**
	 * Print form inline styles
	 * @since     1.0.0
	 * @param     array    $form     GF form Array
	 * @echo      string             inline CSS code
	 */
	public static function print_form_inline_style($form) {
		$options = lastform_addon()->get_form_settings( $form );

		$css  = '';

		/**
		 * Default options
		 */
		$question_color           = (!empty($options['question-color'])) ? $options['question-color'] : '#3D3D3D';
		$question_color_rgb       = implode(',', Lastform_Color::hex2rgb($question_color));
		$answer_color             = (!empty($options['answer-color'])) ? $options['answer-color'] : '#41B3FF';
		$answer_color_rgb         = implode(',', Lastform_Color::hex2rgb($answer_color));
		$button_color             = (!empty($options['button-color'])) ? $options['button-color'] : '#41B3FF';
		$button_color_rgb         = implode(',', Lastform_Color::hex2rgb($button_color));
		$bg_color                 = (!empty($options['bg-color'])) ? $options['bg-color'] : '#ffffff';
		$bg_color_rgb             = implode(',', Lastform_Color::hex2rgb($bg_color));
		$warning_color            = (!empty($options['warning-color'])) ? $options['warning-color'] : '#ff7600';
		$warning_color_rgb        = implode(',', Lastform_Color::hex2rgb($warning_color));
		$font_family              = (!empty($options['font-family-name'])) ? $options['font-family-name'] : '';
		$unfocused_fields_opacity = (!empty($options['uncofused-fields-transparency-level']) || (isset($options['uncofused-fields-transparency-level']) && $options['uncofused-fields-transparency-level'] === '0')) ? ((100 - $options['uncofused-fields-transparency-level']) / 100) : 0.2;

		/**
		 * Base CSS
		 */
		$css .= "
			body {
				font-family:'{$font_family}', Helvetica Neue, Helvetica, Arial, sans-serif;
			}
			.lf-input-text:-ms-input-placeholder, .lf-input-number:-ms-input-placeholder, .lf-input-phone:-ms-input-placeholder, .lf-input-date:-ms-input-placeholder, .lf-input-time:-ms-input-placeholder, .lf-input-terminal-number:-ms-input-placeholder, .lf-input-select:-ms-input-placeholder, .lf-input-multiselect-wrapper:-ms-input-placeholder, .lf-input-fileupload:-ms-input-placeholder, .lf-jumper-field:-ms-input-placeholder, .lf-input-textarea:-ms-input-placeholder {
			  color: rgba({$answer_color_rgb}, 0.4);
			}
			.lf-input-text:-moz-placeholder, .lf-input-number:-moz-placeholder, .lf-input-phone:-moz-placeholder, .lf-input-date:-moz-placeholder, .lf-input-time:-moz-placeholder, .lf-input-terminal-number:-moz-placeholder, .lf-input-select:-moz-placeholder, .lf-input-multiselect-wrapper:-moz-placeholder, .lf-input-fileupload:-moz-placeholder, .lf-jumper-field:-moz-placeholder, .lf-input-textarea:-moz-placeholder {
			  color: rgba({$answer_color_rgb}, 0.4);
			}
			.lf-input-text::-moz-placeholder, .lf-input-number::-moz-placeholder, .lf-input-phone::-moz-placeholder, .lf-input-date::-moz-placeholder, .lf-input-time::-moz-placeholder, .lf-input-terminal-number::-moz-placeholder, .lf-input-select::-moz-placeholder, .lf-input-multiselect-wrapper::-moz-placeholder, .lf-input-fileupload::-moz-placeholder, .lf-jumper-field::-moz-placeholder, .lf-input-textarea::-moz-placeholder {
			  color: rgba({$answer_color_rgb}, 0.4);
			}
			.lf-input-text::-webkit-input-placeholder, .lf-input-number::-webkit-input-placeholder, .lf-input-phone::-webkit-input-placeholder, .lf-input-date::-webkit-input-placeholder, .lf-input-time::-webkit-input-placeholder, .lf-input-terminal-number::-webkit-input-placeholder, .lf-input-select::-webkit-input-placeholder, .lf-input-multiselect-wrapper::-webkit-input-placeholder, .lf-input-fileupload::-webkit-input-placeholder, .lf-jumper-field::-webkit-input-placeholder, .lf-input-textarea::-webkit-input-placeholder {
			  color: rgba({$answer_color_rgb}, 0.4);
			}
			.Select-placeholder, .Select--single > .Select-control .Select-value {
			  color: rgba({$answer_color_rgb}, 0.4)
			}
			.lf-is-touch .lf-input-text, .lf-is-touch .lf-input-number, .lf-is-touch .lf-input-phone, .lf-is-touch .lf-input-date, .lf-is-touch .lf-input-time, .lf-is-touch .lf-input-terminal-number, .lf-is-touch .lf-input-select, .lf-is-touch .lf-input-multiselect-wrapper, .lf-is-touch .lf-input-fileupload, .lf-is-touch .lf-jumper-field, .lf-is-touch .lf-input-textarea, .lf-input-text:hover, .lf-input-number:hover, .lf-input-phone:hover, .lf-input-date:hover, .lf-input-time:hover, .lf-input-terminal-number:hover, .lf-input-select:hover, .lf-input-multiselect-wrapper:hover, .lf-input-fileupload:hover, .lf-jumper-field:hover, .lf-input-textarea:hover, .lf-input-text:focus, .lf-input-number:focus, .lf-input-phone:focus, .lf-input-date:focus, .lf-input-time:focus, .lf-input-terminal-number:focus, .lf-input-select:focus, .lf-input-multiselect-wrapper:focus, .lf-input-fileupload:focus, .lf-jumper-field:focus, .lf-input-textarea:focus, .Select:hover .Select-control, .is-open > .Select-control, .is-focused:not(.is-open) > .Select-control, .Select-menu-outer {
			  border-color: rgba({$answer_color_rgb}, 0.9)
			}
			.lf-input-text, .lf-input-number, .lf-input-phone, .lf-input-date, .lf-input-time, .lf-input-terminal-number, .lf-input-select, .lf-input-multiselect-wrapper, .lf-input-fileupload, .lf-jumper-field, .lf-input-textarea, .lf-is-touch .lf-input-text-wrapper:after, .lf-is-touch .lf-input-textarea-wrapper:after, .lf-is-touch .lf-input-number-wrapper:after, .lf-is-touch .lf-input-phone-wrapper:after, .lf-is-touch .lf-input-date-wrapper:after, .lf-is-touch .lf-input-time-wrapper:after, .lf-is-touch .lf-input-select-wrapper:after, .lf-is-touch .lf-input-multiselect-wrapper:after, .lf-is-touch .lf-input-fileupload-wrapper:after, .lf-li-choice, .lf-li-choice:active, .lf-li-choice:hover, .lf-hotkey-key, .lf-hotkey-key > span, .Select-control, .has-value.Select--single > .Select-control .Select-value .Select-value-label, .has-value.is-pseudo-focused.Select--single > .Select-control .Select-value .Select-value-label, .Select-input > input, .Select-option.is-selected, .Select-option.is-focused, .Select--multi .Select-value, .Select--multi a.Select-value-label, .DayPicker-NavButton:after, .DayPicker-Caption > select, .DayPicker-TodayButton, .DayPicker-Day--today, .lf-section-title, .lf-input-date-placeholder, .lf-input-time-placeholder, .lf-input-datepicker-wrapper.lf-datepicler-icon-calendar:after, .lf-clear-datepicker, .lf-input-multiselect-label, .lf-multiselect-selected i, .lf-selected .lf-hotkey-key, .lf-selected .lf-hotkey-key > span, .lf-total-input-value, .lf-li-rate-highlight, .Select--multi .Select-value-icon {
				color: {$answer_color}
			}
			.lf-is-touch .lf-field-warning.lf-input-text-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-textarea-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-number-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-phone-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-date-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-time-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-select-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-multiselect-wrapper:after, .lf-is-touch .lf-field-warning.lf-input-fileupload-wrapper:after, .lf-field-warning .lf-input-text, .lf-field-warning .lf-input-number, .lf-field-warning .lf-input-phone, .lf-field-warning .lf-input-date, .lf-field-warning .lf-input-time, .lf-field-warning .lf-input-terminal-number, .lf-field-warning .lf-input-select, .lf-field-warning .lf-input-multiselect-wrapper, .lf-field-warning .lf-input-fileupload, .lf-field-warning .lf-jumper-field, .lf-field-warning .lf-input-textarea, .Select-clear-zone:hover:after, .lf-needs-review .lf-submit .lf-warning-icon, .lf-question i.lf-question-icon-required, .lf-section-title i.lf-question-icon-required, .lf-clear-datepicker:hover, .lf-field-errors, .lf-invalid-email-warning, .lf-rejected-files {
			  color: {$warning_color}
			}
			.lf-field-warning .lf-input-text:-ms-input-placeholder, .lf-field-warning .lf-input-number:-ms-input-placeholder, .lf-field-warning .lf-input-phone:-ms-input-placeholder, .lf-field-warning .lf-input-date:-ms-input-placeholder, .lf-field-warning .lf-input-time:-ms-input-placeholder, .lf-field-warning .lf-input-terminal-number:-ms-input-placeholder, .lf-field-warning .lf-input-select:-ms-input-placeholder, .lf-field-warning .lf-input-multiselect-wrapper:-ms-input-placeholder, .lf-field-warning .lf-input-fileupload:-ms-input-placeholder, .lf-field-warning .lf-jumper-field:-ms-input-placeholder, .lf-field-warning .lf-input-textarea:-ms-input-placeholder, .lf-field-warning .lf-input-text:-moz-placeholder, .lf-field-warning .lf-input-number:-moz-placeholder, .lf-field-warning .lf-input-phone:-moz-placeholder, .lf-field-warning .lf-input-date:-moz-placeholder, .lf-field-warning .lf-input-time:-moz-placeholder, .lf-field-warning .lf-input-terminal-number:-moz-placeholder, .lf-field-warning .lf-input-select:-moz-placeholder, .lf-field-warning .lf-input-multiselect-wrapper:-moz-placeholder, .lf-field-warning .lf-input-fileupload:-moz-placeholder, .lf-field-warning .lf-jumper-field:-moz-placeholder, .lf-field-warning .lf-input-textarea:-moz-placeholder, .lf-field-warning .lf-input-text::-moz-placeholder, .lf-field-warning .lf-input-number::-moz-placeholder, .lf-field-warning .lf-input-phone::-moz-placeholder, .lf-field-warning .lf-input-date::-moz-placeholder, .lf-field-warning .lf-input-time::-moz-placeholder, .lf-field-warning .lf-input-terminal-number::-moz-placeholder, .lf-field-warning .lf-input-select::-moz-placeholder, .lf-field-warning .lf-input-multiselect-wrapper::-moz-placeholder, .lf-field-warning .lf-input-fileupload::-moz-placeholder, .lf-field-warning .lf-jumper-field::-moz-placeholder, .lf-field-warning .lf-input-textarea::-moz-placeholder, .lf-field-warning .lf-input-text::-webkit-input-placeholder, .lf-field-warning .lf-input-number::-webkit-input-placeholder, .lf-field-warning .lf-input-phone::-webkit-input-placeholder, .lf-field-warning .lf-input-date::-webkit-input-placeholder, .lf-field-warning .lf-input-time::-webkit-input-placeholder, .lf-field-warning .lf-input-terminal-number::-webkit-input-placeholder, .lf-field-warning .lf-input-select::-webkit-input-placeholder, .lf-field-warning .lf-input-multiselect-wrapper::-webkit-input-placeholder, .lf-field-warning .lf-input-fileupload::-webkit-input-placeholder, .lf-field-warning .lf-jumper-field::-webkit-input-placeholder, .lf-field-warning .lf-input-textarea::-webkit-input-placeholder, .lf-field-warning .lf-input-text:hover, .lf-field-warning .lf-input-number:hover, .lf-field-warning .lf-input-phone:hover, .lf-field-warning .lf-input-date:hover, .lf-field-warning .lf-input-time:hover, .lf-field-warning .lf-input-terminal-number:hover, .lf-field-warning .lf-input-select:hover, .lf-field-warning .lf-input-multiselect-wrapper:hover, .lf-field-warning .lf-input-fileupload:hover, .lf-field-warning .lf-jumper-field:hover, .lf-field-warning .lf-input-textarea:hover {
			  border-color: rgba({$warning_color_rgb}, 0.3)
			}
			.lf-is-touch .lf-field-warning .lf-input-text, .lf-is-touch .lf-field-warning .lf-input-number, .lf-is-touch .lf-field-warning .lf-input-phone, .lf-is-touch .lf-field-warning .lf-input-date, .lf-is-touch .lf-field-warning .lf-input-time, .lf-is-touch .lf-field-warning .lf-input-terminal-number, .lf-is-touch .lf-field-warning .lf-input-select, .lf-is-touch .lf-field-warning .lf-input-multiselect-wrapper, .lf-is-touch .lf-field-warning .lf-input-fileupload, .lf-is-touch .lf-field-warning .lf-jumper-field, .lf-is-touch .lf-field-warning .lf-input-textarea, .lf-field-warning .lf-input-text:focus, .lf-field-warning .lf-input-number:focus, .lf-field-warning .lf-input-phone:focus, .lf-field-warning .lf-input-date:focus, .lf-field-warning .lf-input-time:focus, .lf-field-warning .lf-input-terminal-number:focus, .lf-field-warning .lf-input-select:focus, .lf-field-warning .lf-input-multiselect-wrapper:focus, .lf-field-warning .lf-input-fileupload:focus, .lf-field-warning .lf-jumper-field:focus, .lf-field-warning .lf-input-textarea:focus {
			  border-color: rgba({$warning_color_rgb}, 0.9)
			}
			.lf-li-choice.lf-selected, .lf-review-fields-button, .lf-nav-review, .lf-nav-inner, .lf-welcome-start-button, .lf-submit-button-text, .lf-upload-button, .lf-save-and-continue-email-submit, .lf-save-and-continue-button, .lf-pages-nav-button-text, .lf-li-choice, .lf-delete-file, .lf-list-row-creator.lf-button-text, .DayPicker, .lf-floating-press-enter, .lf-progress-box, .lf-list-row, .lf-footer.lf-footer-welcome {
			  background-color: rgba({$bg_color_rgb}, 0.9);
			}
			.lf-li-choice.lf-selected, .lf-review-fields-button, .lf-nav-review, .lf-nav-inner, .lf-welcome-start-button, .lf-submit-button-text, .lf-upload-button, .lf-save-and-continue-email-submit, .lf-save-and-continue-button, .lf-pages-nav-button-text, .lf-li-choice, .lf-delete-file, .lf-list-row-creator.lf-button-text, .DayPicker, .lf-floating-press-enter, .lf-progress-box, .lf-list-row {
			  border-color: rgba({$question_color_rgb}, 0.1);
			}
			.lf-review-fields-button:active, .lf-nav-review:active, .lf-nav-inner:active, .lf-welcome-start-button:active, .lf-submit-button-text:active, .lf-upload-button:active, .lf-save-and-continue-email-submit:active, .lf-save-and-continue-button:active, .lf-pages-nav-button-text:active, .lf-li-choice:active, .lf-delete-file:active, .lf-list-row-creator.lf-button-text:active, .DayPicker:active, .lf-floating-press-enter:active, .lf-progress-box:active, .lf-list-row:active, .lf-hotkey-key, .lf-hotkey-key > span, .input + .toggle {
			  border-color: rgba({$question_color_rgb}, 0.25);
			}
			.lf-nav-inner, .lf-welcome-start-button, .lf-submit-button-text, .lf-upload-button, .lf-save-and-continue-email-submit, .lf-nav-inner:active, .lf-welcome-start-button:active, .lf-submit-button-text:active, .lf-upload-button:active, .lf-save-and-continue-email-submit:active, .lf-nav-inner:hover, .lf-welcome-start-button:hover, .lf-submit-button-text:hover, .lf-upload-button:hover, .lf-save-and-continue-email-submit:hover {
			  background-color: {$button_color};
			  border-color: ".Lastform_Color::hex_darker($button_color).";
			  color: {$bg_color};
			}
			.lf-page-progress-percentage .lf-progress-bar-fill span, .lf-li-choice.lf-selected {
			  color: {$bg_color};
			}
			.lf-nav-inner:active:not(.lf-disabled), .lf-welcome-start-button:active:not(.lf-disabled), .lf-submit-button-text:active:not(.lf-disabled), .lf-upload-button:active:not(.lf-disabled), .lf-save-and-continue-email-submit:active:not(.lf-disabled) {
			  border-color: ".Lastform_Color::hex_darker($button_color, 60).";
			  background-color: ".Lastform_Color::hex_darker($button_color).";
			}
			.lf-li-choice.lf-selected, .DayPicker-Day--selected:not(.DayPicker-Day--disabled):not(.DayPicker-Day--outside), .input:checked + .toggle:after, .lf-input-terminal-number:focus, .lf-progress-bar-fill {
			  background-color: {$answer_color};
			}
			.lf-li-choice.lf-selected {
			  border-color: ".Lastform_Color::hex_darker($answer_color).";
			}
			.lf-li-choice.lf-selected:active:not(.lf-disabled) {
			  border-color: ".Lastform_Color::hex_darker($answer_color, 60).";
			  background-color: ".Lastform_Color::hex_darker($answer_color).";
			}
			body, .lf-save-and-continue-button, .lf-pages-nav-button-text, .lf-li-choice, .lf-hotkey-key, .lf-hotkey-key > span, .lf-selected .lf-hotkey-key, .lf-selected .lf-hotkey-key > span, .Select-menu-outer, .Select-option, .DayPicker, .DayPicker-Caption > select {
			  background-color: {$bg_color};
			}
			.lf-save-and-continue-button, .lf-pages-nav-button-text {
			  border-color: {$button_color};
			}
			.lf-save-and-continue-button, .lf-pages-nav-button-text, .lf-save-and-continue-button:active, .lf-pages-nav-button-text:active, .lf-save-and-continue-button:hover, .lf-pages-nav-button-text:hover, .lf-loader {
			  color: {$button_color};
			}
			.lf-li-choice, .input:checked + .toggle {
			  border-color: {$answer_color};
			}
			.lf-review-fields-button, .lf-nav-review {
			  background-color: {$warning_color};
			  border-color: ".Lastform_Color::hex_darker($warning_color).";
			}
			.lf-review-fields-button:active:not(.lf-disabled), .lf-nav-review:active:not(.lf-disabled) {
			  border-color: ".Lastform_Color::hex_darker($warning_color, 60).";
			  background-color: ".Lastform_Color::hex_darker($warning_color).";
			}
			.lf-shift-enter-tip, .lf-choose-multiple-tip, .lf-hotkey-key, .lf-hotkey-key > span, .DayPicker-Day, .label {
			  color: rgba({$question_color_rgb}, 0.5);
			}
			.lf-shift-enter-tip > i, .lf-choose-multiple-tip > i, .lf-shift-enter-tip strong, .lf-choose-multiple-tip strong {
			  color: rgba({$question_color_rgb}, 0.8);
			}
			.lf-input-text, .lf-input-number, .lf-input-phone, .lf-input-date, .lf-input-time, .lf-input-terminal-number, .lf-input-select, .lf-input-multiselect-wrapper, .lf-input-fileupload, .lf-jumper-field, .lf-input-textarea, .Select-control {
				border-color: rgba({$question_color_rgb}, 0.15);
			}
			.lf-selected .lf-hotkey-key, .lf-selected .lf-hotkey-key > span {
			  border-color: ".Lastform_Color::hex_darker($answer_color).";
			}
			.has-value.Select--single > .Select-control .Select-value a.Select-value-label:hover, .has-value.Select--single > .Select-control .Select-value a.Select-value-label:focus, .has-value.is-pseudo-focused.Select--single > .Select-control .Select-value a.Select-value-label:hover, .has-value.is-pseudo-focused.Select--single > .Select-control .Select-value a.Select-value-label:focus, .Select-control:hover .Select-clear-zone:after, .is-open .Select-clear-zone:after {
			  color: rgba({$answer_color_rgb}, 0.9);
			}
			.Select-loading {
			  border-right-color: {$answer_color};
			}
			.Select-arrow:after {
			  color: rgba({$question_color_rgb}, 0.15);
			}
			.Select-option {
			  color: rgba({$question_color_rgb},.8);
			}
			.Select-option.is-selected {
			  background-color: rgba({$answer_color_rgb}, 0.05);
			}
			.Select-option.is-focused {
			  background-color: rgba({$answer_color_rgb}, 0.1);
			}
			.Select--multi .Select-value-icon:hover, .Select--multi .Select-value-icon:focus {
			  color: ".Lastform_Color::hex_darker($answer_color, 5).";
			}
			.DayPicker-Caption > select {
			  border-color: rgba({$answer_color_rgb}, 0.6);
			}
			.DayPicker-Day:hover, .lf-is-not-touch .lf-input-fileupload.lf-active {
			  background-color: rgba({$answer_color_rgb}, 0.15);
			}
			.input + .toggle:after {
			  background-color: rgba({$question_color_rgb}, 0.25);
			}
			.lf-section-title {
			  border-bottom-color: {$answer_color};
			}
			.lf-input-terminal-wrapper > span {
			  color: rgba({$question_color_rgb}, 0.2);
			}
			.lf-input-terminal-number:focus {
			  text-shadow: 0 0 0 {$bg_color};
			}
			body, .lf-multiselect-selected, .lf-is-not-touch .lf-input-fileupload.lf-active {
			  color: {$question_color};
			}
			.lf-file-preview i, .lf-floating-press-enter {
			  color: rgba({$question_color_rgb}, 0.75);
			}
			.lf-progress-bar {
			  background-color: rgba({$question_color_rgb}, 0.1);
			}
			.lf-li-choice {
			  background-color: rgba({$bg_color_rgb}, 0.5);
			}
			.lf-waypoint-inactive {
				opacity: {$unfocused_fields_opacity};
			}
		";

		/**
		 * Background Image CSS
		 */

		if ( ! empty($bg_image_url) ) {
			/**
			 * Image
			 */
			$css .= "
				.lf-bg {
					background-image:url({$options['bg-image-url']});
				}
			";

			/**
			 * Lighter or Darker
			 */
			if ($bg_luminosity_level && $bg_luminosity == 'lighter') {
				$css .= "
					.lf-bg:after {
						background-color:#fff;
					}
				";
			} else if ($bg_luminosity_level) {
				$css .= "
					.lf-bg:after {
						background-color:#000;
					}
				";
			}

			/**
			 * Luminosity level
			 */
			if ($bg_luminosity_level) {
				$bg_luminosity_level = $bg_luminosity_level / 100;
				$css .= "
					.lf-bg:after {
						opacity:{$bg_luminosity_level};
					}
				";
			}

			/**
			 * Image scaling
			 */
			switch ($bg_image_scaling) {
				case 'repeat':
					$css .= "
						.lf-bg {
							background-repeat: repeat;
						}
					";
					break;
				case 'no-repeat':
					$css .= "
						.lf-bg {
							background-repeat: no-repeat;
						}
					";
					break;
				// Fullscreen
				default:
					$css .= "
						.lf-bg {
							background-position: center;
							-moz-background-size: cover;
							-o-background-size: cover;
							-webkit-background-size: cover;
							background-size: cover;
						}
					";
					break;
			}
		}

		$css = apply_filters('lastform_public_get_form_inline_style', $css, $form);

		echo '<style>' . trim(preg_replace('/\s+/', ' ', $css)) . '</style>';
	}

}
