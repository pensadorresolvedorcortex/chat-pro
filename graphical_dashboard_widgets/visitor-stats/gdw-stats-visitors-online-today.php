<div class="">
        
<?php
    global $wpdb;
    $today_date = date("Y-m-d");
    $entries = $wpdb->get_results( "SELECT id,knp_time FROM {$wpdb->prefix}gdwwid WHERE knp_date='".$today_date."' ORDER BY id ASC");


            $count = 0;
            $pageviewsHour = array();
            for($i = 2;$i<=24;$i++){
              $pageviewsHour[$i] = 0;
              $i++;
            }
            //echo "<pre>"; print_r($pageviewsHour); echo "<pre>"; 
            if( $entries ) { 

                foreach( $entries as $entry ) {
                    $timestr = $entry->knp_time;
                    $timeexp = explode(":",$timestr);
                    $h = $timeexp[0];
                    if($h%2 == 1){
                        $index = $h + 1;
                    } else {
                        $index = $h + 2;
                    }

                    if(!isset($pageviewsHour[$index])){ $pageviewsHour[$index] = 0; } 
                    
                    $pageviewsHour[$index] = $pageviewsHour[$index] + 1;
                    
                    $count++;
                }

            } 

                $graph_data = ""; 
                foreach ($pageviewsHour as $key => $value) {
                  $graph_data .= $value.", ";
                }
                $graph_data = substr($graph_data,0,-2);
                $graph_data = "[".$graph_data."]";

            ?>

<?php 
    $getcolor = gdw_dashboard_widget_color();

?>

<div class="totalviewscount">
        <span class='viewslabel'><?php echo __("Page Views","gdwlang"); ?><br><?php echo __("Today","gdwlang"); ?></span>
        <span class="count"><?php echo $count; ?></span>
    </div>

      <div class="chartBox">
         <h4 class='widhead'><?php echo __("Page Views Today","gdwlang"); ?> (<?php echo $today_date; ?>) <?php echo __("By Hour","gdwlang"); ?></h4>
          <div class="chart has-fixed-height" style="height:180px" id="page_views_today"></div>
      </div>

<script type="text/javascript">
        // Initialize after dom ready
        var myChart21 = echarts.init(document.getElementById('page_views_today')); 
        
        var option = {

                // Setup grid
                grid: {
                    zlevel: 0,
                    x: 20,
                    x2: 20,
                    y: 20,
                    y2: 20,
                    borderWidth: 0,
                    backgroundColor: 'rgba(0,0,0,0)',
                    borderColor: 'rgba(0,0,0,0)',
                },

                // Add tooltip
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { 
                        type: 'shadow', // line|shadow
                        lineStyle:{color: 'rgba(0,0,0,.5)', width: 1},
                        shadowStyle:{color: 'rgba(0,0,0,.1)'}
                      }
                },

                // Add legend
                legend: {
                    data: []
                },
                toolbox: {
                  orient: 'vertical',
                    show : true,
                    showTitle: true,
                    color : ['#bdbdbd','#bdbdbd','#bdbdbd','#bdbdbd'],
                    itemSize: 13,
                    itemGap: 10,
                    feature : {
                        mark : {show: false},
                        dataZoom : {
                            show : true,
                            title : {
                                dataZoom : '<?php echo __("Data Zoom","gdwlang"); ?>',
                                dataZoomReset : '<?php echo __("Reset Zoom","gdwlang"); ?>'
                            }
                        },
                        dataView : {show: false, readOnly: true},
                        magicType : {
                          show: true, 
                          title : {
                              line : '<?php echo __("Line","gdwlang"); ?>',
                              bar : '<?php echo __("Bar","gdwlang"); ?>',
                          },
                          type: ['line', 'bar'],
                          option: {
                            /*line: {
                                itemStyle: {
                                  normal: {
                                    color:'rgba(3,1,1,1.0)', 
                                  }
                                },
                                data: [1,2,3,4,5,6,7,8,9,10,11,12]
                            }*/
                          }
                        },
                        restore : {show: false},
                        saveAsImage : {show: true,title:'<?php echo __("Save as Image","gdwlang"); ?>'}
                    }
                },

                // Enable drag recalculate
                calculable: true,

                // Horizontal axis
                xAxis: [{
                    type: 'category',
                    boundaryGap: false,
                    data: [
                        '0h-2h', '2h-4h', '4h-6h', '6h-8h', '8h-10h', '10h-12h', '12h-14h', '14h-16h', '16h-18h', '18h-20h', '20h-22h', '22h-24h'
                    ],
                    axisLine: {
                        show: true,
                        onZero: true,
                        lineStyle: {
                            color: '#757575',
                            type: 'solid',
                            width: '2',
                            shadowColor: 'rgba(0,0,0,0)',
                            shadowBlur: 5,
                            shadowOffsetX: 3,
                            shadowOffsetY: 3,
                        },
                    },                    
                    axisTick: {
                        show: false,
                    },
                    splitLine: {
                          show: false,
                          lineStyle: {
                              color: '#fff',
                              type: 'solid',
                              width: 0,
                              shadowColor: 'rgba(0,0,0,0)',
                        },
                    },
                }],

                // Vertical axis
                yAxis: [{
                    type: 'value',
                    splitLine: {
                          show: false,
                          lineStyle: {
                              color: 'fff',
                              type: 'solid',
                              width: 0,
                              shadowColor: 'rgba(0,0,0,0)',
                        },
                    },
                    axisLabel: {
                        show: false,
                    },                    
                    axisTick: {
                        show: false,
                    },                    
                    axisLine: {
                        show: false,
                        onZero: true,
                        lineStyle: {
                            color: '#ff0000',
                            type: 'solid',
                            width: '0',
                            shadowColor: 'rgba(0,0,0,0)',
                            shadowBlur: 5,
                            shadowOffsetX: 3,
                            shadowOffsetY: 3,
                        },
                    },


                }],

                // Add series
                series: [
                    {
                        name: '<?php echo __("Page Views","gdwlang"); ?>',
                        type: 'bar',
                        smooth: true,
                        symbol:'none',
                        symbolSize:2,
                        showAllSymbol: true,
                        itemStyle: {
                          normal: {
                            color:'<?php echo $getcolor[0]; ?>', 
                            borderWidth:2, borderColor:'<?php echo $getcolor[0]; ?>', 
                            areaStyle: {color:'<?php echo $getcolor[0]; ?>', type: 'default'}
                          }
                        },

                        data: <?php echo $graph_data; ?>
                    }
                ]
            };

        // Load data into the ECharts instance 
        myChart21.setOption(option); 
                    jQuery(window).on('resize', function(){
                      myChart21.resize();
                    });
        
    </script>





</div>