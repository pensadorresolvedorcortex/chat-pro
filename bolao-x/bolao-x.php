<?php
/*
Plugin Name: Bolao X
Description: Sistema de gerenciamento de bolão com conferência automática, histórico de resultados, exportação em PDF e Excel e pagamento via Mercado Pago.
Version: 2.8.42
Text Domain: bolao-x
Domain Path: /languages
Author: Bolao X
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class BOLAOX_Plugin {
    private static $instance = null;
    private $notice = '';
    private $log_file = '';
    private $general_log_file = '';
    const TEXT_DOMAIN = 'bolao-x';
const VERSION = '2.8.42';
    const MP_WEBHOOK_TOKEN = 'CwbzYUaV8TNfv*J$Dua6JiHy@';
    const MP_API_URL = 'https://api.mercadopago.com';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'wp_dashboard_setup', array( $this, 'register_dashboard_widget' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_filter( 'gettext', array( $this, 'filter_gettext' ), 10, 3 );
        add_filter( 'enter_title_here', array( $this, 'title_placeholder' ), 10, 2 );
        add_action( 'init', array( $this, 'track_visit' ) );
        add_action( 'save_post_bolaox_result', array( $this, 'clear_pending_payments' ), 10, 3 );
        add_action( 'save_post_bolaox_concurso', array( $this, 'auto_create_result' ), 10, 3 );
        add_action( 'admin_menu', array( $this, 'pending_payments_menu' ) );
        add_action( 'wp_logout', array( $this, 'logout_session' ) );
        add_filter( 'manage_bolaox_aposta_posts_columns', array( $this, 'aposta_columns' ) );
        add_action( 'manage_bolaox_aposta_posts_custom_column', array( $this, 'aposta_column_content' ), 10, 2 );
        add_filter( 'manage_bolaox_result_posts_columns', array( $this, 'result_columns' ) );
        add_action( 'manage_bolaox_result_posts_custom_column', array( $this, 'result_column_content' ), 10, 2 );
        add_action( 'restrict_manage_posts', array( $this, 'aposta_filter' ) );
        add_action( 'restrict_manage_posts', array( $this, 'result_filter' ) );
        add_filter( 'pre_get_posts', array( $this, 'aposta_filter_query' ) );
        add_filter( 'pre_get_posts', array( $this, 'result_filter_query' ) );

        $upload = wp_upload_dir();
        $dir    = trailingslashit( $upload['basedir'] ) . 'bolao-x';
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        $this->log_file        = $dir . '/mp-error.log';
        $this->general_log_file = $dir . '/general.log';
        add_shortcode( 'bolao_x_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'bolao_x_results', array( $this, 'render_results_shortcode' ) );
        add_shortcode( 'bolao_x_history', array( $this, 'render_history_shortcode' ) );
        add_shortcode( 'bolao_x_my_bets', array( $this, 'render_my_bets_shortcode' ) );
        add_shortcode( 'bolao_x_stats', array( $this, 'render_stats_shortcode' ) );
        add_shortcode( 'bolao_x_profile', array( $this, 'render_profile_shortcode' ) );
        add_shortcode( 'bolao_x_minha_conta', array( $this, 'render_profile_shortcode' ) );
        add_shortcode( 'bolao_x_login', array( $this, 'render_login_shortcode' ) );
        add_shortcode( 'bolao_x_dashboard', array( $this, 'render_dashboard_shortcode' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    public function register_settings() {
        register_setting( 'bolaox', 'bolaox_cutoffs' );
        register_setting( 'bolaox', 'bolaox_mp_prod_public' );
        register_setting( 'bolaox', 'bolaox_mp_prod_token' );
        register_setting( 'bolaox', 'bolaox_mp_test_public' );
        register_setting( 'bolaox', 'bolaox_mp_test_token' );
        register_setting( 'bolaox', 'bolaox_pix_key' );
        register_setting( 'bolaox', 'bolaox_mp_mode' );
        register_setting( 'bolaox', 'bolaox_lowest_info' );
        register_setting( 'bolaox', 'bolaox_form_page' );
        register_setting( 'bolaox', 'bolaox_price' );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'bolao-x', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    private function validate_numbers( $numbers ) {
        $nums = array_map( 'trim', explode( ',', $numbers ) );
        if ( count( $nums ) !== 10 ) {
            return false;
        }
        $clean = array();
        foreach ( $nums as $n ) {
            if ( $n === '00' || $n === '0' ) {
                $clean[] = '00';
                continue;
            }
            if ( ! ctype_digit( $n ) || (int) $n < 1 || (int) $n > 99 ) {
                return false;
            }
            $clean[] = sprintf( '%02d', (int) $n );
        }
        return implode( ',', $clean );
    }

    private function sanitize_phone( $phone ) {
        return preg_replace( '/\D+/', '', $phone );
    }

    /**
     * Ensure the session is restored from the auth cookie when possible.
     */
    private function maybe_restore_session() {
        if ( is_user_logged_in() ) {
            return;
        }

        $uid = 0;
        $cookie = isset( $_COOKIE['bolaox_session'] ) ? $_COOKIE['bolaox_session'] : '';
        if ( $cookie && strpos( $cookie, ':' ) !== false ) {
            list( $id, $token ) = explode( ':', $cookie, 2 );
            $hash = get_user_meta( (int) $id, '_bolaox_token', true );
            $exp  = intval( get_user_meta( (int) $id, '_bolaox_token_exp', true ) );
            if ( $hash && $exp > time() && wp_check_password( $token, $hash, $id ) ) {
                $uid = (int) $id;
            } else {
                $this->clear_session_cookie();
            }
        } else {
            $uid = wp_validate_auth_cookie( '', 'logged_in' );
        }

        if ( $uid ) {
            wp_set_current_user( $uid );
        }
    }

    private function create_session( $user_id ) {
        $token  = wp_generate_password( 20, false );
        $hash   = wp_hash_password( $token );
        update_user_meta( $user_id, '_bolaox_token', $hash );
        update_user_meta( $user_id, '_bolaox_token_exp', time() + HOUR_IN_SECONDS );
        // also set the default WordPress auth cookies so REST requests work
        if ( ! is_user_logged_in() ) {
            wp_set_current_user( $user_id );
            wp_set_auth_cookie( $user_id );
        }
        $cookie = $user_id . ':' . $token;
        $args   = array(
            'expires'  => time() + HOUR_IN_SECONDS,
            'path'     => COOKIEPATH ? COOKIEPATH : '/',
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        );
        if ( ! headers_sent() ) {
            setcookie( 'bolaox_session', $cookie, $args );
            return '';
        }
        $js  = 'document.cookie="bolaox_session=' . rawurlencode( $cookie ) . ';path=' . ( COOKIEPATH ? COOKIEPATH : '/' ) . ';max-age=' . HOUR_IN_SECONDS;
        if ( is_ssl() ) {
            $js .= ';secure';
        }
        $js .= ';samesite=Lax";';
        return '<script>' . $js . '</script>';
    }

    private function clear_session_cookie() {
        if ( isset( $_COOKIE['bolaox_session'] ) ) {
            setcookie( 'bolaox_session', '', time() - HOUR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
            unset( $_COOKIE['bolaox_session'] );
        }
        if ( function_exists( 'wp_clear_auth_cookie' ) ) {
            wp_clear_auth_cookie();
        }
    }

    public function logout_session() {
        $this->clear_session_cookie();
    }

    private function get_form_page_url() {
        $page_id = get_option( 'bolaox_form_page', 0 );
        if ( $page_id && get_post_status( $page_id ) ) {
            return get_permalink( $page_id );
        }
        $pages = get_posts( array(
            'post_type'      => 'page',
            's'              => '[bolao_x_form',
            'posts_per_page' => 1,
        ) );
        if ( $pages ) {
            $page_id = $pages[0]->ID;
            update_option( 'bolaox_form_page', $page_id );
            return get_permalink( $page_id );
        }
        return home_url( '/' );
    }

    private function get_mp_access_token() {
        $mode = get_option( 'bolaox_mp_mode', 'test' );
        if ( 'prod' === $mode ) {
            return trim( get_option( 'bolaox_mp_prod_token', '' ) );
        }
        return trim( get_option( 'bolaox_mp_test_token', '' ) );
    }

    private function get_mp_public_key() {
        $mode = get_option( 'bolaox_mp_mode', 'test' );
        if ( 'prod' === $mode ) {
            return trim( get_option( 'bolaox_mp_prod_public', '' ) );
        }
        return trim( get_option( 'bolaox_mp_test_public', '' ) );
    }

    private function validate_mp_credentials( $mode ) {
        $token = ( 'prod' === $mode ) ? get_option( 'bolaox_mp_prod_token', '' ) : get_option( 'bolaox_mp_test_token', '' );
        if ( ! $token ) {
            return false;
        }
        $url  = self::MP_API_URL . '/users/me';
        $args = array(
            'headers' => array( 'Authorization' => 'Bearer ' . $token ),
            'timeout' => 20,
        );
        $res = wp_remote_get( $url, $args );
        if ( is_wp_error( $res ) ) {
            $this->log_error( 'Falha ao validar credenciais: ' . $res->get_error_message() );
            return false;
        }
        $code = wp_remote_retrieve_response_code( $res );
        if ( $code !== 200 ) {
            $this->log_error( 'Credenciais inválidas: HTTP ' . $code );
        }
        return $code === 200;
    }

    private function log_mp_error( $msg ) {
        if ( ! $this->log_file ) {
            return;
        }
        if ( strlen( $msg ) > 1000 ) {
            $msg = substr( $msg, 0, 1000 ) . '...';
        }
        $entry = '[' . current_time( 'mysql' ) . "] " . $msg . "\n";
        error_log( $entry, 3, $this->log_file );
    }

    private function log_error( $msg ) {
        if ( ! $this->general_log_file ) {
            return;
        }
        if ( strlen( $msg ) > 1000 ) {
            $msg = substr( $msg, 0, 1000 ) . '...';
        }
        $entry = '[' . current_time( 'mysql' ) . "] " . $msg . "\n";
        error_log( $entry, 3, $this->general_log_file );
    }


    private function get_browser_name( $ua ) {
        if ( strpos( $ua, 'Firefox' ) !== false ) return 'Firefox';
        if ( strpos( $ua, 'Edg' ) !== false ) return 'Edge';
        if ( strpos( $ua, 'Chrome' ) !== false ) return 'Chrome';
        if ( strpos( $ua, 'Safari' ) !== false ) return 'Safari';
        if ( strpos( $ua, 'Trident' ) !== false || strpos( $ua, 'MSIE' ) !== false ) return 'IE';
        return 'Outro';
    }

    private function get_platform_name( $ua ) {
        $ua = strtolower( $ua );
        if ( strpos( $ua, 'android' ) !== false ) return 'Android';
        if ( strpos( $ua, 'iphone' ) !== false || strpos( $ua, 'ipad' ) !== false ) return 'iOS';
        if ( strpos( $ua, 'windows' ) !== false ) return 'Windows';
        if ( strpos( $ua, 'mac' ) !== false ) return 'macOS';
        if ( strpos( $ua, 'linux' ) !== false ) return 'Linux';
        return 'Outro';
    }

    public function track_visit() {
        if ( is_admin() ) {
            return;
        }
        $data = get_option( 'bolaox_visits', array() );
        $date = current_time( 'Y-m-d' );
        if ( ! isset( $data['days'][ $date ] ) ) {
            $data['days'][ $date ] = 0;
        }
        $data['days'][ $date ]++;

        $country = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '??';
        $data['countries'][ $country ] = ( $data['countries'][ $country ] ?? 0 ) + 1;

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browser = $this->get_browser_name( $ua );
        $platform = $this->get_platform_name( $ua );
        $data['browsers'][ $browser ] = ( $data['browsers'][ $browser ] ?? 0 ) + 1;
        $data['platforms'][ $platform ] = ( $data['platforms'][ $platform ] ?? 0 ) + 1;

        if ( is_user_logged_in() ) {
            $uid = get_current_user_id();
            if ( ! isset( $data['users'][ $date ] ) ) {
                $data['users'][ $date ] = array();
            }
            if ( ! in_array( $uid, $data['users'][ $date ], true ) ) {
                $data['users'][ $date ][] = $uid;
            }
        }

        if ( count( $data['days'] ) > 30 ) {
            $data['days'] = array_slice( $data['days'], -30, true );
        }
        if ( isset( $data['users'] ) && count( $data['users'] ) > 30 ) {
            $data['users'] = array_slice( $data['users'], -30, true );
        }
        update_option( 'bolaox_visits', $data );

        $online = get_transient( 'bolaox_online' );
        if ( ! is_array( $online ) ) {
            $online = array();
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $online[ $ip ] = time();
        foreach ( $online as $k => $t ) {
            if ( $t < time() - 300 ) {
                unset( $online[ $k ] );
            }
        }
        set_transient( 'bolaox_online', $online, 5 * MINUTE_IN_SECONDS );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'bolaox_page';
        return $vars;
    }

    private function prepare_visit_stats() {
        $data = get_option( 'bolaox_visits', array() );
        $days = $data['days'] ?? array();
        $users = $data['users'] ?? array();
        $countries = $data['countries'] ?? array();
        $platforms = $data['platforms'] ?? array();
        $browsers = $data['browsers'] ?? array();
        $dates = array();
        $visit_counts = array();
        $user_counts = array();
        $today_views = 0;
        for ( $i = 14; $i >= 0; $i-- ) {
            $d = date( 'Y-m-d', strtotime( "-$i days" ) );
            $dates[] = date_i18n( 'd/m', strtotime( $d ) );
            $visit_counts[] = intval( $days[ $d ] ?? 0 );
            $user_counts[]  = isset( $users[ $d ] ) ? count( $users[ $d ] ) : 0;
            if ( 0 === $i ) {
                $today_views = intval( $days[ $d ] ?? 0 );
            }
        }
        arsort( $countries );
        arsort( $platforms );
        arsort( $browsers );
        $online = get_transient( 'bolaox_online' );
        $online_count = is_array( $online ) ? count( $online ) : 0;
        return array(
            'dates'       => $dates,
            'visits'      => $visit_counts,
            'users'       => $user_counts,
            'countries'   => array( 'labels' => array_keys( $countries ), 'data' => array_values( $countries ) ),
            'platforms'   => array( 'labels' => array_keys( $platforms ), 'data' => array_values( $platforms ) ),
            'browsers'    => array( 'labels' => array_keys( $browsers ), 'data' => array_values( $browsers ) ),
            'today_views' => $today_views,
            'online'      => $online_count,
        );
    }
    private function verify_mp_payment( $payment_id, $expected = null ) {
        $token = $this->get_mp_access_token();
        if ( ! $token || ! $payment_id ) {
            return false;
        }
        $url  = self::MP_API_URL . '/v1/payments/' . intval( $payment_id );
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
            ),
            'timeout' => 20,
        );
        $res = wp_remote_get( $url, $args );
        if ( is_wp_error( $res ) ) {
            $this->log_mp_error( 'Erro consulta pagamento: ' . $res->get_error_message() );
            $this->log_error( 'Erro consulta pagamento: ' . $res->get_error_message() );
            return false;
        }
        $body = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( isset( $body['status'] ) && 'approved' === $body['status'] ) {
            if ( null !== $expected && isset( $body['transaction_amount'] ) ) {
                $amount = floatval( $body['transaction_amount'] );
                if ( $amount + 0.001 < floatval( $expected ) ) {
                    $this->log_error( 'Valor do pagamento divergente: ' . $amount );
                    return false;
                }
            }
            return true;
        }
        if ( isset( $body['status'] ) && in_array( $body['status'], array( 'in_process', 'pending' ), true ) ) {
            $this->log_error( 'Pagamento pendente: ' . wp_remote_retrieve_body( $res ) );
            return false;
        }
        $this->log_error( 'Pagamento não aprovado: ' . wp_remote_retrieve_body( $res ) );
        return false;
    }

    private function create_mp_pix_payment( $ref, $qty = 1 ) {
        $token = $this->get_mp_access_token();
        if ( ! $token ) {
            return array();
        }
        $url   = self::MP_API_URL . '/v1/payments';
        $price = floatval( get_option( 'bolaox_price', 10 ) );
        $amount = $price * max( 1, intval( $qty ) );
        $pix_key = trim( get_option( 'bolaox_pix_key', '' ) );
        $payer_email = 'apostador@example.com';
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            if ( $user && $user->user_email ) {
                $payer_email = $user->user_email;
            }
        } else {
            $admin = get_option( 'admin_email' );
            if ( $admin ) {
                $payer_email = $admin;
            }
        }
        $idempotency = $ref ? sanitize_text_field( $ref ) : sanitize_text_field( uniqid( 'pix_', true ) );
        $body  = array(
            'transaction_amount' => $amount,
            'description'        => 'Aposta ' . $ref,
            'payment_method_id'  => 'pix',
            'external_reference' => (string) $ref,
            'notification_url'   => home_url( '/wp-json/bolao-x/v1/mp?token=' . self::MP_WEBHOOK_TOKEN ),
            'payer'              => array( 'email' => $payer_email ),
        );
        $args = array(
            'headers' => array(
                'Authorization'    => 'Bearer ' . $token,
                'Content-Type'     => 'application/json',
                'X-Idempotency-Key' => $idempotency,
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 20,
        );
        $res = wp_remote_post( $url, $args );
        if ( is_wp_error( $res ) ) {
            $this->log_mp_error( 'Erro ao criar pagamento: ' . $res->get_error_message() );
            $this->log_error( 'Erro ao criar pagamento Pix: ' . $res->get_error_message() );
            return array();
        }
        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( isset( $data['id'], $data['point_of_interaction']['transaction_data']['qr_code'] ) ) {
            if ( is_numeric( $ref ) ) {
                update_post_meta( intval( $ref ), '_bolaox_mp_pref', sanitize_text_field( $data['id'] ) );
            }
            return array(
                'id'      => $data['id'],
                'qr_code' => $data['point_of_interaction']['transaction_data']['qr_code'],
                'qr_code_base64' => isset( $data['point_of_interaction']['transaction_data']['qr_code_base64'] ) ?
                    $data['point_of_interaction']['transaction_data']['qr_code_base64'] : '',
            );
        }
        $this->log_mp_error( 'Resposta inesperada da API: ' . wp_remote_retrieve_body( $res ) );
        $this->log_error( 'Resposta inesperada da API Pix: ' . wp_remote_retrieve_body( $res ) );
        return array();
    }

    public function admin_notices() {
        if ( current_user_can( 'manage_options' ) ) {
            $mode = get_option( 'bolaox_mp_mode', 'test' );
            $token = 'prod' === $mode ? get_option( 'bolaox_mp_prod_token', '' ) : get_option( 'bolaox_mp_test_token', '' );
            if ( ! $token ) {
                $msg = ( 'prod' === $mode ) ? __( 'Informe o Access Token de produção do Mercado Pago em Bolao X > Configurações.', self::TEXT_DOMAIN ) : __( 'Informe o Access Token de teste do Mercado Pago em Bolao X > Configurações.', self::TEXT_DOMAIN );
                echo '<div class="notice notice-error"><p>' . esc_html( $msg ) . '</p></div>';
            }
        }
        if ( $this->notice ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $this->notice ) . '</p></div>';
            $this->notice = '';
        }
    }

    public function enqueue_assets() {
        $this->maybe_restore_session();
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style(
            'bolaox-fonts',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap',
            array(),
            null
        );
        wp_enqueue_style(
            'bolaox-style',
            plugin_dir_url( __FILE__ ) . 'assets/css/bolao-x.css',
            array(),
            self::VERSION
        );
        if ( is_admin() ) {
            wp_enqueue_style(
                'bolaox-admin',
                plugin_dir_url( __FILE__ ) . 'assets/css/bolao-x-admin.css',
                array(),
                self::VERSION
            );
            if ( isset( $_GET['page'] ) && 'bolaox' === $_GET['page'] ) {
                wp_enqueue_script(
                    'chartjs',
                    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                    array(),
                    '4.4.1',
                    true
                );
                wp_enqueue_script(
                    'bolaox-admin',
                    plugin_dir_url( __FILE__ ) . 'assets/js/bolaox-admin.js',
                    array( 'chartjs' ),
                    self::VERSION,
                    true
                );
                wp_localize_script( 'bolaox-admin', 'bolaoxStats', $this->prepare_visit_stats() );
            }
        }
        wp_enqueue_script(
            'bolaox-js',
            plugin_dir_url( __FILE__ ) . 'assets/js/bolao-x.js',
            array(),
            self::VERSION,
            true
        );
        $user = is_user_logged_in() ? wp_get_current_user() : null;
        wp_localize_script(
            'bolaox-js',
            'bolaoxData',
            array(
                'nonce'       => wp_create_nonce( 'wp_rest' ),
                'logged_in'   => is_user_logged_in(),
                'user_name'   => $user ? $user->display_name : '',
                'mybets_url'  => rest_url( 'bolao-x/v1/mybets' ),
                'form_url'    => add_query_arg( 'bx_sec', 'form', $this->get_form_page_url() ),
                'loading'     => __( 'Carregando...', self::TEXT_DOMAIN ),
                'load_error'  => __( 'Erro ao carregar apostas.', self::TEXT_DOMAIN ),
                'login_url'   => home_url( '/login-cadastro' ),
            )
        );
    }

    public function register_post_type() {
        register_post_type( 'bolaox_aposta', array(
            'labels' => array(
                'name'          => __( 'Apostas', self::TEXT_DOMAIN ),
                'singular_name' => __( 'Aposta', self::TEXT_DOMAIN ),
                'add_new'       => __( 'Aposta Manual', self::TEXT_DOMAIN ),
                'add_new_item'  => __( 'Criar Aposta', self::TEXT_DOMAIN ),
                'not_found'     => __( 'Nenhuma aposta encontrada.', self::TEXT_DOMAIN ),
            ),
            'public'  => false,
            'show_ui' => true,
            'supports' => array( 'title' ),
        ) );

        register_post_type( 'bolaox_result', array(
            'labels' => array(
                'name'          => __( 'Resultados', self::TEXT_DOMAIN ),
                'singular_name' => __( 'Resultado', self::TEXT_DOMAIN ),
                'add_new'       => __( 'Novo Resultado', self::TEXT_DOMAIN ),
                'add_new_item'  => __( 'Publicar Resultado', self::TEXT_DOMAIN ),
                'not_found'     => __( 'Nenhum resultado encontrado.', self::TEXT_DOMAIN ),
            ),
            'public'  => false,
            'show_ui' => true,
            'supports' => array( 'title' ),
        ) );

        register_post_type( 'bolaox_concurso', array(
            'labels' => array(
                'name'          => __( 'Concursos', self::TEXT_DOMAIN ),
                'singular_name' => __( 'Concurso', self::TEXT_DOMAIN ),
                'add_new'       => __( 'Novo Concurso', self::TEXT_DOMAIN ),
                'add_new_item'  => __( 'Criar Concurso', self::TEXT_DOMAIN ),
                'search_items'  => __( 'Pesquisar Concursos', self::TEXT_DOMAIN ),
            ),
            'public'  => false,
            'show_ui' => true,
            'supports' => array( 'title' ),
        ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'bolaox_numbers', __( 'Dezenas', self::TEXT_DOMAIN ), array( $this, 'numbers_meta_box' ), 'bolaox_aposta' );
        add_meta_box( 'bolaox_numbers', __( 'Dezenas', self::TEXT_DOMAIN ), array( $this, 'numbers_meta_box' ), 'bolaox_result' );
        add_meta_box( 'bolaox_res_concurso', __( 'Concurso', self::TEXT_DOMAIN ), array( $this, 'result_concurso_meta_box' ), 'bolaox_result', 'side' );
        add_meta_box( 'bolaox_payment', __( 'Status do Pagamento', self::TEXT_DOMAIN ), array( $this, 'payment_meta_box' ), 'bolaox_aposta', 'side' );
        add_meta_box( 'bolaox_fixed', __( 'Apostador Fixo', self::TEXT_DOMAIN ), array( $this, 'fixed_meta_box' ), 'bolaox_aposta', 'side' );
        add_meta_box( 'bolaox_concurso_meta', __( 'Detalhes do Concurso', self::TEXT_DOMAIN ), array( $this, 'concurso_meta_box' ), 'bolaox_concurso' );
    }

    public function numbers_meta_box( $post ) {
        $numbers = get_post_meta( $post->ID, '_bolaox_numbers', true );
        echo '<input type="text" name="bolaox_numbers" value="' . esc_attr( $numbers ) . '" placeholder="' . esc_attr__( 'Ex: 05,12,23,34,45,56,67,78,89,90', self::TEXT_DOMAIN ) . '" style="width:100%" />';
    }

    public function payment_meta_box( $post ) {
        $status = get_post_meta( $post->ID, '_bolaox_payment', true );
        if ( ! $status ) {
            $status = 'pending';
        }
        echo '<select name="bolaox_payment">'
            . '<option value="pending"' . selected( $status, 'pending', false ) . '>' . esc_html__( 'Pendente', self::TEXT_DOMAIN ) . '</option>'
            . '<option value="paid"' . selected( $status, 'paid', false ) . '>' . esc_html__( 'Pago', self::TEXT_DOMAIN ) . '</option>'
            . '</select>';
    }

    public function fixed_meta_box( $post ) {
        $fixed = get_post_meta( $post->ID, '_bolaox_fixed', true );
        if ( '' === $fixed && 'auto-draft' === $post->post_status ) {
            $fixed = '1';
        }
        echo '<label><input type="checkbox" name="bolaox_fixed" value="1"' . checked( $fixed, '1', false ) . ' /> ' . esc_html__( 'Manter para próximos concursos', self::TEXT_DOMAIN ) . '</label>';
    }

    public function concurso_meta_box( $post ) {
        $start = get_post_meta( $post->ID, '_bolaox_start', true );
        $end   = get_post_meta( $post->ID, '_bolaox_end', true );
        $active = get_option( 'bolaox_active_concurso', 0 );
        echo '<p><label>' . esc_html__( 'Início', self::TEXT_DOMAIN ) . '<br />';
        echo '<input type="datetime-local" name="bolaox_start" value="' . esc_attr( $start ) . '" /></label></p>';
        echo '<p><label>' . esc_html__( 'Fim', self::TEXT_DOMAIN ) . '<br />';
        echo '<input type="datetime-local" name="bolaox_end" value="' . esc_attr( $end ) . '" /></label></p>';
        echo '<p><label><input type="checkbox" name="bolaox_active" value="1"' . checked( $active, $post->ID, false ) . ' /> ' . esc_html__( 'Concurso Ativo', self::TEXT_DOMAIN ) . '</label></p>';
    }

    public function result_concurso_meta_box( $post ) {
        $selected = get_post_meta( $post->ID, '_bolaox_concurso', true );
        $contests = get_posts( array( 'post_type' => 'bolaox_concurso', 'numberposts' => -1 ) );
        echo '<select name="bolaox_concurso">';
        foreach ( $contests as $c ) {
            echo '<option value="' . $c->ID . '"' . selected( $selected, $c->ID, false ) . '>' . esc_html( $c->post_title ) . '</option>';
        }
        echo '</select>';
    }

    public function save_meta( $post_id ) {
        if ( isset( $_POST['bolaox_numbers'] ) ) {
            $numbers = sanitize_text_field( $_POST['bolaox_numbers'] );
            $valid   = $this->validate_numbers( $numbers );
            if ( false === $valid ) {
                $this->notice = __( 'Dezenas da aposta inválidas. Use 10 números de 00 a 99 separados por vírgula.', self::TEXT_DOMAIN );
                return;
            }
            update_post_meta( $post_id, '_bolaox_numbers', $valid );
            if ( 'bolaox_result' === get_post_type( $post_id ) ) {
                update_post_meta( $post_id, '_bolaox_result', $valid );
            }
        }
        if ( isset( $_POST['bolaox_concurso'] ) ) {
            update_post_meta( $post_id, '_bolaox_concurso', intval( $_POST['bolaox_concurso'] ) );
        }
        if ( isset( $_POST['bolaox_payment'] ) ) {
            $status = in_array( $_POST['bolaox_payment'], array( 'pending', 'paid' ), true ) ? $_POST['bolaox_payment'] : 'pending';
            update_post_meta( $post_id, '_bolaox_payment', $status );
        }
        if ( isset( $_POST['bolaox_fixed'] ) ) {
            update_post_meta( $post_id, '_bolaox_fixed', '1' );
        } else {
            delete_post_meta( $post_id, '_bolaox_fixed' );
        }
        if ( isset( $_POST['bolaox_start'] ) || isset( $_POST['bolaox_end'] ) ) {
            $start = isset( $_POST['bolaox_start'] ) ? sanitize_text_field( $_POST['bolaox_start'] ) : '';
            $end   = isset( $_POST['bolaox_end'] ) ? sanitize_text_field( $_POST['bolaox_end'] ) : '';
            update_post_meta( $post_id, '_bolaox_start', $start );
            update_post_meta( $post_id, '_bolaox_end', $end );
        }
        if ( isset( $_POST['bolaox_active'] ) ) {
            $prev = get_option( 'bolaox_active_concurso', 0 );
            update_option( 'bolaox_active_concurso', $post_id );
            if ( $prev != $post_id ) {
                $this->on_contest_switch( $prev, $post_id );
            }
        } elseif ( 'bolaox_concurso' === get_post_type( $post_id ) && get_option( 'bolaox_active_concurso' ) == $post_id ) {
            delete_option( 'bolaox_active_concurso' );
        }
    }

    public function admin_menu() {
        add_menu_page( 'Bolao X', 'Bolao X', 'manage_options', 'bolaox', array( $this, 'results_page' ) );
        add_submenu_page( 'bolaox', __( 'Configurações', self::TEXT_DOMAIN ), __( 'Configurações', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-settings', array( $this, 'settings_page' ) );
        add_submenu_page( 'bolaox', __( 'Importar CSV', self::TEXT_DOMAIN ), __( 'Importar CSV', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-import', array( $this, 'import_page' ) );
        add_submenu_page( 'bolaox', __( 'Histórico', self::TEXT_DOMAIN ), __( 'Histórico', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-history', array( $this, 'history_page' ) );
        add_submenu_page( 'bolaox', __( 'Estatísticas', self::TEXT_DOMAIN ), __( 'Estatísticas', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-stats', array( $this, 'stats_page' ) );
        add_submenu_page( 'bolaox', __( 'Concursos', self::TEXT_DOMAIN ), __( 'Concursos', self::TEXT_DOMAIN ), 'manage_options', 'edit.php?post_type=bolaox_concurso' );
        add_submenu_page( 'bolaox', __( 'Logs', self::TEXT_DOMAIN ), __( 'Logs', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-logs', array( $this, 'logs_page' ) );
        add_submenu_page( 'bolaox', __( 'Logs Gerais', self::TEXT_DOMAIN ), __( 'Logs Gerais', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-general-logs', array( $this, 'general_logs_page' ) );
        add_menu_page( __( 'Contemplados', self::TEXT_DOMAIN ), __( 'Contemplados', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-contemplados', array( $this, 'contemplados_page' ), 'dashicons-awards' );
    }

    public function results_page() {
        $stats = $this->prepare_visit_stats();
        $total_visits = array_sum( $stats['visits'] );
        $total_users  = array_sum( $stats['users'] );
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Análises Gerais', self::TEXT_DOMAIN ) . '</h1>';
        echo '<div class="bolaox-dashboard-analytics">';
        echo '<div class="bx-info-tabs">';
        echo '<div class="bx-info-tab"><span>' . esc_html__( 'USUÁRIOS ONLINE', self::TEXT_DOMAIN ) . '</span><strong>' . intval( $stats['online'] ) . '</strong></div>';
        echo '<div class="bx-info-tab"><span>' . esc_html__( 'EXIBIÇÕES HOJE', self::TEXT_DOMAIN ) . '</span><strong>' . intval( $stats['today_views'] ) . '</strong></div>';
        echo '<div class="bx-info-tab"><span>' . esc_html__( 'VISITAS NOS ÚLTIMOS 15 DIAS', self::TEXT_DOMAIN ) . '</span><strong>' . intval( $total_visits ) . '</strong></div>';
        $browser_lines = array();
        $browser_total = array_sum( $stats['browsers']['data'] );
        for ( $i = 0; $i < 3; $i++ ) {
            $label = $stats['browsers']['labels'][ $i ] ?? '-';
            $count = $stats['browsers']['data'][ $i ] ?? 0;
            $pct   = $browser_total ? round( $count / $browser_total * 100 ) : 0;
            $browser_lines[] = esc_html( $label ) . ' ' . intval( $pct ) . '%';
        }
        $platform_lines = array();
        $platform_total = array_sum( $stats['platforms']['data'] );
        for ( $i = 0; $i < 3; $i++ ) {
            $label = $stats['platforms']['labels'][ $i ] ?? '-';
            $count = $stats['platforms']['data'][ $i ] ?? 0;
            $pct   = $platform_total ? round( $count / $platform_total * 100 ) : 0;
            $platform_lines[] = esc_html( $label ) . ' ' . intval( $pct ) . '%';
        }
        echo '<div class="bx-info-tab"><span>' . esc_html__( 'NAVEGADORES MAIS USADOS', self::TEXT_DOMAIN ) . '</span><strong>' . implode( '<br>', $browser_lines ) . '</strong></div>';
        echo '<div class="bx-info-tab"><span>' . esc_html__( 'SISTEMAS OPERACIONAIS MAIS USADOS', self::TEXT_DOMAIN ) . '</span><strong>' . implode( '<br>', $platform_lines ) . '</strong></div>';
        echo '</div>';
        echo '<div class="bolaox-graphs">';
        echo '<div class="bolaox-graph"><h2>' . esc_html__( 'VISITAS NOS ÚLTIMOS 15 DIAS', self::TEXT_DOMAIN ) . '</h2><canvas id="bx-visits"></canvas></div>';
        echo '<div class="bolaox-graph"><h2>' . esc_html__( 'USUÁRIOS NOS ÚLTIMOS 15 DIAS', self::TEXT_DOMAIN ) . '</h2><canvas id="bx-users"></canvas></div>';
        echo '<div class="bolaox-graph"><h2>' . esc_html__( 'VISITAS POR PAÍS', self::TEXT_DOMAIN ) . '</h2><canvas id="bx-countries"></canvas></div>';
        echo '<div class="bolaox-graph"><h2>' . esc_html__( 'PLATAFORMAS USADAS', self::TEXT_DOMAIN ) . '</h2><canvas id="bx-platforms"></canvas></div>';
        echo '<div class="bolaox-graph"><h2>' . esc_html__( 'NAVEGADORES USADOS', self::TEXT_DOMAIN ) . '</h2><canvas id="bx-browsers"></canvas></div>';
        echo '</div></div>';
        echo '</div>';
    }

    public function settings_page() {
        $days = array(
            1 => __( 'Segunda', self::TEXT_DOMAIN ),
            2 => __( 'Terça', self::TEXT_DOMAIN ),
            3 => __( 'Quarta', self::TEXT_DOMAIN ),
            4 => __( 'Quinta', self::TEXT_DOMAIN ),
            5 => __( 'Sexta', self::TEXT_DOMAIN ),
            6 => __( 'Sábado', self::TEXT_DOMAIN ),
            7 => __( 'Domingo', self::TEXT_DOMAIN ),
        );
        if ( isset( $_POST['bolaox_nonce'] ) && wp_verify_nonce( $_POST['bolaox_nonce'], 'bolaox_settings' ) ) {
            if ( isset( $_POST['bolaox_cutoffs'] ) && is_array( $_POST['bolaox_cutoffs'] ) ) {
                $new = array();
                foreach ( $days as $idx => $label ) {
                    $t = isset( $_POST['bolaox_cutoffs'][ $idx ] ) ? sanitize_text_field( $_POST['bolaox_cutoffs'][ $idx ] ) : '';
                    $new[ $idx ] = $t;
                }
                update_option( 'bolaox_cutoffs', $new );
            }
            update_option( 'bolaox_mp_prod_public', sanitize_text_field( $_POST['bolaox_mp_prod_public'] ?? '' ) );
            update_option( 'bolaox_mp_prod_token', sanitize_text_field( $_POST['bolaox_mp_prod_token'] ?? '' ) );
            update_option( 'bolaox_mp_test_public', sanitize_text_field( $_POST['bolaox_mp_test_public'] ?? '' ) );
            update_option( 'bolaox_mp_test_token', sanitize_text_field( $_POST['bolaox_mp_test_token'] ?? '' ) );
            update_option( 'bolaox_pix_key', sanitize_text_field( $_POST['bolaox_pix_key'] ?? '' ) );
            $mode = in_array( $_POST['bolaox_mp_mode'] ?? 'test', array( 'prod', 'test' ), true ) ? $_POST['bolaox_mp_mode'] : 'test';
            update_option( 'bolaox_mp_mode', $mode );
            if ( isset( $_POST['bolaox_price'] ) ) {
                $price = floatval( sanitize_text_field( $_POST['bolaox_price'] ) );
                if ( $price <= 0 ) {
                    $price = 10;
                }
                update_option( 'bolaox_price', $price );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Configurações salvas.', self::TEXT_DOMAIN ) . '</p></div>';
        }
        $cutoffs = get_option( 'bolaox_cutoffs', array() );
        $prod_public = get_option( 'bolaox_mp_prod_public', '' );
        $prod_token  = get_option( 'bolaox_mp_prod_token', '' );
        $test_public = get_option( 'bolaox_mp_test_public', '' );
        $test_token  = get_option( 'bolaox_mp_test_token', '' );
        $mode        = get_option( 'bolaox_mp_mode', 'test' );
        $pix_key     = get_option( 'bolaox_pix_key', '' );
        $price       = get_option( 'bolaox_price', 10 );
        echo '<div class="wrap"><h1>' . esc_html__( 'Configurações', self::TEXT_DOMAIN ) . '</h1>';
        echo '<form method="post">';
        wp_nonce_field( 'bolaox_settings', 'bolaox_nonce' );
        echo '<h2>' . esc_html__( 'Horários de Restrição de Apostas', self::TEXT_DOMAIN ) . '</h2>';
        echo '<table class="form-table bolaox-cutoffs"><tbody>';
        foreach ( $days as $idx => $label ) {
            $val = isset( $cutoffs[ $idx ] ) ? $cutoffs[ $idx ] : '';
            echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
            echo '<input type="time" name="bolaox_cutoffs[' . $idx . ']" value="' . esc_attr( $val ) . '" /></td></tr>';
        }
        echo '<tr><th scope="row">' . esc_html__( 'Credenciais de Produção', self::TEXT_DOMAIN ) . '</th><td>';
        echo '<p><label>Public Key<br /><input type="text" name="bolaox_mp_prod_public" value="' . esc_attr( $prod_public ) . '" class="regular-text" /></label></p>';
        echo '<p><label>Access Token<br /><input type="text" name="bolaox_mp_prod_token" value="' . esc_attr( $prod_token ) . '" class="regular-text" /></label></p>';
        echo '</td></tr>';
        echo '<tr><th scope="row">' . esc_html__( 'Credenciais de Teste', self::TEXT_DOMAIN ) . '</th><td>';
        echo '<p><label>Public Key<br /><input type="text" name="bolaox_mp_test_public" value="' . esc_attr( $test_public ) . '" class="regular-text" /></label></p>';
        echo '<p><label>Access Token<br /><input type="text" name="bolaox_mp_test_token" value="' . esc_attr( $test_token ) . '" class="regular-text" /></label></p>';
        echo '</td></tr>';
        echo '<tr><th scope="row">' . esc_html__( 'Modo ativo', self::TEXT_DOMAIN ) . '</th><td><select name="bolaox_mp_mode">';
        echo '<option value="test"' . selected( $mode, 'test', false ) . '>Teste</option>';
        echo '<option value="prod"' . selected( $mode, 'prod', false ) . '>Produção</option>';
        echo '</select></td></tr>';
        $nonce = wp_create_nonce( 'wp_rest' );
        echo '<tr><th scope="row">' . esc_html__( 'Validar credenciais', self::TEXT_DOMAIN ) . '</th><td>';
        echo '<button type="button" class="button bolaox-validate-creds" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Validar', self::TEXT_DOMAIN ) . '</button> <span class="bolaox-valid-msg"></span>';
        echo '</td></tr>';
        echo '<tr><th scope="row">' . esc_html__( 'Chave Pix para exibir', self::TEXT_DOMAIN ) . '</th><td>';
        echo '<input type="text" name="bolaox_pix_key" value="' . esc_attr( $pix_key ) . '" class="regular-text" />';
        echo '</td></tr>';
        echo '<tr><th scope="row">' . esc_html__( 'Preço da aposta (R$)', self::TEXT_DOMAIN ) . '</th><td><input type="number" step="0.01" name="bolaox_price" value="' . esc_attr( $price ) . '" /></td></tr>';
        echo '</tbody></table>';
        submit_button();
        echo '</form></div>';
    }

    public function import_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Importar Apostas via CSV', self::TEXT_DOMAIN ) . '</h1>';
        if ( isset( $_POST['bolaox_import_nonce'] ) && wp_verify_nonce( $_POST['bolaox_import_nonce'], 'bolaox_import' ) && ! empty( $_FILES['bolaox_csv']['tmp_name'] ) ) {
            $count = 0;
            $fh = fopen( $_FILES['bolaox_csv']['tmp_name'], 'r' );
            if ( $fh ) {
                while ( ( $data = fgetcsv( $fh ) ) !== false ) {
                    if ( count( $data ) < 11 ) {
                        continue;
                    }
                    $name = array_shift( $data );
                    $numbers = implode( ',', $data );
                    $numbers = $this->validate_numbers( $numbers );
                    if ( false === $numbers ) {
                        continue;
                    }
                    $post_id = wp_insert_post( array(
                        'post_type'   => 'bolaox_aposta',
                        'post_title'  => sanitize_text_field( $name ),
                        'post_status' => 'publish',
                        'post_author' => get_current_user_id(),
                    ) );
                    if ( $post_id ) {
                        update_post_meta( $post_id, '_bolaox_numbers', $numbers );
                        update_post_meta( $post_id, '_bolaox_fixed', '1' );
                        if ( get_current_user_id() ) {
                            update_post_meta( $post_id, '_bolaox_user', get_current_user_id() );
                        }
                        $count++;
                    }
                }
                fclose( $fh );
            }
            echo '<div class="updated"><p>' . sprintf( esc_html__( 'Importadas %d apostas.', self::TEXT_DOMAIN ), intval( $count ) ) . '</p></div>';
        }
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field( 'bolaox_import', 'bolaox_import_nonce' );
        echo '<input type="file" name="bolaox_csv" accept="text/csv" required /> ';
        submit_button( __( 'Importar', self::TEXT_DOMAIN ) );
        echo '</form></div>';
    }

    public function history_page() {
        if ( isset( $_GET['export'] ) ) {
            $id = intval( $_GET['export'] );
            $numbers = get_post_meta( $id, '_bolaox_result', true );
            if ( $numbers ) {
                if ( isset( $_GET['type'] ) && 'pdf' === $_GET['type'] ) {
                    $this->export_pdf( $numbers );
                } elseif ( isset( $_GET['type'] ) && 'xls' === $_GET['type'] ) {
                    $this->export_xls( $numbers );
                } else {
                    $this->export_csv( $numbers );
                }
            }
        }
        $posts = get_posts( array( 'post_type' => 'bolaox_result', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
        echo '<div class="wrap"><h1>' . esc_html__( 'Histórico de Resultados', self::TEXT_DOMAIN ) . '</h1>';
        if ( ! $posts ) {
            echo '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p></div>';
            return;
        }
        echo '<table class="widefat"><thead><tr><th>Data</th><th>Resultado</th><th>Ações</th></tr></thead><tbody>';
        foreach ( $posts as $p ) {
            $nums = get_post_meta( $p->ID, '_bolaox_result', true );
            $base = admin_url( 'admin.php?page=bolaox-history&export=' . $p->ID );
            $csv  = esc_url( $base . '&type=csv' );
            $xls  = esc_url( $base . '&type=xls' );
            $pdf  = esc_url( $base . '&type=pdf' );
            echo '<tr><td>' . esc_html( get_the_date( '', $p ) ) . '</td><td>' . esc_html( $nums ) . '</td><td><a class="button" href="' . $csv . '">CSV</a> <a class="button" href="' . $xls . '">Excel</a> <a class="button" href="' . $pdf . '">PDF</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function stats_page() {
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Estatísticas', self::TEXT_DOMAIN ) . '</h1><p>' . esc_html__( 'Nenhuma aposta cadastrada.', self::TEXT_DOMAIN ) . '</p></div>';
            return;
        }
        $counts = array_fill( 0, 100, 0 );
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums    = array_map( 'trim', explode( ',', $numbers ) );
            foreach ( $nums as $n ) {
                $idx = intval( $n );
                if ( $idx >= 0 && $idx < 100 ) {
                    $counts[ $idx ]++;
                }
            }
        }
        $total = array_sum( $counts );
        echo '<div class="wrap"><h1>' . esc_html__( 'Estatísticas de Frequência', self::TEXT_DOMAIN ) . '</h1>';
        echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Dezena', self::TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Ocorrências', self::TEXT_DOMAIN ) . '</th><th>%</th></tr></thead><tbody>';
        for ( $i = 0; $i < 100; $i++ ) {
            if ( $counts[ $i ] > 0 ) {
                $num  = str_pad( strval( $i ), 2, '0', STR_PAD_LEFT );
                $perc = round( ( $counts[ $i ] / $total ) * 100 );
                $bar  = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $perc . '" data-progress="' . $perc . '%"><span style="width:' . $perc . '%"></span></div>';
                echo '<tr><td>' . $num . '</td><td>' . $counts[ $i ] . '</td><td>' . $bar . '</td></tr>';
            }
        }
        echo '</tbody></table></div>';
    }

    public function logs_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Logs de Pagamento', self::TEXT_DOMAIN ) . '</h1>';
        if ( isset( $_POST['bolaox_clear_logs'] ) && check_admin_referer( 'bolaox_clear_logs' ) ) {
            if ( file_exists( $this->log_file ) ) {
                file_put_contents( $this->log_file, '' );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Logs limpos.', self::TEXT_DOMAIN ) . '</p></div>';
        }
        if ( file_exists( $this->log_file ) && filesize( $this->log_file ) ) {
            $content = file_get_contents( $this->log_file );
            echo '<textarea readonly rows="20" style="width:100%">' . esc_textarea( $content ) . '</textarea>';
            echo '<form method="post">';
            wp_nonce_field( 'bolaox_clear_logs' );
            echo '<p><input type="submit" name="bolaox_clear_logs" class="button" value="' . esc_attr__( 'Limpar logs', self::TEXT_DOMAIN ) . '" /></p>';
            echo '</form>';
        } else {
            echo '<p>' . esc_html__( 'Nenhum log encontrado.', self::TEXT_DOMAIN ) . '</p>';
        }
        echo '</div>';
    }

    public function general_logs_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Logs Gerais', self::TEXT_DOMAIN ) . '</h1>';
        if ( isset( $_POST['bolaox_clear_general'] ) && check_admin_referer( 'bolaox_clear_general' ) ) {
            if ( file_exists( $this->general_log_file ) ) {
                file_put_contents( $this->general_log_file, '' );
            }
            echo '<div class="updated"><p>' . esc_html__( 'Logs limpos.', self::TEXT_DOMAIN ) . '</p></div>';
        }
        if ( file_exists( $this->general_log_file ) && filesize( $this->general_log_file ) ) {
            $content = file_get_contents( $this->general_log_file );
            echo '<textarea readonly rows="20" style="width:100%">' . esc_textarea( $content ) . '</textarea>';
            echo '<form method="post">';
            wp_nonce_field( 'bolaox_clear_general' );
            echo '<p><input type="submit" name="bolaox_clear_general" class="button" value="' . esc_attr__( 'Limpar logs', self::TEXT_DOMAIN ) . '" /></p>';
            echo '</form>';
        } else {
            echo '<p>' . esc_html__( 'Nenhum log encontrado.', self::TEXT_DOMAIN ) . '</p>';
        }
        echo '</div>';
    }

    public function pending_payments_menu() {
        add_submenu_page(
            'edit.php?post_type=bolaox_aposta',
            __( 'Pagamentos não confirmados', self::TEXT_DOMAIN ),
            __( 'Pagamentos não confirmados', self::TEXT_DOMAIN ),
            'manage_options',
            'bolaox-pending',
            array( $this, 'pending_payments_page' )
        );
    }

    public function pending_payments_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Pagamentos via Pix não confirmados', self::TEXT_DOMAIN ) . '</h1>';
        if ( isset( $_POST['bolaox_mark_paid'], $_POST['post_id'] ) && check_admin_referer( 'bolaox_mark_paid_' . intval( $_POST['post_id'] ) ) ) {
            update_post_meta( intval( $_POST['post_id'] ), '_bolaox_payment', 'paid' );
            echo '<div class="updated"><p>' . esc_html__( 'Pagamento marcado como pago.', self::TEXT_DOMAIN ) . '</p></div>';
        }
        $contest = intval( get_option( 'bolaox_active_concurso', 0 ) );
        $posts   = get_posts( array(
            'post_type'   => 'bolaox_aposta',
            'numberposts' => -1,
            'meta_query'  => array(
                array( 'key' => '_bolaox_payment', 'value' => 'pending' ),
                array( 'key' => '_bolaox_concurso', 'value' => $contest ),
            ),
        ) );
        if ( ! $posts ) {
            echo '<p>' . esc_html__( 'Nenhum pagamento pendente.', self::TEXT_DOMAIN ) . '</p></div>';
            return;
        }
        echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Aposta', self::TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Dezenas', self::TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Ações', self::TEXT_DOMAIN ) . '</th></tr></thead><tbody>';
        foreach ( $posts as $p ) {
            $nums = get_post_meta( $p->ID, '_bolaox_numbers', true );
            echo '<tr><td>' . esc_html( $p->post_title ) . '</td><td>' . esc_html( $nums ) . '</td><td>';
            echo '<form method="post" style="display:inline">';
            wp_nonce_field( 'bolaox_mark_paid_' . $p->ID );
            echo '<input type="hidden" name="post_id" value="' . $p->ID . '" />';
            echo '<input type="submit" name="bolaox_mark_paid" class="button" value="' . esc_attr__( 'Marcar como pago', self::TEXT_DOMAIN ) . '" />';
            echo '</form></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function contemplados_page() {
        $selected = isset( $_GET['contest'] ) ? intval( $_GET['contest'] ) : 0;
        $order    = isset( $_GET['orderby'] ) && 'date' === $_GET['orderby'] ? 'date' : 'score';
        $contests = get_posts( array(
            'post_type'   => 'bolaox_concurso',
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );

        $active   = intval( get_option( 'bolaox_active_concurso', 0 ) );
        if ( ! $selected ) {
            $selected = $active;
        }

        echo '<div class="wrap bolaox-contemplados"><h1>' . esc_html__( 'Contemplados', self::TEXT_DOMAIN ) . '</h1>';

        if ( ! $contests ) {
            echo '<p>' . esc_html__( 'Nenhum concurso cadastrado.', self::TEXT_DOMAIN ) . '</p></div>';
            return;
        }

        echo '<form method="get" class="bolaox-contest-form" style="margin-bottom:15px">';
        echo '<input type="hidden" name="page" value="bolaox-contemplados" />';
        echo '<label>' . esc_html__( 'Filtrar por Concurso', self::TEXT_DOMAIN ) . ' ';
        echo '<select name="contest">';
        foreach ( $contests as $c ) {
            echo '<option value="' . $c->ID . '"' . selected( $selected, $c->ID, false ) . '>' . esc_html( $c->post_title ) . '</option>';
        }
        echo '</select></label> ';
        echo '<label>' . esc_html__( 'Ordenar por', self::TEXT_DOMAIN ) . ' ';
        echo '<select name="orderby">';
        echo '<option value="score"' . selected( $order, 'score', false ) . '>' . esc_html__( 'Pontuação', self::TEXT_DOMAIN ) . '</option>';
        echo '<option value="date"' . selected( $order, 'date', false ) . '>' . esc_html__( 'Data', self::TEXT_DOMAIN ) . '</option>';
        echo '</select></label> ';
        submit_button( __( 'Filtrar', self::TEXT_DOMAIN ), 'secondary', '', false );
        echo '</form>';

        if ( ! $selected ) {
            echo '<p>' . esc_html__( 'Nenhum concurso ativo.', self::TEXT_DOMAIN ) . '</p></div>';
            return;
        }

        if ( isset( $_GET['export'] ) ) {
            $result_post = get_posts( array(
                'post_type'   => 'bolaox_result',
                'numberposts' => 1,
                'post_status' => 'publish',
                'meta_query'  => array(
                    array( 'key' => '_bolaox_concurso', 'value' => $selected ),
                ),
            ) );
            if ( $result_post ) {
                $numbers = get_post_meta( $result_post[0]->ID, '_bolaox_result', true );
                if ( ! $numbers ) {
                    $numbers = get_post_meta( $result_post[0]->ID, '_bolaox_numbers', true );
                }
                $this->export_pdf( $numbers, get_the_title( $selected ) );
            }
        }

        $result_post = get_posts( array(
            'post_type'   => 'bolaox_result',
            'numberposts' => 1,
            'post_status' => 'publish',
            'meta_query'  => array(
                array( 'key' => '_bolaox_concurso', 'value' => $selected ),
            ),
        ) );
        if ( ! $result_post ) {
            echo '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p></div>';
            return;
        }

        $numbers = get_post_meta( $result_post[0]->ID, '_bolaox_result', true );
        if ( ! $numbers ) {
            $numbers = get_post_meta( $result_post[0]->ID, '_bolaox_numbers', true );
        }
        echo '<h2>' . sprintf( esc_html__( 'Resultado do Concurso %s', self::TEXT_DOMAIN ), get_the_title( $selected ) ) . '</h2>';
        $export_link = esc_url( admin_url( 'admin.php?page=bolaox-contemplados&contest=' . $selected . '&orderby=' . $order . '&export=1' ) );
        echo '<p><a class="button" href="' . $export_link . '">' . esc_html__( 'Exportar Relatório', self::TEXT_DOMAIN ) . '</a></p>';
        echo $this->generate_report( $numbers, $selected, $order );
        echo '</div>';
    }


    public function clear_pending_payments( $post_id, $post, $update ) {
        if ( 'publish' !== $post->post_status ) {
            return;
        }
        $contest = intval( get_option( 'bolaox_active_concurso', 0 ) );
        if ( ! $contest ) {
            return;
        }
        $pending = get_posts( array(
            'post_type'   => 'bolaox_aposta',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => array(
                array( 'key' => '_bolaox_concurso', 'value' => $contest ),
                array( 'key' => '_bolaox_payment', 'value' => 'paid', 'compare' => '!=' ),
            ),
        ) );
        foreach ( $pending as $pid ) {
            wp_delete_post( $pid, true );
        }
    }

    private function generate_report( $result, $contest_id = 0, $order_by = 'score' ) {
        $res_numbers = array_filter( array_map( 'trim', explode( ',', $result ) ), 'strlen' );
        $res_display = array();
        foreach ( $res_numbers as $r ) {
            $res_display[] = sprintf( '<span class="bolaox-number">%s</span>', esc_html( $r ) );
        }
        $res_drawn = implode( '', $res_display );
        $args = array(
            'post_type'   => 'bolaox_aposta',
            'numberposts' => -1,
        );
        if ( $contest_id ) {
            $args['meta_query'] = array(
                array( 'key' => '_bolaox_concurso', 'value' => $contest_id ),
            );
        }
        $posts = get_posts( $args );
        if ( ! $posts ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhuma aposta cadastrada.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $rows = array();
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums    = array_filter( array_map( 'trim', explode( ',', $numbers ) ), 'strlen' );
            $hits    = array_intersect( $res_numbers, $nums );
            $hit_count = count( $hits );
            $percentage = $hit_count * 10;
            $progress  = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percentage . '" data-progress="' . $percentage . '%" style="--progress:' . $percentage . '%">';
            $progress .= '<span></span></div>';
            $duplicates = array_unique( array_diff_assoc( $nums, array_unique( $nums ) ) );
            $display_nums = array();
            $display_res  = array();
            foreach ( $nums as $n ) {
                $n = trim( $n );
                $classes = array( 'bolaox-number' );
                if ( in_array( $n, $duplicates ) ) {
                    $classes[] = 'dup';
                }
                if ( in_array( $n, $res_numbers ) ) {
                    $classes[] = 'hit';
                }
                $display_nums[] = sprintf(
                    '<span class="%s">%s</span>',
                    esc_attr( implode( ' ', $classes ) ),
                    esc_html( $n )
                );
            }
            foreach ( $res_numbers as $r ) {
                $cls = array( 'bolaox-number' );
                if ( in_array( $r, $nums ) ) {
                    $cls[] = 'hit';
                }
                $display_res[] = sprintf( '<span class="%s">%s</span>', esc_attr( implode( ' ', $cls ) ), esc_html( $r ) );
            }
            $class = '';
            if ( $hit_count >= 8 ) {
                $class = ' class="bolaox-hit-' . $hit_count . '"';
            }
            $rows[] = array(
                'id'      => $p->ID,
                'hits'    => $hit_count,
                'date'    => $p->post_date,
                'class'   => $class,
                'name'    => esc_html( $p->post_title ),
                'progress' => $progress,
                'numbers' => implode( '', $display_nums ),
                'drawn'   => implode( '', $display_res ),
                'contest' => get_the_title( $contest_id ),
                'type'    => '',
            );
        }

        if ( 'date' === $order_by ) {
            usort( $rows, function ( $a, $b ) {
                return strcmp( $b['date'], $a['date'] );
            } );
        } else {
            usort( $rows, function ( $a, $b ) {
                return $b['hits'] <=> $a['hits'];
            } );
        }
        $max_hits = $rows ? max( wp_list_pluck( $rows, 'hits' ) ) : 0;
        $min_hits = $rows ? min( wp_list_pluck( $rows, 'hits' ) ) : 0;

        $out = '';
        $winners_high = array_filter(
            $rows,
            function ( $row ) use ( $max_hits ) {
                return $row['hits'] === $max_hits;
            }
        );
        $winners_low  = array_filter(
            $rows,
            function ( $row ) use ( $min_hits ) {
                return $row['hits'] === $min_hits;
            }
        );
        foreach ( $winners_high as &$r ) {
            $r['type'] = __( 'Mais pontos', self::TEXT_DOMAIN );
        }
        foreach ( $winners_low as &$r ) {
            $r['type'] = __( 'Menos pontos', self::TEXT_DOMAIN );
        }

        $out .= '<h3 class="bolaox-section-title">' . esc_html__( 'VENCEDORES COM MAIS PONTOS', self::TEXT_DOMAIN ) . '</h3>';
        $out .= $this->build_results_table( $winners_high );
        $out .= '<h3 class="bolaox-section-title">' . esc_html__( 'VENCEDORES COM MENOS PONTOS', self::TEXT_DOMAIN ) . '</h3>';
        $out .= $this->build_results_table( $winners_low );
        $out .= '<h3 class="bolaox-section-title">' . esc_html__( 'TODAS AS APOSTAS', self::TEXT_DOMAIN ) . '</h3>';
        $out .= $this->build_results_table( $rows );

        $total = count( $rows );
        $avg   = $total ? array_sum( wp_list_pluck( $rows, 'hits' ) ) / $total : 0;
        $rate  = $avg ? ( $avg / 10 ) * 100 : 0;
        $out .= '<h3 class="bolaox-section-title">' . esc_html__( 'ESTATÍSTICAS', self::TEXT_DOMAIN ) . '</h3>';
        $out .= '<ul class="bolaox-stats">';
        $out .= '<li>' . sprintf( esc_html__( 'Total de Participantes: %d', self::TEXT_DOMAIN ), $total ) . '</li>';
        $out .= '<li>' . sprintf( esc_html__( 'Média de Pontos: %.2f', self::TEXT_DOMAIN ), $avg ) . '</li>';
        $out .= '<li>' . sprintf( esc_html__( 'Taxa de Acerto: %s%%', self::TEXT_DOMAIN ), round( $rate ) ) . '</li>';
        $out .= '</ul>';

        return $this->wrap_app( $out );
    }

    private function build_results_table( $rows ) {
        if ( ! $rows ) {
            return '<p>' . esc_html__( 'Nenhum vencedor.', self::TEXT_DOMAIN ) . '</p>';
        }
        $out  = '<div class="bolaox-table-wrapper bolaox-scroll"><table class="widefat bolaox-table"><thead><tr>';
        $out .= '<th>#</th><th>' . esc_html__( 'Apostador', self::TEXT_DOMAIN ) . '</th>';
        $out .= '<th>' . esc_html__( 'Concurso', self::TEXT_DOMAIN ) . '</th>';
        $out .= '<th>' . esc_html__( 'Números apostados', self::TEXT_DOMAIN ) . '</th>';
        $out .= '<th>' . esc_html__( 'Números sorteados', self::TEXT_DOMAIN ) . '</th>';
        $out .= '<th>' . esc_html__( 'Acertos', self::TEXT_DOMAIN ) . '</th>';
        $out .= '<th>' . esc_html__( 'Tipo', self::TEXT_DOMAIN ) . '</th></tr></thead><tbody>';
        $idx  = 1;
        foreach ( $rows as $row ) {
            $num   = str_pad( strval( $idx++ ), 2, '0', STR_PAD_LEFT );
            $out  .= '<tr' . $row['class'] . '><td class="col-index">' . $num . '</td><td class="col-name">' . $row['name'] . '</td><td>' . esc_html( $row['contest'] ) . '</td><td class="bolaox-numlist">' . $row['numbers'] . '</td><td class="bolaox-numlist">' . $row['drawn'] . '</td><td>' . $row['hits'] . '</td><td>' . esc_html( $row['type'] ) . '</td></tr>';
        }
        $out .= '</tbody></table></div>';
        return $out;
    }

    private function export_csv( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts       = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return;
        }
        header( 'Content-Type: text/csv' );
        header( 'Content-Disposition: attachment; filename="bolao-resultados.csv"' );
        echo "Aposta,Acertos,Dezenas\n";
        foreach ( $posts as $p ) {
            $numbers   = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums      = array_map( 'trim', explode( ',', $numbers ) );
            $hits      = array_intersect( $res_numbers, $nums );
            $hit_count = count( $hits );
            $title     = str_replace( '"', '""', $p->post_title );
            echo '"' . $title . '",' . $hit_count . ',"' . implode( ' ', $nums ) . '"' . "\n";
        }
        exit;
    }

    private function export_xls( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts       = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return;
        }
        header( 'Content-Type: application/vnd.ms-excel' );
        header( 'Content-Disposition: attachment; filename="bolao-resultados.xls"' );
        echo "Aposta\tAcertos\tDezenas\n";
        foreach ( $posts as $p ) {
            $numbers   = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums      = array_map( 'trim', explode( ',', $numbers ) );
            $hits      = array_intersect( $res_numbers, $nums );
            $hit_count = count( $hits );
            echo $p->post_title . "\t" . $hit_count . "\t" . implode( ' ', $nums ) . "\n";
        }
        exit;
    }

    private function export_pdf( $result, $contest_title = '' ) {
        require_once __DIR__ . '/lib/fpdf.php';
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts       = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return;
        }
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 14 );
        if ( $contest_title ) {
            $title = sprintf( __( 'Relatorio do Concurso %s', self::TEXT_DOMAIN ), $contest_title );
        } else {
            $title = __( 'Relatorio de Apostas', self::TEXT_DOMAIN );
        }
        $pdf->Cell( 0, 10, $title, 0, 1, 'C' );
        $pdf->Ln( 2 );
        $pdf->SetFont( 'Arial', 'B', 12 );
        $pdf->Cell( 60, 8, __( 'Aposta', self::TEXT_DOMAIN ), 1 );
        $pdf->Cell( 30, 8, __( 'Acertos', self::TEXT_DOMAIN ), 1, 0, 'C' );
        $pdf->Cell( 0, 8, __( 'Dezenas', self::TEXT_DOMAIN ), 1, 1 );
        $pdf->SetFont( 'Arial', '', 12 );
        foreach ( $posts as $p ) {
            $numbers   = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums      = array_map( 'trim', explode( ',', $numbers ) );
            $hits      = array_intersect( $res_numbers, $nums );
            $hit_count = count( $hits );
            $pdf->Cell( 60, 8, $p->post_title, 1 );
            $pdf->Cell( 30, 8, $hit_count, 1, 0, 'C' );
            $pdf->Cell( 0, 8, implode( ' ', $nums ), 1, 1 );
        }
        $pdf->Output( 'D', 'bolao-resultados.pdf' );
        exit;
    }

    private function export_json( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return;
        }
        $rows = array();
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums    = array_map( 'trim', explode( ',', $numbers ) );
            $hits    = count( array_intersect( $res_numbers, $nums ) );
            $rows[] = array(
                'aposta'  => $p->post_title,
                'acertos' => $hits,
                'dezenas' => $nums,
            );
        }
        header( 'Content-Type: application/json' );
        header( 'Content-Disposition: attachment; filename="bolao-resultados.json"' );
        echo wp_json_encode( $rows );
        exit;
    }

    private function send_results_email( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        foreach ( $posts as $p ) {
            $user = get_user_by( 'ID', $p->post_author );
            if ( ! $user || ! is_email( $user->user_email ) ) {
                continue;
            }
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums    = array_map( 'trim', explode( ',', $numbers ) );
            $hits    = count( array_intersect( $res_numbers, $nums ) );
            $message  = sprintf( __( 'Olá %s,', self::TEXT_DOMAIN ), $p->post_title ) . "\n\n";
            $message .= sprintf( __( 'O resultado da semana foi: %s', self::TEXT_DOMAIN ), $result ) . "\n";
            $message .= sprintf( __( 'Sua aposta: %s', self::TEXT_DOMAIN ), implode( ',', $nums ) ) . "\n";
            $message .= sprintf( __( 'Acertos: %d', self::TEXT_DOMAIN ), $hits ) . "\n";
            if ( $hits >= 8 ) {
                $message .= sprintf( __( 'Parabéns! Você acertou %d dezenas.', self::TEXT_DOMAIN ), $hits );
            }
            wp_mail( $user->user_email, __( 'Resultado do Bolão', self::TEXT_DOMAIN ), $message );
        }
    }

    private function update_lowest_info( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            update_option( 'bolaox_lowest_info', array( 'id' => 0, 'hits' => 0, 'pool' => 0 ) );
            return;
        }
        $min = null;
        $ids = array();
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums = array_map( 'trim', explode( ',', $numbers ) );
            $hits = count( array_intersect( $res_numbers, $nums ) );
            if ( null === $min || $hits < $min ) {
                $min = $hits;
                $ids = array( $p->ID );
            } elseif ( $hits === $min ) {
                $ids[] = $p->ID;
            }
        }
        $info = get_option( 'bolaox_lowest_info', array( 'id' => 0, 'hits' => 0, 'pool' => 0 ) );
        if ( count( $ids ) === 1 ) {
            $info = array( 'id' => $ids[0], 'hits' => $min, 'pool' => 0 );
        } else {
            $pool = isset( $info['pool'] ) ? intval( $info['pool'] ) : 0;
            $info = array( 'id' => 0, 'hits' => $min, 'pool' => $pool + 1 );
        }
        update_option( 'bolaox_lowest_info', $info );
    }

    private function render_number_board( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $out = '<div class="bolaox-scroll"><div class="bolaox-board">';
        for ( $i = 0; $i < 100; $i++ ) {
            $num   = str_pad( strval( $i ), 2, '0', STR_PAD_LEFT );
            $class = 'bolaox-number';
            if ( in_array( $num, $res_numbers, true ) ) {
                $class .= ' drawn';
            }
            $out .= '<span class="' . $class . '">' . esc_html( $num ) . '</span>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private function render_repeated_numbers( $contest_id = 0 ) {
        $args = array(
            'post_type'   => 'bolaox_aposta',
            'numberposts' => -1,
        );
        if ( $contest_id ) {
            $args['meta_query'] = array(
                array( 'key' => '_bolaox_concurso', 'value' => $contest_id ),
            );
        }
        $posts = get_posts( $args );
        if ( ! $posts ) {
            return '';
        }
        $counts = array_fill( 0, 100, 0 );
        foreach ( $posts as $p ) {
            $nums = array_map( 'trim', explode( ',', get_post_meta( $p->ID, '_bolaox_numbers', true ) ) );
            foreach ( $nums as $n ) {
                $idx = intval( $n );
                if ( $idx >= 0 && $idx < 100 ) {
                    $counts[ $idx ]++;
                }
            }
        }
        $dups = array();
        for ( $i = 0; $i < 100; $i++ ) {
            if ( $counts[ $i ] > 1 ) {
                $dups[] = str_pad( strval( $i ), 2, '0', STR_PAD_LEFT );
            }
        }
        if ( ! $dups ) {
            return '';
        }
        $out  = '<h3>' . esc_html__( 'NÚMEROS REPETIDOS', self::TEXT_DOMAIN ) . '</h3>';
        $out .= '<div class="bolaox-scroll"><div class="bolaox-board">';
        foreach ( $dups as $n ) {
            $out .= '<span class="bolaox-number">' . esc_html( $n ) . '</span>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private function render_recent_repeated_numbers() {
        $posts = get_posts( array(
            'post_type'   => 'bolaox_result',
            // Analyze the last 12 draws so statistics reset every dozen contests
            'numberposts' => 12,
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        if ( ! $posts ) {
            return '';
        }
        $counts = array_fill( 0, 100, 0 );
        foreach ( $posts as $p ) {
            $nums = get_post_meta( $p->ID, '_bolaox_result', true );
            if ( ! $nums ) {
                $nums = get_post_meta( $p->ID, '_bolaox_numbers', true );
            }
            foreach ( array_map( 'trim', explode( ',', $nums ) ) as $n ) {
                $idx = intval( $n );
                if ( $idx >= 0 && $idx < 100 ) {
                    $counts[ $idx ]++;
                }
            }
        }
        $repeats = array();
        for ( $i = 0; $i < 100; $i++ ) {
            if ( $counts[ $i ] > 1 ) {
                $repeats[ $i ] = $counts[ $i ];
            }
        }
        if ( ! $repeats ) {
            return '';
        }
        arsort( $repeats );
        $out  = '<h3>' . esc_html__( 'NÚMEROS REPETIDOS', self::TEXT_DOMAIN ) . '</h3>';
        $out .= '<div class="bolaox-scroll"><div class="bolaox-board">';
        foreach ( array_keys( $repeats ) as $n ) {
            $out .= '<span class="bolaox-number">' . str_pad( strval( $n ), 2, '0', STR_PAD_LEFT ) . '</span>';
        }
        $out .= '</div></div>';
        return $out;
    }

    private function wrap_app( $html ) {
        $logged = is_user_logged_in();
        $class  = $logged ? 'bx-logged-in' : 'bx-logged-out';
        $attr   = $logged ? '1' : '0';
        return '<div class="bolaox-app ' . $class . '" data-logged-in="' . $attr . '">' . $html . '</div>';
    }

    public function render_form_shortcode() {
        $this->maybe_restore_session();
        $cutoffs  = get_option( 'bolaox_cutoffs', array() );
        $countdown = '';
        $contest_id = intval( get_option( 'bolaox_active_concurso', 0 ) );
        $now = current_time( 'timestamp' );
        if ( ! $contest_id ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhum concurso ativo.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $start = get_post_meta( $contest_id, '_bolaox_start', true );
        $end   = get_post_meta( $contest_id, '_bolaox_end', true );
        if ( $start && $now < strtotime( $start ) ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Apostas ainda não liberadas.', self::TEXT_DOMAIN ) . '</p>' );
        }
        if ( $end && $now >= strtotime( $end ) ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Apostas encerradas. Tente novamente no próximo concurso.', self::TEXT_DOMAIN ) . '</p>' );
        }

        $price   = floatval( get_option( 'bolaox_price', 10 ) );
        $raw_key = get_option( 'bolaox_pix_key', '' );
        $pix_key = is_string( $raw_key ) ? trim( $raw_key ) : '';
        $msg     = '';
        if ( $cutoffs ) {
            $day  = (int) date( 'N', $now );
            if ( ! empty( $cutoffs[ $day ] ) ) {
                $cutoff_ts = strtotime( date( 'Y-m-d ' . $cutoffs[ $day ], $now ) );
                if ( $now >= $cutoff_ts ) {
                    return $this->wrap_app( '<p>' . esc_html__( 'Apostas encerradas. Tente novamente no próximo concurso.', self::TEXT_DOMAIN ) . '</p>' );
                }
                $countdown = '<div class="bolaox-countdown" data-end="' . esc_attr( $cutoff_ts ) . '" data-expired="' . esc_attr__( 'Tempo esgotado', self::TEXT_DOMAIN ) . '"></div>';
            }
        }
        $pix        = array();
        $payment_id = '';

        if ( isset( $_POST['bolaox_submit'] ) && isset( $_POST['bolaox_nonce'] ) && wp_verify_nonce( $_POST['bolaox_nonce'], 'bolaox_form' ) ) {
            $numbers_raw = isset( $_POST['bolaox_numbers'] ) ? (array) $_POST['bolaox_numbers'] : array();
            $valid_sets  = array();
            foreach ( $numbers_raw as $set ) {
                $clean = $this->validate_numbers( sanitize_text_field( $set ) );
                if ( false === $clean ) {
                    return $this->wrap_app( '<p>' . esc_html__( 'Formato de dezenas inválido. Use 10 números de 00 a 99 separados por vírgula.', self::TEXT_DOMAIN ) . '</p>' );
                }
                $valid_sets[] = $clean;
            }
            $qty        = count( $valid_sets );
            $payment_id = sanitize_text_field( $_POST['bolaox_payment_id'] );
            $status = 'paid';
            if ( ! $this->verify_mp_payment( $payment_id, $price * $qty ) ) {
                $status      = 'pending';
                $msg         = '<p class="bolaox-error">' . esc_html__( 'Pagamento via Pix não confirmado.', self::TEXT_DOMAIN ) . '</p>';
                $pix         = $this->create_mp_pix_payment( 'tmp-' . wp_generate_password( 8, false, false ), $qty );
                $payment_id  = isset( $pix['id'] ) ? $pix['id'] : '';
            }

            $phone = '';
            if ( ! is_user_logged_in() ) {
                $phone = isset( $_POST['bolaox_phone'] ) ? $this->sanitize_phone( $_POST['bolaox_phone'] ) : '';
                if ( empty( $phone ) ) {
                    return $this->wrap_app( '<p>' . esc_html__( 'Telefone obrigatório.', self::TEXT_DOMAIN ) . '</p>' );
                }
                $name = sanitize_text_field( $_POST['bolaox_name'] );
            } else {
                $name  = wp_get_current_user()->display_name;
                $phone = get_user_meta( get_current_user_id(), 'bolaox_phone', true );
            }
            $i    = 1;
            foreach ( $valid_sets as $set ) {
                $title   = $qty > 1 ? $name . ' #' . $i : $name;
                $post_id = wp_insert_post( array(
                    'post_type'   => 'bolaox_aposta',
                    'post_title'  => $title,
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                ) );
                if ( $post_id ) {
                    update_post_meta( $post_id, '_bolaox_numbers', $set );
                    update_post_meta( $post_id, '_bolaox_payment', $status );
                    update_post_meta( $post_id, '_bolaox_mp_pref', $payment_id );
                    update_post_meta( $post_id, '_bolaox_concurso', $contest_id );
                    update_post_meta( $post_id, '_bolaox_fixed', '0' );
                    if ( $phone ) {
                        update_post_meta( $post_id, '_bolaox_phone', $phone );
                    }
                    if ( get_current_user_id() ) {
                        update_post_meta( $post_id, '_bolaox_user', get_current_user_id() );
                    }
                }
                $i++;
            }

            $redirect_url = esc_url( add_query_arg( array(
                'phone'       => rawurlencode( $phone ),
                'redirect_to' => rawurlencode( home_url( '/minhas-apostas' ) ),
            ), home_url( '/login-cadastro' ) ) );

            if ( ! headers_sent() ) {
                wp_safe_redirect( $redirect_url );
                exit;
            }

            return '<script>window.location.href="' . esc_url( $redirect_url ) . '";</script>';
        }

        $html  = '<div class="bolaox-form">';
        if ( $msg ) {
            $html .= $msg;
        }
        $html .= '<div id="bolaox-pix-modal" class="bolaox-modal"><div class="bolaox-modal-content">';
        $src = '';
        $code = '';
        if ( $pix ) {
            if ( ! empty( $pix['qr_code_base64'] ) ) {
                $src  = 'data:image/png;base64,' . $pix['qr_code_base64'];
            } elseif ( ! empty( $pix['qr_code'] ) ) {
                $src  = 'https://chart.googleapis.com/chart?chs=500x500&cht=qr&chl=' . rawurlencode( $pix['qr_code'] );
            }
            $code = isset( $pix['qr_code'] ) ? $pix['qr_code'] : '';
        }
        $html .= '<p><img src="' . esc_attr( $src ) . '" alt="Pix QR" width="500" height="500" /></p>';
        $html .= '<p>' . esc_html__( 'Copie o Código:', self::TEXT_DOMAIN ) . '<br />';
        $html .= '<input type="text" id="bolaox-pix-code" value="' . esc_attr( $code ) . '" readonly class="bolaox-pix-code" />';
        $html .= '<button type="button" class="button bolaox-copy" data-target="#bolaox-pix-code">' . esc_html__( 'Copiar', self::TEXT_DOMAIN ) . '</button></p>';
        $html .= '<p><span class="button bolaox-modal-close">' . esc_html__( 'Fechar', self::TEXT_DOMAIN ) . '</span></p></div></div>';

        $html .= '<form method="post" class="bolaox-form-inner">';
        if ( $countdown ) {
            $html .= $countdown;
        }
        $html .= wp_nonce_field( 'bolaox_form', 'bolaox_nonce', true, false );
        $html .= '<input type="hidden" name="bolaox_payment_id" value="' . esc_attr( $payment_id ) . '" />';
        if ( ! is_user_logged_in() ) {
            $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Como quer ser chamado?', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_name" required /></label></p>';
            $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Telefone', self::TEXT_DOMAIN ) . '<br /><input type="tel" name="bolaox_phone" class="bolaox-phone" required /></label></p>';
        }
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Escolha 10 dezenas', self::TEXT_DOMAIN ) . '</label>';
        $html .= '<div class="bolaox-numbers">';
        for ( $i = 0; $i < 100; $i++ ) {
            $num = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
            $html .= '<span class="bolaox-number">' . esc_html( $num ) . '</span>';
        }
        $html .= '<input type="hidden" class="bolaox-current" />';
        $html .= '</div></p>';
        $html .= '<p><button type="button" class="button bolaox-add-bet" data-label-init="' . esc_attr__( 'Adicionar Jogo', self::TEXT_DOMAIN ) . '" data-label-more="' . esc_attr__( 'Adicionar mais um Jogo', self::TEXT_DOMAIN ) . '">' . esc_html__( 'Adicionar Jogo', self::TEXT_DOMAIN ) . '</button></p>';
        $html .= '<div class="bolaox-cart"></div>';
        $html .= '<p><a href="#" class="button bolaox-pix-btn" data-target="#bolaox-pix-modal">' . esc_html__( 'PAGAR COM PIX', self::TEXT_DOMAIN ) . '</a></p>';
        $html .= '<p class="bolaox-price" data-price="' . esc_attr( number_format( $price, 2, '.', '' ) ) . '">' . sprintf( esc_html__( 'Valor total: R$ %s', self::TEXT_DOMAIN ), number_format( $price, 2, ',', '.' ) ) . '</p>';
        $html .= '<p class="bolaox-field"><input type="submit" name="bolaox_submit" value="' . esc_attr__( 'APOSTE AGORA', self::TEXT_DOMAIN ) . '" class="button bolaox-submit" /></p>';
        $html .= '</form></div>';
        return $this->wrap_app( $html );
    }

    public function render_results_shortcode() {
        if ( isset( $_GET['bolaox_all_results'] ) ) {
            return $this->render_all_results_shortcode();
        }

        $posts = get_posts( array(
            'post_type'   => 'bolaox_result',
            'numberposts' => 3,
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        if ( ! $posts ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p>' );
        }

        $first_nums    = '';
        $first_contest = 0;
        $output  = '<div class="bolaox-result">';
        $output .= '<h3>' . esc_html__( 'ÚLTIMOS RESULTADOS', self::TEXT_DOMAIN ) . '</h3>';
        foreach ( $posts as $index => $p ) {
            $nums = get_post_meta( $p->ID, '_bolaox_result', true );
            if ( ! $nums ) {
                $nums = get_post_meta( $p->ID, '_bolaox_numbers', true );
            }
            if ( 0 === $index ) {
                $first_nums    = $nums;
                $first_contest = intval( get_post_meta( $p->ID, '_bolaox_concurso', true ) );
            }
            $title = get_the_title( $p );
            $output .= '<h4 class="bolaox-result-title">' . esc_html( $title ) . '</h4>';
            $output .= $this->render_number_board( $nums );
        }
        $link   = add_query_arg( 'bolaox_all_results', 1, get_permalink() );
        $output .= '<p class="bolaox-center"><a href="' . esc_url( $link ) . '" class="bolaox-button bolaox-view-all">' . esc_html__( 'VER TODOS RESULTADOS', self::TEXT_DOMAIN ) . '</a></p>';
        $output .= $this->render_recent_repeated_numbers();
        $output .= $this->generate_report( $first_nums, $first_contest, 'score' );
        $output .= '</div>';
        return $this->wrap_app( $output );
    }

    private function render_all_results_shortcode() {
        $paged    = max( 1, intval( get_query_var( 'bolaox_page' ) ) );
        $per_page = 6;
        $query    = new WP_Query( array(
            'post_type'      => 'bolaox_result',
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
        ) );
        if ( ! $query->have_posts() ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $out = '<ul class="bolaox-history">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $nums = get_post_meta( get_the_ID(), '_bolaox_result', true );
            if ( ! $nums ) {
                $nums = get_post_meta( get_the_ID(), '_bolaox_numbers', true );
            }
            $numlist = '';
            foreach ( array_map( 'trim', explode( ',', $nums ) ) as $n ) {
                $numlist .= '<span class="bolaox-number drawn">' . esc_html( $n ) . '</span>';
            }
            $out .= '<li class="bolaox-history-item"><h3 class="bolaox-history-date">' . esc_html( get_the_title() ) . '</h3><div class="bolaox-scroll"><div class="bolaox-numlist">' . $numlist . '</div></div></li>';
        }
        wp_reset_postdata();
        $out .= '</ul>';
        $total = $query->max_num_pages;
        if ( $total > 1 ) {
            $out .= '<div class="bolaox-pagination">';
            for ( $i = 1; $i <= $total; $i++ ) {
                $url   = esc_url( add_query_arg( 'bolaox_page', $i ) );
                $class = $i === $paged ? ' class="current"' : '';
                $out  .= '<a href="' . $url . '"' . $class . '>' . $i . '</a> ';
            }
            $out .= '</div>';
        }
        return $this->wrap_app( $out );
    }

    public function render_history_shortcode() {
        $posts = get_posts( array( 'post_type' => 'bolaox_result', 'numberposts' => 5, 'orderby' => 'date', 'order' => 'DESC' ) );
        if ( ! $posts ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $out = '<ul class="bolaox-history">';
        foreach ( $posts as $p ) {
            $nums = get_post_meta( $p->ID, '_bolaox_result', true );
            $date = esc_html( get_the_date( '', $p ) );
            $numlist = '';
            foreach ( array_map( 'trim', explode( ',', $nums ) ) as $n ) {
                $numlist .= '<span class="bolaox-number drawn">' . esc_html( $n ) . '</span>';
            }
            $out .= '<li class="bolaox-history-item"><h3 class="bolaox-history-date">' . $date . '</h3><div class="bolaox-scroll"><div class="bolaox-numlist">' . $numlist . '</div></div></li>';
        }
        $out .= '</ul>';
        return $this->wrap_app( $out );
    }

    /**
     * Get contest posts that contain bets from the given user.
     */
    private function get_user_contests( $user_id ) {
        $phone = get_user_meta( $user_id, 'bolaox_phone', true );
        $or    = array( 'relation' => 'OR', array( 'key' => '_bolaox_user', 'value' => $user_id ) );
        if ( $phone ) {
            $or[] = array( 'key' => '_bolaox_phone', 'value' => $phone );
        }
        $bet_ids = get_posts( array(
            'post_type'   => 'bolaox_aposta',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => array( $or ),
        ) );
        if ( ! $bet_ids ) {
            return array();
        }
        $contest_ids = array();
        foreach ( $bet_ids as $bid ) {
            $cid = get_post_meta( $bid, '_bolaox_concurso', true );
            if ( $cid ) {
                $contest_ids[] = intval( $cid );
            }
        }
        $contest_ids = array_unique( $contest_ids );
        if ( ! $contest_ids ) {
            return array();
        }
        return get_posts( array(
            'post_type'   => 'bolaox_concurso',
            'post__in'    => $contest_ids,
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
    }

    private function generate_my_bets_table( $user_id, $contest_id = 0 ) {
        $phone = get_user_meta( $user_id, 'bolaox_phone', true );
        $or    = array( 'relation' => 'OR', array( 'key' => '_bolaox_user', 'value' => $user_id ) );
        if ( $phone ) {
            $or[] = array( 'key' => '_bolaox_phone', 'value' => $phone );
        }
        $meta = array( $or );
        if ( $contest_id ) {
            $meta[] = array( 'key' => '_bolaox_concurso', 'value' => $contest_id );
        }
        $query = new WP_Query(
            array(
                'post_type'      => 'bolaox_aposta',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => $meta,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );
        $posts = $query->posts;
        if ( ! $posts ) {
            $query = new WP_Query(
                array(
                    'post_type'      => 'bolaox_aposta',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'author'         => $user_id,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                )
            );
            $posts = $query->posts;
        }
        if ( ! $posts ) {
            $msg = $contest_id
                ? __( 'Nenhuma aposta encontrada para este concurso.', self::TEXT_DOMAIN )
                : __( 'Você ainda não cadastrou apostas.', self::TEXT_DOMAIN );
            return '<p>' . esc_html( $msg ) . '</p>';
        }
        $result      = get_option( 'bolaox_result', '' );
        $res_numbers = $result ? array_map( 'trim', explode( ',', $result ) ) : array();
        $out  = '<div class="bolaox-table-wrapper bolaox-scroll"><table class="widefat bolaox-table">';
        $out .= '<thead><tr>' .
            '<th>' . esc_html__( 'Aposta', self::TEXT_DOMAIN ) . '</th>' .
            '<th>' . esc_html__( 'Acertos', self::TEXT_DOMAIN ) . '</th>' .
            '<th>%</th>' .
            '<th>' . esc_html__( 'Dezenas', self::TEXT_DOMAIN ) . '</th>' .
            '<th>' . esc_html__( 'Status', self::TEXT_DOMAIN ) . '</th>' .
        '</tr></thead><tbody>';
        foreach ( $posts as $p ) {
            $numbers   = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums      = array_map( 'trim', explode( ',', $numbers ) );
            $hits      = $result ? array_intersect( $res_numbers, $nums ) : array();
            $hit_count = count( $hits );
            $percentage = $hit_count * 10;
            $progress     = sprintf(
                '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="%1$d" data-progress="%1$d%%"><span></span></div>',
                $percentage
            );
            $duplicates   = array_unique( array_diff_assoc( $nums, array_unique( $nums ) ) );
            $display_nums = array();
            foreach ( $nums as $n ) {
                $n = trim( $n );
                $classes = array( 'bolaox-number' );
                if ( in_array( $n, $duplicates, true ) ) {
                    $classes[] = 'dup';
                }
                if ( in_array( $n, $res_numbers, true ) ) {
                    $classes[] = 'hit';
                }
                $display_nums[] = sprintf(
                    '<span class="%s">%s</span>',
                    esc_attr( implode( ' ', $classes ) ),
                    esc_html( $n )
                );
            }
            $status = get_post_meta( $p->ID, '_bolaox_payment', true );
            if ( ! $status ) {
                $status = 'pending';
            }
            $label = 'pending' === $status ? __( 'Pendente', self::TEXT_DOMAIN ) : __( 'Pago', self::TEXT_DOMAIN );
            $out   .= sprintf(
                '<tr><td>%s</td><td>%d</td><td>%s</td><td class="bolaox-numlist">%s</td><td>%s</td></tr>',
                esc_html( $p->post_title ),
                $hit_count,
                $progress,
                implode( '', $display_nums ),
                esc_html( $label )
            );
        }
        $out .= '</tbody></table></div>';
        return $out;
    }

    public function render_my_bets_shortcode() {
        $this->maybe_restore_session();
        if ( ! is_user_logged_in() ) {
            $redirect = esc_url_raw( $_SERVER['REQUEST_URI'] );
            $url      = esc_url( add_query_arg( 'redirect_to', rawurlencode( $redirect ), home_url( '/login-cadastro' ) ) );
            if ( ! headers_sent() ) {
                wp_safe_redirect( $url );
                exit;
            }
            return '<script>window.location.href="' . esc_url( $url ) . '";</script>';
        }

        if ( ! headers_sent() ) {
            nocache_headers();
            if ( ! defined( 'DONOTCACHEPAGE' ) ) {
                define( 'DONOTCACHEPAGE', true );
            }
        }
        $contest   = isset( $_GET['contest'] ) ? intval( $_GET['contest'] ) : 0;
        $contests  = $this->get_user_contests( get_current_user_id() );
        $allowed   = wp_list_pluck( $contests, 'ID' );
        if ( $contest && ! in_array( $contest, $allowed, true ) ) {
            $contest = 0;
        }
        $table = $this->generate_my_bets_table( get_current_user_id(), $contest );
        // Use esc_url_raw so the query string remains intact for JavaScript
        // which reads this attribute and performs fetch requests.
        $url   = esc_url_raw( add_query_arg( 'contest', $contest, rest_url( 'bolao-x/v1/mybets' ) ) );
        $html  = '';
        if ( $contests ) {
            $html .= '<form method="get" class="bolaox-contest-form" id="bolaox-contest-form">';
            $html .= '<label>' . esc_html__( 'Filtrar por Concurso', self::TEXT_DOMAIN ) . '</label>';
            $html .= '<select name="contest">';
            $html .= '<option value="0">' . esc_html__( 'Todos', self::TEXT_DOMAIN ) . '</option>';
            foreach ( $contests as $c ) {
                $html .= '<option value="' . $c->ID . '"' . selected( $contest, $c->ID, false ) . '>' . esc_html( $c->post_title ) . '</option>';
            }
            $html .= '</select>';
            $html .= '</form>';
        }
        $html .= '<div id="bolaox-my-bets" data-mybets-url="' . $url . '">' . $table . '</div>';
        return $this->wrap_app( $html );
    }

    public function render_stats_shortcode() {
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return '<p>' . esc_html__( 'Nenhuma aposta cadastrada.', self::TEXT_DOMAIN ) . '</p>';
        }
        $counts = array_fill( 0, 100, 0 );
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums    = array_map( 'trim', explode( ',', $numbers ) );
            foreach ( $nums as $n ) {
                $idx = intval( $n );
                if ( $idx >= 0 && $idx < 100 ) {
                    $counts[ $idx ]++;
                }
            }
        }
        $total = array_sum( $counts );
        $out = '<div class="bolaox-table-wrapper bolaox-scroll"><table class="widefat bolaox-table bolaox-stats"><thead><tr><th>Dezena</th><th>Ocorrências</th><th>%</th></tr></thead><tbody>';
        for ( $i = 0; $i < 100; $i++ ) {
            if ( $counts[ $i ] > 0 ) {
                $num  = str_pad( strval( $i ), 2, '0', STR_PAD_LEFT );
                $perc = round( ( $counts[ $i ] / $total ) * 100 );
                $bar  = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $perc . '" data-progress="' . $perc . '%"><span style="width:' . $perc . '%"></span></div>';
                $out .= '<tr><td>' . $num . '</td><td>' . $counts[ $i ] . '</td><td>' . $bar . '</td></tr>';
            }
        }
        $out .= '</tbody></table></div>';
        return $out;
    }

    public function render_profile_shortcode() {
        $this->maybe_restore_session();
        if ( ! is_user_logged_in() ) {
            $redirect = esc_url_raw( $_SERVER['REQUEST_URI'] );
            $url      = esc_url( add_query_arg( 'redirect_to', rawurlencode( $redirect ), home_url( '/login-cadastro' ) ) );
            if ( ! headers_sent() ) {
                wp_safe_redirect( $url );
                exit;
            }
            return '<script>window.location.href="' . esc_url( $url ) . '";</script>';
        }
        $user      = wp_get_current_user();
        $avatar_id = get_user_meta( $user->ID, 'bolaox_avatar_id', true );
        $msg = '';
        if ( isset( $_POST['bolaox_profile_nonce'] ) && wp_verify_nonce( $_POST['bolaox_profile_nonce'], 'bolaox_profile' ) ) {
            $first = sanitize_text_field( $_POST['bolaox_first_name'] );
            $last  = sanitize_text_field( $_POST['bolaox_last_name'] );
            wp_update_user( array( 'ID' => $user->ID, 'first_name' => $first, 'last_name' => $last ) );
            $msg = '<p>' . esc_html__( 'Dados atualizados com sucesso.', self::TEXT_DOMAIN ) . '</p>';
        } elseif ( isset( $_POST['bolaox_pass_nonce'] ) && wp_verify_nonce( $_POST['bolaox_pass_nonce'], 'bolaox_pass' ) ) {
            $p1 = sanitize_text_field( $_POST['bolaox_pass1'] );
            $p2 = sanitize_text_field( $_POST['bolaox_pass2'] );
            if ( $p1 && $p1 === $p2 ) {
                wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $p1 ) );
                $msg = '<p>' . esc_html__( 'Senha alterada com sucesso.', self::TEXT_DOMAIN ) . '</p>';
            } else {
                $msg = '<p class="bolaox-error">' . esc_html__( 'As senhas não conferem.', self::TEXT_DOMAIN ) . '</p>';
            }
        }
        if ( isset( $_POST['bolaox_avatar_nonce'] ) && wp_verify_nonce( $_POST['bolaox_avatar_nonce'], 'bolaox_avatar' ) ) {
            if ( isset( $_POST['bolaox_delete_avatar'] ) && $avatar_id ) {
                wp_delete_attachment( $avatar_id, true );
                delete_user_meta( $user->ID, 'bolaox_avatar_id' );
                $avatar_id = 0;
                $msg = '<p>' . esc_html__( 'Avatar removido.', self::TEXT_DOMAIN ) . '</p>';
            } elseif ( ! empty( $_FILES['bolaox_avatar']['name'] ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $aid = media_handle_upload( 'bolaox_avatar', 0 );
                if ( ! is_wp_error( $aid ) ) {
                    if ( $avatar_id ) {
                        wp_delete_attachment( $avatar_id, true );
                    }
                    update_user_meta( $user->ID, 'bolaox_avatar_id', $aid );
                    $avatar_id = $aid;
                    $msg = '<p>' . esc_html__( 'Avatar atualizado.', self::TEXT_DOMAIN ) . '</p>';
                } else {
                    $msg = '<p class="bolaox-error">' . esc_html__( 'Falha ao enviar a imagem.', self::TEXT_DOMAIN ) . '</p>';
                }
            }
        }
        $html  = $msg . '<form method="post" class="bolaox-profile">';
        $html .= wp_nonce_field( 'bolaox_profile', 'bolaox_profile_nonce', true, false );
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Nome', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_first_name" class="bolaox-input" value="' . esc_attr( $user->first_name ) . '" /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Sobrenome', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_last_name" class="bolaox-input" value="' . esc_attr( $user->last_name ) . '" /></label></p>';
        $html .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Salvar', self::TEXT_DOMAIN ) . '" /></p>';
        $html .= '</form>';

        $html .= '<form method="post" enctype="multipart/form-data" class="bolaox-avatar-form">';
        $html .= wp_nonce_field( 'bolaox_avatar', 'bolaox_avatar_nonce', true, false );
        $html .= '<div class="bolaox-avatar-preview">';
        if ( $avatar_id ) {
            $html .= wp_get_attachment_image( $avatar_id, array( 96, 96 ), false, array( 'class' => 'bolaox-avatar-img' ) );
        } else {
            $html .= '<span class="dashicons dashicons-admin-users bolaox-avatar-img"></span>';
        }
        $html .= '</div>';
        $html .= '<p class="bolaox-field"><input type="file" name="bolaox_avatar" accept="image/*" /></p>';
        if ( $avatar_id ) {
            $html .= '<p class="bolaox-field"><button type="submit" name="bolaox_delete_avatar" value="1" class="bolaox-delete-avatar">' . esc_html__( 'Excluir avatar', self::TEXT_DOMAIN ) . '</button></p>';
        }
        $html .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Salvar avatar', self::TEXT_DOMAIN ) . '" /></p>';
        $html .= '</form>';

        $html .= '<form method="post" class="bolaox-pass-form">';
        $html .= wp_nonce_field( 'bolaox_pass', 'bolaox_pass_nonce', true, false );
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Nova senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_pass1" class="bolaox-input" required /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Confirme a senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_pass2" class="bolaox-input" required /></label></p>';
        $html .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Alterar senha', self::TEXT_DOMAIN ) . '" /></p>';
        $html .= '</form>';
        return $this->wrap_app( $html );
    }

    public function render_login_shortcode( $atts = array() ) {
        if ( is_user_logged_in() ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Você já está logado.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $atts        = shortcode_atts( array( 'redirect_to' => '' ), $atts );
        $default     = add_query_arg( 'bx_sec', 'form', $this->get_form_page_url() );
        $redirect_to = $atts['redirect_to'] ? esc_url_raw( $atts['redirect_to'] ) : ( isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( $_REQUEST['redirect_to'] ) : esc_url_raw( $default ) );
        $pref_phone  = isset( $_REQUEST['phone'] ) ? $this->sanitize_phone( $_REQUEST['phone'] ) : '';
        $open_reg    = isset( $_REQUEST['register'] ) ? true : false;
        $msg         = '';
        $cookie_js   = '';
        if ( isset( $_POST['bolaox_login_nonce'] ) && wp_verify_nonce( $_POST['bolaox_login_nonce'], 'bolaox_login' ) ) {
            $phone = $this->sanitize_phone( $_POST['bolaox_phone'] );
            $pass  = isset( $_POST['bolaox_password'] ) ? $_POST['bolaox_password'] : '';
            $users = get_users( array( 'meta_key' => 'bolaox_phone', 'meta_value' => $phone, 'number' => 1 ) );
            if ( $users ) {
                $user  = $users[0];
                if ( wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
                    wp_set_current_user( $user->ID );
                    $cookie_js = $this->create_session( $user->ID );
                    $msg = '<p class="bolaox-login-success" data-redirect="' . esc_attr( $redirect_to ) . '">' . esc_html__( 'Login realizado com sucesso.', self::TEXT_DOMAIN ) . '</p>';
                } else {
                    $msg = '<p class="bolaox-error">' . esc_html__( 'Senha incorreta.', self::TEXT_DOMAIN ) . '</p>';
                }
            } else {
                $url = add_query_arg( array( 'register' => 1, 'phone' => rawurlencode( $phone ), 'redirect_to' => rawurlencode( $redirect_to ) ), home_url( '/login-cadastro' ) );
                if ( ! headers_sent() ) {
                    wp_safe_redirect( $url );
                    exit;
                }
                return '<script>window.location.href="' . esc_url( $url ) . '";</script>';
            }
        } elseif ( isset( $_POST['bolaox_register_nonce'] ) && wp_verify_nonce( $_POST['bolaox_register_nonce'], 'bolaox_register' ) ) {
            $phone   = $this->sanitize_phone( $_POST['bolaox_phone'] );
            $pass    = isset( $_POST['bolaox_password'] ) ? $_POST['bolaox_password'] : '';
            $pass2   = isset( $_POST['bolaox_password2'] ) ? $_POST['bolaox_password2'] : '';
            $display = sanitize_text_field( $_POST['bolaox_display'] );
            if ( empty( $phone ) || empty( $pass ) || empty( $display ) ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'Preencha todos os campos.', self::TEXT_DOMAIN ) . '</p>';
            } elseif ( ! preg_match( '/^\d{10,11}$/', $phone ) ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'Telefone inválido.', self::TEXT_DOMAIN ) . '</p>';
            } elseif ( strlen( $pass ) < 6 ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'A senha precisa ter ao menos 6 caracteres.', self::TEXT_DOMAIN ) . '</p>';
            } elseif ( $pass !== $pass2 ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'As senhas não conferem.', self::TEXT_DOMAIN ) . '</p>';
            } elseif ( get_users( array( 'meta_key' => 'bolaox_phone', 'meta_value' => $phone, 'number' => 1 ) ) ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'Telefone já cadastrado.', self::TEXT_DOMAIN ) . '</p>';
            } else {
                $username = 'u' . $phone;
                $email    = $phone . '@example.com';
                $user_id  = wp_insert_user( array( 'user_login' => $username, 'user_pass' => $pass, 'user_email' => $email, 'display_name' => $display ) );
                if ( is_wp_error( $user_id ) ) {
                    $msg = '<p class="bolaox-error">' . esc_html__( 'Erro ao criar usuário.', self::TEXT_DOMAIN ) . '</p>';
                } else {
                    update_user_meta( $user_id, 'bolaox_phone', $phone );
                    wp_set_current_user( $user_id );
                    $cookie_js = $this->create_session( $user_id );
                    $msg = '<p class="bolaox-register-success" data-redirect="' . esc_attr( $redirect_to ) . '">' . esc_html__( 'Cadastro realizado com sucesso.', self::TEXT_DOMAIN ) . '</p>';
                }
            }
        }

        $login_form  = '<form method="post" class="bolaox-login-form">';
        $login_form .= wp_nonce_field( 'bolaox_login', 'bolaox_login_nonce', true, false );
        $login_form .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect_to ) . '" />';
        $login_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Telefone', self::TEXT_DOMAIN ) . '<br /><input type="tel" name="bolaox_phone" class="bolaox-phone" value="' . esc_attr( $pref_phone ) . '" required /></label></p>';
        $login_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_password" required /></label></p>';
        $login_form .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Entrar', self::TEXT_DOMAIN ) . '" /></p>';
        $login_form .= '</form>';

        $register_form  = '<form method="post" class="bolaox-register-form">';
        $register_form .= wp_nonce_field( 'bolaox_register', 'bolaox_register_nonce', true, false );
        $register_form .= '<input type="hidden" name="redirect_to" value="' . esc_attr( $redirect_to ) . '" />';
        $register_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Como quer ser chamado?', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_display" class="bolaox-input" required /></label></p>';
        $register_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Telefone', self::TEXT_DOMAIN ) . '<br /><input type="tel" name="bolaox_phone" class="bolaox-phone" value="' . esc_attr( $pref_phone ) . '" required /></label></p>';
        $register_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_password" required /></label></p>';
        $register_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Confirme a senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_password2" required /></label></p>';
        $register_form .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Criar conta', self::TEXT_DOMAIN ) . '" /></p>';
        $register_form .= '</form>';

        $redirect = '';
        if ( strpos( $msg, 'bolaox-login-success' ) !== false || strpos( $msg, 'bolaox-register-success' ) !== false ) {
            if ( ! headers_sent() ) {
                wp_safe_redirect( $redirect_to );
                exit;
            }
            $redirect = $cookie_js . '<script>window.location.href="' . esc_url( $redirect_to ) . '";</script>';
        }

        $tab_attr = $open_reg ? 'register' : 'login';
        $html  = '<div class="bolaox-login-tabs" data-tab="' . $tab_attr . '">' . $msg;
        $login_li_class = $open_reg ? '' : ' class="active"';
        $reg_li_class   = $open_reg ? ' class="active"' : '';
        $login_div = $open_reg ? '' : ' active';
        $reg_div   = $open_reg ? ' active' : '';
        $html .= '<ul class="bolaox-tabs"><li' . $login_li_class . '>' . esc_html__( 'Acessar', self::TEXT_DOMAIN ) . '</li><li' . $reg_li_class . '>' . esc_html__( 'Cadastrar', self::TEXT_DOMAIN ) . '</li></ul>';
        $html .= '<div class="bolaox-tab-content' . $login_div . '">' . $login_form . '</div>';
        $html .= '<div class="bolaox-tab-content' . $reg_div . '">' . $register_form . '</div>';
        $html .= '</div>' . $redirect;
        return $this->wrap_app( $html );
    }

    public function render_dashboard_shortcode() {
        $this->maybe_restore_session();
        if ( ! is_user_logged_in() ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Você precisa entrar para acessar o painel.', self::TEXT_DOMAIN ) . '</p>' );
        }
        if ( ! isset( $_GET['bx_sec'] ) ) {
            return do_shortcode( '[bolao_x_form]' );
        }
        $avatar_id = get_user_meta( get_current_user_id(), 'bolaox_avatar_id', true );
        $sections = array(
            'profile'  => array( 'dashicons-admin-users', __( 'Perfil', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_profile]' ) ),
            'form'     => array( 'dashicons-edit', __( 'Nova Aposta', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_form]' ) ),
            'mybets'   => array( 'dashicons-chart-bar', __( 'Minhas Apostas', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_my_bets]' ) ),
            'results'  => array( 'dashicons-awards', __( 'Resultados', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_results]' ) ),
            'stats'    => array( 'dashicons-analytics', __( 'Estatísticas', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_stats]' ) ),
            'history'  => array( 'dashicons-backup', __( 'Histórico', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_history]' ) ),
            'logout'   => array( 'dashicons-migrate', __( 'Sair', self::TEXT_DOMAIN ), '<p><a class="button bolaox-submit" href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Sair', self::TEXT_DOMAIN ) . '</a></p>' ),
        );
        $logo = '<img src="https://www.bolaox.com.br/wp-content/uploads/2025/07/logo-footer.png" class="bolaox-mobile-logo" alt="Bolao X" />';
        $html = $logo . '<div class="bolaox-dashboard">';
        foreach ( $sections as $key => $data ) {
            list( $icon, $label ) = $data;
            $icon_html = '<span class="dashicons ' . esc_attr( $icon ) . '"></span>';
            if ( 'profile' === $key && $avatar_id ) {
                $icon_html = wp_get_attachment_image( $avatar_id, array( 32, 32 ), false, array( 'class' => 'bolaox-avatar-icon' ) );
            }
            $html .= '<div class="bolaox-card" data-target="bx-' . esc_attr( $key ) . '">' . $icon_html . '<span>' . esc_html( $label ) . '</span></div>';
        }
        $html .= '</div><div class="bolaox-dashboard-sections">';
        foreach ( $sections as $key => $data ) {
            $html .= '<div id="bx-' . esc_attr( $key ) . '" class="bolaox-section" style="display:none">' . $data[2] . '</div>';
        }
        $html .= '</div>';
        return $this->wrap_app( $html );
    }



    public function register_dashboard_widget() {
        wp_add_dashboard_widget( 'bolaox_overview', 'Resumo Bolao X', array( $this, 'dashboard_widget' ) );
    }

    public function dashboard_widget() {
        $bets = wp_count_posts( 'bolaox_aposta' );
        $count = $bets && isset( $bets->publish ) ? intval( $bets->publish ) : 0;
        $result = get_option( 'bolaox_result', '' );
        $winners = array( '10' => 0, '9' => 0, '8' => 0 );
        if ( $result ) {
            $res_numbers = array_map( 'trim', explode( ',', $result ) );
            $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
            foreach ( $posts as $p ) {
                $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
                $nums    = array_map( 'trim', explode( ',', $numbers ) );
                $hits    = count( array_intersect( $res_numbers, $nums ) );
                if ( $hits >= 8 ) {
                    $key = strval( $hits );
                    if ( isset( $winners[ $key ] ) ) {
                        $winners[ $key ]++;
                    }
                }
            }
        }
        echo '<p>' . sprintf( esc_html__( 'Total de apostas: %d', self::TEXT_DOMAIN ), $count ) . '</p>';
        if ( $result ) {
            echo '<p>' . esc_html__( 'Acertos:', self::TEXT_DOMAIN ) . '</p><ul>';
            echo '<li>' . sprintf( esc_html__( '10 dezenas: %d', self::TEXT_DOMAIN ), $winners['10'] ) . '</li>';
            echo '<li>' . sprintf( esc_html__( '9 dezenas: %d', self::TEXT_DOMAIN ), $winners['9'] ) . '</li>';
            echo '<li>' . sprintf( esc_html__( '8 dezenas: %d', self::TEXT_DOMAIN ), $winners['8'] ) . '</li>';
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__( 'Nenhum resultado cadastrado ainda.', self::TEXT_DOMAIN ) . '</p>';
        }
    }

    public function filter_gettext( $translated, $text, $domain ) {
        if ( 'default' !== $domain || ! function_exists( 'get_current_screen' ) ) {
            return $translated;
        }
        $screen = get_current_screen();
        if ( ! $screen ) {
            return $translated;
        }
        if ( 'bolaox_aposta' === $screen->post_type ) {
            if ( in_array( $text, array( 'Add New Post', 'Adicionar post', 'Adicionar novo' ), true ) ) {
                return __( 'Aposta Manual', self::TEXT_DOMAIN );
            }
            if ( 'Publish' === $text || 'Publicar' === $text ) {
                return __( 'Criar Aposta', self::TEXT_DOMAIN );
            }
        } elseif ( 'bolaox_result' === $screen->post_type ) {
            if ( in_array( $text, array( 'Add New Post', 'Adicionar post', 'Adicionar novo' ), true ) ) {
                return __( 'Novo Resultado', self::TEXT_DOMAIN );
            }
            if ( 'Publish' === $text || 'Publicar' === $text ) {
                return __( 'Publicar Resultado', self::TEXT_DOMAIN );
            }
            if ( in_array( $text, array( 'Edit Post', 'Editar post' ), true ) ) {
                return __( 'Editar Resultado', self::TEXT_DOMAIN );
            }
        }
        return $translated;
    }

    public function title_placeholder( $title, $post ) {
        if ( 'bolaox_aposta' === $post->post_type ) {
            return __( 'Nome do Apostador', self::TEXT_DOMAIN );
        } elseif ( 'bolaox_result' === $post->post_type ) {
            return __( 'Qual resultado deseja publicar?', self::TEXT_DOMAIN );
        } elseif ( 'bolaox_concurso' === $post->post_type ) {
            return __( 'Insira o Nome do Concurso', self::TEXT_DOMAIN );
        }
        return $title;
    }


    public function register_routes() {
        register_rest_route(
            'bolao-x/v1',
            '/mp',
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'handle_mp_webhook' ),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'bolao-x/v1',
            '/validate',
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'rest_validate_credentials' ),
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
            )
        );
        register_rest_route(
            'bolao-x/v1',
            '/create-payment',
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'rest_create_payment' ),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'bolao-x/v1',
            '/mybets',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'rest_my_bets' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public function handle_mp_webhook( WP_REST_Request $request ) {
        $token = $request->get_param( 'token' );
        if ( $token !== self::MP_WEBHOOK_TOKEN ) {
            return new WP_Error( 'forbidden', 'Token inválido', array( 'status' => 403 ) );
        }
        $payment_id = intval( $request->get_param( 'data_id' ) );
        if ( ! $payment_id ) {
            return new WP_Error( 'bad_request', 'ID ausente', array( 'status' => 400 ) );
        }
        $url  = self::MP_API_URL . '/v1/payments/' . $payment_id;
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->get_mp_access_token(),
            ),
            'timeout' => 20,
        );
        $res = wp_remote_get( $url, $args );
        if ( is_wp_error( $res ) ) {
            $this->log_mp_error( 'Erro consulta pagamento: ' . $res->get_error_message() );
            return new WP_Error( 'request_failed', 'Erro na consulta', array( 'status' => 500 ) );
        }
        $body = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( isset( $body['status'] ) && 'approved' === $body['status'] ) {
            $post_id = intval( $body['external_reference'] );
            if ( $post_id ) {
                update_post_meta( $post_id, '_bolaox_payment', 'paid' );
                return array( 'success' => true );
            }
        }
        $this->log_mp_error( 'Pagamento não confirmado: ' . wp_remote_retrieve_body( $res ) );
        return new WP_Error( 'invalid', 'Pagamento não confirmado', array( 'status' => 400 ) );
    }

    public function rest_validate_credentials( WP_REST_Request $request ) {
        $mode = $request->get_param( 'mode' );
        if ( ! in_array( $mode, array( 'prod', 'test' ), true ) ) {
            return new WP_Error( 'invalid', 'Modo inválido', array( 'status' => 400 ) );
        }
        if ( $this->validate_mp_credentials( $mode ) ) {
            return array( 'success' => true );
        }
        return new WP_Error( 'invalid', 'Credenciais inválidas', array( 'status' => 400 ) );
    }

    public function rest_create_payment( WP_REST_Request $request ) {
        $qty = max( 1, intval( $request->get_param( 'qty' ) ) );
        $pref = sanitize_text_field( $request->get_param( 'ref' ) );
        $data = $this->create_mp_pix_payment( $pref, $qty );
        if ( ! $data ) {
            return new WP_Error( 'failed', 'Erro ao criar pagamento', array( 'status' => 500 ) );
        }
        return $data;
    }

    public function auto_create_result( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
            return;
        }

        $existing = get_posts( array(
            'post_type'   => 'bolaox_result',
            'post_status' => 'any',
            'numberposts' => 1,
            'meta_query'  => array(
                array( 'key' => '_bolaox_concurso', 'value' => $post_id ),
            ),
        ) );

        $title = sprintf( __( 'Resultado do Concurso %s', self::TEXT_DOMAIN ), $post->post_title );

        if ( $existing ) {
            $eid = $existing[0]->ID;
            if ( $existing[0]->post_title !== $title ) {
                wp_update_post( array( 'ID' => $eid, 'post_title' => $title ) );
            }
            return;
        }

        $res_id = wp_insert_post( array(
            'post_type'   => 'bolaox_result',
            'post_title'  => $title,
            'post_status' => 'draft',
        ) );

        if ( $res_id ) {
            update_post_meta( $res_id, '_bolaox_concurso', $post_id );
        }
    }

    private function on_contest_switch( $old_id, $new_id ) {
        if ( $old_id ) {
            $rotatives = get_posts( array(
                'post_type'   => 'bolaox_aposta',
                'numberposts' => -1,
                'fields'      => 'ids',
                'meta_query'  => array(
                    array( 'key' => '_bolaox_concurso', 'value' => $old_id ),
                    array(
                        'relation' => 'OR',
                        array( 'key' => '_bolaox_fixed', 'compare' => 'NOT EXISTS' ),
                        array( 'key' => '_bolaox_fixed', 'value' => '0' ),
                    ),
                ),
            ) );
            foreach ( $rotatives as $rid ) {
                wp_delete_post( $rid, true );
            }
        }
        if ( $new_id && $old_id ) {
            $fixed = get_posts( array(
                'post_type'   => 'bolaox_aposta',
                'numberposts' => -1,
                'meta_query'  => array(
                    array( 'key' => '_bolaox_concurso', 'value' => $old_id ),
                    array( 'key' => '_bolaox_fixed', 'value' => '1' ),
                ),
            ) );
            foreach ( $fixed as $fb ) {
                $new_post = wp_insert_post( array(
                    'post_type'   => 'bolaox_aposta',
                    'post_title'  => $fb->post_title,
                    'post_status' => 'publish',
                    'post_author' => $fb->post_author,
                ) );
                if ( $new_post ) {
                    update_post_meta( $new_post, '_bolaox_numbers', get_post_meta( $fb->ID, '_bolaox_numbers', true ) );
                    update_post_meta( $new_post, '_bolaox_payment', get_post_meta( $fb->ID, '_bolaox_payment', true ) );
                    update_post_meta( $new_post, '_bolaox_fixed', '1' );
                    update_post_meta( $new_post, '_bolaox_concurso', $new_id );
                }
            }
        }
    }

    public function aposta_columns( $cols ) {
        $cols['bolaox_fixed'] = __( 'Fixo', self::TEXT_DOMAIN );
        return $cols;
    }

    public function aposta_column_content( $col, $post_id ) {
        if ( 'bolaox_fixed' === $col ) {
            $val = get_post_meta( $post_id, '_bolaox_fixed', true );
            echo $val ? '&#10004;' : '&#8211;';
        }
    }

    public function aposta_filter() {
        global $typenow;
        if ( 'bolaox_aposta' === $typenow ) {
            $val = $_GET['bolaox_fixed'] ?? '';
            echo '<select name="bolaox_fixed"><option value="">' . esc_html__( 'Tipo', self::TEXT_DOMAIN ) . '</option>';
            echo '<option value="1"' . selected( $val, '1', false ) . '>' . esc_html__( 'Fixas', self::TEXT_DOMAIN ) . '</option>';
            echo '<option value="0"' . selected( $val, '0', false ) . '>' . esc_html__( 'Rotativas', self::TEXT_DOMAIN ) . '</option>';
            echo '</select>';
        }
    }

    public function aposta_filter_query( $query ) {
        global $pagenow;
        if ( is_admin() && 'edit.php' === $pagenow && 'bolaox_aposta' === ( $query->get( 'post_type' ) ?? '' ) ) {
            $val = $_GET['bolaox_fixed'] ?? '';
            if ( $val !== '' ) {
                $query->set( 'meta_query', array(
                    array( 'key' => '_bolaox_fixed', 'value' => $val )
                ) );
            }
        }
    }

    public function result_filter() {
        global $typenow;
        if ( 'bolaox_result' === $typenow ) {
            $val = $_GET['bolaox_concurso'] ?? '';
            $contests = get_posts( array( 'post_type' => 'bolaox_concurso', 'numberposts' => -1 ) );
            echo '<select name="bolaox_concurso"><option value="">' . esc_html__( 'Concurso', self::TEXT_DOMAIN ) . '</option>';
            foreach ( $contests as $c ) {
                echo '<option value="' . $c->ID . '"' . selected( $val, $c->ID, false ) . '>' . esc_html( $c->post_title ) . '</option>';
            }
            echo '</select>';
        }
    }

    public function result_filter_query( $query ) {
        global $pagenow;
        if ( is_admin() && 'edit.php' === $pagenow && 'bolaox_result' === ( $query->get( 'post_type' ) ?? '' ) ) {
            $val = $_GET['bolaox_concurso'] ?? '';
            if ( $val ) {
                $query->set( 'meta_query', array(
                    array( 'key' => '_bolaox_concurso', 'value' => intval( $val ) )
                ) );
            }
        }
    }

    public function result_columns( $cols ) {
        $cols['bolaox_concurso'] = __( 'Concurso', self::TEXT_DOMAIN );
        return $cols;
    }

    public function result_column_content( $col, $post_id ) {
        if ( 'bolaox_concurso' === $col ) {
            $cid = get_post_meta( $post_id, '_bolaox_concurso', true );
            if ( $cid ) {
                $title = get_the_title( $cid );
                echo esc_html( $title );
            } else {
                echo '&#8211;';
            }
        }
    }

    public function rest_my_bets( WP_REST_Request $req ) {
        $this->maybe_restore_session();
        nocache_headers();
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'forbidden', 'auth', array( 'status' => 401 ) );
        }
        $contest = intval( $req->get_param( 'contest' ) );
        return array(
            'html' => $this->generate_my_bets_table( get_current_user_id(), $contest ),
        );
    }
    public static function activate() {
        self::instance();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

}

register_activation_hook( __FILE__, array( 'BOLAOX_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BOLAOX_Plugin', 'deactivate' ) );

if ( extension_loaded( 'gd' ) ) {
    BOLAOX_Plugin::instance();
} else {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>' .
            esc_html__( 'O plugin Bolao X requer a extensão PHP GD para gerar QR Codes.', 'bolao-x' ) .
            '</p></div>';
    } );
}

