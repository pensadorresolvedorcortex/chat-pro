<?php

function wp_show_stats_pages() {

    global $wpdb;
    
    // get page data 
    $totalPages = wp_count_posts('page');
    $totalPagesArray = (array)$totalPages;
    unset($totalPagesArray['auto-draft']);
    unset($totalPagesArray['inherit']);
    $countPages = array_sum($totalPagesArray);
    
    ?>

        <?php if($countPages > 0){ 
            
            $data_str = "";
            $data_obj = "";
            //if(isset($usersCount['avail_roles']) && sizeof($usersCount['avail_roles']) > 0){
                foreach ($totalPagesArray as $key => $value) {
                    $data_str .= "'".ucfirst($key)."', ";

                    if($value == '0'){ $value = "'-'";}
                     $data_obj .= "{value: ".$value.",  name:'".ucfirst($key)."'}, ";
                }

                 $data_str = substr($data_str,0,-2);
                 $data_str = "[".$data_str."]";

                 $data_obj = substr($data_obj,0,-2);
                 $data_obj = "[".$data_obj."]";

           // }
        ?>

<?php 
    $getcolor = mtrl_dashboard_widget_color();
                // print_r($getcolor);
?>

<?php 
/*$getcolor = array();
$getcolor[0] = "#E57373";
$getcolor[1] = "#FFD54F";
$getcolor[2] = "#F06292";
$getcolor[3] = "#FFB74D";
$getcolor[4] = "#FF8A65";
$getcolor[5] = "#FFF176";*/
?>
            <div class="chartBox"><?php // echo "<pre>"; print_r($totalPagesArray); echo "Total Pages: ".$countPages; echo "</pre>"; ?>
                <div id="totalPages_wiseChart" style='height:180px;'></div>
            </div>

            <script type="text/javascript">
              // Initialize after dom ready
            //   console.log("hi");
              var myChart7 = echarts.init(document.getElementById('totalPages_wiseChart')); 
                    
              var option = {
                color: ['<?php echo $getcolor[0]; ?>','<?php echo $getcolor[1]; ?>','<?php echo $getcolor[2]; ?>','<?php echo $getcolor[3]; ?>','<?php echo $getcolor[4]; ?>','<?php echo $getcolor[5]; ?>'],

                    tooltip : {
                        trigger: 'item',
                        formatter: "{a} <br/>{b} : {c} ({d}%)"
                    },
                            legend: {
                                x: 'left',
                                orient:'vertical',
                                padding: 0,
                                data:<?php echo $data_str; ?>
                            },
                    toolbox: {
                        show : true,
                        color : ['#bdbdbd','#bdbdbd','#bdbdbd','#bdbdbd'],
                    itemSize: 13,
                    itemGap: 10,
                        feature : {
                            mark : {show: false},
                                    dataView : {show: false, readOnly: true},
                                    magicType : {
                                        show: true, 
                                        type: ['pie', 'funnel'],
                                        option: {
                                            funnel: {
                                                x: '25%',
                                                width: '50%',
                                                funnelAlign: 'center',
                                                max: 1700
                                            },
                                            pie: {
                                                roseType : 'none',
                                            }
                                        }
                                    },
                                    restore : {show: false},
                                    saveAsImage : {show: true}
                        }
                    },
                    calculable : true,
                    series : [
                        {
                            name:'Contagem de Páginas',
                            type:'pie',
                            radius : [20, '80%'],
                            roseType : 'radius',
                            center: ['50%', '45%'],
                            width: '50%',       // for funnel
                            max: 40,            // for funnel
                            itemStyle : {
                                   normal : { label : { show : true }, labelLine : { show : true } },
                                   emphasis : { label : { show : false }, labelLine : {show : false} }
                             },
                            data:<?php echo $data_obj; ?>
                        }
                    ]};

                    // Load data into the ECharts instance 
                    myChart7.setOption(option); 
                    jQuery(window).on('resize', function(){
                      myChart7.resize();
                    });
                    
                </script>

        <?php } ?>
        





<?php } ?>