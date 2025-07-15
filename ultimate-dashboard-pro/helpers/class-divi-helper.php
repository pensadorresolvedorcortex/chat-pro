<?php
/**
 * Divi builder helper.
 *
 * @package Ultimate_Dashboard_Pro
 */

namespace UdbPro\Helpers;

use WP_Post;

defined( 'ABSPATH' ) || die( "Can't access directly" );

/**
 * Class to set up Divi helper.
 */
class Divi_Helper {

	/**
	 * WP_Post instance.
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Divi's layout post type.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * Class constructor.
	 *
	 * @param WP_Post|null $post Instance of WP_Post.
	 */
	public function __construct( $post = null ) {

		$this->post = $post;

		$this->post_type = defined( 'ET_BUILDER_LAYOUT_POST_TYPE' ) ? ET_BUILDER_LAYOUT_POST_TYPE : 'et_pb_layout';

	}

	/**
	 * Check whether Divi Builder is active.
	 *
	 * @return bool
	 */
	public function is_active() {

		if ( ! $this->post ) {
			return false;
		}

		if ( ! function_exists( 'et_pb_is_pagebuilder_used' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Verify if a post was built with Divi Builder.
	 *
	 * @return bool
	 */
	public function built_with_divi() {

		if ( ! $this->is_active() ) {
			return false;
		}

		return et_pb_is_pagebuilder_used( $this->post->ID );

	}

	/**
	 * Do stuff before output.
	 */
	public function prepare_hooks() {

		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );

	}

	/**
	 * Do some actions on admin_head hook.
	 */
	public function admin_head() {

		wp_enqueue_script(
			'udb-admin-page-iframe',
			ULTIMATE_DASHBOARD_PRO_PLUGIN_URL . '/modules/admin-page/assets/js/admin-page-iframe.js',
			array(),
			ULTIMATE_DASHBOARD_PRO_PLUGIN_VERSION,
			false
		);

	}

	/**
	 * Do some actions on admin_footer hook.
	 */
	public function admin_footer() {

		// Maybe do something.
	}

	/**
	 * Render the content via iframe.
	 *
	 * @param string $location The location of the rendering process. Accepts 'admin_page' or 'dashboard'.
	 */
	public function render_content( $location = 'admin_page' ) {

		if ( ! $this->post ) {
			echo '';
		}

		$udb_nonce = '';

		if ( 'dashboard' === $location ) {
			$udb_nonce = wp_create_nonce( ULTIMATE_DASHBOARD_PRO_PLUGIN_DIR . $this->post->ID . 'udb-divi-layout-iframe' );
		}

		$post_name = $this->post->post_name;
		$post_url  = site_url( $this->post_type . '/' . $post_name . '/?udb-inside-iframe=1&layout-id=' . $this->post->ID );

		if ( $udb_nonce ) {
			$post_url .= '&udb-nonce=' . $udb_nonce;
		}
		?>

		<iframe src="<?php echo esc_url( $post_url ); ?>" width="100%"
				id="udb-admin-page-iframe"
				style="position: relative; min-height: 1000px; border: 0; margin: 0; padding: 0; overflow: hidden !important;"></iframe>

		<?php
	}

}