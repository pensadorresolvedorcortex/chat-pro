<?php

function wp_show_stats_comments() {

    global $wpdb;
    
    // get total comments
    $totalComments = wp_count_comments();
    
    // Get years that have posts and get comments count per year
    $years = $wpdb->get_results("SELECT YEAR(post_date) AS year FROM " . $wpdb->prefix . "posts 
            WHERE post_type = 'post' AND post_status = 'publish' 
            GROUP BY year DESC");
    $yearWiseArray = array();
    $monthsArray = array();
    //print_r($years);
    // find year wise and month wise comments
    foreach($years as $k => $year){
        
        // year wise
        $yearWiseComments = $wpdb->get_results("
            SELECT YEAR(comment_date) as comment_year, COUNT(comment_ID) as comment_count 
                FROM " . $wpdb->prefix . "comments
                WHERE YEAR(comment_date) =  '" . $year->year . "' AND comment_type = '' 
                GROUP BY comment_year
                ORDER BY comment_date ASC"
        );
        if(!empty($yearWiseComments[0]->comment_year)){
            $yearWiseArray[$yearWiseComments[0]->comment_year] = $yearWiseComments[0]->comment_count;
        }
        
        // month wise
        $monthWiseComments = $wpdb->get_results("
            SELECT MONTH(comment_date) as comment_month, COUNT(comment_ID) as comment_count 
                FROM " . $wpdb->prefix . "comments
                WHERE YEAR(comment_date) =  '" . $year->year . "' AND comment_type = ''
                GROUP BY comment_month
                ORDER BY comment_date ASC"
            );
        
        foreach($monthWiseComments as $mk => $comment){
            $monthWiseArray[$year->year][$comment->comment_month] = $comment->comment_count;
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
    $getcolor = mtrl_dashboard_widget_color();

?>

<?php if(sizeof($monthsArray) > 0){ 

$x_months = "['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']";

$monthsArraycolors = array($getcolor[0],$getcolor[1],$getcolor[2],$getcolor[3],$getcolor[4],$getcolor[5]);

$legendsyear = "";
foreach ($monthsArray as $yeartitle => $monthdata) { $legendsyear .= "'".$yeartitle."', "; }
$legendsyear = substr($legendsyear,0,-2);

?>   
            <div class="chartBox"><?php //print_r($monthsArray); ?>
                <h4 class='widhead'>Comentários por Mês em Cada Ano</h4>
                <div id="mtrl_comments_byMonthYear" style='height:180px;'></div>
            </div>

            <script type="text/javascript">
                    // Initialize after dom ready
                    var myChart2 = echarts.init(document.getElementById('mtrl_comments_byMonthYear')); 
                    
                    var option_monthcomments = {

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
                                data: [<?php echo $legendsyear;  ?>]
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
                                            dataZoom : 'Zoom de Dados',
                                            dataZoomReset : 'Redefinir Zoom'
                                        }
                                    },
                                    dataView : {show: false, readOnly: true},
                                    magicType : {
                                      show: true, 
                                      title : {
                                          line : 'Área',
                                          bar : 'Barra',
                                          stack : 'Barra Empilhada',
                                          tiled: 'Barra Lado a Lado'
                                      },
                                      type: ['line', 'bar','stack','tiled']
                                    },
                                    restore : {show: false},
                                    saveAsImage : {show: true,title:'Salvar como Imagem'}
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
                    myChart2.setOption(option_monthcomments); 
                    jQuery(window).on('resize', function(){
                      myChart2.resize();
                    });

                    //window.onresize = myChart2.resize;
                    
                    //window.onresize = echarts.init(document.getElementById('mtrl_comments_byMonthYear')).resize;

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

      //   echo "". $x_years." ".$y_total; ?>




            <div class="chartBox"><?php //print_r($yearWiseArray); ?>
                <h4 class='widhead'>Comentários por Ano</h4>
                <div id="mtrl_comments_byYear" style='height:180px;'></div>
            </div>


            <script type="text/javascript">
                    // Initialize after dom ready
                   var myChart3 = echarts.init(document.getElementById('mtrl_comments_byYear')); 
                    
                    var option_yr_comment = {

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
                                            dataZoom : 'Zoom de Dados',
                                            dataZoomReset : 'Redefinir Zoom'
                                        }
                                    },
                                    dataView : {show: false, readOnly: true},
                                    magicType : {
                                      show: true, 
                                      title : {
                                          line : 'Área',
                                          bar : 'Barra'
                                      },
                                      type: ['line', 'bar']
                                    },
                                    restore : {show: false},
                                    saveAsImage : {show: true,title:'Salvar como Imagem'}
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
                                    name: 'Comments',
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
                    myChart3.setOption(option_yr_comment); 
                    jQuery(window).on('resize', function(){
                      myChart3.resize();
                    });
                    
                </script>


<?php } ?>






        <?php if(is_array($totalComments) && sizeof($totalComments) > 0){ 
            
            $data_str = "";
            $data_obj = "";
           // if(isset($usersCount['avail_roles']) && sizeof($usersCount['avail_roles']) > 0){
                foreach ($totalComments as $key => $value) {
                    if($key == "all" || $key == "total_comments"){ continue; }
                    if($key == "post-trashed"){ $key = "Trashed with Post"; }
                    if($key == "moderated"){ $key = "Pending"; }

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
            <div class="chartBox"><?php //print_r($totalComments); echo "Total Comments: ". $totalComments->total_comments; ?>
                  <h4 class='widhead'>Contagem e Tipo de Comentários</h4>
                <div id="comments_typewiseChart" style='height:180px;'></div>
            </div>

            <script type="text/javascript">
              // Initialize after dom ready
              var myChart1 = echarts.init(document.getElementById('comments_typewiseChart')); 
                    
              var option_commentwise = {
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
                            name:'Contagem de Comentários',
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
                    myChart1.setOption(option_commentwise); 
                    jQuery(window).on('resize', function(){
                      myChart1.resize();
                    });
                    
                </script>

        <?php } ?>
        





<?php } ?>