<?php
/**
 * Main plugin controller.
 *
 * @package ADC\Login
 */

namespace ADC\Login;

use ADC\Login\Admin\Content_Dashboard;
use ADC\Login\Admin\Settings;
use ADC\Login\Auth\Authentication;
use ADC\Login\Content\Manager as Content_Manager;
use ADC\Login\Email\Emails;
use ADC\Login\Elementor\Integration as Elementor_Integration;
use ADC\Login\Frontend\Router;
use ADC\Login\REST\Controller as Rest_Controller;
use ADC\Login\TwoFA\Manager as TwoFA_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Plugin
 */
class Plugin {

    /**
     * Plugin singleton instance.
     *
     * @var Plugin
     */
    protected static $instance;

    /**
     * Admin settings handler.
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Content manager.
     *
     * @var Content_Manager
     */
    protected $content_manager;

    /**
     * Dashboard handler.
     *
     * @var Content_Dashboard
     */
    protected $content_dashboard;

    /**
     * Authentication handler.
     *
     * @var Authentication
     */
    protected $auth;

    /**
     * Router handler.
     *
     * @var Router
     */
    protected $router;

    /**
     * Emails handler.
     *
     * @var Emails
     */
    protected $emails;

    /**
     * Two factor manager.
     *
     * @var TwoFA_Manager
     */
    protected $twofa;

    /**
     * REST controller.
     *
     * @var Rest_Controller
     */
    protected $rest_controller;

    /**
     * Elementor integration handler.
     *
     * @var Elementor_Integration
     */
    protected $elementor;

    /**
     * Get singleton instance.
     *
     * @return Plugin
     */
    public static function instance() {
        if ( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Initialize plugin hooks.
     */
    public function init() {
        $this->includes();
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_assets' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'init', array( $this, 'register_pretty_admin_rewrites' ) );
        add_filter( 'query_vars', array( $this, 'register_pretty_admin_query_var' ) );
        add_action( 'init', array( $this, 'maybe_redirect_pretty_admin_urls' ), 1 );
        add_action( 'admin_init', array( $this, 'maybe_redirect_pretty_admin_urls' ), 0 );
        add_action( 'template_redirect', array( $this, 'maybe_redirect_pretty_admin_urls' ), 0 );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );

        $this->content_manager = new Content_Manager();
        $this->content_manager->init();

        $this->settings = new Settings();
        $this->settings->init();

        $this->content_dashboard = new Content_Dashboard( $this->content_manager );
        $this->content_dashboard->init();

        $this->emails = new Emails();
        $this->emails->init();

        $this->twofa = new TwoFA_Manager( $this->emails );
        $this->twofa->init();

        $this->auth = new Authentication( $this->twofa, $this->emails );
        $this->auth->init();

        $this->router = new Router( $this->twofa );
        $this->router->init();

        $this->rest_controller = new Rest_Controller( $this->auth, $this->twofa );
        add_action( 'rest_api_init', array( $this->rest_controller, 'register_routes' ) );

        $this->elementor = new Elementor_Integration();
        $this->elementor->init();
    }

    /**
     * Allow friendly admin URLs (e.g. /wp-admin/adc-login-settings) to resolve correctly.
     */
    public function maybe_redirect_pretty_admin_urls() {
        static $handled = false;

        if ( $handled || wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }

        if ( ! is_admin() ) {
            $pretty_slug = get_query_var( 'adc_login_pretty' );

            if ( ! empty( $pretty_slug ) ) {
                $this->redirect_for_pretty_slug( $pretty_slug );
            }
        }

        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if ( '' === $request_uri ) {
            return;
        }

        $requested_path = wp_parse_url( $request_uri, PHP_URL_PATH );

        if ( empty( $requested_path ) ) {
            return;
        }

        $requested_path = '/' . ltrim( $requested_path, '/' );

        foreach ( $this->get_pretty_admin_redirects() as $friendly_slug => $target ) {
            $friendly_path = wp_parse_url( admin_url( $friendly_slug ), PHP_URL_PATH );

            if ( empty( $friendly_path ) ) {
                continue;
            }

            $friendly_path = '/' . ltrim( $friendly_path, '/' );

            if ( untrailingslashit( $requested_path ) !== untrailingslashit( $friendly_path ) ) {
                continue;
            }

            $this->redirect_for_pretty_slug( $friendly_slug );
        }
    }

    /**
     * Redirect the current request to the canonical admin destination for a friendly slug.
     *
     * @param string $slug Requested friendly slug.
     */
    protected function redirect_for_pretty_slug( $slug ) {
        static $handled = false;

        if ( $handled ) {
            return;
        }

        $slug = sanitize_key( $slug );

        $redirects = $this->get_pretty_admin_redirects();

        if ( empty( $redirects[ $slug ] ) ) {
            return;
        }

        $handled = true;
        wp_safe_redirect( admin_url( $redirects[ $slug ] ) );
        exit;
    }

    /**
     * Map of friendly admin URL slugs to their canonical destinations.
     *
     * @return array<string, string>
     */
    protected function get_pretty_admin_redirects() {
        $redirects = array(
            'adc-login-settings' => 'admin.php?page=adc-login-settings',
            'adc-login-hub'      => 'admin.php?page=adc-login-dashboard',
            'adc-login-console'  => 'admin.php?page=adc-login-dashboard',
        );

        /**
         * Filter the list of friendly admin redirects handled by the plugin.
         *
         * @since 1.0.0
         *
         * @param array<string, string> $redirects Registered redirect map.
         */
        return apply_filters( 'adc_login_pretty_admin_redirects', $redirects );
    }

    /**
     * Register rewrite tags and rules so friendly slugs also work without the /wp-admin/ prefix.
     */
    public function register_pretty_admin_rewrites() {
        add_rewrite_tag( '%adc_login_pretty%', '([^&]+)' );

        foreach ( array_keys( $this->get_pretty_admin_redirects() ) as $slug ) {
            $slug = sanitize_key( trim( $slug, '/' ) );

            if ( '' === $slug ) {
                continue;
            }

            add_rewrite_rule(
                '^' . \preg_quote( $slug, '/' ) . '/?$',
                'index.php?adc_login_pretty=' . $slug,
                'top'
            );
        }
    }

    /**
     * Ensure WordPress recognizes the query var used for pretty admin redirects.
     *
     * @param array<int, string> $vars Registered query vars.
     *
     * @return array<int, string>
     */
    public function register_pretty_admin_query_var( $vars ) {
        $vars[] = 'adc_login_pretty';

        return $vars;
    }

    /**
     * Include class files that are not autoloaded by convention.
     */
    protected function includes() {
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-content.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-content-dashboard.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-settings.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-router.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-auth.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-twofa.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-emails.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/rest-controller.php';
        require_once ADC_LOGIN_PLUGIN_DIR . 'includes/class-elementor.php';
    }

    /**
     * Load plugin translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'login-academia-da-comunicacao', false, dirname( plugin_basename( ADC_LOGIN_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Register CSS and JS assets.
     */
    public function register_assets() {
        wp_register_style( 'adc-login-frontend', ADC_LOGIN_PLUGIN_URL . 'assets/css/frontend.css', array(), '1.0.0' );
        wp_register_script( 'adc-login-frontend', ADC_LOGIN_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), '1.0.0', true );

        $palette_css = sprintf(
            ':root{--adc-primary:%1$s;--adc-accent:%2$s;--adc-ink:%3$s;}',
            esc_attr( get_option_value( 'color_primary' ) ),
            esc_attr( get_option_value( 'color_accent' ) ),
            esc_attr( get_option_value( 'color_ink' ) )
        );
        wp_add_inline_style( 'adc-login-frontend', $palette_css );

        wp_localize_script(
            'adc-login-frontend',
            'ADCLogin',
            array(
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'restUrl'          => esc_url_raw( rest_url( 'adc/v1/' ) ),
                'nonce'            => wp_create_nonce( 'wp_rest' ),
                'i18n'             => array(
                    'required' => __( 'Este campo é obrigatório.', 'login-academia-da-comunicacao' ),
                ),
            )
        );

        if ( function_exists( 'register_block_type' ) ) {
            wp_register_script(
                'adc-login-blocks',
                ADC_LOGIN_PLUGIN_URL . 'assets/js/blocks.js',
                array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
                '1.0.0',
                true
            );

            $block_definitions = array(
                'login-onboarding' => array(
                    'title'       => __( 'Onboarding da Academia', 'login-academia-da-comunicacao' ),
                    'description' => __( 'Fluxo guiado com slides e formulários da Academia da Comunicação.', 'login-academia-da-comunicacao' ),
                    'icon'        => 'slides',
                    'previewText' => __( 'Prévia do onboarding da Academia da Comunicação', 'login-academia-da-comunicacao' ),
                    'category'    => 'widgets',
                    'keywords'    => array( __( 'onboarding', 'login-academia-da-comunicacao' ), __( 'login', 'login-academia-da-comunicacao' ) ),
                ),
                'login-form'       => array(
                    'title'       => __( 'Formulário de Login ADC', 'login-academia-da-comunicacao' ),
                    'description' => __( 'Formulário de autenticação com reCAPTCHA, social e reforço de 2FA.', 'login-academia-da-comunicacao' ),
                    'icon'        => 'lock',
                    'previewText' => __( 'Prévia do formulário de login', 'login-academia-da-comunicacao' ),
                    'category'    => 'widgets',
                    'keywords'    => array( __( 'login', 'login-academia-da-comunicacao' ), __( 'conta', 'login-academia-da-comunicacao' ) ),
                ),
                'signup-form'      => array(
                    'title'       => __( 'Formulário de Cadastro ADC', 'login-academia-da-comunicacao' ),
                    'description' => __( 'Cadastro completo com aceite de termos, gênero e validações.', 'login-academia-da-comunicacao' ),
                    'icon'        => 'id',
                    'previewText' => __( 'Prévia do formulário de cadastro', 'login-academia-da-comunicacao' ),
                    'category'    => 'widgets',
                    'keywords'    => array( __( 'cadastro', 'login-academia-da-comunicacao' ), __( 'registro', 'login-academia-da-comunicacao' ) ),
                ),
                'forgot-form'      => array(
                    'title'       => __( 'Formulário de Recuperação ADC', 'login-academia-da-comunicacao' ),
                    'description' => __( 'Fluxo de lembrete de senha com template de e-mail personalizado.', 'login-academia-da-comunicacao' ),
                    'icon'        => 'email',
                    'previewText' => __( 'Prévia do formulário de recuperação de senha', 'login-academia-da-comunicacao' ),
                    'category'    => 'widgets',
                    'keywords'    => array( __( 'senha', 'login-academia-da-comunicacao' ), __( 'recuperação', 'login-academia-da-comunicacao' ) ),
                ),
                'twofa-form'       => array(
                    'title'       => __( 'Formulário de 2FA ADC', 'login-academia-da-comunicacao' ),
                    'description' => __( 'Verificação de código 2FA com reforço visual e acessibilidade.', 'login-academia-da-comunicacao' ),
                    'icon'        => 'shield',
                    'previewText' => __( 'Prévia do formulário de verificação 2FA', 'login-academia-da-comunicacao' ),
                    'category'    => 'widgets',
                    'keywords'    => array( __( '2fa', 'login-academia-da-comunicacao' ), __( 'segurança', 'login-academia-da-comunicacao' ) ),
                ),
            );

            wp_localize_script(
                'adc-login-blocks',
                'ADCLoginBlocks',
                array(
                    'blocks' => $block_definitions,
                )
            );

            if ( function_exists( 'wp_set_script_translations' ) ) {
                wp_set_script_translations( 'adc-login-blocks', 'login-academia-da-comunicacao', dirname( plugin_basename( ADC_LOGIN_PLUGIN_FILE ) ) . '/languages' );
            }
        }
    }

    /**
     * Register Gutenberg blocks that mirror the available shortcodes.
     */
    public function register_blocks() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $blocks = array(
            'login-onboarding' => array(
                'render_callback' => array( $this, 'render_onboarding_block' ),
            ),
            'login-form'       => array(
                'render_callback' => array( $this, 'render_login_block' ),
            ),
            'signup-form'      => array(
                'render_callback' => array( $this, 'render_signup_block' ),
            ),
            'forgot-form'      => array(
                'render_callback' => array( $this, 'render_forgot_block' ),
            ),
            'twofa-form'       => array(
                'render_callback' => array( $this, 'render_twofa_block' ),
            ),
        );

        foreach ( $blocks as $slug => $args ) {
            register_block_type(
                'adc/' . $slug,
                array_merge(
                    array(
                        'editor_script' => 'adc-login-blocks',
                        'style'         => 'adc-login-frontend',
                        'editor_style'  => 'adc-login-frontend',
                        'supports'      => array(
                            'html' => false,
                        ),
                    ),
                    $args
                )
            );
        }
    }

    /**
     * Enqueue assets for the block editor preview.
     */
    public function enqueue_block_editor_assets() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_enqueue_style( 'adc-login-frontend' );
    }

    /**
     * Register admin assets.
     */
    public function register_admin_assets() {
        $screen = get_current_screen();

        if ( empty( $screen ) ) {
            return;
        }

        $allowed_ids = array(
            'settings_page_adc-login-settings',
            'login-academia-da-comunicacao_page_adc-login-settings',
        );

        if ( ! in_array( $screen->id, $allowed_ids, true ) ) {
            return;
        }

        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode( 'adc_onboarding', array( $this, 'render_onboarding' ) );
        add_shortcode( 'adc_login', array( $this, 'render_login_form' ) );
        add_shortcode( 'adc_signup', array( $this, 'render_signup_form' ) );
        add_shortcode( 'adc_2fa', array( $this, 'render_twofa_form' ) );
        add_shortcode( 'adc_forgot', array( $this, 'render_forgot_form' ) );
    }

    /**
     * Render onboarding template.
     *
     * @return string
     */
    public function render_onboarding() {
        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_script( 'adc-login-frontend' );

        ob_start();
        include locate_template( 'onboarding.php' );

        return ob_get_clean();
    }

    /**
     * Render login form.
     *
     * @return string
     */
    public function render_login_form() {
        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_script( 'adc-login-frontend' );

        ob_start();
        include locate_template( 'form-login.php' );

        return ob_get_clean();
    }

    /**
     * Render signup form.
     *
     * @return string
     */
    public function render_signup_form() {
        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_script( 'adc-login-frontend' );

        ob_start();
        include locate_template( 'form-signup.php' );

        return ob_get_clean();
    }

    /**
     * Render forgot password form.
     *
     * @return string
     */
    public function render_forgot_form() {
        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_script( 'adc-login-frontend' );

        ob_start();
        include locate_template( 'form-forgot.php' );

        return ob_get_clean();
    }

    /**
     * Render 2FA form.
     *
     * @return string
     */
    public function render_twofa_form() {
        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_script( 'adc-login-frontend' );

        ob_start();
        include locate_template( 'form-2fa.php' );

        return ob_get_clean();
    }

    /**
     * Server render callback for onboarding block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     *
     * @return string
     */
    public function render_onboarding_block( $attributes = array(), $content = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        return $this->render_onboarding();
    }

    /**
     * Server render callback for login block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     *
     * @return string
     */
    public function render_login_block( $attributes = array(), $content = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        return $this->render_login_form();
    }

    /**
     * Server render callback for signup block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     *
     * @return string
     */
    public function render_signup_block( $attributes = array(), $content = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        return $this->render_signup_form();
    }

    /**
     * Server render callback for forgot password block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     *
     * @return string
     */
    public function render_forgot_block( $attributes = array(), $content = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        return $this->render_forgot_form();
    }

    /**
     * Server render callback for 2FA block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     *
     * @return string
     */
    public function render_twofa_block( $attributes = array(), $content = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
        return $this->render_twofa_form();
    }

    /**
     * Activate plugin.
     */
    public static function activate() {
        $options = get_options();
        update_option( 'adc_login_options', $options );

        $instance = static::instance();
        $instance->register_pretty_admin_rewrites();
        \flush_rewrite_rules();
    }

    /**
     * Deactivate plugin.
     */
    public static function deactivate() {
        \flush_rewrite_rules();
    }
}
