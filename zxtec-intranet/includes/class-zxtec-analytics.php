<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ZXTEC_Analytics {
    public static function page_html() {
        $data = \ZXTEC_Financial::get_monthly_summary( 12 );
        $labels = array();
        $rev = array();
        $exp = array();
        $net = array();
        foreach ( $data as $row ) {
            $labels[] = $row['label'];
            $rev[]    = round( $row['revenue'], 2 );
            $exp[]    = round( $row['expenses'], 2 );
            $net[]    = round( $row['revenue'] - $row['expenses'], 2 );
        }
        ob_start();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Relatorio Grafico', 'zxtec' ); ?></h1>
            <canvas id="zxtec_chart" height="200"></canvas>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        (function(){
            const ctx = document.getElementById('zxtec_chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo wp_json_encode( $labels ); ?>,
                    datasets: [
                        {label:'Receita',data:<?php echo wp_json_encode( $rev ); ?>,borderColor:'#007bff',fill:false},
                        {label:'Despesas',data:<?php echo wp_json_encode( $exp ); ?>,borderColor:'#dc3545',fill:false},
                        {label:'Lucro',data:<?php echo wp_json_encode( $net ); ?>,borderColor:'#28a745',fill:false}
                    ]
                }
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
