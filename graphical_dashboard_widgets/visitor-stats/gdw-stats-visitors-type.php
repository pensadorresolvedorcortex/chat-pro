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
    

    $visitDate = array();
    for($i=0;$i<$days;$i++){
         $indexts = $to_ts - ($i * 24 * 60 * 60);
         $indexdate = date('Y-m-d',  $indexts);
         $visitDate[$indexdate] = array();
         $visitDate[$indexdate]['total'] = 0;
         $visitDate[$indexdate]['unique'] = 0;
    }


    $entries = $wpdb->get_results( "SELECT  id,isunique,knp_date,knp_ts,knp_time FROM {$wpdb->prefix}gdwwid WHERE knp_ts >= '" . $from_ts . "' AND  knp_ts <= '" . $to_ts . "' ORDER BY id DESC");
 


        if( $entries ) { 
            //$count = 1;
            foreach( $entries as $entry ) {
                $index = $entry->knp_date;
                if(!isset($visitDate[$index])){ 
                    $visitDate[$index] = array(); 
                    $visitDate[$index]['unique'] = 0;
                    $visitDate[$index]['total'] = 0;
                }

                $isunique = $entry->isunique;

                $visitDate[$index]['total'] = $visitDate[$index]['total'] + 1;
                if($isunique == "yes"){
                    $visitDate[$index]['unique'] = $visitDate[$index]['unique'] + 1;
                }
                 
    		  //echo $entry->id."<br />"; echo $entry->knp_date." "; echo $entry->knp_ts." "; echo $entry->knp_time; echo $isunique."<br />"; 

            }

         } 

         $graph_data = "";
         $x_dates = ""; $y_unique = ""; $y_total = "";

         foreach ($visitDate as $key => $value) {
            $x_dates .= "'".$key."', ";
            $y_unique .= $value['unique'].", ";
            $y_total .= $value['total'].", ";
         }

         $x_dates = substr($x_dates,0,-2);
         $x_dates = "[".$x_dates."]";

         $y_unique = substr($y_unique,0,-2);
         $y_unique = "[".$y_unique."]";

         $y_total = substr($y_total,0,-2);
         $y_total = "[".$y_total."]";

        //  echo "<pre>"; print_r($visitDate); echo $x_dates."<br>".$y_total."<br>".$y_unique."<pre>"; 

         ?>
    

      <div class="chartBox" style="width:100%;">
      <div class="widhead"></div>
      <div class="" style="width:100%;height:180px" id="visitors_type"></div>
      </div>

<?php 
    $getcolor = gdw_dashboard_widget_color();
  //  print_r($getcolor);
?>



<script type="text/javascript">
        // Initialize after dom ready
        var myChart4 = echarts.init(document.getElementById('visitors_type')); 
        
        var option = {

                // Setup grid
                grid: {
                    zlevel: 0,
                    x: 40,
                    x2: 60,
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
                    data: ['<?php echo __("Unique Visitors","gdwlang"); ?>','<?php echo __("Total Visitors","gdwlang"); ?>']
                },
                toolbox: {
                  orient: 'vertical',
                    show : true,
                    showTitle: true,
                    color : ['#bdbdbd','#bdbdbd','#bdbdbd','#bdbdbd'],
                    itemSize: 14,
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
                        name: '<?php echo __("Unique Visitors","gdwlang"); ?>',
                        type: 'bar',
                        smooth: true,
                        symbol:'none',
                        symbolSize:2,
                        showAllSymbol: true,
                        itemStyle: {
                          normal: {
                            color:'<?php echo $getcolor['0']; ?>', 
                            borderWidth:4, borderColor:'<?php echo $getcolor['0']; ?>', 
                            areaStyle: {color:'<?php echo $getcolor['0']; ?>', type: 'default'}
                          }
                        },

                        data: <?php echo $y_unique; ?>
                    },
                    {
                        name: '<?php echo __("Total Visitors","gdwlang"); ?>',
                        type: 'bar',
                        smooth: true,
                        symbol:'none',
                        symbolSize:2,
                        showAllSymbol: true,
                        itemStyle: {
                          normal: {
                            color:'<?php echo $getcolor['1']; ?>', 
                            borderWidth:4, borderColor:'<?php echo $getcolor['1']; ?>', 
                            areaStyle: {color:'<?php echo $getcolor['1']; ?>', type: 'default'}
                          }
                        },

                        data: <?php echo $y_total; ?>
                    },]
            };

        // Load data into the ECharts instance 
        myChart4.setOption(option); 
                    jQuery(window).on('resize', function(){
                      myChart4.resize();
                    });

    </script>


</div>
