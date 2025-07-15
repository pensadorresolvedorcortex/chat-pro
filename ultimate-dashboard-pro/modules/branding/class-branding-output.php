<?php
/**
 * Branding output.
 *
 * @package Ultimate_Dashboard_Pro
 */

namespace UdbPro\Branding;

defined( 'ABSPATH' ) || die( "Can't access directly" );

use Udb\Base\Base_Output;
use UdbPro\Helpers\Branding_Helper;

/**
 * Class to setup branding output.
 */
class Branding_Output extends Base_Output {

	/**
	 * The class instance.
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * The current module url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Module constructor.
	 */
	public function __construct() {

		$this->url = ULTIMATE_DASHBOARD_PRO_PLUGIN_URL . '/modules/branding';

	}

	/**
	 * Get instance of the class.
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Init the class setup.
	 */
	public static function init() {

		$class = new self();
		$class->setup();

	}

	/**
	 * Setup branding output.
	 */
	public function setup() {

		add_action( 'admin_enqueue_scripts', array( self::get_instance(), 'dashboard_styles' ), 100 );
		add_action( 'wp_enqueue_scripts', array( self::get_instance(), 'frontend_styles' ), 100 );

		add_action( 'admin_enqueue_scripts', array( self::get_instance(), 'wp_admin_darkmode_styles' ), 100 );
		add_action( 'admin_enqueue_scripts', array( self::get_instance(), 'block_editor_darkmode_styles' ), 100 );

		add_action( 'admin_head', array( self::get_instance(), 'admin_styles' ), 100 );
		add_action( 'admin_head', array( self::get_instance(), 'admin_styles_preview' ), 120 );

		add_filter( 'udb_branding_dashboard_styles', array( self::get_instance(), 'minify_css' ), 20 );
		add_filter( 'udb_branding_admin_styles', array( self::get_instance(), 'minify_css' ), 20 );
		add_filter( 'udb_branding_frontend_styles', array( self::get_instance(), 'minify_css' ), 20 );
		add_filter( 'udb_branding_login_styles', array( self::get_instance(), 'minify_css' ), 20 );

		add_action( 'admin_bar_menu', array( self::get_instance(), 'replace_admin_bar_logo' ), 11 );
		add_filter( 'udb_admin_bar_logo_url', array( self::get_instance(), 'change_admin_bar_logo_url' ) );
		add_action( 'admin_bar_menu', array( self::get_instance(), 'remove_admin_bar_logo' ), 99 );

		add_action( 'admin_head', array( self::get_instance(), 'replace_block_editor_logo' ), 15 );

		add_action( 'adminmenu', array( self::get_instance(), 'modern_admin_bar_logo' ) );
		add_action( 'adminmenu', array( self::get_instance(), 'modern_admin_bar_logo_preview' ), 30 );

	}

	/**
	 * Enqueue dashboard styles.
	 */
	public function dashboard_styles() {

		$udb_dashboard_styles = $this->get_dashboard_styles();
		wp_add_inline_style( 'udb-dashboard', $udb_dashboard_styles );

	}

	/**
	 * Enqueue darkmode styles for admin area.
	 */
	public function wp_admin_darkmode_styles() {

		$screen_helper = $this->screen();

		// The `$screen_helper->is_block_editor_page()` was a new method added along when adding the dark mode feature.
		if ( ! method_exists( $screen_helper, 'is_block_editor_page' ) ) {
			return;
		}

		// The branding page has its own darkmode style tag.
		if ( $screen_helper->is_branding() ) {
			return;
		}

		$branding = get_option( 'udb_branding', array() );

		$darkmode_enabled = ! empty( $branding['wp_admin_darkmode'] );

		// Enqueue dark mode for admin area.
		if ( $darkmode_enabled && ! $screen_helper->is_block_editor_page() ) {
			wp_enqueue_style( 'udb-wp-admin-darkmode', $this->url . '/assets/css/wp-admin-darkmode.css', array(), ULTIMATE_DASHBOARD_PRO_PLUGIN_VERSION );
		}

	}

	/**
	 * Enqueue darkmode styles for block editor screen.
	 */
	public function block_editor_darkmode_styles() {

		$screen_helper = $this->screen();

		// The `$screen_helper->is_block_editor_page()` was a new method added along when adding the dark mode feature.
		if ( ! method_exists( $screen_helper, 'is_block_editor_page' ) ) {
			return;
		}

		// The branding page has its own darkmode style tag.
		if ( $screen_helper->is_branding() ) {
			return;
		}

		$branding = get_option( 'udb_branding', array() );

		$darkmode_enabled = ! empty( $branding['block_editor_darkmode'] );

		// Enqueue dark mode for block editor screen.
		if ( $darkmode_enabled && $screen_helper->is_block_editor_page() ) {
			wp_enqueue_style( 'udb-block-editor-darkmode', $this->url . '/assets/css/block-editor-darkmode.css', array(), ULTIMATE_DASHBOARD_PRO_PLUGIN_VERSION );
		}

	}

	/**
	 * Get dashboard styles.
	 *
	 * @return string The dashboard CSS.
	 */
	public function get_dashboard_styles() {

		$css = '';

		ob_start();
		include_once __DIR__ . '/inc/widget-styles.css.php';
		$css = ob_get_clean();

		return apply_filters( 'udb_branding_dashboard_styles', $css );

	}

	/**
	 * Print admin styles.
	 *
	 * @param bool $inherit_blueprint Whether the admin styles source is inherited from blueprint.
	 */
	public function admin_styles( $inherit_blueprint = false ) {

		$branding         = get_option( 'udb_branding', array() );
		$branding_enabled = isset( $branding['enabled'] );
		$active_layout    = isset( $branding['layout'] ) && 'modern' === $branding['layout'] ? 'modern' : 'default';

		if ( ! $inherit_blueprint && $this->screen()->is_branding() ) {
			return;
		}

		if ( $branding_enabled ) {
			echo '<style class="udb-admin-colors-output udb-' . $active_layout . '-admin-colors-output ' . ( $inherit_blueprint ? 'udb-inherited-from-blueprint' : '' ) . '">' . $this->get_admin_styles( $active_layout ) . '</style>';
		}

	}

	/**
	 * Print admin styles for preview purpose.
	 */
	public function admin_styles_preview() {

		$branding         = get_option( 'udb_branding', array() );
		$branding_enabled = isset( $branding['enabled'] );
		$active_layout    = isset( $branding['layout'] ) && 'modern' === $branding['layout'] ? 'modern' : 'default';
		$darkmode_enabled = ! empty( $branding['wp_admin_darkmode'] );

		if ( ! $this->screen()->is_branding() ) {
			return;
		}

		echo '<style' . ( ! $darkmode_enabled ? ' type="text/udb"' : '' ) . ' class="udb-wp-admin-darkmode-preview udb-darkmode-output udb-wp-admin-darkmode-output">' . $this->get_darkmode_styles( 'wp-admin' ) . '</style>
		';

		echo '<style' . ( ! $branding_enabled || 'default' !== $active_layout ? ' type="text/udb"' : '' ) . ' class="udb-admin-colors-preview udb-admin-colors-output udb-default-admin-colors-output">' . $this->get_admin_styles( 'default' ) . '</style>
		';

		echo '<style' . ( ! $branding_enabled || 'modern' !== $active_layout ? ' type="text/udb"' : '' ) . ' class="udb-admin-colors-preview udb-admin-colors-output udb-modern-admin-colors-output">' . $this->get_admin_styles( 'modern' ) . '</style>';

	}

	/**
	 * Get darkmode styles.
	 *
	 * @param string $target The target to get the styles for. Accepts "wp-admin" or "block-editor".
	 * @return string The darkmode CSS.
	 */
	public function get_darkmode_styles( $target = 'wp-admin' ) {

		ob_start();

		require __DIR__ . '/assets/css/' . $target . '-darkmode.css';

		$css = ob_get_clean();

		return apply_filters( 'udb_branding_darkmode_styles', $css );

	}

	/**
	 * Get admin styles.
	 *
	 * @param string $layout The layout to get the styles for. Accepts "default" or "modern".
	 * @return string The admin CSS.
	 */
	public function get_admin_styles( $layout = 'default' ) {

		ob_start();

		require __DIR__ . '/inc/admin-styles-' . $layout . '.css.php';

		$css = ob_get_clean();

		return apply_filters( 'udb_branding_admin_styles', $css );

	}

	/**
	 * Enqueue frontend styles.
	 */
	public function frontend_styles() {

		$branding_helper = new Branding_Helper();

		if ( ! $branding_helper->is_enabled() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		$udb_frontend_styles = $this->get_frontend_styles();
		wp_add_inline_style( 'admin-bar', $udb_frontend_styles );

	}

	/**
	 * Get frontend styles.
	 *
	 * @return string The frontend CSS.
	 */
	public function get_frontend_styles() {

		$css = '';

		ob_start();
		include_once __DIR__ . '/inc/frontend-styles.css.php';
		$css = ob_get_clean();

		return apply_filters( 'udb_branding_frontend_styles', $css );

	}

	/**
	 * Minify CSS
	 *
	 * @param string $css The css.
	 *
	 * @return string the minified CSS.
	 */
	public function minify_css( $css ) {

		// Remove comments.
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );

		// Remove spaces.
		$css = str_replace( ': ', ':', $css );
		$css = str_replace( ' {', '{', $css );
		$css = str_replace( ', ', ',', $css );
		$css = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $css );

		return $css;

	}

	/**
	 * Replace admin bar logo.
	 *
	 * We do this to add a filter to the logo URL.
	 *
	 * @param object $wp_admin_bar The wp admin bar.
	 */
	public function replace_admin_bar_logo( $wp_admin_bar ) {

		$wp_admin_bar->remove_menu( 'wp-logo' );

		$args = array(
			'id'    => 'wp-logo',
			'title' => '<span class="ab-icon"></span>',
			'href'  => apply_filters( 'udb_admin_bar_logo_url', network_site_url() ),
			'meta'  => array(
				'class' => 'udb-wp-logo',
			),
		);

		if ( is_admin() && $this->screen()->is_branding() ) {
			$branding  = get_option( 'udb_branding' );
			$classname = 'udb-wp-logo';

			if ( isset( $branding['enabled'] ) ) {
				if ( isset( $branding['remove_admin_bar_logo'] ) ) {
					$classname = 'udb-wp-logo udb-is-hidden';
				} elseif ( 'modern' === $branding['layout'] ) {
						$classname = 'udb-wp-logo udb-is-hidden';
				}
			}

			$args['meta'] = array(
				'class' => $classname,
			);
		}

		$wp_admin_bar->add_menu( $args );

	}

	/**
	 * Change admin bar logo URL.
	 *
	 * Doesn't require separate multisite support!
	 *
	 * @param string $admin_bar_logo_url The admin bar logo URL.
	 *
	 * @return string The updated admin bar logo URL.
	 */
	public function change_admin_bar_logo_url( $admin_bar_logo_url ) {

		$branding = get_option( 'udb_branding' );

		if ( ! isset( $branding['enabled'] ) ) {
			return $admin_bar_logo_url;
		}

		if ( isset( $branding['remove_admin_bar_logo'] ) ) {
			return $admin_bar_logo_url;
		}

		if ( ! empty( $branding['admin_bar_logo_url'] ) ) {
			$admin_bar_logo_url = $branding['admin_bar_logo_url'];
		}

		return $admin_bar_logo_url;

	}

	/**
	 * Remove admin bar logo.
	 *
	 * @param object $wp_admin_bar The wp admin bar.
	 */
	public function remove_admin_bar_logo( $wp_admin_bar ) {

		$branding = get_option( 'udb_branding' );

		if ( ! is_admin() || ! $this->screen()->is_branding() ) {
			if ( isset( $branding['remove_admin_bar_logo'] ) ) {
				$wp_admin_bar->remove_node( 'wp-logo' );
			}
		}

	}

	/**
	 * Replace block editor logo.
	 */
	public function replace_block_editor_logo() {

		$current_screen = get_current_screen();

		if ( ! property_exists( $current_screen, 'is_block_editor' ) || ! $current_screen->is_block_editor ) {
			return;
		}

		$branding = get_option( 'udb_branding', [] );

		if ( ! isset( $branding['enabled'] ) ) {
			return;
		}

		$logo_url = isset( $branding['block_editor_logo_image'] ) && $branding['block_editor_logo_image'] ? $branding['block_editor_logo_image'] : '';

		if ( ! $logo_url ) {
			return;
		}
		?>

		<style type="text/css" class="udb-block-editor-logo-style">
			#editor .edit-post-header .edit-post-fullscreen-mode-close svg {
				display: none;
			}

			<?php
			/**
			 * We can't use "cover" or "contain" as the value for background-size.
			 * If a square logo is uploaded, "cover" or "contain" doesn't work.
			 * The background image seems cut off.
			 *
			 * The ::before dimension is 42px x 43px.
			 * But if we use 42px as the width, the background image seems cut off.
			 * Also we can't just use 100% for the width.
			 *
			 * That's why set set the background-size to "38px auto".
			 */
			?>
			#editor .edit-post-header .edit-post-fullscreen-mode-close::before {
				background-image: url( <?php echo esc_url( $logo_url ); ?> );
				background-repeat: no-repeat;
				background-position: center;	
				background-size: 38px auto;
			}
		</style>

		<?php
	}

	/**
	 * Modern layout: custom admin bar logo.
	 *
	 * @param bool $inherit_blueprint Whether the admin styles source is inherited from blueprint.
	 */
	public function modern_admin_bar_logo( $inherit_blueprint = false ) {

		$branding_helper     = new Branding_Helper();
		$is_branding_enabled = $branding_helper->is_enabled();
		$branding            = get_option( 'udb_branding' );

		// Stop here if branding is not enabled.
		if ( ! $is_branding_enabled ) {
			return;
		}

		// Stop here if modern layout is not selected.
		if ( ! isset( $branding['layout'] ) || 'modern' !== $branding['layout'] ) {
			return;
		}

		// If no logo is selected, use default.
		if ( ! empty( $branding['admin_bar_logo_image'] ) ) {
			$logo = $branding['admin_bar_logo_image'];
		} else {
			$logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAVkAAAA0CAYAAAAg70vrAAAN4mlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgogICAgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iCiAgICB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIKICAgIHhtbG5zOnN0RXZ0PSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VFdmVudCMiCiAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIgogICB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDQyAoTWFjaW50b3NoKSIKICAgeG1wOkNyZWF0ZURhdGU9IjIwMTctMTAtMjdUMjM6MDA6MTErMDI6MDAiCiAgIHhtcDpNb2RpZnlEYXRlPSIyMDIwLTAzLTA2VDE5OjU4OjM4KzAxOjAwIgogICB4bXA6TWV0YWRhdGFEYXRlPSIyMDIwLTAzLTA2VDE5OjU4OjM4KzAxOjAwIgogICB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjU3M2M4NDg0LTgzOTEtNDlhOS1iNjBhLTc1MTZhNTc5NDI5MyIKICAgeG1wTU06RG9jdW1lbnRJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjYyOGE1MGI3LWZjNzMtMTE3YS04ODcxLThlOWM3M2RlYWRmZiIKICAgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjJBMUQ5OTJEQjM3RTExRTdBMjZEODZBMTU2ODUxODVFIgogICBkYzpmb3JtYXQ9ImFwcGxpY2F0aW9uL3ZuZC5hZG9iZS5waG90b3Nob3AiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgdGlmZjpPcmllbnRhdGlvbj0iMSIKICAgdGlmZjpYUmVzb2x1dGlvbj0iNzIuMCIKICAgdGlmZjpZUmVzb2x1dGlvbj0iNzIuMCIKICAgdGlmZjpSZXNvbHV0aW9uVW5pdD0iMiIKICAgdGlmZjpJbWFnZVdpZHRoPSIzNDUiCiAgIHRpZmY6SW1hZ2VMZW5ndGg9IjUyIgogICBleGlmOkNvbG9yU3BhY2U9IjEiCiAgIGV4aWY6UGl4ZWxYRGltZW5zaW9uPSIzNDUiCiAgIGV4aWY6UGl4ZWxZRGltZW5zaW9uPSI1MiI+CiAgIDx4bXBNTTpEZXJpdmVkRnJvbQogICAgc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo0OTQ5YzU2MS01MmY1LTQ3ZDMtYmU1My1mZGJiYTE5ODBmZDIiCiAgICBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjJBMUQ5OTJEQjM3RTExRTdBMjZEODZBMTU2ODUxODVFIgogICAgc3RSZWY6b3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjJBMUQ5OTJEQjM3RTExRTdBMjZEODZBMTU2ODUxODVFIi8+CiAgIDx4bXBNTTpIaXN0b3J5PgogICAgPHJkZjpTZXE+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjQ5NDljNTYxLTUyZjUtNDdkMy1iZTUzLWZkYmJhMTk4MGZkMiIKICAgICAgc3RFdnQ6d2hlbj0iMjAxNy0xMC0zMFQxMTo1OTo1NSswMTowMCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTcgKE1hY2ludG9zaCkiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0iY29udmVydGVkIgogICAgICBzdEV2dDpwYXJhbWV0ZXJzPSJmcm9tIGltYWdlL3BuZyB0byBhcHBsaWNhdGlvbi92bmQuYWRvYmUucGhvdG9zaG9wIi8+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249ImRlcml2ZWQiCiAgICAgIHN0RXZ0OnBhcmFtZXRlcnM9ImNvbnZlcnRlZCBmcm9tIGltYWdlL3BuZyB0byBhcHBsaWNhdGlvbi92bmQuYWRvYmUucGhvdG9zaG9wIi8+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjQzOTZjMDY2LTVjZjMtNGQ0OC05NDUwLWZmZGY3ZWIzYTc3MyIKICAgICAgc3RFdnQ6d2hlbj0iMjAxNy0xMC0zMFQxMTo1OTo1NSswMTowMCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTcgKE1hY2ludG9zaCkiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiCiAgICAgIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6NTczYzg0ODQtODM5MS00OWE5LWI2MGEtNzUxNmE1Nzk0MjkzIgogICAgICBzdEV2dDp3aGVuPSIyMDE3LTExLTA2VDE5OjQ5OjQ3KzAxOjAwIgogICAgICBzdEV2dDpzb2Z0d2FyZUFnZW50PSJBZG9iZSBQaG90b3Nob3AgQ0MgKE1hY2ludG9zaCkiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0icHJvZHVjZWQiCiAgICAgIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkFmZmluaXR5IFBob3RvIDEuOC4wIgogICAgICBzdEV2dDp3aGVuPSIyMDIwLTAzLTA2VDE5OjU4OjM4KzAxOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICAgPHhtcE1NOkluZ3JlZGllbnRzPgogICAgPHJkZjpCYWc+CiAgICAgPHJkZjpsaQogICAgICBzdFJlZjpsaW5rRm9ybT0iUmVmZXJlbmNlU3RyZWFtIgogICAgICBzdFJlZjpmaWxlUGF0aD0iY2xvdWQtYXNzZXQ6Ly9jYy1hcGktc3RvcmFnZS5hZG9iZS5pby9hc3NldHMvYWRvYmUtbGlicmFyaWVzL2ExOWM5N2MwLTZmZGMtMTFlNC1iZDQ1LTZmNWE5YjU1ZmNmYjtub2RlPTIyNTVlNmU0LWIyZmItNDlhOC04MmZiLWVjZjFjNGE2NGViMCIKICAgICAgc3RSZWY6RG9jdW1lbnRJRD0idXVpZDphYzdiNjI5Mi1lYzEyLTdkNDctYWEzZS00MDE1MmNkNjM0ZDQiLz4KICAgICA8cmRmOmxpCiAgICAgIHN0UmVmOmxpbmtGb3JtPSJSZWZlcmVuY2VTdHJlYW0iCiAgICAgIHN0UmVmOmZpbGVQYXRoPSJjbG91ZC1hc3NldDovL2NjLWFwaS1zdG9yYWdlLmFkb2JlLmlvL2Fzc2V0cy9hZG9iZS1saWJyYXJpZXMvYTE5Yzk3YzAtNmZkYy0xMWU0LWJkNDUtNmY1YTliNTVmY2ZiO25vZGU9NWQyMjBmYTEtMjQ1OC00NDgwLThkMDMtMzRjMzNhMTU2NDVhIgogICAgICBzdFJlZjpEb2N1bWVudElEPSJ1dWlkOjAyNjM2ZDI3LWQ1YzctYmE0MS04NzI2LWEzNDc4YTkwZTg2OSIvPgogICAgPC9yZGY6QmFnPgogICA8L3htcE1NOkluZ3JlZGllbnRzPgogIDwvcmRmOkRlc2NyaXB0aW9uPgogPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KPD94cGFja2V0IGVuZD0iciI/PrF7UB4A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';
		}

		// If no logo url was set, use default.
		if ( ! empty( $branding['admin_bar_logo_url'] ) ) {
			$url = $branding['admin_bar_logo_url'];
		} else {
			$url = network_site_url();
		}

		// Let's add a filter, in case someone wants to dynamically change the logo.
		$logo = apply_filters( 'udb_admin_bar_logo_image', $logo );

		$classname = '';

		if ( isset( $branding['remove_admin_bar_logo'] ) ) {
			$classname = 'udb-is-hidden';
		}

		if ( $inherit_blueprint ) {
			$classname .= ' udb-inherited-from-blueprint';
		}
		?>

		<li class="udb-admin-logo-wrapper udb-admin-logo-wrapper-output <?php echo esc_attr( $classname ); ?>">
			<a href="<?php echo esc_url( $url ); ?>">
				<img class="udb-admin-logo" src="<?php echo esc_url( $logo ); ?>" />
			</a>
		</li>

		<?php

	}

	/**
	 * Modern layout: custom admin bar logo for preview purpose.
	 */
	public function modern_admin_bar_logo_preview() {

		// Only for branding's settings page.
		if ( ! $this->screen()->is_branding() ) {
			return;
		}

		$branding = get_option( 'udb_branding' );

		// If the saved layout is modern, then we already have the markup.
		if ( isset( $branding['layout'] ) && 'modern' === $branding['layout'] ) {
			return;
		}

		// If no logo is selected, use default.
		if ( ! empty( $branding['admin_bar_logo_image'] ) ) {
			$logo = $branding['admin_bar_logo_image'];
		} else {
			$logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAVkAAAA0CAYAAAAg70vrAAAN4mlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iWE1QIENvcmUgNS41LjAiPgogPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIgogICAgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iCiAgICB4bWxuczpzdFJlZj0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlUmVmIyIKICAgIHhtbG5zOnN0RXZ0PSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VFdmVudCMiCiAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iCiAgICB4bWxuczp0aWZmPSJodHRwOi8vbnMuYWRvYmUuY29tL3RpZmYvMS4wLyIKICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIgogICB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDQyAoTWFjaW50b3NoKSIKICAgeG1wOkNyZWF0ZURhdGU9IjIwMTctMTAtMjdUMjM6MDA6MTErMDI6MDAiCiAgIHhtcDpNb2RpZnlEYXRlPSIyMDIwLTAzLTA2VDE5OjU4OjM4KzAxOjAwIgogICB4bXA6TWV0YWRhdGFEYXRlPSIyMDIwLTAzLTA2VDE5OjU4OjM4KzAxOjAwIgogICB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjU3M2M4NDg0LTgzOTEtNDlhOS1iNjBhLTc1MTZhNTc5NDI5MyIKICAgeG1wTU06RG9jdW1lbnRJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjYyOGE1MGI3LWZjNzMtMTE3YS04ODcxLThlOWM3M2RlYWRmZiIKICAgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjJBMUQ5OTJEQjM3RTExRTdBMjZEODZBMTU2ODUxODVFIgogICBkYzpmb3JtYXQ9ImFwcGxpY2F0aW9uL3ZuZC5hZG9iZS5waG90b3Nob3AiCiAgIHBob3Rvc2hvcDpDb2xvck1vZGU9IjMiCiAgIHBob3Rvc2hvcDpJQ0NQcm9maWxlPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgdGlmZjpPcmllbnRhdGlvbj0iMSIKICAgdGlmZjpYUmVzb2x1dGlvbj0iNzIuMCIKICAgdGlmZjpZUmVzb2x1dGlvbj0iNzIuMCIKICAgdGlmZjpSZXNvbHV0aW9uVW5pdD0iMiIKICAgdGlmZjpJbWFnZVdpZHRoPSIzNDUiCiAgIHRpZmY6SW1hZ2VMZW5ndGg9IjUyIgogICBleGlmOkNvbG9yU3BhY2U9IjEiCiAgIGV4aWY6UGl4ZWxYRGltZW5zaW9uPSIzNDUiCiAgIGV4aWY6UGl4ZWxZRGltZW5zaW9uPSI1MiI+CiAgIDx4bXBNTTpEZXJpdmVkRnJvbQogICAgc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo0OTQ5YzU2MS01MmY1LTQ3ZDMtYmU1My1mZGJiYTE5ODBmZDIiCiAgICBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjJBMUQ5OTJEQjM3RTExRTdBMjZEODZBMTU2ODUxODVFIgogICAgc3RSZWY6b3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjJBMUQ5OTJEQjM3RTExRTdBMjZEODZBMTU2ODUxODVFIi8+CiAgIDx4bXBNTTpIaXN0b3J5PgogICAgPHJkZjpTZXE+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjQ5NDljNTYxLTUyZjUtNDdkMy1iZTUzLWZkYmJhMTk4MGZkMiIKICAgICAgc3RFdnQ6d2hlbj0iMjAxNy0xMC0zMFQxMTo1OTo1NSswMTowMCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTcgKE1hY2ludG9zaCkiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0iY29udmVydGVkIgogICAgICBzdEV2dDpwYXJhbWV0ZXJzPSJmcm9tIGltYWdlL3BuZyB0byBhcHBsaWNhdGlvbi92bmQuYWRvYmUucGhvdG9zaG9wIi8+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249ImRlcml2ZWQiCiAgICAgIHN0RXZ0OnBhcmFtZXRlcnM9ImNvbnZlcnRlZCBmcm9tIGltYWdlL3BuZyB0byBhcHBsaWNhdGlvbi92bmQuYWRvYmUucGhvdG9zaG9wIi8+CiAgICAgPHJkZjpsaQogICAgICBzdEV2dDphY3Rpb249InNhdmVkIgogICAgICBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjQzOTZjMDY2LTVjZjMtNGQ0OC05NDUwLWZmZGY3ZWIzYTc3MyIKICAgICAgc3RFdnQ6d2hlbj0iMjAxNy0xMC0zMFQxMTo1OTo1NSswMTowMCIKICAgICAgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTcgKE1hY2ludG9zaCkiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0ic2F2ZWQiCiAgICAgIHN0RXZ0Omluc3RhbmNlSUQ9InhtcC5paWQ6NTczYzg0ODQtODM5MS00OWE5LWI2MGEtNzUxNmE1Nzk0MjkzIgogICAgICBzdEV2dDp3aGVuPSIyMDE3LTExLTA2VDE5OjQ5OjQ3KzAxOjAwIgogICAgICBzdEV2dDpzb2Z0d2FyZUFnZW50PSJBZG9iZSBQaG90b3Nob3AgQ0MgKE1hY2ludG9zaCkiCiAgICAgIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4KICAgICA8cmRmOmxpCiAgICAgIHN0RXZ0OmFjdGlvbj0icHJvZHVjZWQiCiAgICAgIHN0RXZ0OnNvZnR3YXJlQWdlbnQ9IkFmZmluaXR5IFBob3RvIDEuOC4wIgogICAgICBzdEV2dDp3aGVuPSIyMDIwLTAzLTA2VDE5OjU4OjM4KzAxOjAwIi8+CiAgICA8L3JkZjpTZXE+CiAgIDwveG1wTU06SGlzdG9yeT4KICAgPHhtcE1NOkluZ3JlZGllbnRzPgogICAgPHJkZjpCYWc+CiAgICAgPHJkZjpsaQogICAgICBzdFJlZjpsaW5rRm9ybT0iUmVmZXJlbmNlU3RyZWFtIgogICAgICBzdFJlZjpmaWxlUGF0aD0iY2xvdWQtYXNzZXQ6Ly9jYy1hcGktc3RvcmFnZS5hZG9iZS5pby9hc3NldHMvYWRvYmUtbGlicmFyaWVzL2ExOWM5N2MwLTZmZGMtMTFlNC1iZDQ1LTZmNWE5YjU1ZmNmYjtub2RlPTIyNTVlNmU0LWIyZmItNDlhOC04MmZiLWVjZjFjNGE2NGViMCIKICAgICAgc3RSZWY6RG9jdW1lbnRJRD0idXVpZDphYzdiNjI5Mi1lYzEyLTdkNDctYWEzZS00MDE1MmNkNjM0ZDQiLz4KICAgICA8cmRmOmxpCiAgICAgIHN0UmVmOmxpbmtGb3JtPSJSZWZlcmVuY2VTdHJlYW0iCiAgICAgIHN0UmVmOmZpbGVQYXRoPSJjbG91ZC1hc3NldDovL2NjLWFwaS1zdG9yYWdlLmFkb2JlLmlvL2Fzc2V0cy9hZG9iZS1saWJyYXJpZXMvYTE5Yzk3YzAtNmZkYy0xMWU0LWJkNDUtNmY1YTliNTVmY2ZiO25vZGU9NWQyMjBmYTEtMjQ1OC00NDgwLThkMDMtMzRjMzNhMTU2NDVhIgogICAgICBzdFJlZjpEb2N1bWVudElEPSJ1dWlkOjAyNjM2ZDI3LWQ1YzctYmE0MS04NzI2LWEzNDc4YTkwZTg2OSIvPgogICAgPC9yZGY6QmFnPgogICA8L3htcE1NOkluZ3JlZGllbnRzPgogIDwvcmRmOkRlc2NyaXB0aW9uPgogPC9yZGY6UkRGPgo8L3g6eG1wbWV0YT4KPD94cGFja2V0IGVuZD0iciI/PrF7UB4A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';
		}

		// If no logo url was set, use default.
		if ( ! empty( $branding['admin_bar_logo_url'] ) ) {
			$url = $branding['admin_bar_logo_url'];
		} else {
			$url = network_site_url();
		}

		// Let's add a filter, in case someone wants to dynamically change the logo.
		$logo = apply_filters( 'udb_admin_bar_logo_image', $logo );
		?>

		<li class="udb-admin-logo-wrapper udb-admin-logo-wrapper-preview udb-is-hidden">
			<a href="<?php echo esc_url( $url ); ?>">
				<img class="udb-admin-logo" src="<?php echo esc_url( $logo ); ?>" />
			</a>
		</li>

		<?php

	}

	/**
	 * Print color in rgba format from hex color.
	 *
	 * @param string     $hex_color Color in hex format.
	 * @param int|string $opacity The alpha opacity part of an rgba color.
	 */
	public function print_rgba_from_hex( $hex_color, $opacity ) {

		if ( ! class_exists( '\Udb\Helpers\Color_Helper' ) ) {
			echo esc_attr( $hex_color );
			return;
		}

		$color_helper = new \Udb\Helpers\Color_Helper();

		$rgb = $color_helper->hex_to_rgb( $hex_color );

		$rgba_string = 'rgba(' . $rgb[0] . ', ' . $rgb[1] . ', ' . $rgb[2] . ', ' . $opacity . ')';

		echo esc_attr( $rgba_string );

	}

}
