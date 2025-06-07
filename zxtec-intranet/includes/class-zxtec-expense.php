<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZXTEC_Expense {
    /**
     * Generate expenses report HTML
     */
    public static function get_report_html() {
        $expenses = get_posts( array(
            'post_type'   => 'zxtec_expense',
            'numberposts' => -1,
            'post_status' => 'publish',
        ) );

        if ( empty( $expenses ) ) {
            return '<p>' . esc_html__( 'Nenhuma despesa registrada.', 'zxtec' ) . '</p>';
        }

        ob_start();
        echo '<p><a class="button" href="?page=zxtec_expense_report&download=csv">' . esc_html__( 'Exportar CSV', 'zxtec' ) . '</a></p>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>' . esc_html__( 'Data', 'zxtec' ) . '</th><th>' . esc_html__( 'Tecnico', 'zxtec' ) . '</th><th>' . esc_html__( 'Descricao', 'zxtec' ) . '</th><th>' . esc_html__( 'Valor (R$)', 'zxtec' ) . '</th></tr></thead><tbody>';
        foreach ( $expenses as $e ) {
            $tech_id = get_post_meta( $e->ID, '_zxtec_technician', true );
            $user = $tech_id ? get_userdata( $tech_id ) : null;
            $date = get_post_meta( $e->ID, '_zxtec_date', true );
            $amount = floatval( get_post_meta( $e->ID, '_zxtec_amount', true ) );
            echo '<tr><td>' . esc_html( $date ) . '</td><td>' . esc_html( $user ? $user->display_name : '' ) . '</td><td>' . esc_html( $e->post_title ) . '</td><td>' . number_format_i18n( $amount, 2 ) . '</td></tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /**
     * Output CSV with expenses
     */
    public static function download_csv() {
        $expenses = get_posts( array(
            'post_type'   => 'zxtec_expense',
            'numberposts' => -1,
            'post_status' => 'publish',
        ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=despesas.csv' );
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'Data', 'Tecnico', 'Descricao', 'Valor' ) );
        foreach ( $expenses as $e ) {
            $tech_id = get_post_meta( $e->ID, '_zxtec_technician', true );
            $user = $tech_id ? get_userdata( $tech_id ) : null;
            $date = get_post_meta( $e->ID, '_zxtec_date', true );
            $amount = floatval( get_post_meta( $e->ID, '_zxtec_amount', true ) );
            fputcsv( $out, array( $date, $user ? $user->display_name : '', $e->post_title, number_format( $amount, 2, ',', '' ) ) );
        }
        fclose( $out );
    }
}
