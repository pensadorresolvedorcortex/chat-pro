<?php
/**
 * Bricks builder helper.
 *
 * @package Ultimate_Dashboard_Pro
 */

namespace UdbPro\Helpers;

use ReflectionClass;
use WP_Post;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Class to setup Breakdance helper.
 */
class Breakdance_Helper {

	/**
	 * WP_Post instance.
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Class constructor.
	 *
	 * @param WP_Post|null $post Instance of WP_Post.
	 */
	public function __construct( $post = null ) {

		$this->post = $post;

	}

	/**
	 * Check whether "Breakdance" builder is active.
	 *
	 * @return bool
	 */
	public function is_active() {

		if (
			function_exists( '\Breakdance\Render\render' )
			&& function_exists( '\Breakdance\Render\getWordPressHtmlOutput' )
			&& function_exists( '\Breakdance\Render\renderHtmlFromScriptAndStyleHolder' )
			&& function_exists( '\Breakdance\Themeless\outputHeadHtml' )
			&& function_exists( '\Breakdance\Themeless\get_header_for_theme_simulator_having_breakdance_template_for_request' )
			&& function_exists( '\Breakdance\Themeless\get_footer_for_theme_simulator_having_breakdance_template_for_request' )
			&& class_exists( '\Breakdance\Render\ScriptAndStyleHolder' )
			&& defined( '__BREAKDANCE_DIR__' )
			&& defined( 'BREAKDANCE_HEADER_ASSETS_PLACEHOLDER' )
			&& defined( 'BREAKDANCE_FOOTER_ASSETS_PLACEHOLDER' )
			&& defined( 'BREAKDANCE_ASSETS_PRIORITY' )
		) {
			return true;
		}

		return false;

	}

	/**
	 * Verify if a post was built with Breakdance Builder.
	 *
	 * @return bool
	 */
	public function built_with_breakdance() {

		if ( ! $this->is_active() ) {
			return false;
		}

		if ( ! get_post_meta( $this->post->ID, 'breakdance_dependency_cache', true ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Prepare Breakdance output.
	 */
	public function prepare_output() {

		if ( ! $this->is_active() ) {
			return;
		}

	}

	/**
	 * Render the content of a post.
	 */
	public function render_content() {

		if ( ! $this->post ) {
			echo '';
		}

		if ( ! $this->is_active() ) {
			echo apply_filters( 'the_content', $this->post->post_content );

			return;
		}

		// echo \Breakdance\Render\render( $post_id );

		ob_start();

		echo BREAKDANCE_HEADER_ASSETS_PLACEHOLDER;

		$renderedTemplateHtml = \Breakdance\Render\render( $this->post->ID );

		echo $renderedTemplateHtml;

		echo BREAKDANCE_FOOTER_ASSETS_PLACEHOLDER;

		$html = ob_get_clean();

		$headerAndFooterHtml = \Breakdance\Render\renderHtmlFromScriptAndStyleHolder(
			\Breakdance\Render\ScriptAndStyleHolder::getInstance()
		);

		echo str_replace(
			array( BREAKDANCE_HEADER_ASSETS_PLACEHOLDER, BREAKDANCE_FOOTER_ASSETS_PLACEHOLDER ),
			array( $headerAndFooterHtml['headerHtml'], $headerAndFooterHtml['footerHtml'] ),
			$html
		);

	}

}
