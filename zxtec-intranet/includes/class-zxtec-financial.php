<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZXTEC_Financial {
    /**
     * Commission rate as decimal for a user
     */
    public static function get_rate_for( $user_id = 0 ) {
        $rate = floatval( get_option( 'zxtec_commission', 10 ) );
        if ( $user_id ) {
            $user_rate = get_user_meta( $user_id, 'zxtec_commission', true );
            if ( $user_rate !== '' && $user_rate !== false ) {
                $rate = floatval( $user_rate );
            }
        }
        return $rate / 100;
    }

    /**
     * Retrieve concluded orders filtered by date range
     */
    private static function get_orders( $start = '', $end = '' ) {
        $meta = array(
            array(
                'key'   => '_zxtec_status',
                'value' => 'concluido',
            ),
        );
        if ( $start ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $start, 'compare' => '>=' );
        }
        if ( $end ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $end, 'compare' => '<=' );
        }

        return get_posts( array(
            'post_type'   => 'zxtec_order',
            'numberposts' => -1,
            'post_status' => 'publish',
            'meta_query'  => $meta,
        ) );
    }

    /**
     * Retrieve expenses optionally filtered by date range
     */
    private static function get_expenses( $start = '', $end = '' ) {
        $args = array(
            'post_type'   => 'zxtec_expense',
            'numberposts' => -1,
            'post_status' => 'publish',
        );
        $meta = array();
        if ( $start ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $start, 'compare' => '>=' );
        }
        if ( $end ) {
            $meta[] = array( 'key' => '_zxtec_date', 'value' => $end, 'compare' => '<=' );
        }
        if ( ! empty( $meta ) ) {
            $args['meta_query'] = $meta;
        }
        return get_posts( $args );
    }
    /**
     * Generate financial report table
     */
    public static function get_report_html( $start = '', $end = '' ) {
        $orders = self::get_orders( $start, $end );

        $totals = array();
        $expenses_total = array();
        foreach ( $orders as $order ) {
            $tech = get_post_meta( $order->ID, '_zxtec_technician', true );
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            if ( ! isset( $totals[ $tech ] ) ) {
                $totals[ $tech ] = 0;
            }
            $totals[ $tech ] += $price;
        }

        $expenses = self::get_expenses( $start, $end );
        foreach ( $expenses as $e ) {
            $tech = get_post_meta( $e->ID, '_zxtec_technician', true );
            $amount = floatval( get_post_meta( $e->ID, '_zxtec_amount', true ) );
            if ( ! isset( $expenses_total[ $tech ] ) ) {
                $expenses_total[ $tech ] = 0;
            }
            $expenses_total[ $tech ] += $amount;
        }

        ob_start();
        $rate_percent = floatval( get_option( 'zxtec_commission', 10 ) );
        $qs = '';
        if ( $start ) { $qs .= '&start=' . urlencode( $start ); }
        if ( $end ) { $qs .= '&end=' . urlencode( $end ); }
        echo '<p><a class="button" href="?page=zxtec_financial_report&download=csv' . $qs . '">' . esc_html__( 'Exportar CSV', 'zxtec' ) . '</a> ';
        echo '<a class="button" href="?page=zxtec_financial_report&download=pdf' . $qs . '">' . esc_html__( 'Exportar PDF', 'zxtec' ) . '</a> ';
        echo '<a class="button" href="?page=zxtec_financial_report&download=xls' . $qs . '">' . esc_html__( 'Exportar Excel', 'zxtec' ) . '</a></p>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>' . esc_html__( 'Tecnico', 'zxtec' ) . '</th><th>' . esc_html__( 'Total (R$)', 'zxtec' ) . '</th><th>' . esc_html__( 'Despesas', 'zxtec' ) . '</th><th>' . sprintf( esc_html__( 'Comissao (%s%%)', 'zxtec' ), number_format_i18n( $rate_percent, 2 ) ) . '</th><th>' . esc_html__( 'Saldo', 'zxtec' ) . '</th></tr></thead><tbody>';
        foreach ( $totals as $tech => $total ) {
            $user_info = get_userdata( $tech );
            $commission = $total * self::get_rate_for( $tech );
            $exp = $expenses_total[ $tech ] ?? 0;
            $net = $commission - $exp;
            echo '<tr><td>' . esc_html( $user_info ? $user_info->display_name : '#' . $tech ) . '</td><td>' . number_format_i18n( $total, 2 ) . '</td><td>' . number_format_i18n( $exp, 2 ) . '</td><td>' . number_format_i18n( $commission, 2 ) . '</td><td>' . number_format_i18n( $net, 2 ) . '</td></tr>';
        }
        echo '</tbody></table>';
        return ob_get_clean();
    }

    /**
     * Output CSV with financial totals
     */
    public static function download_csv( $start = '', $end = '' ) {
        $orders = self::get_orders( $start, $end );

        $totals = array();
        $expenses_total = array();
        foreach ( $orders as $order ) {
            $tech = get_post_meta( $order->ID, '_zxtec_technician', true );
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            if ( ! isset( $totals[ $tech ] ) ) {
                $totals[ $tech ] = 0;
            }
            $totals[ $tech ] += $price;
        }

        $expenses = self::get_expenses( $start, $end );
        foreach ( $expenses as $e ) {
            $tech = get_post_meta( $e->ID, '_zxtec_technician', true );
            $amount = floatval( get_post_meta( $e->ID, '_zxtec_amount', true ) );
            if ( ! isset( $expenses_total[ $tech ] ) ) {
                $expenses_total[ $tech ] = 0;
            }
            $expenses_total[ $tech ] += $amount;
        }

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=relatorio_financeiro.csv' );
        $output = fopen( 'php://output', 'w' );
        $rate = floatval( get_option( 'zxtec_commission', 10 ) );
        fputcsv( $output, array( 'Tecnico', 'Total (R$)', 'Despesas', 'Comissao (' . $rate . '%)', 'Saldo' ) );
        foreach ( $totals as $tech => $total ) {
            $user_info = get_userdata( $tech );
            $commission = $total * self::get_rate_for( $tech );
            $exp = $expenses_total[ $tech ] ?? 0;
            $net = $commission - $exp;
            fputcsv( $output, array( $user_info ? $user_info->display_name : '#' . $tech, number_format( $total, 2, ',', '' ), number_format( $exp, 2, ',', '' ), number_format( $commission, 2, ',', '' ), number_format( $net, 2, ',', '' ) ) );
        }
        fclose( $output );
    }

    /**
     * Output PDF with financial totals
     */
    public static function download_pdf( $start = '', $end = '' ) {
        $orders = self::get_orders( $start, $end );

        $totals = array();
        $expenses_total = array();
        foreach ( $orders as $order ) {
            $tech = get_post_meta( $order->ID, '_zxtec_technician', true );
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            if ( ! isset( $totals[ $tech ] ) ) {
                $totals[ $tech ] = 0;
            }
            $totals[ $tech ] += $price;
        }

        $expenses = self::get_expenses( $start, $end );
        foreach ( $expenses as $e ) {
            $tech = get_post_meta( $e->ID, '_zxtec_technician', true );
            $amount = floatval( get_post_meta( $e->ID, '_zxtec_amount', true ) );
            if ( ! isset( $expenses_total[ $tech ] ) ) {
                $expenses_total[ $tech ] = 0;
            }
            $expenses_total[ $tech ] += $amount;
        }

        require_once dirname( dirname( __FILE__ ) ) . '/lib/fpdf.php';
        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', '', 12 );
        $pdf->Cell( 0, 10, __( 'Relatorio Financeiro', 'zxtec' ), 0, 1 );
        foreach ( $totals as $tech => $total ) {
            $user_info = get_userdata( $tech );
            $rate = self::get_rate_for( $tech );
            $commission = $total * $rate;
            $exp = $expenses_total[ $tech ] ?? 0;
            $net = $commission - $exp;
            $line = sprintf( '%s - %s (despesas: %s, %s%%: %s, saldo: %s)',
                $user_info ? $user_info->display_name : '#' . $tech,
                number_format_i18n( $total, 2 ),
                number_format_i18n( $exp, 2 ),
                number_format_i18n( $rate * 100, 2 ),
                number_format_i18n( $commission, 2 ),
                number_format_i18n( $net, 2 )
            );
            $pdf->Cell( 0, 8, $line, 0, 1 );
        }
        $pdf->Output( 'D', 'relatorio_financeiro.pdf' );
    }

    /**
     * Output Excel (XLS) with financial totals
     */
    public static function download_xls( $start = '', $end = '' ) {
        $orders = self::get_orders( $start, $end );

        $totals = array();
        $expenses_total = array();
        foreach ( $orders as $order ) {
            $tech = get_post_meta( $order->ID, '_zxtec_technician', true );
            $service_id = get_post_meta( $order->ID, '_zxtec_service', true );
            $price = floatval( get_post_meta( $service_id, '_zxtec_price', true ) );
            if ( ! isset( $totals[ $tech ] ) ) {
                $totals[ $tech ] = 0;
            }
            $totals[ $tech ] += $price;
        }

        $expenses = self::get_expenses( $start, $end );
        foreach ( $expenses as $e ) {
            $tech = get_post_meta( $e->ID, '_zxtec_technician', true );
            $amount = floatval( get_post_meta( $e->ID, '_zxtec_amount', true ) );
            if ( ! isset( $expenses_total[ $tech ] ) ) {
                $expenses_total[ $tech ] = 0;
            }
            $expenses_total[ $tech ] += $amount;
        }

        header( 'Content-Type: application/vnd.ms-excel; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=relatorio_financeiro.xls' );
        $rate = floatval( get_option( "zxtec_commission", 10 ) );
        echo "Tecnico\tTotal (R$)\tDespesas\tComissao ($rate%)\tSaldo\n";
        foreach ( $totals as $tech => $total ) {
            $user_info = get_userdata( $tech );
            $commission = $total * self::get_rate_for( $tech );
            $exp = $expenses_total[ $tech ] ?? 0;
            $net = $commission - $exp;
            echo ( $user_info ? $user_info->display_name : '#' . $tech ) . "\t" . number_format( $total, 2, ',', '' ) . "\t" . number_format( $exp, 2, ',', '' ) . "\t" . number_format( $commission, 2, ',', '' ) . "\t" . number_format( $net, 2, ',', '' ) . "\n";
        }
    }
}
