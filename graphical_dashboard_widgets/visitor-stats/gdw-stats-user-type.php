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
    

    $usertypeDate = array();
    for($i=0;$i<$days;$i++){
         $indexts = $to_ts - ($i * 24 * 60 * 60);
         $indexdate = date('Y-m-d',  $indexts);
         $usertypeDate[$indexdate] = array();
         $usertypeDate[$indexdate]['guest'] = 0;
         $usertypeDate[$indexdate]['registered'] = 0;
    }


    $entries = $wpdb->get_results( "SELECT id,userid,knp_date,knp_ts FROM {$wpdb->prefix}gdwwid WHERE knp_ts >= '" . $from_ts . "' AND  knp_ts <= '" . $to_ts . "' ORDER BY id DESC");
 


        if( $entries ) { 
            //$count = 1;
            foreach( $entries as $entry ) {
                $index = $entry->knp_date;
                if(!isset($usertypeDate[$index])){ 
                    $usertypeDate[$index] = array(); 
                    $usertypeDate[$index]['registered'] = 0;
                    $usertypeDate[$index]['guest'] = 0;
                }

                $userid = $entry->userid;

                if(is_numeric($userid)){
                    $usertypeDate[$index]['registered'] = $usertypeDate[$index]['registered'] + 1;
                } else {
                    $usertypeDate[$index]['guest'] = $usertypeDate[$index]['guest'] + 1;
                }

            }

         } 

         $graph_data = "";
         $x_dates = ""; $y_registered = ""; $y_guest = "";

         foreach ($usertypeDate as $key => $value) {
            $x_dates .= "'".$key."', ";
            $y_registered .= $value['registered'].", ";
            $y_guest .= $value['guest'].", ";
         }

         $x_dates = substr($x_dates,0,-2);
         $x_dates = "[".$x_dates."]";

         $y_registered = substr($y_registered,0,-2);
         $y_registered = "[".$y_registered."]";

         $y_guest = substr($y_guest,0,-2);
         $y_guest = "[".$y_guest."]";

        //  echo "<pre>"; print_r($usertypeDate); echo $x_dates."<br>".$y_guest."<br>".$y_registered."<pre>"; 

         ?>
    

<?php 
    $getcolor = gdw_dashboard_widget_color();

?>

      <div class="chartBox">
          <div class="" style="height:180px" id="user_type"></div>
      </div>

<script type="text/javascript">
        // Initialize after dom ready
        var myChart20 = echarts.init(document.getElementById('user_type')); 
        
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
                    data: ['<?php echo __("Guest Visitors","gdwlang"); ?>','<?php echo __("Registered Users","gdwlang"); ?>']
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
                              bar : '<?php echo __("Bar","gdwlang"); ?>',
                              stack : '<?php echo __("Stacked Bar","gdwlang"); ?>',
                              tiled : '<?php echo __("Tiled Bar","gdwlang"); ?>'
                          },
                          type: ['line', 'bar','stack','tiled']
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
                    data: <?php echo $x_dates; ?>,
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
                        name: '<?php echo __("Registered Users","gdwlang"); ?>',
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

                        data: <?php echo $y_registered; ?>
                    },
                    {
                        name: '<?php echo __("Guest Visitors","gdwlang"); ?>',
                        type: 'bar',
                        smooth: true,
                        symbol:'none',
                        symbolSize:2,
                        showAllSymbol: true,
                        itemStyle: {
                          normal: {
                            color:'<?php echo $getcolor[1]; ?>', 
                            borderWidth:2, borderColor:'<?php echo $getcolor[1]; ?>', 
                            areaStyle: {color:'<?php echo $getcolor[1]; ?>', type: 'default'}
                          }
                        },

                        data: <?php echo $y_guest; ?>
                    },
                

            ]
            };

        // Load data into the ECharts instance 
        myChart20.setOption(option); 
                     jQuery(window).on('resize', function(){
                      myChart20.resize();
                    });
       
    </script>


</div>
