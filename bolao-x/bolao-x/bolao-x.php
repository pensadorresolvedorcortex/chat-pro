<?php
/*
Plugin Name: Bolao X
Description: Sistema de gerenciamento de bolão com conferência automática, histórico de resultados, exportação em PDF e Excel e pagamento via Mercado Pago.
Version: 2.8.3
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
    const VERSION = '2.8.3';
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
        add_shortcode( 'bolao_x_login', array( $this, 'render_login_shortcode' ) );
        add_shortcode( 'bolao_x_dashboard', array( $this, 'render_dashboard_shortcode' ) );
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

    private function verify_mp_payment( $payment_id ) {
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
            return true;
        }
        if ( isset( $body['status'] ) && in_array( $body['status'], array( 'in_process', 'pending' ), true ) ) {
            $this->log_error( 'Pagamento pendente: ' . wp_remote_retrieve_body( $res ) );
            return false;
        }
        $this->log_error( 'Pagamento não aprovado: ' . wp_remote_retrieve_body( $res ) );
        return false;
    }

    private function create_mp_pix_payment( $ref ) {
        $token = $this->get_mp_access_token();
        if ( ! $token ) {
            return array();
        }
        $url   = self::MP_API_URL . '/v1/payments';
        $price = floatval( get_option( 'bolaox_price', 10 ) );
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
            'transaction_amount' => $price,
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
        wp_enqueue_script(
            'bolaox-js',
            plugin_dir_url( __FILE__ ) . 'assets/js/bolao-x.js',
            array(),
            self::VERSION,
            true
        );
        wp_localize_script( 'bolaox-js', 'bolaoxData', array( 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
    }

    public function register_post_type() {
        register_post_type( 'bolaox_aposta', array(
            'labels' => array(
                'name' => __( 'Apostas', self::TEXT_DOMAIN ),
                'singular_name' => __( 'Aposta', self::TEXT_DOMAIN ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title' ),
        ) );

        register_post_type( 'bolaox_result', array(
            'labels' => array(
                'name' => __( 'Resultados', self::TEXT_DOMAIN ),
                'singular_name' => __( 'Resultado', self::TEXT_DOMAIN ),
            ),
            'public' => false,
            'show_ui' => true,
            'supports' => array( 'title' ),
        ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'bolaox_numbers', __( 'Dezenas', self::TEXT_DOMAIN ), array( $this, 'numbers_meta_box' ), 'bolaox_aposta' );
        add_meta_box( 'bolaox_payment', __( 'Status do Pagamento', self::TEXT_DOMAIN ), array( $this, 'payment_meta_box' ), 'bolaox_aposta', 'side' );
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
        echo '<select name="bolaox_payment">
            <option value="pending"' . selected( $status, 'pending', false ) . '>' . esc_html__( 'Pendente', self::TEXT_DOMAIN ) . '</option>
            <option value="paid"' . selected( $status, 'paid', false ) . '>' . esc_html__( 'Pago', self::TEXT_DOMAIN ) . '</option>
        </select>';
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
        }
        if ( isset( $_POST['bolaox_payment'] ) ) {
            $status = in_array( $_POST['bolaox_payment'], array( 'pending', 'paid' ), true ) ? $_POST['bolaox_payment'] : 'pending';
            update_post_meta( $post_id, '_bolaox_payment', $status );
        }
    }

    public function admin_menu() {
        add_menu_page( 'Bolao X', 'Bolao X', 'manage_options', 'bolaox', array( $this, 'results_page' ) );
        add_submenu_page( 'bolaox', __( 'Configurações', self::TEXT_DOMAIN ), __( 'Configurações', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-settings', array( $this, 'settings_page' ) );
        add_submenu_page( 'bolaox', __( 'Importar CSV', self::TEXT_DOMAIN ), __( 'Importar CSV', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-import', array( $this, 'import_page' ) );
        add_submenu_page( 'bolaox', __( 'Histórico', self::TEXT_DOMAIN ), __( 'Histórico', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-history', array( $this, 'history_page' ) );
        add_submenu_page( 'bolaox', __( 'Estatísticas', self::TEXT_DOMAIN ), __( 'Estatísticas', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-stats', array( $this, 'stats_page' ) );
        add_submenu_page( 'bolaox', __( 'Logs', self::TEXT_DOMAIN ), __( 'Logs', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-logs', array( $this, 'logs_page' ) );
        add_submenu_page( 'bolaox', __( 'Logs Gerais', self::TEXT_DOMAIN ), __( 'Logs Gerais', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-general-logs', array( $this, 'general_logs_page' ) );
    }

    public function results_page() {
        if ( isset( $_POST['bolaox_result_nonce'] ) && wp_verify_nonce( $_POST['bolaox_result_nonce'], 'bolaox_result' ) ) {
            $valid = $this->validate_numbers( sanitize_text_field( $_POST['bolaox_result'] ) );
            if ( false === $valid ) {
                $this->notice = __( 'Resultado semanal inválido. Informe 10 números de 00 a 99 separados por vírgula.', self::TEXT_DOMAIN );
            } else {
                update_option( 'bolaox_result', $valid );
                $post_id = wp_insert_post( array(
                    'post_type'   => 'bolaox_result',
                    'post_title'  => current_time( 'Y-m-d' ),
                    'post_status' => 'publish',
                ) );
                if ( $post_id ) {
                    update_post_meta( $post_id, '_bolaox_result', $valid );
                }
                $this->send_results_email( $valid );
                $this->update_lowest_info( $valid );
            }
        }
        $result = get_option( 'bolaox_result', '' );
        if ( isset( $_GET['export'] ) && $result ) {
            if ( 'pdf' === $_GET['export'] ) {
                $this->export_pdf( $result );
            } elseif ( 'xls' === $_GET['export'] ) {
                $this->export_xls( $result );
            } elseif ( 'json' === $_GET['export'] ) {
                $this->export_json( $result );
            } else {
                $this->export_csv( $result );
            }
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'Resultado Semanal', self::TEXT_DOMAIN ) . '</h1>';
        echo '<form method="post">';
        wp_nonce_field( 'bolaox_result', 'bolaox_result_nonce' );
        echo '<input type="text" name="bolaox_result" value="' . esc_attr( $result ) . '" placeholder="' . esc_attr__( 'Ex: 05,19,28,36,44,59,66,71,86,96', self::TEXT_DOMAIN ) . '" style="width:100%" />';
        submit_button( __( 'Salvar', self::TEXT_DOMAIN ) );
        echo '</form>';
        if ( $result ) {
            echo '<h2>' . esc_html__( 'Conferência', self::TEXT_DOMAIN ) . '</h2>';
            echo $this->generate_report( $result );
            $base = admin_url( 'admin.php?page=bolaox' );
            echo '<p>';
            echo '<a href="' . esc_url( $base . '&export=json' ) . '" class="button">' . esc_html__( 'Exportar JSON', self::TEXT_DOMAIN ) . '</a> ';
            echo '<a href="' . esc_url( $base . '&export=csv' ) . '" class="button">' . esc_html__( 'Exportar CSV', self::TEXT_DOMAIN ) . '</a> ';
            echo '<a href="' . esc_url( $base . '&export=xls' ) . '" class="button">' . esc_html__( 'Exportar Excel', self::TEXT_DOMAIN ) . '</a> ';
            echo '<a href="' . esc_url( $base . '&export=pdf' ) . '" class="button">' . esc_html__( 'Exportar PDF', self::TEXT_DOMAIN ) . '</a>';
            echo '</p>';
            echo '</p>';
        }
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
        echo '<table class="form-table"><tbody>';
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
                    ) );
                    if ( $post_id ) {
                        update_post_meta( $post_id, '_bolaox_numbers', $numbers );
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

    private function generate_report( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhuma aposta cadastrada.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $rows = array();
        $info = get_option( 'bolaox_lowest_info', array( 'id' => 0, 'hits' => 0, 'pool' => 0 ) );
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums    = array_map( 'trim', explode( ',', $numbers ) );
            $hits    = array_intersect( $res_numbers, $nums );
            $hit_count = count( $hits );
            $percentage = $hit_count * 10;
            $progress  = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percentage . '" data-progress="' . $percentage . '%" style="--progress:' . $percentage . '%">';
            $progress .= '<span></span></div>';
            $duplicates = array_unique( array_diff_assoc( $nums, array_unique( $nums ) ) );
            $display_nums = array();
            foreach ( $nums as $n ) {
                $n = trim( $n );
                $classes = array( 'bolaox-number' );
                if ( in_array( $n, $duplicates ) ) {
                    $classes[] = 'dup';
                }
                if ( in_array( $n, $res_numbers ) ) {
                    $classes[] = 'hit';
                }
                $display_nums[] = '<span class="' . implode( ' ', $classes ) . '">' . esc_html( $n ) . '</span>';
            }
            $class = '';
            if ( $hit_count >= 8 ) {
                $class = ' class="bolaox-hit-' . $hit_count . '"';
            }
            $rows[] = array(
                'id'      => $p->ID,
                'hits'    => $hit_count,
                'class'   => $class,
                'name'    => esc_html( $p->post_title ),
                'progress' => $progress,
                'numbers' => implode( ', ', $display_nums ),
            );
        }

        foreach ( $rows as $k => $row ) {
            if ( $info['id'] && (int) $info['id'] === (int) $row['id'] ) {
                $rows[ $k ]['class'] .= ' bolaox-lowest';
            }
        }

        usort( $rows, function ( $a, $b ) {
            return $b['hits'] <=> $a['hits'];
        } );

        $lowest = null;
        foreach ( $rows as $k => $row ) {
            if ( strpos( $row['class'], 'bolaox-lowest' ) !== false ) {
                $lowest = $row;
                unset( $rows[ $k ] );
                break;
            }
        }
        if ( $lowest ) {
            $rows[] = $lowest;
        }

        $out = '<div class="bolaox-table-wrapper"><table class="widefat bolaox-table"><thead><tr><th>#</th><th>' . esc_html__( 'Apostador', self::TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Acertos', self::TEXT_DOMAIN ) . '</th><th class="col-percent">%</th><th>' . esc_html__( 'Dezenas', self::TEXT_DOMAIN ) . '</th></tr></thead><tbody>';
        $idx = 1;
        foreach ( $rows as $row ) {
            $num = str_pad( strval( $idx++ ), 2, '0', STR_PAD_LEFT );
            $out .= '<tr' . $row['class'] . '><td class="col-index">' . $num . '</td><td class="col-name">' . $row['name'] . '</td><td>' . $row['hits'] . '</td><td class="col-percent">' . $row['progress'] . '</td><td class="bolaox-numlist">' . $row['numbers'] . '</td></tr>';
        }
        $out .= '</tbody></table></div>';
        if ( $info['id'] ) {
            $winner_name = get_the_title( $info['id'] );
            $msg = sprintf( __( 'Menos Pontos: %s com %d ponto(s)', self::TEXT_DOMAIN ), esc_html( $winner_name ), intval( $info['hits'] ) );
            $out .= '<div class="bolaox-low-card">' . $msg . '</div>';
        } elseif ( $info['pool'] > 0 ) {
            $msg = sprintf( __( 'Premiação Menos Pontos acumulada (%d)', self::TEXT_DOMAIN ), intval( $info['pool'] ) );
            $out .= '<div class="bolaox-low-card">' . $msg . '</div>';
        }
        return $this->wrap_app( $out );
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

    private function export_pdf( $result ) {
        require_once __DIR__ . '/lib/fpdf.php';
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts       = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return;
        }
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 14 );
        $pdf->Cell( 0, 10, __( 'Relatorio de Apostas', self::TEXT_DOMAIN ), 0, 1, 'C' );
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
        $out = '<div class="bolaox-board">';
        for ( $i = 0; $i < 100; $i++ ) {
            $num   = str_pad( strval( $i ), 2, '0', STR_PAD_LEFT );
            $class = 'bolaox-number';
            if ( in_array( $num, $res_numbers, true ) ) {
                $class .= ' drawn';
            }
            $out .= '<span class="' . $class . '">' . esc_html( $num ) . '</span>';
        }
        $out .= '</div>';
        return $out;
    }

    private function render_repeated_numbers() {
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
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
        $out .= '<div class="bolaox-board">';
        foreach ( $dups as $n ) {
            $out .= '<span class="bolaox-number">' . esc_html( $n ) . '</span>';
        }
        $out .= '</div>';
        return $out;
    }

    private function wrap_app( $html ) {
        return '<div class="bolaox-app">' . $html . '</div>';
    }

    public function render_form_shortcode() {
        $cutoffs = get_option( 'bolaox_cutoffs', array() );
        $countdown = '';
        $price   = floatval( get_option( 'bolaox_price', 10 ) );
        $raw_key = get_option( 'bolaox_pix_key', '' );
        $pix_key = is_string( $raw_key ) ? trim( $raw_key ) : '';
        $msg     = '';
        if ( $cutoffs ) {
            $now  = current_time( 'timestamp' );
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
        if ( ! isset( $_POST['bolaox_submit'] ) ) {
            $pix        = $this->create_mp_pix_payment( 'tmp-' . wp_generate_password( 8, false, false ) );
            $payment_id = isset( $pix['id'] ) ? $pix['id'] : '';
        }

        if ( isset( $_POST['bolaox_submit'] ) && isset( $_POST['bolaox_nonce'] ) && wp_verify_nonce( $_POST['bolaox_nonce'], 'bolaox_form' ) ) {
            $payment_id = sanitize_text_field( $_POST['bolaox_payment_id'] );
            if ( ! $this->verify_mp_payment( $payment_id ) ) {
                $msg        = '<p class="bolaox-error">' . esc_html__( 'Pagamento via Pix não confirmado.', self::TEXT_DOMAIN ) . '</p>';
                $pix        = $this->create_mp_pix_payment( 'tmp-' . wp_generate_password( 8, false, false ) );
                $payment_id = isset( $pix['id'] ) ? $pix['id'] : '';
            } else {
                $name    = sanitize_text_field( $_POST['bolaox_name'] );
                $numbers = sanitize_text_field( $_POST['bolaox_numbers'] );
                $numbers = $this->validate_numbers( $numbers );
                if ( false === $numbers ) {
                    return $this->wrap_app( '<p>' . esc_html__( 'Formato de dezenas inválido. Use 10 números de 00 a 99 separados por vírgula.', self::TEXT_DOMAIN ) . '</p>' );
                }
                $post_id = wp_insert_post( array(
                    'post_type'   => 'bolaox_aposta',
                    'post_title'  => $name,
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                ) );
                if ( $post_id ) {
                    update_post_meta( $post_id, '_bolaox_numbers', $numbers );
                    update_post_meta( $post_id, '_bolaox_payment', 'paid' );
                    update_post_meta( $post_id, '_bolaox_mp_pref', $payment_id );
                    $msg  = '<h3 class="bolaox-success-title">' . esc_html__( 'Aposta registrada com sucesso!', self::TEXT_DOMAIN ) . '</h3>';
                    $msg .= '<p class="bolaox-success-label">' . esc_html__( 'Sua aposta:', self::TEXT_DOMAIN ) . '</p>';
                    $msg .= '<div class="bolaox-numlist">';
                    foreach ( array_map( 'trim', explode( ',', $numbers ) ) as $n ) {
                        $msg .= '<span class="bolaox-number drawn">' . esc_html( $n ) . '</span>';
                    }
                    $msg .= '</div>';
                    return $this->wrap_app( $msg );
                }
            }
        }

        $html  = '<div class="bolaox-form">';
        if ( $msg ) {
            $html .= $msg;
        }
        $html .= '<div id="bolaox-pix-modal" class="bolaox-modal"><div class="bolaox-modal-content">';
        if ( $pix ) {
            if ( ! empty( $pix['qr_code_base64'] ) ) {
                $urlqr = 'data:image/png;base64,' . $pix['qr_code_base64'];
                $src   = esc_attr( $urlqr );
            } else {
                $urlqr = 'https://chart.googleapis.com/chart?chs=500x500&cht=qr&chl=' . rawurlencode( $pix['qr_code'] );
                $src   = esc_url( $urlqr );
            }
            $html .= '<p><img src="' . $src . '" alt="Pix QR" width="500" height="500" /></p>';
            $html .= '<p>' . esc_html__( 'Copie o Código:', self::TEXT_DOMAIN ) . '<br />';
            $html .= '<input type="text" id="bolaox-pix-code" value="' . esc_attr( $pix['qr_code'] ) . '" readonly class="bolaox-pix-code" />';
            $html .= '<button type="button" class="button bolaox-copy" data-target="#bolaox-pix-code">' . esc_html__( 'Copiar', self::TEXT_DOMAIN ) . '</button></p>';
        } else {
            $html .= '<p class="bolaox-error">' . esc_html__( 'Falha ao gerar QR Code do Pix. Verifique suas credenciais do Mercado Pago.', self::TEXT_DOMAIN ) . '</p>';
        }
        $html .= '<p><span class="button bolaox-modal-close">' . esc_html__( 'Fechar', self::TEXT_DOMAIN ) . '</span></p></div></div>';

        $html .= '<form method="post" class="bolaox-form-inner">';
        if ( $countdown ) {
            $html .= $countdown;
        }
        $html .= wp_nonce_field( 'bolaox_form', 'bolaox_nonce', true, false );
        $html .= '<input type="hidden" name="bolaox_payment_id" value="' . esc_attr( $payment_id ) . '" />';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Como quer ser chamado?', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_name" required /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Escolha 10 dezenas', self::TEXT_DOMAIN ) . '</label>';
        $html .= '<div class="bolaox-numbers">';
        for ( $i = 0; $i < 100; $i++ ) {
            $num = str_pad( (string) $i, 2, '0', STR_PAD_LEFT );
            $html .= '<span class="bolaox-number">' . esc_html( $num ) . '</span>';
        }
        $html .= '<input type="hidden" name="bolaox_numbers" required />';
        $html .= '</div></p>';
        $html .= '<p><a href="#" class="button bolaox-open-modal bolaox-pix-btn" data-target="#bolaox-pix-modal">' . esc_html__( 'PAGAR COM PIX', self::TEXT_DOMAIN ) . '</a></p>';
        $html .= '<p class="bolaox-price">' . sprintf( esc_html__( 'Valor da aposta: R$ %s', self::TEXT_DOMAIN ), number_format( $price, 2, ',', '.' ) ) . '</p>';
        $html .= '<p class="bolaox-field"><input type="submit" name="bolaox_submit" value="' . esc_attr__( 'APOSTE AGORA', self::TEXT_DOMAIN ) . '" class="button bolaox-submit" /></p>';
        $html .= '</form></div>';
        return $this->wrap_app( $html );
    }

    public function render_results_shortcode() {
        $result = get_option( 'bolaox_result', '' );
        if ( ! $result ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $output  = '<div class="bolaox-result">';
        $output .= '<h3>' . esc_html__( 'RESULTADO DA SEMANA', self::TEXT_DOMAIN ) . '</h3>';
        $output .= '<p class="bolaox-res-num">' . esc_html( $result ) . '</p>';
        $output .= $this->render_number_board( $result );
        $output .= $this->render_repeated_numbers();
        $output .= $this->generate_report( $result );
        $output .= '</div>';
        return $this->wrap_app( $output );
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
            $out .= '<li class="bolaox-history-item"><h3 class="bolaox-history-date">' . $date . '</h3><div class="bolaox-numlist">' . $numlist . '</div></li>';
        }
        $out .= '</ul>';
        return $this->wrap_app( $out );
    }

    public function render_my_bets_shortcode() {
        if ( ! is_user_logged_in() ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Faça login para ver suas apostas.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $user_id = get_current_user_id();
        $posts   = get_posts( array(
            'post_type'   => 'bolaox_aposta',
            'numberposts' => -1,
            'author'      => $user_id,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        if ( ! $posts ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Você ainda não cadastrou apostas.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $result = get_option( 'bolaox_result', '' );
        $res_numbers = $result ? array_map( 'trim', explode( ',', $result ) ) : array();
        $out = '<div class="bolaox-table-wrapper"><table class="widefat bolaox-table"><thead><tr><th>' . esc_html__( 'Aposta', self::TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Acertos', self::TEXT_DOMAIN ) . '</th><th>%</th><th>' . esc_html__( 'Dezenas', self::TEXT_DOMAIN ) . '</th><th>' . esc_html__( 'Status', self::TEXT_DOMAIN ) . '</th></tr></thead><tbody>';
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums = array_map( 'trim', explode( ',', $numbers ) );
            $hits = $result ? array_intersect( $res_numbers, $nums ) : array();
            $hit_count = count( $hits );
            $percentage = $hit_count * 10;
            $progress = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percentage . '" data-progress="' . $percentage . '%"><span></span></div>';
            $status = get_post_meta( $p->ID, '_bolaox_payment', true );
            if ( ! $status ) { $status = 'pending'; }
            $label = 'pending' === $status ? __( 'Pendente', self::TEXT_DOMAIN ) : __( 'Pago', self::TEXT_DOMAIN );
            $out .= '<tr><td>' . esc_html( $p->post_title ) . '</td><td>' . $hit_count . '</td><td>' . $progress . '</td><td>' . esc_html( $numbers ) . '</td><td>' . esc_html( $label ) . '</td></tr>';
        }
        $out .= '</tbody></table></div>';
        return $this->wrap_app( $out );
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
        $out = '<div class="bolaox-table-wrapper"><table class="widefat bolaox-table bolaox-stats"><thead><tr><th>Dezena</th><th>Ocorrências</th><th>%</th></tr></thead><tbody>';
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
        if ( ! is_user_logged_in() ) {
            $login = wp_login_form( array( 'echo' => false ) );
            $login .= '<p class="bolaox-lost"><a href="' . esc_url( wp_lostpassword_url() ) . '">' . esc_html__( 'Perdeu a senha?', self::TEXT_DOMAIN ) . '</a></p>';
            return $this->wrap_app( '<div class="bolaox-login">' . $login . '</div>' );
        }
        $user  = wp_get_current_user();
        $msg   = '';
        $avatar_id = get_user_meta( $user->ID, 'bolaox_avatar_id', true );
        if ( isset( $_POST['bolaox_profile_nonce'] ) && wp_verify_nonce( $_POST['bolaox_profile_nonce'], 'bolaox_profile' ) ) {
            $first = sanitize_text_field( $_POST['bolaox_first_name'] );
            $last  = sanitize_text_field( $_POST['bolaox_last_name'] );
            wp_update_user( array( 'ID' => $user->ID, 'first_name' => $first, 'last_name' => $last ) );
            if ( ! empty( $_FILES['bolaox_avatar']['name'] ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                if ( $avatar_id ) {
                    wp_delete_attachment( $avatar_id, true );
                }
                $att_id = media_handle_upload( 'bolaox_avatar', 0 );
                if ( ! is_wp_error( $att_id ) ) {
                    update_user_meta( $user->ID, 'bolaox_avatar_id', $att_id );
                    $avatar_id = $att_id;
                }
            }
            $msg = '<p>' . esc_html__( 'Dados atualizados com sucesso.', self::TEXT_DOMAIN ) . '</p>';
        } elseif ( isset( $_POST['bolaox_remove_avatar'] ) ) {
            delete_user_meta( $user->ID, 'bolaox_avatar_id' );
            $avatar_id = 0;
            $msg = '<p>' . esc_html__( 'Foto removida com sucesso.', self::TEXT_DOMAIN ) . '</p>';
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
        $html  = $msg . '<form method="post" enctype="multipart/form-data" class="bolaox-profile">';
        $html .= wp_nonce_field( 'bolaox_profile', 'bolaox_profile_nonce', true, false );
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Nome', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_first_name" class="bolaox-input" value="' . esc_attr( $user->first_name ) . '" /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Sobrenome', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_last_name" class="bolaox-input" value="' . esc_attr( $user->last_name ) . '" /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Foto', self::TEXT_DOMAIN ) . '<br /><input type="file" name="bolaox_avatar" class="bolaox-input" accept="image/*" /></label></p>';
        if ( $avatar_id ) {
            $html .= '<p class="bolaox-avatar-preview">' . wp_get_attachment_image( $avatar_id, 'thumbnail', false, array( 'class' => 'bolaox-avatar-img' ) ) . ' <button type="submit" name="bolaox_remove_avatar" class="button bolaox-delete-avatar">' . esc_html__( 'Remover Foto', self::TEXT_DOMAIN ) . '</button></p>';
        }
        $html .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Salvar', self::TEXT_DOMAIN ) . '" /></p>';
        $html .= '</form>';
        $html .= '<form method="post" class="bolaox-pass-form">';
        $html .= wp_nonce_field( 'bolaox_pass', 'bolaox_pass_nonce', true, false );
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Nova senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_pass1" class="bolaox-input" required /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Confirme a senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_pass2" class="bolaox-input" required /></label></p>';
        $html .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Alterar senha', self::TEXT_DOMAIN ) . '" /></p>';
        $html .= '</form>';
        return $this->wrap_app( $html );
    }

    public function render_login_shortcode() {
        if ( is_user_logged_in() ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Você já está logado.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $msg = '';
        if ( isset( $_POST['bolaox_login_nonce'] ) && wp_verify_nonce( $_POST['bolaox_login_nonce'], 'bolaox_login' ) ) {
            $phone = $this->sanitize_phone( $_POST['bolaox_phone'] );
            $pass  = $_POST['bolaox_password'];
            $users = get_users( array( 'meta_key' => 'bolaox_phone', 'meta_value' => $phone, 'number' => 1 ) );
            if ( $users ) {
                $user   = $users[0];
                $creds  = array( 'user_login' => $user->user_login, 'user_password' => $pass, 'remember' => true );
                $signon = wp_signon( $creds, false );
                if ( ! is_wp_error( $signon ) ) {
                    wp_set_current_user( $signon->ID );
                    wp_set_auth_cookie( $signon->ID );
                    $msg = '<p class="bolaox-success bolaox-login-success">' . esc_html__( 'Login realizado com sucesso.', self::TEXT_DOMAIN ) . '</p>';
                } else {
                    $msg = '<p class="bolaox-error">' . esc_html__( 'Senha incorreta.', self::TEXT_DOMAIN ) . '</p>';
                }
            } else {
                $msg = '<p class="bolaox-error">' . esc_html__( 'Telefone não encontrado.', self::TEXT_DOMAIN ) . '</p>';
            }
        } elseif ( isset( $_POST['bolaox_register_nonce'] ) && wp_verify_nonce( $_POST['bolaox_register_nonce'], 'bolaox_register' ) ) {
            $phone = $this->sanitize_phone( $_POST['bolaox_phone'] );
            $pass  = $_POST['bolaox_password'];
            if ( empty( $phone ) || empty( $pass ) ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'Preencha todos os campos.', self::TEXT_DOMAIN ) . '</p>';
            } elseif ( get_users( array( 'meta_key' => 'bolaox_phone', 'meta_value' => $phone, 'number' => 1 ) ) ) {
                $msg = '<p class="bolaox-error">' . esc_html__( 'Telefone já cadastrado.', self::TEXT_DOMAIN ) . '</p>';
            } else {
                $username = 'u' . $phone;
                $email    = $phone . '@example.com';
                $user_id  = wp_create_user( $username, $pass, $email );
                if ( is_wp_error( $user_id ) ) {
                    $msg = '<p class="bolaox-error">' . esc_html__( 'Erro ao criar usuário.', self::TEXT_DOMAIN ) . '</p>';
                } else {
                    update_user_meta( $user_id, 'bolaox_phone', $phone );
                    wp_signon( array( 'user_login' => $username, 'user_password' => $pass, 'remember' => true ), false );
                    $msg = '<p class="bolaox-success bolaox-register-success">' . esc_html__( 'Cadastro realizado com sucesso.', self::TEXT_DOMAIN ) . '</p>';
                }
            }
        }

        $login_form  = '<form method="post" class="bolaox-login-form">';
        $login_form .= wp_nonce_field( 'bolaox_login', 'bolaox_login_nonce', true, false );
        $login_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Telefone', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_phone" class="bolaox-phone" required /></label></p>';
        $login_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_password" required /></label></p>';
        $login_form .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Entrar', self::TEXT_DOMAIN ) . '" /></p>';
        $login_form .= '</form>';

        $register_form  = '<form method="post" class="bolaox-register-form">';
        $register_form .= wp_nonce_field( 'bolaox_register', 'bolaox_register_nonce', true, false );
        $register_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Telefone', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_phone" class="bolaox-phone" required /></label></p>';
        $register_form .= '<p class="bolaox-field"><label>' . esc_html__( 'Senha', self::TEXT_DOMAIN ) . '<br /><input type="password" name="bolaox_password" required /></label></p>';
        $register_form .= '<p class="bolaox-field"><input type="submit" class="button bolaox-submit" value="' . esc_attr__( 'Criar conta', self::TEXT_DOMAIN ) . '" /></p>';
        $register_form .= '</form>';

        $redirect = '';
        if ( strpos( $msg, 'bolaox-login-success' ) !== false || strpos( $msg, 'bolaox-register-success' ) !== false ) {
            $redirect = '<script>setTimeout(function(){window.location.href="' . esc_url( home_url( '/participe' ) ) . '";},1500);</script>';
        }

        $html  = '<div class="bolaox-login-tabs">' . $msg;
        $html .= '<ul class="bolaox-tabs"><li class="active">' . esc_html__( 'Acessar', self::TEXT_DOMAIN ) . '</li><li>' . esc_html__( 'Cadastrar', self::TEXT_DOMAIN ) . '</li></ul>';
        $html .= '<div class="bolaox-tab-content active">' . $login_form . '</div>';
        $html .= '<div class="bolaox-tab-content">' . $register_form . '</div>';
        $html .= '</div>' . $redirect;
        return $this->wrap_app( $html );
    }

    public function render_dashboard_shortcode() {
        if ( ! is_user_logged_in() ) {
            return $this->wrap_app( '<p>' . esc_html__( 'Você precisa entrar para acessar o painel.', self::TEXT_DOMAIN ) . '</p>' );
        }
        $sections = array(
            'profile'  => array( 'dashicons-admin-users', __( 'Perfil', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_profile]' ) ),
            'form'     => array( 'dashicons-edit', __( 'Nova Aposta', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_form]' ) ),
            'mybets'   => array( 'dashicons-chart-bar', __( 'Minhas Apostas', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_my_bets]' ) ),
            'results'  => array( 'dashicons-awards', __( 'Resultados', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_results]' ) ),
            'stats'    => array( 'dashicons-analytics', __( 'Estatísticas', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_stats]' ) ),
            'history'  => array( 'dashicons-backup', __( 'Histórico', self::TEXT_DOMAIN ), do_shortcode( '[bolao_x_history]' ) ),
            'logout'   => array( 'dashicons-migrate', __( 'Sair', self::TEXT_DOMAIN ), '<p><a class="button bolaox-submit" href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Sair', self::TEXT_DOMAIN ) . '</a></p>' ),
        );
        $html = '<div class="bolaox-dashboard">';
        foreach ( $sections as $key => $data ) {
            list( $icon, $label ) = $data;
            $html .= '<div class="bolaox-card" data-target="bx-' . esc_attr( $key ) . '"><span class="dashicons ' . esc_attr( $icon ) . '"></span><span>' . esc_html( $label ) . '</span></div>';
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

