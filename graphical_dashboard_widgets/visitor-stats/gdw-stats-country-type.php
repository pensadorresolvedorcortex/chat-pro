<div class="">


<?php
    global $wpdb;
    $gmt_offset = get_option('gmt_offset');
    //$gmt_offset = 5.5;
    $days = 30;
    $timezoneName = timezone_name_from_abbr("", ($gmt_offset*3600), false);

    $today_date = date("Y-m-d");
    $curr_ts = strtotime('+'.$gmt_offset.' hour');
    $curr_date = date('Y-m-d', $curr_ts);

    $get_curr_ts = DateTime::createFromFormat('Y-m-d', $curr_date, new DateTimeZone($timezoneName));
    $curr_ts = $get_curr_ts->getTimestamp();

    $to_ts = ($curr_ts); // -1 day
    $from_ts = ($to_ts - ((24 * 60 * 60) * $days)); // - 30 days
    $to_date = date('Y-m-d', $to_ts);
    $from_date = date('Y-m-d', $from_ts);

    $get_to_ts = DateTime::createFromFormat('Y-m-d', $to_date, new DateTimeZone($timezoneName));
    $to_ts = $get_to_ts->getTimestamp();

    $get_from_ts = DateTime::createFromFormat('Y-m-d', $from_date, new DateTimeZone($timezoneName));
    $from_ts = $get_from_ts->getTimestamp();
    


    $entries = $wpdb->get_results( "SELECT  id,countryName FROM {$wpdb->prefix}gdwwid WHERE knp_ts >= '" . $from_ts . "' AND  knp_ts <= '" . $to_ts . "' ORDER BY id DESC");
 
    $country_list = array('Afghanistan','Angola','Albania','United Arab Emirates','Argentina','Armenia','French Southern and Antarctic Lands','Australia','Austria','Azerbaijan','Burundi','Belgium','Benin','Burkina Faso','Bangladesh','Bulgaria','The Bahamas','Bosnia and Herzegovina','Belarus','Belize','Bermuda','Bolivia','Brazil','Brunei','Bhutan','Botswana','Central African Republic','Canada','Switzerland','Chile','China','Ivory Coast','Cameroon','Democratic Republic of the Congo','Republic of the Congo','Colombia','Costa Rica','Cuba','Northern Cyprus','Cyprus','Czech Republic','Germany','Djibouti','Denmark','Dominican Republic','Algeria','Ecuador','Egypt','Eritrea','Spain','Estonia','Ethiopia','Finland','Fiji','Falkland Islands','France','Gabon','United Kingdom','Georgia','Ghana','Guinea','Gambia','Guinea Bissau','Equatorial Guinea','Greece','Greenland','Guatemala','French Guiana','Guyana','Honduras','Croatia','Haiti','Hungary','Indonesia','India','Ireland','Iran','Iraq','Iceland','Israel','Italy','Jamaica','Jordan','Japan','Kazakhstan','Kenya','Kyrgyzstan','Cambodia','South Korea','Kosovo','Kuwait','Laos','Lebanon','Liberia','Libya','Sri Lanka','Lesotho','Lithuania','Luxembourg','Latvia','Morocco','Moldova','Madagascar','Mexico','Macedonia','Mali','Myanmar','Montenegro','Mongolia','Mozambique','Mauritania','Malawi','Malaysia','Namibia','New Caledonia','Niger','Nigeria','Nicaragua','Netherlands','Norway','Nepal','New Zealand','Oman','Pakistan','Panama','Peru','Philippines','Papua New Guinea','Poland','Puerto Rico','North Korea','Portugal','Paraguay','Qatar','Romania','Russia','Rwanda','Western Sahara','Saudi Arabia','Sudan','South Sudan','Senegal','Solomon Islands','Sierra Leone','El Salvador','Somaliland','Somalia','Republic of Serbia','Suriname','Slovakia','Slovenia','Sweden','Swaziland','Syria','Chad','Togo','Thailand','Tajikistan','Turkmenistan','East Timor','Trinidad and Tobago','Tunisia','Turkey','United Republic of Tanzania','Uganda','Ukraine','Uruguay','United States of America','Uzbekistan','Venezuela','Vietnam','Vanuatu','West Bank','Yemen','South Africa','Zambia','Zimbabwe');

    $country_stats = array();
    foreach ($country_list as $cname) {
        $country_stats[$cname] = 0;
    }

        if( $entries ) { 
            //$count = 1;
            foreach( $entries as $entry ) {
                $index = $entry->countryName;
                if(trim($index) != ""){
                    if(!isset($country_stats[$index])){ 
                        $country_stats[$index] = 0; 
                    }

                    $country_stats[$index] = $country_stats[$index] + 1;
                }

            }

         } 

         $x_countries = "";
         $max = 100;
         foreach ($country_stats as $key => $value) {
            $x_countries .= "{name : '".$key."', value : ".$value."}, ";
            if($value > $max){ $max = $value; }
         }

            if ($max % 100){
                     $max = $max + (100 - $max % 100);
            }
         //echo $max;

         $x_countries = substr($x_countries,0,-2);
         $x_countries = "[".$x_countries."]";

         //echo "<pre>"; echo $x_countries."<pre>"; 

         ?>
    

      <div class="chartBox">
        <h4 class="widhead"><?php echo __("Visits (by Country) in last","gdwlang"); ?> <?php echo $days; ?> <?php echo __("days","gdwlang"); ?></h4>
          <div class="" style="height:300px" id="country_type"></div>
      </div>

<?php 
    $getcolor = gdw_dashboard_widget_color();
?>


<script type="text/javascript">
        // Initialize after dom ready
        var myChart16 = echarts.init(document.getElementById('country_type')); 
        
        var option = {

                tooltip : {
                        trigger: 'item',
                        formatter : function (params) {
                            return params.seriesName + '<br/>' + params.name + ' : ' + params.value;
                        },
                        transitionDuration: 0,
                        showDelay: 0,
                        hideDelay: 0,
                        position: [80,400],
                        enterable: false
                    },
                clickable: false,
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
                        /*dataZoom : {
                            show : true,
                            title : {
                                dataZoom : '<?php echo __("Data Zoom","gdwlang"); ?>',
                                dataZoomReset : '<?php echo __("Reset Zoom","gdwlang"); ?>'
                            }
                        },*/
                        dataView : {show: false, readOnly: true},
                        /*magicType : {
                          show: true, 
                          title : {
                              line : '<?php echo __("Area","gdwlang"); ?>',
                              bar : '<?php echo __("Bar","gdwlang"); ?>'
                          },
                          type: ['line', 'bar']
                        },*/
                        restore : {show: false},
                        saveAsImage : {show: true,title:'<?php echo __("Save as Image","gdwlang"); ?>'}
                    }
                },
                dataRange: {
                        min: 0,
                        max: <?php echo $max; ?>,
                        text:['High','Low'],
                        realtime: false,
                        calculable : true,
                        color: ['<?php echo $getcolor[0]; ?>','<?php echo $getcolor[1]; ?>','#bdbdbd']
                    },

                // Add series
                series: [
                    {
                        name: '<?php echo __("Visits in last","gdwlang"); ?> <?php echo $days; ?> <?php echo __("days","gdwlang"); ?>',
                        type: 'map',
                        mapType: 'world',
                        roam: true,
                        mapLocation: {
                            
                        },
                        itemStyle:{
                            emphasis:{label:{show:true}}
                        },
                        data:<?php echo $x_countries; ?>
                    }
    ]
 };

        // Load data into the ECharts instance 
        myChart16.setOption(option); 
                    jQuery(window).on('resize', function(){
                      myChart16.resize();
                    });
        
        
    </script>


</div>
