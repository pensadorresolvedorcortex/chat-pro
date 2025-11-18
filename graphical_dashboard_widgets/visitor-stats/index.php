<?php
//echo "called2";
require_once( plugin_dir_path( __FILE__ ) . 'includes/Browser.php');
/*Uncomment GeoPlugin*/
//require_once( plugin_dir_path( __FILE__ ) . 'includes/geoplugin.class.php');


define('GDW_VISITOR_STATS_PATH', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
function gdwwid_init_scripts()
{
		//wp_enqueue_script('jquery');
		wp_enqueue_style('gdw-wid-style', GDW_VISITOR_STATS_PATH.'css/style.min.css');
		//wp_enqueue_style('gdw-wid-flags', GDW_VISITOR_STATS_PATH.'css/flags.css');
		wp_enqueue_script('gdw-wid-js', plugins_url( '/js/scripts.js' , __FILE__ ) , array( 'jquery' ));
		wp_localize_script( 'gdw-wid-js', 'gdwwid_ajax', array( 'gdwwid_ajaxurl' => admin_url( 'admin-ajax.php') ));
}

function gdwwid_init_scripts_frontend(){
    global $gdwadmin;
	if(isset($gdwadmin['front-usertracking']) && $gdwadmin['front-usertracking'] == "yes"){ 
		wp_enqueue_script('gdw-wid-js', plugins_url( '/js/scripts-front.js' , __FILE__ ) , array( 'jquery' ));
		wp_localize_script( 'gdw-wid-js', 'gdwwid_ajax', array( 'gdwwid_ajaxurl' => admin_url( 'admin-ajax.php') ));
		//echo "ontrack";
	} else {
		//echo "offtrack";
	}

}


function gdwwid_countryname($countrycode = "")
{

	$allcountries = '{"BD": "Bangladesh", "BE": "Belgium", "BF": "Burkina Faso", "BG": "Bulgaria", "BA": "Bosnia and Herzegovina", "BB": "Barbados", "WF": "Wallis and Futuna", "BL": "Saint Barthelemy", "BM": "Bermuda", "BN": "Brunei", "BO": "Bolivia", "BH": "Bahrain", "BI": "Burundi", "BJ": "Benin", "BT": "Bhutan", "JM": "Jamaica", "BV": "Bouvet Island", "BW": "Botswana", "WS": "Samoa", "BQ": "Bonaire, Saint Eustatius and Saba ", "BR": "Brazil", "BS": "Bahamas", "JE": "Jersey", "BY": "Belarus", "BZ": "Belize", "RU": "Russia", "RW": "Rwanda", "RS": "Serbia", "TL": "East Timor", "RE": "Reunion", "TM": "Turkmenistan", "TJ": "Tajikistan", "RO": "Romania", "TK": "Tokelau", "GW": "Guinea-Bissau", "GU": "Guam", "GT": "Guatemala", "GS": "South Georgia and the South Sandwich Islands", "GR": "Greece", "GQ": "Equatorial Guinea", "GP": "Guadeloupe", "JP": "Japan", "GY": "Guyana", "GG": "Guernsey", "GF": "French Guiana", "GE": "Georgia", "GD": "Grenada", "GB": "United Kingdom", "GA": "Gabon", "SV": "El Salvador", "GN": "Guinea", "GM": "Gambia", "GL": "Greenland", "GI": "Gibraltar", "GH": "Ghana", "OM": "Oman", "TN": "Tunisia", "JO": "Jordan", "HR": "Croatia", "HT": "Haiti", "HU": "Hungary", "HK": "Hong Kong", "HN": "Honduras", "HM": "Heard Island and McDonald Islands", "VE": "Venezuela", "PR": "Puerto Rico", "PS": "Palestinian Territory", "PW": "Palau", "PT": "Portugal", "SJ": "Svalbard and Jan Mayen", "PY": "Paraguay", "IQ": "Iraq", "PA": "Panama", "PF": "French Polynesia", "PG": "Papua New Guinea", "PE": "Peru", "PK": "Pakistan", "PH": "Philippines", "PN": "Pitcairn", "PL": "Poland", "PM": "Saint Pierre and Miquelon", "ZM": "Zambia", "EH": "Western Sahara", "EE": "Estonia", "EG": "Egypt", "ZA": "South Africa", "EC": "Ecuador", "IT": "Italy", "VN": "Vietnam", "SB": "Solomon Islands", "ET": "Ethiopia", "SO": "Somalia", "ZW": "Zimbabwe", "SA": "Saudi Arabia", "ES": "Spain", "ER": "Eritrea", "ME": "Montenegro", "MD": "Moldova", "MG": "Madagascar", "MF": "Saint Martin", "MA": "Morocco", "MC": "Monaco", "UZ": "Uzbekistan", "MM": "Myanmar", "ML": "Mali", "MO": "Macao", "MN": "Mongolia", "MH": "Marshall Islands", "MK": "Macedonia", "MU": "Mauritius", "MT": "Malta", "MW": "Malawi", "MV": "Maldives", "MQ": "Martinique", "MP": "Northern Mariana Islands", "MS": "Montserrat", "MR": "Mauritania", "IM": "Isle of Man", "UG": "Uganda", "TZ": "Tanzania", "MY": "Malaysia", "MX": "Mexico", "IL": "Israel", "FR": "France", "IO": "British Indian Ocean Territory", "SH": "Saint Helena", "FI": "Finland", "FJ": "Fiji", "FK": "Falkland Islands", "FM": "Micronesia", "FO": "Faroe Islands", "NI": "Nicaragua", "NL": "Netherlands", "NO": "Norway", "NA": "Namibia", "VU": "Vanuatu", "NC": "New Caledonia", "NE": "Niger", "NF": "Norfolk Island", "NG": "Nigeria", "NZ": "New Zealand", "NP": "Nepal", "NR": "Nauru", "NU": "Niue", "CK": "Cook Islands", "XK": "Kosovo", "CI": "Ivory Coast", "CH": "Switzerland", "CO": "Colombia", "CN": "China", "CM": "Cameroon", "CL": "Chile", "CC": "Cocos Islands", "CA": "Canada", "CG": "Republic of the Congo", "CF": "Central African Republic", "CD": "Democratic Republic of the Congo", "CZ": "Czech Republic", "CY": "Cyprus", "CX": "Christmas Island", "CR": "Costa Rica", "CW": "Curacao", "CV": "Cape Verde", "CU": "Cuba", "SZ": "Swaziland", "SY": "Syria", "SX": "Sint Maarten", "KG": "Kyrgyzstan", "KE": "Kenya", "SS": "South Sudan", "SR": "Suriname", "KI": "Kiribati", "KH": "Cambodia", "KN": "Saint Kitts and Nevis", "KM": "Comoros", "ST": "Sao Tome and Principe", "SK": "Slovakia", "KR": "South Korea", "SI": "Slovenia", "KP": "North Korea", "KW": "Kuwait", "SN": "Senegal", "SM": "San Marino", "SL": "Sierra Leone", "SC": "Seychelles", "KZ": "Kazakhstan", "KY": "Cayman Islands", "SG": "Singapore", "SE": "Sweden", "SD": "Sudan", "DO": "Dominican Republic", "DM": "Dominica", "DJ": "Djibouti", "DK": "Denmark", "VG": "British Virgin Islands", "DE": "Germany", "YE": "Yemen", "DZ": "Algeria", "US": "United States", "UY": "Uruguay", "YT": "Mayotte", "UM": "United States Minor Outlying Islands", "LB": "Lebanon", "LC": "Saint Lucia", "LA": "Laos", "TV": "Tuvalu", "TW": "Taiwan", "TT": "Trinidad and Tobago", "TR": "Turkey", "LK": "Sri Lanka", "LI": "Liechtenstein", "LV": "Latvia", "TO": "Tonga", "LT": "Lithuania", "LU": "Luxembourg", "LR": "Liberia", "LS": "Lesotho", "TH": "Thailand", "TF": "French Southern Territories", "TG": "Togo", "TD": "Chad", "TC": "Turks and Caicos Islands", "LY": "Libya", "VA": "Vatican", "VC": "Saint Vincent and the Grenadines", "AE": "United Arab Emirates", "AD": "Andorra", "AG": "Antigua and Barbuda", "AF": "Afghanistan", "AI": "Anguilla", "VI": "U.S. Virgin Islands", "IS": "Iceland", "IR": "Iran", "AM": "Armenia", "AL": "Albania", "AO": "Angola", "AQ": "Antarctica", "AS": "American Samoa", "AR": "Argentina", "AU": "Australia", "AT": "Austria", "AW": "Aruba", "IN": "India", "AX": "Aland Islands", "AZ": "Azerbaijan", "IE": "Ireland", "ID": "Indonesia", "UA": "Ukraine", "QA": "Qatar", "MZ": "Mozambique"}';
	$allcountries_obj = json_decode($allcountries);	

	$countryName = "";
	if($countrycode != ""){
		$countryName = $allcountries_obj->$countrycode; // -> "US"
		return $countryName;
	}
	return "";
//	return "India";

}

function gdwwid_ajax_online_visit_info(){	
	
	$countryName = gdwwid_countryname($_POST['countrycode']);
	$ip = $_POST['ip'];
	$city = $_POST['city'];
	$region = $_POST['region'];
	gdwwid_visit($ip,$countryName,$city,$region);
	die();
}
add_action('wp_ajax_gdwwid_ajax_online_visit_info', 'gdwwid_ajax_online_visit_info');
add_action('wp_ajax_nopriv_gdwwid_ajax_online_visit_info', 'gdwwid_ajax_online_visit_info');

function gdwwid_visit($ip = "",$countryName = "",$city = "",$region = ""){

	$knp_date = gdwwid_get_date();
	$knp_time = gdwwid_get_time();
	$knp_ts = gdwwid_get_ts();
	$knp_datetime = gdwwid_get_datetime();	
	$duration = $knp_datetime;
	
	$browser = new Browser_GDW();
	$platform = $browser->getPlatform();
	$browser = $browser->getBrowser();
	

	$referer = gdwwid_get_referer();
	$referer = explode(',',$referer);
	$referer_doamin = $referer['0'];
	$referer_url = $referer['1'];

	$screensize = gdwwid_get_screensize();

	$userid = gdwwid_getuser();
	$url_id_array = gdwwid_geturl_id();
	$url_id_array = explode(',',$url_id_array);
	$url_id = $url_id_array['0'];
	$url_term = $url_id_array['1'];
	
	$event = "visit";
	
	$isunique = gdwwid_get_unique();
	$landing = gdwwid_landing();
	$knp_session_id = gdwwid_session();
	
	
	global $wpdb;
	$table = $wpdb->prefix . "gdwwid";
		
	gdw_admin_activation();


	// Insert table queries

	$wpdb->query( $wpdb->prepare("INSERT INTO $table 
								( id, session_id, knp_date, knp_time, knp_ts, duration, userid, event, browser, platform, ip, city, region, countryName, url_id, url_term, referer_doamin, referer_url, screensize, isunique, landing )
			VALUES	( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
						array	( '', $knp_session_id, $knp_date, $knp_time, $knp_ts, $duration, $userid, $event, $browser, $platform, $ip, $city, $region, $countryName, $url_id, $url_term, $referer_doamin, $referer_url, $screensize, $isunique, $landing )
								));
		
	$table = $wpdb->prefix . "gdwwid_online";	
	$result = $wpdb->get_results("SELECT * FROM $table WHERE session_id='$knp_session_id'", ARRAY_A);
	$count = $wpdb->num_rows;

	if($count==NULL)
		{
	$wpdb->query( $wpdb->prepare("INSERT INTO $table 
								( id, session_id, knp_time, knp_ts, userid, url_id, url_term, city, region, countryName, browser, platform, referer_doamin, referer_url) VALUES	(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							array( '', $knp_session_id, $knp_datetime, $knp_ts, $userid, $url_id, $url_term, $city, $region, $countryName, $browser, $platform, $referer_doamin, $referer_url)
								));
		}
	else
		{
			$wpdb->query("UPDATE $table SET knp_time='$knp_datetime', knp_ts='$knp_ts', url_id='$url_id', referer_doamin='$referer_doamin', referer_url='$referer_url' WHERE session_id='$knp_session_id'");
		}					
}


function gdwwid_login($user_login, $user){
	$knp_date = gdwwid_get_date();
	$knp_time = gdwwid_get_time();
	$knp_ts = gdwwid_get_ts();
	$knp_datetime = gdwwid_get_datetime();	
	$duration = $knp_datetime;
	
	$browser = new Browser_GDW();
	$platform = $browser->getPlatform();
	$browser = $browser->getBrowser();
	
	$ip = $_SERVER['REMOTE_ADDR'];
	
	$city = "";
	$region = "";
	$countryName = "";

	$referer = gdwwid_get_referer();
	$referer = explode(',',$referer);
	$referer_doamin = $referer['0'];
	$referer_url = $referer['1'];

	$screensize = gdwwid_get_screensize();

	$userdet = get_user_by("login",$user_login);

	$userid = $userdet->ID; //get_current_user_id(); // $userid->ID;

	$url_id_array = gdwwid_geturl_id();
	$url_id_array = explode(',',$url_id_array);
	$url_id = $url_id_array['0'];
	$url_term = $url_id_array['1'];

	$event = "login";

	$isunique = gdwwid_get_unique();
	$landing = '0'; //gdwwid_landing() headers already sent problem
	$knp_session_id = gdwwid_session();
	
	global $wpdb;
	$table = $wpdb->prefix . "gdwwid";
		
	$wpdb->query( $wpdb->prepare("INSERT INTO $table 
								( id, session_id, knp_date, knp_time, knp_ts, duration, userid, event, browser, platform, ip, city, region, countryName, url_id, url_term, referer_doamin, referer_url, screensize, isunique, landing )
			VALUES	( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
						array	( '', $knp_session_id, $knp_date, $knp_time, $knp_ts, $duration, $userid, $event, $browser, $platform, $ip, $city, $region, $countryName, $url_id, $url_term, $referer_doamin, $referer_url, $screensize, $isunique, $landing )
								));
		
	$table = $wpdb->prefix . "gdwwid_online";
	$result = $wpdb->get_results("SELECT * FROM $table WHERE session_id='$knp_session_id'", ARRAY_A);
	$count = $wpdb->num_rows;

	if($count==NULL){
	$wpdb->query( $wpdb->prepare("INSERT INTO $table ( id, session_id, knp_time, knp_ts, userid, url_id, url_term, city, region, countryName, browser, platform, referer_doamin, referer_url) VALUES	(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							array( '', $knp_session_id, $knp_datetime, $knp_ts, $userid, $url_id, $url_term, $city, $region, $countryName, $browser, $platform, $referer_doamin, $referer_url)
						));
	} else {
		$wpdb->query("UPDATE $table SET knp_time='$knp_datetime', knp_ts='$knp_ts', url_id='$url_id', referer_doamin='$referer_doamin', referer_url='$referer_url' WHERE session_id='$knp_session_id'");
	}
}

//add_action('wp_login', 'gdwwid_login', 10, 2);

function gdwwid_logout(){
	$knp_date = gdwwid_get_date();
	$knp_time = gdwwid_get_time();
	$knp_ts = gdwwid_get_ts();
	$knp_datetime = gdwwid_get_datetime();	
	$duration = $knp_datetime;
	
	$browser = new Browser_GDW();
	$platform = $browser->getPlatform();
	$browser = $browser->getBrowser();
	
	$ip = $_SERVER['REMOTE_ADDR'];
	
	$city = "";
	$region = "";
	$countryName = "";

	
	$referer = gdwwid_get_referer();
	$referer = explode(',',$referer);
	$referer_doamin = $referer['0'];
	$referer_url = $referer['1'];

	$screensize = gdwwid_get_screensize();

	$userid = gdwwid_getuser();

	$url_id_array = gdwwid_geturl_id();
	$url_id_array = explode(',',$url_id_array);
	$url_id = $url_id_array['0'];
	$url_term = $url_id_array['1'];

	$event = "logout";

	$isunique = 'no';
	$landing = '0'; //gdwwid_landing() headers already sent problem
	$knp_session_id = gdwwid_session();
	
	
	global $wpdb;
	$table = $wpdb->prefix . "gdwwid";
		
	$wpdb->query( $wpdb->prepare("INSERT INTO $table 
								( id, session_id, knp_date, knp_time, knp_ts, duration, userid, event, browser, platform, ip, city, region, countryName, url_id, url_term, referer_doamin, referer_url, screensize, isunique, landing )
			VALUES	( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )",
						array	( '', $knp_session_id, $knp_date, $knp_time, $knp_ts, $duration, $userid, $event, $browser, $platform, $ip, $city, $region, $countryName, $url_id, $url_term, $referer_doamin, $referer_url, $screensize, $isunique, $landing )
								));
		
		


	$table = $wpdb->prefix . "gdwwid_online";	
	$result = $wpdb->get_results("SELECT * FROM $table WHERE session_id='$knp_session_id'", ARRAY_A);
	$count = $wpdb->num_rows;


 

	if($count==NULL)
		{
	$wpdb->query( $wpdb->prepare("INSERT INTO $table 
								( id, session_id, knp_time, knp_ts, userid, url_id, url_term, city, region, countryName, browser, platform, referer_doamin, referer_url) VALUES	(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
							array( '', $knp_session_id, $knp_datetime, $knp_ts, $userid, $url_id, $url_term, $city, $region, $countryName, $browser, $platform, $referer_doamin, $referer_url)
								));
		}
	else
		{
			$wpdb->query("UPDATE $table SET knp_time='$knp_datetime', knp_ts='$knp_ts', url_id='$url_id', referer_doamin='$referer_doamin', referer_url='$referer_url' WHERE session_id='$knp_session_id'");
		}
			
}


function gdwwid_register_session(){
    if( !session_id() )
        session_start();
}
add_action('init','gdwwid_register_session');


function gdwwid_session(){
	$knp_session_id = session_id();
	return $knp_session_id;
}


function gdwwid_ajax_online_total(){	
		global $wpdb;
		$table = $wpdb->prefix . "gdwwid_online";	
		$count_online = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
		$count_online = $wpdb->num_rows;
		
		echo $count_online;

		$time = date("Y-m-d H:i:s", strtotime(gdwwid_get_datetime()." -120 seconds"));
		$wpdb->query("DELETE FROM $table WHERE knp_time < '$time' ");

		die();
}
add_action('wp_ajax_gdwwid_ajax_online_total', 'gdwwid_ajax_online_total');
add_action('wp_ajax_nopriv_gdwwid_ajax_online_total', 'gdwwid_ajax_online_total');



function gdwwid_offline_visitors(){
		$knp_session_id = gdwwid_session();
		$last_time = gdwwid_get_time();


		global $wpdb;
		$table = $wpdb->prefix."gdwwid";
		
		
		$wpdb->query("UPDATE $table SET duration = '$last_time' WHERE session_id='$knp_session_id' ORDER BY id DESC LIMIT 1");

		$table = $wpdb->prefix . "gdwwid_online";
		
		$wpdb->delete( $table, array( 'session_id' => $knp_session_id ) );
}

add_action('wp_ajax_gdwwid_offline_visitors', 'gdwwid_offline_visitors');
add_action('wp_ajax_nopriv_gdwwid_offline_visitors', 'gdwwid_offline_visitors');




function gdwwid_visitors_page(){	
		global $wpdb;
		$table = $wpdb->prefix . "gdwwid_online";
		$entries = $wpdb->get_results( "SELECT * FROM $table ORDER BY knp_time DESC" );
		

		

 		echo "<br /><br />";
		echo "<table class='widefat' >";
		echo "<thead><tr>";
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Page</strong></th>";
		echo "<th scope='col' class='manage-column column-name' style=''><strong>User</strong></th>";
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Time</strong></th>";		
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Duration</strong></th>";		
		echo "<th scope='col' class='manage-column column-name' style=''><strong>City</strong></th>";
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Country</strong></th>";
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Browser</strong></th>";	
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Platform</strong></th>";
		echo "<th scope='col' class='manage-column column-name' style=''><strong>Referer</strong></th>";
		
		echo "</tr></thead>";
		echo "<tr class='no-online' style='text-align:center;'>";
				echo "<td colspan='8' style='color:#f00;'>";
				
				if($entries ==NULL)
					{
					echo "No User online";
					
					}
				
				echo "</td>";
		
		echo "</tr>";

		
		
		
		
		 $count = 1;
		foreach( $entries as $entry )
			{
				
				$class = ( $count % 2 == 0 ) ? ' class="alternate"' : '';
				
				
				echo "<tr $class>";
				echo "<td>";
				$url_term = $entry->url_term;
				$url_id = $entry->url_id;
				if(is_numeric($url_id))
					{	
						echo "<a href='".get_permalink($url_id)."'>".get_the_title($url_id)."</a>";

					}
				else
					{
						
						echo "<a href='http://".$url_id."'>".$url_term."</a>";

					}
				echo "</td>";				
				


				echo "<td>";
				$userid = $entry->userid;
				if(is_numeric($userid))
					{	
						$user_info = get_userdata($userid);

						echo "<span title='".$user_info->display_name."' class='avatar'>".get_avatar( $userid, 32 )."</span>";
					}
				else
					{
						echo "<span title='Guest' class='avatar'>".get_avatar( 0, 32 )."</span>";
					}
				echo "</td>";



				
				echo "<td>";
				$knp_time = $entry->knp_time;
				
				
				$time = date("H:i:s", strtotime($knp_time));
				
				echo "<span class='time'>".$time."</span>";
				echo "</td>";				
				
				
				echo "<td>";
				$current_time = strtotime(gdwwid_get_datetime());
				$knp_time = strtotime($entry->knp_time);
				$duration = ($current_time - $knp_time);

				echo "<span class='duration'>".gmdate("H:i:s", $duration)."</span>";
				echo "</td>";				
				
				echo "<td>";
				$city = $entry->city;
				
				if(empty($city))
					{
					echo "<span title='unknown' class='city'>Unknown</span>";
					}
				else
					{
					echo "<span title='".$city."' class='city'>".$city."</span>";
					}
				
				
				echo "</td>";				
				
				echo "<td>";
				$countryName = $entry->countryName;
				if(empty($countryName))
					{
					echo "<span title='unknown' >Unknown</span>";
					}
				else
					{
					echo "<span title='".$countryName."' class='flag flag-".strtolower($countryName)."'></span>";
					}
				
				
				echo "</td>";
				
				echo "<td>";
				$browser = $entry->browser;			
				echo "<span  title='".$browser."' class='browser ".$browser."'></span>";			
				echo "</td>";				
				
				echo "<td>";
				$platform = $entry->platform;				
				echo "<span  title='".$platform."' class='platform ".$platform."'></span>";				
				echo "</td>";				
				
				
				echo "<td>";
				$referer_doamin = $entry->referer_doamin;
				
				if($referer_doamin==NULL)
					{
						echo "<span title='Referer Doamin'  class='referer_doamin'>Unknown</span>";
						
					}
				elseif($referer_doamin=='direct')
					{
					echo "<span title='Referer Doamin'  class='referer_doamin'>Direct Visit</span>";
					}	
					
				elseif($referer_doamin=='none')
					{
					echo "<span title='Referer Doamin'  class='referer_doamin'>Unknown</span>";
					}
				else
					{
						echo "<span title='Referer Doamin'  class='referer_doamin'>".$referer_doamin."</span> - ";
					}
					
					
				$referer_url = $entry->referer_url;
				
				if($referer_url==NULL || $referer_url=='none' || $referer_url=='direct')
					{
						echo "<span title='Referer URL' class='referer_url'></span>";
						
					}
				else
					{
						echo "<span title='Referer URL' class='referer_url'> <a href='http://".$referer_url."'>URL</a></span>";
					}				

				echo "</td>";				
							
				echo "</tr>";
				
				
			$count++;
			}
		
		
		echo "</table>";

		die();
}


add_action('wp_ajax_gdwwid_visitors_page', 'gdwwid_visitors_page');
add_action('wp_ajax_nopriv_gdwwid_visitors_page', 'gdwwid_visitors_page');


add_action('wp_ajax_gdwwid_visitors2', 'gdwwid_visitors2');
add_action('wp_ajax_nopriv_gdwwid_visitors2', 'gdwwid_visitors2');





function gdwwid_getuser(){
		if ( is_user_logged_in() ) 
			{
				$userid = get_current_user_id();
			}
		else
			{
				$userid = "guest";
			}
			
		return $userid;
}


function gdwwid_geturl_id(){	
		global $post;
		
		
		
		if(is_home())
			{
				$url_term = 'home';
				$url_id = $_SERVER['PHP_SELF'];
			}
		elseif(is_singular())
			{
				$url_term = get_post_type();
				$url_id = get_the_ID();
			}
		elseif( is_tag())
			{
				$url_term = 'tag';
				$url_id = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}			
			
		elseif(is_archive())
			{
				$url_term = 'archive';
				$url_id = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}
		elseif(is_search())
			{
				$url_term = 'search';
				$url_id = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}			
			
			
		elseif( is_404())
			{
				$url_term = 'err_404';
				$url_id = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}			
		elseif( is_admin())
			{
				$url_term = 'dashboard';
				$url_id = admin_url();
			}	

		else
			{
				$url_term = 'unknown';
				$url_id = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			}
					
	
		return $url_id.",".$url_term;
		
}


function gdwwid_get_referer(){	
		if(isset($_SERVER["HTTP_REFERER"]))
			{
				$referer = $_SERVER["HTTP_REFERER"];
				$pieces = parse_url($referer);
				$domain = isset($pieces['host']) ? $pieces['host'] : '';
					if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs))
						{
							$referer = $regs['domain'];
						}
					else
						{
							$referer = "none";
						}
				
				$referurl = $_SERVER["HTTP_REFERER"];
			
			}
		else
			{
				$referer = "direct";
				$referurl = "none";
			}
		return $referer.",".$referurl;
}


function gdwwid_get_screensize(){
	
		if(!isset($_COOKIE["knp_screensize"]))
			{
				
			?>
			<script>
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + 365);    
		var screen_width =  screen.width +"x"+ screen.height;  
		var c_value=screen_width + "; expires="+exdate.toUTCString()+"; path=/";
		document.cookie= 'knp_screensize=' + c_value;
			
			
			</script>
            
            <?php
				$knp_screensize = "unknown";
				
				
			}
		else 
			{
				$knp_screensize = $_COOKIE["knp_screensize"];
			}
		
		
		return $knp_screensize;  
} 

function gdwwid_landing(){
			if (!isset($_COOKIE['knp_landing']))
				{	

					?>
					<script>
						var exdate=new Date();
						exdate.setDate(exdate.getDate() + 365);    
						knp_landing = 1;
						var c_value=knp_landing + "; expires="+exdate.toUTCString()+"; path=/";
						document.cookie= 'knp_landing=' + c_value;
					
					</script>
					
					<?php
					
					$knp_landing = 1;
					
				}
			else
				{

					$knp_landing = $_COOKIE['knp_landing'];
					$knp_landing += 1;

					?>
					<script>
						var exdate=new Date();
						exdate.setDate(exdate.getDate() + 365);    
						knp_landing =<?php echo $knp_landing; ?>;
						var c_value=knp_landing + "; expires="+exdate.toUTCString()+"; path=/";
						document.cookie= 'knp_landing=' + c_value;
					
					</script>
					
					<?php
					
					
					
					
					
					
					
				}
				

			return $knp_landing;
			
}

function gdw_get_strtotime(){
			$gmt_offset = get_option('gmt_offset');
			$strtotime = strtotime('+'.$gmt_offset.' hour');
			//$strtotime = strtotime('24 July 2016');
			return $strtotime;
}
function gdwwid_get_date(){	
			$strtotime = gdw_get_strtotime();
			$knp_datetime = date('Y-m-d', $strtotime);
			
			return $knp_datetime;
}
		

function gdwwid_get_time(){	
			$strtotime = gdw_get_strtotime();
			$knp_time = date('H:i:s', $strtotime);
			
			return $knp_time;
		
}

function gdwwid_get_ts(){	
			$strtotime = gdw_get_strtotime();
			$knp_ts = $strtotime; 
			//$knp_ts = 1470661892 - 372800; 
			return $knp_ts;
		
}
		
function gdwwid_get_datetime(){	
			$strtotime = gdw_get_strtotime();
			$knp_datetime = date('Y-m-d H:i:s', $strtotime);
			
			return $knp_datetime;
}		
		
		
		


function gdwwid_get_unique(){	

			$cookie_site = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

			$cookie_nam = 'knp_page_'.$cookie_site;

			if (isset($_COOKIE[$cookie_nam]))
				{	
					
					$visited = "yes";
		
				}
			else
				{
					
					?>
					<script>
					document.cookie="<?php echo $cookie_nam ?>=yes";
					</script>
					
					<?php
					
					$visited = "no";
				}
		
		
		
		
		
		
			if(empty($_COOKIE[$cookie_nam]))
				{
					$isunique ="yes";
				}
			else 
				{
					$isunique ="no";
				}
				
			return $isunique;
		
}


add_action('wp_ajax_gdwwid_online_today_visitors', 'gdwwid_online_today_visitors');
add_action('wp_ajax_nopriv_gdwwid_online_today_visitors', 'gdwwid_online_today_visitors');

function gdw_today_visitors() {
	wp_add_dashboard_widget( 'gdw_today_visitors_wp_dashboard', __('Today Page Views & Online Users', 'gdwlang') , 'gdw_today_visitors_output' );
}

function gdw_today_visitors_output() {
	include('gdw-stats-online.php');
	include('gdw-stats-visitors-online-today-ajaxcall.php');
//	include('gdw-stats-online-user-details.php');
}

function gdwwid_online_today_visitors(){	
	include('gdw-stats-visitors-online-today.php');
	die();
}

/*Dashboard Widget Test 1*/
add_action('wp_ajax_gdwwid_visitors_type', 'gdwwid_visitors_type');
add_action('wp_ajax_nopriv_gdwwid_visitors_type', 'gdwwid_visitors_type');

function gdw_visitors_type() {
	wp_add_dashboard_widget( 'gdw_visitors_type_wp_dashboard', __('Visitors in last 15 days', 'gdwlang') , 'gdw_visitors_type_output' );
}

function gdw_visitors_type_output() {
	include('gdw-stats-visitors-type-ajaxcall.php');
}

function gdwwid_visitors_type(){
	include('gdw-stats-visitors-type.php');
	
	die();
}




/*Dashboard Widget Test 1*/
add_action('wp_ajax_gdwwid_user_type', 'gdwwid_user_type');
add_action('wp_ajax_nopriv_gdwwid_user_type', 'gdwwid_user_type');

function gdw_user_type() {
	wp_add_dashboard_widget( 'gdw_user_type_wp_dashboard',  __('Users in last 15 days', 'gdwlang') , 'gdw_user_type_output' );
}

function gdw_user_type_output() {
	include('gdw-stats-user-type-ajaxcall.php');
}

function gdwwid_user_type(){
	//echo "hihihihihihi";
	//gdwwid_login();
	include('gdw-stats-user-type.php');
	die();
}




/*Dashboard Widget Test 1*/
add_action('wp_ajax_gdwwid_browser_type', 'gdwwid_browser_type');
add_action('wp_ajax_nopriv_gdwwid_browser_type', 'gdwwid_browser_type');

function gdw_browser_type() {
	wp_add_dashboard_widget( 'gdw_browser_type_wp_dashboard', __('Browsers Used', 'gdwlang') , 'gdw_browser_type_output' );
}

function gdw_browser_type_output() {
	include('gdw-stats-browser-type-ajaxcall.php');
}

function gdwwid_browser_type(){
	include('gdw-stats-browser-type.php');
	die();
}


/*Dashboard Widget Test 1*/
add_action('wp_ajax_gdwwid_platform_type', 'gdwwid_platform_type');
add_action('wp_ajax_nopriv_gdwwid_platform_type', 'gdwwid_platform_type');

function gdw_platform_type() {
	wp_add_dashboard_widget( 'gdw_platform_type_wp_dashboard',  __('Platforms Used', 'gdwlang') , 'gdw_platform_type_output' );
}

function gdw_platform_type_output() {
	include('gdw-stats-platform-type-ajaxcall.php');
}

function gdwwid_platform_type(){
	include('gdw-stats-platform-type.php');
	die();
}



/*Dashboard Widget Test 1*/
add_action('wp_ajax_gdwwid_country_type', 'gdwwid_country_type');
add_action('wp_ajax_nopriv_gdwwid_country_type', 'gdwwid_country_type');

function gdw_country_type() {
	wp_add_dashboard_widget( 'gdw_country_type_wp_dashboard', __('Visits by Country', 'gdwlang') , 'gdw_country_type_output' );
}

function gdw_country_type_output() {
	include('gdw-stats-country-type-ajaxcall.php');
}

function gdwwid_country_type(){
	include('gdw-stats-country-type.php');
	die();
}

?>