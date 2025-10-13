<?php
/**
 * Front-end routing and redirection logic.
 *
 * @package ADC\Login\Frontend
 */

namespace ADC\Login\Frontend;

use ADC\Login\TwoFA\Manager as TwoFA_Manager;
use function ADC\Login\get_onboarding_url;
use function ADC\Login\get_option_value;
use function ADC\Login\get_post_login_url;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Router
 */
class Router {

    /**
     * Two factor manager.
     *
     * @var TwoFA_Manager
     */
    protected $twofa;

    /**
     * Constructor.
     *
     * @param TwoFA_Manager $twofa TwoFA manager.
     */
    public function __construct( TwoFA_Manager $twofa ) {
        $this->twofa = $twofa;
    }

    /**
     * Register hooks.
     */
    public function init() {
        add_action( 'template_redirect', array( $this, 'handle_redirects' ) );
        add_filter( 'login_redirect', array( $this, 'login_redirect' ), 20, 3 );
    }

    /**
     * Handle redirects for onboarding and 2FA enforcement.
     */
    public function handle_redirects() {
        if ( is_admin() || wp_doing_ajax() || defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }

        $force_onboarding = (bool) get_option_value( 'force_onboarding', 1 );
        $onboarding_url   = get_onboarding_url();
        $current_url      = $this->get_current_url();

        if ( $force_onboarding && ! is_user_logged_in() ) {
            if ( $this->should_force_onboarding( $current_url, $onboarding_url ) ) {
                wp_safe_redirect( $onboarding_url );
                exit;
            }
        }

        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();

            if ( $this->twofa->is_enforced() && $this->twofa->user_requires_approval( $user_id ) ) {
                if ( ! $this->is_twofa_page( $current_url ) ) {
                    $target = apply_filters( 'adc_login_twofa_page', $onboarding_url );
                    wp_safe_redirect( add_query_arg( 'step', '2fa', $target ) );
                    exit;
                }
            } elseif ( $this->is_auth_page( $current_url, $onboarding_url ) ) {
                wp_safe_redirect( get_post_login_url() );
                exit;
            }
        }
    }

    /**
     * Determine login redirect target.
     *
     * @param string           $redirect_to Requested redirect.
     * @param string           $requested   Requested redirect.
     * @param \WP_User|WP_Error $user User object.
     *
     * @return string
     */
    public function login_redirect( $redirect_to, $requested, $user ) {
        if ( $this->twofa->is_enforced() && $user instanceof \WP_User ) {
            if ( $this->twofa->user_requires_approval( $user->ID ) ) {
                return add_query_arg( 'step', '2fa', get_onboarding_url() );
            }
        }

        if ( ! empty( $requested ) ) {
            return $requested;
        }

        return get_post_login_url();
    }

    /**
     * Check whether the onboarding should be forced.
     *
     * @param string $current_url Current URL.
     * @param string $onboarding_url Onboarding URL.
     *
     * @return bool
     */
    protected function should_force_onboarding( $current_url, $onboarding_url ) {
        if ( $current_url === $onboarding_url ) {
            return false;
        }

        $login_pages = array(
            wp_login_url(),
            wp_registration_url(),
            wp_lostpassword_url(),
        );

        foreach ( $login_pages as $page_url ) {
            if ( 0 === strpos( $current_url, $page_url ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current URL is part of plugin auth pages.
     *
     * @param string $current_url Current URL.
     * @param string $onboarding_url Onboarding URL.
     *
     * @return bool
     */
    protected function is_auth_page( $current_url, $onboarding_url ) {
        return $current_url === $onboarding_url
            || false !== strpos( $current_url, 'step=auth' )
            || false !== strpos( $current_url, 'step=login' )
            || false !== strpos( $current_url, 'step=signup' )
            || false !== strpos( $current_url, 'step=forgot' );
    }

    /**
     * Determine if current page is the 2FA page.
     *
     * @param string $current_url Current URL.
     *
     * @return bool
     */
    protected function is_twofa_page( $current_url ) {
        $post_id = get_the_ID();
        $has_shortcode = false;

        if ( $post_id ) {
            $content       = get_post_field( 'post_content', $post_id );
            $has_shortcode = has_shortcode( $content, 'adc_2fa' );
        }

        return false !== strpos( $current_url, 'step=2fa' ) || $has_shortcode;
    }

    /**
     * Get current URL including scheme and host.
     *
     * @return string
     */
    protected function get_current_url() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $uri    = $_SERVER['REQUEST_URI']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        return esc_url_raw( $scheme . '://' . $host . $uri );
    }
}
