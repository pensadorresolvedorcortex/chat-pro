<div class="">


<?php
    global $wpdb;
    $gmt_offset = get_option('gmt_offset');
    //$gmt_offset = 5.5;
    $days = 15;
    $timezoneName = timezone_name_from_abbr("", ($gmt_offset*3600), false);

    $today_date = date("Y-m-d");
    $curr_ts = strtotime('+'.$gmt_offset.' hour');
    $curr_date = date('Y-m-d', $curr_ts);

    $get_curr_ts = DateTime::createFromFormat('Y-m-d', $curr_date, new DateTimeZone($timezoneName));
    $curr_ts = $get_curr_ts->getTimestamp();


    $to_ts = ($curr_ts - (24 * 60 * 60)); // -1 day
    $from_ts = ($to_ts - ((24 * 60 * 60) * $days)); // - 15 days
    $to_date = date('Y-m-d', $to_ts);
    $from_date = date('Y-m-d', $from_ts);

    $get_to_ts = DateTime::createFromFormat('Y-m-d', $to_date, new DateTimeZone($timezoneName));
    $to_ts = $get_to_ts->getTimestamp();

    $get_from_ts = DateTime::createFromFormat('Y-m-d', $from_date, new DateTimeZone($timezoneName));
    $from_ts = $get_from_ts->getTimestamp();
    


    $entries = $wpdb->get_results( "SELECT  id,platform FROM {$wpdb->prefix}gdwwid WHERE knp_ts >= '" . $from_ts . "' AND  knp_ts <= '" . $to_ts . "' ORDER BY id DESC");
 


        if( $entries ) { 
            $platform_stats = array();
            $platform_stats['Windows'] = 0;
            $platform_stats['Apple'] = 0;
            $platform_stats['Linux'] = 0;
            $platform_stats['Android'] = 0;

            //$count = 1;
            foreach( $entries as $entry ) {
                $index = $entry->platform;

                if(!isset($platform_stats[$index])){ 
                    $platform_stats[$index] = 0; 
                }

                $platform_stats[$index] = $platform_stats[$index] + 1;

            }

         } 

         $graph_data = "";
         $x_platforms = ""; $y_total = "";

         foreach ($platform_stats as $key => $value) {
            $x_platforms .= "'".$key."', ";
            $y_total .= $value.", ";
         }

         $x_platforms = substr($x_platforms,0,-2);
         $x_platforms = "[".$x_platforms."]";

         $y_total = substr($y_total,0,-2);
         $y_total = "[".$y_total."]";

        // echo "<pre>"; print_r($platform_stats); echo $x_platforms."<br>".$y_total."<pre>"; 

         ?>
    

      <div class="chartBox">
          <div class="" style="height:180px" id="platform_type"></div>
      </div>

<script type="text/javascript">
        // Initialize after dom ready
        var myChart17 = echarts.init(document.getElementById('platform_type')); 
        
        var option = {

                // Setup grid
                grid: {
                    zlevel: 0,
                    x: 30,
                    x2: 50,
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
                    data: ['Platforms Used in last <?php echo $days; ?> days']
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
                              line : '<?php echo __("Area","gdwlang"); ?>',
                              bar : '<?php echo __("Bar","gdwlang"); ?>'
                          },
                          type: ['line', 'bar']
                        },
                        restore : {show: false},
                        saveAsImage : {show: true,title:'<?php echo __("Save as Image","gdwlang"); ?>',title:'<?php echo __("Save as Image","gdwlang"); ?>'}
                    }
                },

                // Enable drag recalculate
                calculable: true,

                // Horizontal axis
                xAxis: [{
                    type: 'category',
                    boundaryGap: false,
                    data: <?php echo $x_platforms; ?>,
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
                        name: 'Total Visits',
                        type: 'line',
                        smooth: true,
                        symbol:'none',
                        symbolSize:2,
                        showAllSymbol: true,
                        itemStyle: {
                          normal: {
                            color:'#757575', 
                            borderWidth:2, borderColor:'rgba(63,81,181,1)', 
                            areaStyle: {color:'rgba(63,81,181,1)', type: 'default'}
                          }
                        },

                        data: <?php echo $y_total; ?>
                    }]
            };

        // Load data into the ECharts instance 
        myChart17.setOption(option);
                    jQuery(window).on('resize', function(){
                      myChart17.resize();
                    });
         
    </script>


</div>
