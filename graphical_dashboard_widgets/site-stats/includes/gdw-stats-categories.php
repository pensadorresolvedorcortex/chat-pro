<?php

function wp_show_stats_categories() {

    global $wpdb;
    
    // get total category
    $totalCategory = wp_count_terms('category');
    $mostUsedcategories = $lessUsedcategories = array();
    // find most and less used category
    $mostArgs=array('orderby' => 'count','order' => 'DESC','hide_empty' => 0,'number' => 5);
    $mostUsedcategories=get_categories($mostArgs);
    $lessArgs=array('orderby' => 'count','order' => 'ASC','hide_empty' => 0,'number' => 5);
    $lessUsedcategories=get_categories($lessArgs);
    
?>

    
<?php 
    $getcolor = gdw_dashboard_widget_color();

?>

       
        <div class="stat-charts-main"><?php // echo "Total: "; print_r($totalCategory); ?>
            <div class="chartBoxLarge"><?php // echo "<pre>Most used: "; //print_r($mostUsedcategories); ?>
                <div id="mostUsedChart"></div>
            </div>
            <div class="chartBoxLarge"><?php // echo "<pre>Less Used: ";// print_r($lessUsedcategories); ?>
                <div id="lessUsedChart"></div>
            </div>
        </div>
    

<?php if(sizeof($mostUsedcategories) > 0){

    $mostcat = array();
    $most_x = $most_y = "";
    foreach ($mostUsedcategories as $key => $value) {
        $mostcat[$value->name] = $value->count;
        $most_x .= "'".$value->name."', ";
        $most_y .= "".$value->count.", ";
    } 

    $most_x = substr($most_x,0,-2);
    $most_x = "[".$most_x."]";
    $most_y = substr($most_y,0,-2);
    $most_y = "[".$most_y."]";

   // echo $most_x."<br>";
   // echo $most_y."<br>";    
   // print_r($mostcat);
?>

            <div class="chartBox"><?php //print_r($yearWiseArray); ?>
                <h4 class='widhead'><?php echo __("Most Used Categories","gdwlang"); ?></h4>
                <div id="gdw_mostcats_byYear" style='height:180px;'></div>
            </div>


            <script type="text/javascript">
                    // Initialize after dom ready
                   var myChart5 = echarts.init(document.getElementById('gdw_mostcats_byYear')); 
                    
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
                                data: ['']
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
                                data: <?php echo $most_x; ?>,
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
                                    name: '<?php echo __("Post in Category","gdwlang"); ?>',
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

                                    data: <?php echo $most_y; ?>
                                }]
                        };

                    // Load data into the ECharts instance 
                    myChart5.setOption(option_yr); 
                    jQuery(window).on('resize', function(){
                      myChart5.resize();
                    });
                    
                </script>



<?php } ?>



<?php if(sizeof($lessUsedcategories) > 0){

    $lesscat = array();
    $less_x = $less_y = "";
    foreach ($lessUsedcategories as $key => $value) {
        $lesscat[$value->name] = $value->count;
        $less_x .= "'".$value->name."', ";
        $less_y .= "".$value->count.", ";
    } 

    $less_x = substr($less_x,0,-2);
    $less_x = "[".$less_x."]";
    $less_y = substr($less_y,0,-2);
    $less_y = "[".$less_y."]";

  //  echo $less_x."<br>";
 //   echo $less_y."<br>";    
  //  print_r($lesscat);
?>

            <br><br><div class="chartBox"><?php //print_r($yearWiseArray); ?>
                <h4 class='widhead'><?php echo __("Least Used Categories","gdwlang"); ?></h4>
                <div id="gdw_lesscats_byYear" style='height:180px;'></div>
            </div>


            <script type="text/javascript">
                    // Initialize after dom ready
                   var myChart6 = echarts.init(document.getElementById('gdw_lesscats_byYear')); 
                    
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
                                data: ['']
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
                                data: <?php echo $less_x; ?>,
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
                                    name: '<?php echo __("Post in Category","gdwlang"); ?>',
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

                                    data: <?php echo $less_y; ?>
                                }]
                        };

                    // Load data into the ECharts instance 
                    myChart6.setOption(option_yr); 
                    jQuery(window).on('resize', function(){
                      myChart6.resize();
                    });
                    
                </script>



<?php } ?>


<?php } ?>