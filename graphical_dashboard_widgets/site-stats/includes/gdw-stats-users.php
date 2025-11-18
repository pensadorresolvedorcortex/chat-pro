<?php

function wp_show_stats_users() {

    global $wpdb;
    global $wp_roles;

    // get role wise users
    $usersCount = count_users();
    
    // get total users count
    $totalUsers = count(get_users());
    
    // Get years of registered users
    $years = $wpdb->get_results("SELECT YEAR(user_registered) AS year FROM " . $wpdb->prefix . "users GROUP BY year DESC");
    $yearWiseArray = array();
    $monthsArray = array();
    // find year wise and month wise comments
    foreach($years as $k => $year){
        
        // year wise
        $yearWiseUsers = $wpdb->get_results("
            SELECT YEAR(user_registered) as users_year, COUNT(ID) as users_count 
                FROM " . $wpdb->prefix . "users 
                WHERE YEAR(user_registered) =  '" . $year->year . "' 
                GROUP BY users_year
                ORDER BY user_registered ASC"
        );
        if(!empty($yearWiseUsers[0]->users_year)){
            $yearWiseArray[$yearWiseUsers[0]->users_year] = $yearWiseUsers[0]->users_count;
        }


        // month wise
        $monthWiseUsers = $wpdb->get_results("
            SELECT MONTH(user_registered) as users_month, COUNT(ID) as users_count 
                FROM " . $wpdb->prefix . "users
                WHERE YEAR(user_registered) =  '" . $year->year . "' 
                GROUP BY users_month
                ORDER BY user_registered ASC"
            );
        
        foreach($monthWiseUsers as $mk => $usr){
            $monthWiseArray[$year->year][$usr->users_month] = $usr->users_count;
        }
    }
    // make the string of month wise comments according to chart's requirements
   foreach($monthWiseArray as $y => $arr){
       $test_arr = array();
       for($i = 1; $i<=12; $i++){
           $test_arr[$i] = isset($arr[$i]) ? $arr[$i] : 0;
       }
       $monthsArray[$y] = implode(",", $test_arr);
   }


?>

<?php 
    $getcolor = gdw_dashboard_widget_color();

?>



<?php if(sizeof($monthsArray) > 0){ 

$x_months = "['".__('Jan','gdwlang')."', '".__('Feb','gdwlang')."', '".__('Mar','gdwlang')."','".__('Apr','gdwlang')."', '".__('May','gdwlang')."', '".__('Jun','gdwlang')."', '".__('Jul','gdwlang')."', '".__('Aug','gdwlang')."', '".__('Sep','gdwlang')."', '".__('Oct','gdwlang')."', '".__('Nov','gdwlang')."', '".__('Dec','gdwlang')."']";

$monthsArraycolors = array($getcolor[0],$getcolor[1],$getcolor[2],$getcolor[3],$getcolor[4],$getcolor[5]);

$legendsyear = "";
foreach ($monthsArray as $yeartitle => $monthdata) { $legendsyear .= "'".$yeartitle."', "; }
$legendsyear = substr($legendsyear,0,-2);

?>    
            <div class="chartBox"><?php //print_r($monthsArray); ?>
                <h4 class='widhead'><?php echo __("User Registrations By Months in Each Year","gdwlang"); ?></h4>
                <div id="gdw_users_byMonthYear" style='height:180px;'></div>
            </div>


<script type="text/javascript">
        // Initialize after dom ready
        var myChart11 = echarts.init(document.getElementById('gdw_users_byMonthYear')); 
        
        var option_month = {

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
                    data: [<?php echo $legendsyear; ?>]
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
                    data: <?php echo $x_months; ?>,
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

                series: [
                <?php 
                $monthyear = 0;
                foreach ($monthsArray as $yeartitle => $monthdata) { 
                    
                ?>
                    {
                        name: '<?php echo $yeartitle; ?>',
                        type: 'bar',
                        smooth: true,
                        symbol:'none',
                        symbolSize:2,
                        showAllSymbol: true,
                        itemStyle: {
                          normal: {
                            color:'<?php echo $monthsArraycolors[$monthyear]; ?>', 
                            borderWidth:2, borderColor:'<?php echo $monthsArraycolors[$monthyear]; ?>', 
                            areaStyle: {color:'<?php echo $monthsArraycolors[$monthyear]; ?>', type: 'default'}
                          }
                        },

                        data: [<?php echo $monthdata; ?>]
                    },
                <?php 
                    $monthyear++; 
                } ?>
                    ]
            };

        // Load data into the ECharts instance 
        myChart11.setOption(option_month); 
                    jQuery(window).on('resize', function(){
                      myChart11.resize();
                    });
        
    </script>



<?php } ?>            

<?php 

if(sizeof($yearWiseArray) > 0){

        $x_years = ""; $y_total = "";

         foreach ($yearWiseArray as $key => $value) {
            $x_years .= "'".$key."', ";
            $y_total .= $value.", ";
         }

         $x_years = substr($x_years,0,-2);
         $x_years = "[".$x_years."]";

         $y_total = substr($y_total,0,-2);
         $y_total = "[".$y_total."]";

       //  echo "". $x_years." ".$y_total; ?>




            <br><br><div class="chartBox"><?php //print_r($yearWiseArray); ?>
                <h4 class='widhead'><?php echo __("User Registrations By Year","gdwlang"); ?></h4>
                <div id="gdw_users_byYear" style='height:180px;'></div>
            </div>


            <script type="text/javascript">
                    // Initialize after dom ready
                   var myChart12 = echarts.init(document.getElementById('gdw_users_byYear')); 
                    
                    var option_yr = {

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
                                data: <?php echo $x_years; ?>,
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
                                    name: '<?php echo __("User Registrations","gdwlang"); ?>',
                                    type: 'line',
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

                                    data: <?php echo $y_total; ?>
                                }]
                        };

                    // Load data into the ECharts instance 
                    myChart12.setOption(option_yr);
                    jQuery(window).on('resize', function(){
                      myChart12.resize();
                    });
                     
                </script>


<?php } ?>







        <?php if($totalUsers > 0){ 
            
            $data_str = "";
            $data_obj = "";
            if(isset($usersCount['avail_roles']) && sizeof($usersCount['avail_roles']) > 0){
                foreach ($usersCount['avail_roles'] as $key => $value) {
                    $data_str .= "'".ucfirst($key)."', ";

                    if($value == '0'){ $value = "'-'";}
                     $data_obj .= "{value: ".$value.",  name:'".ucfirst($key)."'}, ";
                }

                 $data_str = substr($data_str,0,-2);
                 $data_str = "[".$data_str."]";

                 $data_obj = substr($data_obj,0,-2);
                 $data_obj = "[".$data_obj."]";

            }
        ?>
           <br><br> <div class="chartBox"><?php //print_r($usersCount); ?>
                <h4 class='widhead'><?php echo __("User Count and Type","gdwlang"); ?></h4>
                <div id="rolewiseChart" style='height:180px;'></div>
            </div>

            <script type="text/javascript">
              // Initialize after dom ready
              var myChart13 = echarts.init(document.getElementById('rolewiseChart')); 
                    
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
                                        title : {
                                          pie : '<?php echo __("Pie","gdwlang"); ?>',
                                          funnel : '<?php echo __("Funnel","gdwlang"); ?>',
                                        }, 
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
                                     saveAsImage : {show: true,title:'<?php echo __("Save as Image","gdwlang"); ?>'}
                        }
                    },
                    calculable : true,
                    series : [
                        {
                            name:'<?php echo __("User Count","gdwlang"); ?>',
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
                    myChart13.setOption(option); 
                    jQuery(window).on('resize', function(){
                      myChart13.resize();
                    });
                    
                </script>

        <?php } ?>
        

<?php } ?>