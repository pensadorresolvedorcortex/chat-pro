<?php
/*
Plugin Name: Bolao X
Description: Sistema de gerenciamento de bolão com conferência automática, histórico de resultados, exportação em PDF e Excel, notificação por e-mail e visual moderno.
Version: 3.1.0
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
    const TEXT_DOMAIN = 'bolao-x';
    const VERSION = '3.1.0';

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
        add_shortcode( 'bolao_x_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'bolao_x_results', array( $this, 'render_results_shortcode' ) );
        add_shortcode( 'bolao_x_history', array( $this, 'render_history_shortcode' ) );
        add_shortcode( 'bolao_x_my_bets', array( $this, 'render_my_bets_shortcode' ) );
        add_shortcode( 'bolao_x_stats', array( $this, 'render_stats_shortcode' ) );
    }

    public function register_settings() {
        register_setting( 'bolaox', 'bolaox_cutoffs' );
        register_setting( 'bolaox', 'bolaox_pix_key' );
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

    private function generate_pix_qr( $key ) {
        if ( ! function_exists( 'imagepng' ) || ! $key ) {
            return '';
        }
        require_once __DIR__ . '/lib/qrcode.php';
        $qr = QRCode::getMinimumQRCode( $key, QR_ERROR_CORRECT_LEVEL_L );
        $img = $qr->createImage( 4, 2 );
        ob_start();
        imagepng( $img );
        $data = ob_get_clean();
        imagedestroy( $img );
        return 'data:image/png;base64,' . base64_encode( $data );
    }

    public function admin_notices() {
        if ( $this->notice ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $this->notice ) . '</p></div>';
            $this->notice = '';
        }
    }

    public function enqueue_assets() {
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
    }

    public function numbers_meta_box( $post ) {
        $numbers = get_post_meta( $post->ID, '_bolaox_numbers', true );
        echo '<input type="text" name="bolaox_numbers" value="' . esc_attr( $numbers ) . '" placeholder="' . esc_attr__( 'Ex: 05,12,23,34,45,56,67,78,89,90', self::TEXT_DOMAIN ) . '" style="width:100%" />';
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
    }

    public function admin_menu() {
        add_menu_page( 'Bolao X', 'Bolao X', 'manage_options', 'bolaox', array( $this, 'results_page' ) );
        add_submenu_page( 'bolaox', __( 'Configurações', self::TEXT_DOMAIN ), __( 'Configurações', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-settings', array( $this, 'settings_page' ) );
        add_submenu_page( 'bolaox', __( 'Importar CSV', self::TEXT_DOMAIN ), __( 'Importar CSV', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-import', array( $this, 'import_page' ) );
        add_submenu_page( 'bolaox', __( 'Histórico', self::TEXT_DOMAIN ), __( 'Histórico', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-history', array( $this, 'history_page' ) );
        add_submenu_page( 'bolaox', __( 'Estatísticas', self::TEXT_DOMAIN ), __( 'Estatísticas', self::TEXT_DOMAIN ), 'manage_options', 'bolaox-stats', array( $this, 'stats_page' ) );
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
            }
        }
        $result = get_option( 'bolaox_result', '' );
        if ( isset( $_GET['export'] ) && $result ) {
            if ( 'pdf' === $_GET['export'] ) {
                $this->export_pdf( $result );
            } elseif ( 'xls' === $_GET['export'] ) {
                $this->export_xls( $result );
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
            echo '<a href="' . esc_url( $base . '&export=csv' ) . '" class="button">' . esc_html__( 'Exportar CSV', self::TEXT_DOMAIN ) . '</a> ';
            echo '<a href="' . esc_url( $base . '&export=xls' ) . '" class="button">' . esc_html__( 'Exportar Excel', self::TEXT_DOMAIN ) . '</a> ';
            echo '<a href="' . esc_url( $base . '&export=pdf' ) . '" class="button">' . esc_html__( 'Exportar PDF', self::TEXT_DOMAIN ) . '</a>';
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
            $pix_val = isset( $_POST['bolaox_pix_key'] ) ? sanitize_text_field( $_POST['bolaox_pix_key'] ) : '';
            update_option( 'bolaox_pix_key', $pix_val );
            echo '<div class="updated"><p>' . esc_html__( 'Configurações salvas.', self::TEXT_DOMAIN ) . '</p></div>';
        }
        $cutoffs = get_option( 'bolaox_cutoffs', array() );
        $pix_key = get_option( 'bolaox_pix_key', '' );
        echo '<div class="wrap"><h1>' . esc_html__( 'Configurações', self::TEXT_DOMAIN ) . '</h1>';
        echo '<form method="post">';
        wp_nonce_field( 'bolaox_settings', 'bolaox_nonce' );
        echo '<table class="form-table"><tbody>';
        foreach ( $days as $idx => $label ) {
            $val = isset( $cutoffs[ $idx ] ) ? $cutoffs[ $idx ] : '';
            echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
            echo '<input type="time" name="bolaox_cutoffs[' . $idx . ']" value="' . esc_attr( $val ) . '" /></td></tr>';
        }
        echo '<tr><th scope="row">' . esc_html__( 'Chave Pix', self::TEXT_DOMAIN ) . '</th><td><input type="text" name="bolaox_pix_key" value="' . esc_attr( $pix_key ) . '" class="regular-text" /></td></tr>';
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

    private function generate_report( $result ) {
        $res_numbers = array_map( 'trim', explode( ',', $result ) );
        $posts = get_posts( array( 'post_type' => 'bolaox_aposta', 'numberposts' => -1 ) );
        if ( ! $posts ) {
            return '<p>' . esc_html__( 'Nenhuma aposta cadastrada.', self::TEXT_DOMAIN ) . '</p>';
        }
        $rows = array();
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
                if ( in_array( $n, $duplicates ) ) {
                    $display_nums[] = '<span class="bolaox-dup">' . esc_html( $n ) . '</span>';
                } else {
                    $display_nums[] = esc_html( $n );
                }
            }
            $class = '';
            if ( $hit_count >= 8 ) {
                $class = ' class="bolaox-hit-' . $hit_count . '"';
            }
            $rows[] = array(
                'hits'   => $hit_count,
                'class'  => $class,
                'name'   => esc_html( $p->post_title ),
                'progress' => $progress,
                'numbers' => implode( ', ', $display_nums ),
            );
        }

        usort( $rows, function ( $a, $b ) {
            return $b['hits'] <=> $a['hits'];
        } );

        $out = '<table class="widefat bolaox-table"><thead><tr><th>Aposta</th><th>Acertos</th><th>%</th><th>Dezenas</th></tr></thead><tbody>';
        foreach ( $rows as $row ) {
            $out .= '<tr' . $row['class'] . '><td>' . $row['name'] . '</td><td>' . $row['hits'] . '</td><td>' . $row['progress'] . '</td><td>' . $row['numbers'] . '</td></tr>';
        }
        $out .= '</tbody></table>';
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

    public function render_form_shortcode() {
        $cutoffs = get_option( 'bolaox_cutoffs', array() );
        if ( $cutoffs ) {
            $now  = current_time( 'timestamp' );
            $day  = (int) date( 'N', $now );
            if ( ! empty( $cutoffs[ $day ] ) ) {
                $cutoff_ts = strtotime( date( 'Y-m-d ' . $cutoffs[ $day ], $now ) );
                if ( $now >= $cutoff_ts ) {
                    return '<p>' . esc_html__( 'Apostas encerradas. Tente novamente no próximo concurso.', self::TEXT_DOMAIN ) . '</p>';
                }
            }
        }
        if ( isset( $_POST['bolaox_submit'] ) && isset( $_POST['bolaox_nonce'] ) && wp_verify_nonce( $_POST['bolaox_nonce'], 'bolaox_form' ) ) {
            $name = sanitize_text_field( $_POST['bolaox_name'] );
            $numbers = sanitize_text_field( $_POST['bolaox_numbers'] );
            $numbers = $this->validate_numbers( $numbers );
            if ( false === $numbers ) {
                return '<p>' . esc_html__( 'Formato de dezenas inválido. Use 10 números de 00 a 99 separados por vírgula.', self::TEXT_DOMAIN ) . '</p>';
            }
            $post_id = wp_insert_post( array(
                'post_type'   => 'bolaox_aposta',
                'post_title'  => $name,
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            ) );
            if ( $post_id ) {
                update_post_meta( $post_id, '_bolaox_numbers', $numbers );
                $pix  = get_option( 'bolaox_pix_key', '' );
                $msg  = '<p>' . esc_html__( 'Aposta registrada com sucesso!', self::TEXT_DOMAIN ) . '</p>';
                $msg .= '<p>' . sprintf( esc_html__( 'Sua aposta: %s', self::TEXT_DOMAIN ), '<strong>' . esc_html( $numbers ) . '</strong>' ) . '</p>';
                if ( $pix ) {
                    $qr = $this->generate_pix_qr( $pix );
                    if ( $qr ) {
                        $msg .= '<p><img class="bolaox-qr" src="' . esc_attr( $qr ) . '" alt="Pix QR" /></p>';
                    }
                    $msg .= '<p class="bolaox-pix">' . sprintf( esc_html__( 'Chave Pix: %s', self::TEXT_DOMAIN ), '<strong class="bolaox-pix-key">' . esc_html( $pix ) . '</strong>' ) . ' <button type="button" class="button bolaox-copy" data-copy="' . esc_attr( $pix ) . '">' . esc_html__( 'Copiar', self::TEXT_DOMAIN ) . '</button></p>';
                }
                return $msg;
            }
        }
        $html  = '<div class="bolaox-form">';
        $html .= '<form method="post" class="bolaox-form-inner">';
        $html .= wp_nonce_field( 'bolaox_form', 'bolaox_nonce', true, false );
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Nome', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_name" required /></label></p>';
        $html .= '<p class="bolaox-field"><label>' . esc_html__( 'Dezenas', self::TEXT_DOMAIN ) . '<br /><input type="text" name="bolaox_numbers" placeholder="' . esc_attr__( '10 dezenas', self::TEXT_DOMAIN ) . '" required /></label></p>';
        $html .= '<p class="bolaox-field"><input type="submit" name="bolaox_submit" value="' . esc_attr__( 'Enviar', self::TEXT_DOMAIN ) . '" class="button" /></p>';
        $pix_form = get_option( 'bolaox_pix_key', '' );
        if ( $pix_form ) {
            $qr = $this->generate_pix_qr( $pix_form );
            if ( $qr ) {
                $html .= '<p><img class="bolaox-qr" src="' . esc_attr( $qr ) . '" alt="Pix QR" /></p>';
            }
            $html .= '<p class="bolaox-pix">' . sprintf( esc_html__( 'Chave Pix: %s', self::TEXT_DOMAIN ), '<strong>' . esc_html( $pix_form ) . '</strong>' ) . '</p>';
        }
        $html .= '</form></div>';
        return $html;
    }

    public function render_results_shortcode() {
        $result = get_option( 'bolaox_result', '' );
        if ( ! $result ) {
            return '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p>';
        }
        $output  = '<div class="bolaox-result">';
        $output .= '<p><strong>' . esc_html__( 'Resultado da Semana:', self::TEXT_DOMAIN ) . '</strong> ' . esc_html( $result ) . '</p>';
        $output .= $this->generate_report( $result );
        $output .= '</div>';
        return $output;
    }

    public function render_history_shortcode() {
        $posts = get_posts( array( 'post_type' => 'bolaox_result', 'numberposts' => 5, 'orderby' => 'date', 'order' => 'DESC' ) );
        if ( ! $posts ) {
            return '<p>' . esc_html__( 'Nenhum resultado cadastrado.', self::TEXT_DOMAIN ) . '</p>';
        }
        $out = '<ul class="bolaox-history">';
        foreach ( $posts as $p ) {
            $nums = get_post_meta( $p->ID, '_bolaox_result', true );
            $date = esc_html( get_the_date( '', $p ) );
            $out .= '<li><strong>' . $date . ':</strong> ' . esc_html( $nums ) . '</li>';
        }
        $out .= '</ul>';
        return $out;
    }

    public function render_my_bets_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Faça login para ver suas apostas.', self::TEXT_DOMAIN ) . '</p>';
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
            return '<p>' . esc_html__( 'Você ainda não cadastrou apostas.', self::TEXT_DOMAIN ) . '</p>';
        }
        $result = get_option( 'bolaox_result', '' );
        $res_numbers = $result ? array_map( 'trim', explode( ',', $result ) ) : array();
        $out = '<table class="widefat bolaox-table"><thead><tr><th>Aposta</th><th>Acertos</th><th>%</th><th>Dezenas</th></tr></thead><tbody>';
        foreach ( $posts as $p ) {
            $numbers = get_post_meta( $p->ID, '_bolaox_numbers', true );
            $nums = array_map( 'trim', explode( ',', $numbers ) );
            $hits = $result ? array_intersect( $res_numbers, $nums ) : array();
            $hit_count = count( $hits );
            $percentage = $hit_count * 10;
            $progress = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $percentage . '" data-progress="' . $percentage . '%"><span></span></div>';
            $out .= '<tr><td>' . esc_html( $p->post_title ) . '</td><td>' . $hit_count . '</td><td>' . $progress . '</td><td>' . esc_html( $numbers ) . '</td></tr>';
        }
        $out .= '</tbody></table>';
        return $out;
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
        $out = '<table class="widefat bolaox-table bolaox-stats"><thead><tr><th>Dezena</th><th>Ocorrências</th><th>%</th></tr></thead><tbody>';
        for ( $i = 0; $i < 100; $i++ ) {
            if ( $counts[ $i ] > 0 ) {
                $num  = str_pad( strval( $i ), 2, '0', STR_PAD_LEFT );
                $perc = round( ( $counts[ $i ] / $total ) * 100 );
                $bar  = '<div class="bolaox-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $perc . '" data-progress="' . $perc . '%"><span style="width:' . $perc . '%"></span></div>';
                $out .= '<tr><td>' . $num . '</td><td>' . $counts[ $i ] . '</td><td>' . $bar . '</td></tr>';
            }
        }
        $out .= '</tbody></table>';
        return $out;
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
    public static function activate() {
        self::instance();
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

}

register_activation_hook( __FILE__, array( "BOLAOX_Plugin", "activate" ) );
register_deactivation_hook( __FILE__, array( "BOLAOX_Plugin", "deactivate" ) );
BOLAOX_Plugin::instance();

