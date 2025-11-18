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
    
    $browser_stats = array();
    $datestats = array();


    $entries = $wpdb->get_results( "SELECT  id,browser,knp_date FROM {$wpdb->prefix}gdwwid WHERE knp_ts >= '" . $from_ts . "' AND  knp_ts <= '" . $to_ts . "' ORDER BY id DESC");
 
        $common_browsers = array('Chrome','Firefox','Safari','Opera','Internet-Explorer');

        if( $entries ) { 
            //$count = 1;
            foreach( $entries as $entry ) {
                $index = $entry->browser;
                $date = $entry->knp_date;

                if(!isset($browser_stats[$index])){ 
                    $browser_stats[$index] = 0; 
                }

                if(!isset($datestats[$date])){ 
                    $datestats[$date] = array();
                    $datestats[$date]['Chrome'] = 0;
                    $datestats[$date]['Firefox'] = 0;
                    $datestats[$date]['Safari'] = 0;
                    $datestats[$date]['Opera'] = 0;
                    $datestats[$date]['Internet-Explorer'] = 0;
                    $datestats[$date]['Others'] = 0;
                }

                if(!in_array($index,$common_browsers)){
                    $datestats[$date]['Others'] = $datestats[$date]['Others'] + 1;
                } else{
                    $datestats[$date][$index] = $datestats[$date][$index] + 1;
                }
    
                $browser_stats[$index] = $browser_stats[$index] + 1;

            }

         } 

         $graph_data = "";
         $x_browsers = ""; $x_legend = ""; $y_total = "";

         foreach ($browser_stats as $key => $value) {
            $x_browsers .= "'".$key."', ";
            $x_legend .= "'".$key."', ";
            $y_total .= $value.", ";
         }

         $x_legend = substr($x_legend,0,-2);

         $x_browsers = substr($x_browsers,0,-2);
         $x_browsers = "[".$x_browsers."]";

         $y_total = substr($y_total,0,-2);
         $y_total = "[".$y_total."]";

        $dates_str = "";
        $series_data = array();
         foreach ($datestats as $key => $value) {
             $dates_str .= "'".$key."', ";
             $datastr = "";
                 foreach ($value as $bname => $bcount) {
                    if($bcount == '0'){ $bcount = "'-'";}
                     $datastr .= "{value: ".$bcount.",  name:'".$bname."'}, ";
                 }
                   

                 $datastr = substr($datastr,0,-2);
                 $datastr = "[".$datastr."]";

                $series_data[$key] = $datastr;
         }
         $dates_str = substr($dates_str,0,-2);
         $dates_str = "[".$dates_str."]";
        
         ?>
    

<?php 
    $getcolor = gdw_dashboard_widget_color();
?>



<?php if(sizeof($browser_stats) > 0){ ?>
      <div class="chartBox">
        <h4 class='widhead'><?php _e('Browsers used in last', 'gdwlang');  echo $days; _e('days', 'gdwlang'); ?></h4>
          <div class="" style="height:180px" id="browser_type"></div>
      </div>


<script type="text/javascript">
        // Initialize after dom ready
       var myChart14 = echarts.init(document.getElementById('browser_type')); 
        
        var option = {

                // Setup grid
                grid: {
                    zlevel: 0,
                    x: 30,
                    x2: 50,
                    y: 0,
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
                              line : '<?php echo __("Area","gdwlang"); ?>',
                              bar : '<?php echo __("Bar","gdwlang"); ?>'
                          },
                          type: ['line', 'bar']
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
                    data: <?php echo $x_browsers; ?>,
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
                        name: '<?php echo __("Total Visits","gdwlang"); ?>',
                        type: 'line',
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

                        data: <?php echo $y_total; ?>
                    }]
            };

        // Load data into the ECharts instance 
        myChart14.setOption(option); 
                    jQuery(window).on('resize', function(){
                      myChart14.resize();
                    });


        
    </script>
<?php } else { echo __("No Data Recorded Yet!","gdwlang"); } ?>



<?php if(sizeof($series_data) > 0){ ?>
      <div class="chartBox"><div class='widspacer10'></div>
          <h4 class='widhead'><?php _e('Browsers Used By Date', 'gdwlang'); ?></h4>
          <div class="" style="height:180px" id="browser_type_dates"></div>
      </div>

<script type="text/javascript">
var myChart15 = echarts.init(document.getElementById('browser_type_dates')); 

var idx = 1;
var option_dt = {

    timeline : {
        show: true,
        data : <?php echo $dates_str; ?>,
        label : {
            formatter : function(s) {
                return s.slice(0, 17);
            }
        },
        x:30,
        y:null,
        x2:30,
        y2:0,
        width:null,
        height:50,
        backgroundColor:"rgba(0,0,0,0)",
        borderColor:"#eaeaea",
        borderWidth:0,
        padding:5,
        controlPosition:"left",
        autoPlay:false,
        loop:true,
        playInterval:4000,
        lineStyle:-{
            width:1,
            color:"#bdbdbd",
            type:"dashed"
        },

    },

    options : [
        {
            color: ['<?php echo $getcolor[0]; ?>','<?php echo $getcolor[1]; ?>','<?php echo $getcolor[2]; ?>','<?php echo $getcolor[3]; ?>','<?php echo $getcolor[4]; ?>','<?php echo $getcolor[5]; ?>'],
            title : {
                text: '',
                subtext: ''
            },
            tooltip : {
                trigger: 'item',
                formatter: "{a} <br/>{b} : {c} ({d}%)"
            },
            legend: {
                x: 'left',
                orient:'vertical',
                padding: 0,
                data:['<?php echo __("Chrome","gdwlang"); ?>','<?php echo __("Firefox","gdwlang"); ?>','<?php echo __("Safari","gdwlang"); ?>','<?php echo __("Opera","gdwlang"); ?>','<?php echo __("Internet-Explorer","gdwlang"); ?>','<?php echo __("Others","gdwlang"); ?>']
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


            <?php if(sizeof($series_data) > 0){ 
                $count = 1;
                foreach($series_data as $name => $data){
                    if($count == 1){
                ?>
                            series : [
                                {
                                    name:'<?php echo $name; ?>',
                                    type:'pie',
                                    radius : [20, '80%'],
                                    roseType : 'radius',
                                    center: ['50%', '45%'],
                                    width: '50%',       // for funnel
                                    itemStyle : {
                                        normal : { label : { show : true }, labelLine : { show : true } },
                                        emphasis : { label : { show : false }, labelLine : {show : false} }
                                    },
                                    data:<?php echo $data; ?>
                                }
                            ]
                    }, // end options object
            <?php } else { // end count == 1 condition ?>
                {
                    series : [
                        {
                            name:'<?php echo $name; ?>',
                            type:'pie',
                            data:<?php echo $data; ?>
                        }
                    ]
                },

            <?php
                 } // end else of count == 1 condition                 
            
            $count++;
           } // end for loop
        } // end if condition
     ?>
    ] // end options object
};

myChart15.setOption(option_dt);        
                    jQuery(window).on('resize', function(){
                      myChart15.resize();
                    });


</script>
<?php }  else { echo __("No Data Recorded Yet!","gdwlang"); } ?>





</div>
