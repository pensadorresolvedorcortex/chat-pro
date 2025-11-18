<div class="wrap">
<h2></h2>

<div class="wp-settings-pro">
    
    <div class="settings-saved">
    </div>
    


    <div class="option-area">
    <div class="option-title"><strong><?php echo __("Recent Visitors","gdwlang"); ?></strong>
    
    </div>
    <div class="option-descriptions"><?php echo __("List of recent visitors by time.","gdwlang"); ?>
    </div>
    
    <div class="option-input">
		<div class="gdw-wid-box">
        <div class="box-title"><?php echo __("Recent Visitor List","gdwlang"); ?>
        
        </div>
<?php
global $wpdb;
 
$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

if ( isset( $kss_paginate_items ) )
	{
		$limit = get_option('kss_paginate_items');
	} 
else
	{
		
	$limit = 10;
	
	}



$offset = ( $pagenum - 1 ) * $limit;
$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}gdwwid ORDER BY id DESC LIMIT $offset, $limit" );
 

 
?>

<table class="widefat">
    <thead>
        <tr>
            <th scope="col" class="manage-column column-name" style=""><strong>Page Title</strong></th>
        	<th scope="col" class="manage-column column-name" style=""><strong>user</strong></th>
        	<th scope="col" class="manage-column column-name" style=""><strong>Event</strong></th>
        	<th scope="col" class="manage-column column-name" style=""><strong>Date - Time</strong></th>                    
        	<th scope="col" class="manage-column column-name" style=""><strong>Device</strong></th>           
            <th scope="col" class="manage-column column-name" style=""><strong>Location</strong></th>         
            <th scope="col" class="manage-column column-name" style=""><strong>Referer</strong></th>
            <th scope="col" class="manage-column column-name" style=""><strong>Unique</strong></th>
            <th scope="col" class="manage-column column-name" style=""><strong>Landing</strong></th>

        </tr>
    </thead>
 
    <tfoot>
        <tr>
            <th scope="col" class="manage-column column-name" style=""><strong>Page Title</strong></th>
        	<th scope="col" class="manage-column column-name" style=""><strong>user</strong></th>         
        	<th scope="col" class="manage-column column-name" style=""><strong>Event</strong></th>
        	<th scope="col" class="manage-column column-name" style=""><strong>Date - Time</strong></th> 
            <th scope="col" class="manage-column column-name" style=""><strong>Device</strong></th>               
            <th scope="col" class="manage-column column-name" style=""><strong>Location</strong></th>            
            <th scope="col" class="manage-column column-name" style=""><strong>Referer url</strong></th>              
            <th scope="col" class="manage-column column-name" style=""><strong>Unique</strong></th>   
            <th scope="col" class="manage-column column-name" style=""><strong>Landing</strong></th>            
                      
        </tr>
    </tfoot>
 
    <tbody>
        <?php if( $entries ) { ?>
 
            <?php
            $count = 1;
            $class = '';
            foreach( $entries as $entry ) {
                $class = ( $count % 2 == 0 ) ? ' class="alternate"' : '';
            ?>
 
            <tr<?php echo $class; ?>>
            
                <td style="max-width:180px;">
				<?php 
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
                
                ?>
                </td>
                <td><?php 				
						$userid = $entry->userid;
										
						if(is_numeric($userid))
							{
								$user_info = get_userdata($userid);
								echo "<span title='".$user_info->display_name."' class='avatar'>".get_avatar( $userid, 32 )."<i title='User'></i></span>";
							}
						else
							{
								if($userid=='guest')
									{
									echo "<span title='Guest' class='avatar'>".get_avatar( 0, 32 )."</span>";
									}
								else
									{
										$userid = get_userdatabylogin($userid );
										$userid = $userid->ID;
										$user_info = get_userdata($userid);
										echo "<span title='".$user_info->display_name."' class='avatar'>".get_avatar( $userid, 32 )."<i title='Username'></i></span>";
									}
								

							}	
				
				 ?></td>            
                <td><?php echo $entry->event; ?></td>
                <td><?php
				
				
				 echo $entry->knp_date."<br />";
				 echo $entry->knp_time;
				 
				  ?></td>             
                <td><?php 
						$platform = $entry->platform;
						$browser = $entry->browser;	
						$screensize = $entry->screensize;
				
				echo "<span  title='".$platform."' class='platform ".$platform."'></span>";	
				echo "<span  title='".$browser."' class='browser ".$browser."'></span>";
				echo "<span  title='".$screensize."' class='screensize'>".$screensize."</span>";
				
				
				 ?></td>                         
                <td>
				
				<?php
				
				$countryName = $entry->countryName;
				$region = $entry->region;				
				$city = $entry->city;
				echo "<span title='".$countryName."' class='flag flag-".strtolower($countryName)."'></span><br />";
				echo "<span title='".$region."' class='region'>".$region."</span><br />";
				echo "<span title='".$city."' class='city'>".$city."</span>";
				?>
                
                </td>           
                <td>
                <?php
                $referer_url = $entry->referer_url;
				$referer_doamin = $entry->referer_doamin;
				
				if($referer_doamin=='direct')
					{
					echo "Direct Visit";
					}
				else
					{
					echo "<a href='".$referer_url."'>URL</a>";
					if($referer_doamin=='none')
						{
							echo "<span title='Domain is undefine or missing, might be localhost'> - None</span>";
						}
					else
						{
							echo " - <a href='http://".$referer_doamin."'>".$referer_doamin."</a>";
							
						}
					
					}
				
				?>
                </td>
                <td><?php $isunique = $entry->isunique; ?>  
                
<span title="Post View Unique Status: <?php echo $isunique; ?>" class="isunique-<?php echo $isunique; ?>"></span>
                
                </td>
                <td><?php echo $entry->landing; ?>   </td>                
            </tr>
 
            <?php
                $count++;
            }
            ?>
 
        <?php } else { ?>
        <tr>
            <td colspan="2">No Views Yet</td>
        </tr>
        <?php } ?>
    </tbody>
</table>
 
<?php
 
$total = $wpdb->get_var( "SELECT COUNT(`id`) FROM {$wpdb->prefix}gdwwid" );
$num_of_pages = ceil( $total / $limit );
$page_links = paginate_links( array(
    'base' => add_query_arg( 'pagenum', '%#%' ),
    'format' => '',
    'prev_text' => __( '&laquo;', 'gdwlang' ),
    'next_text' => __( '&raquo;', 'gdwlang' ),
    'total' => $num_of_pages,
    'current' => $pagenum
) );
 
if ( $page_links ) {
    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
}
 

?>

        </div>
    </div>

</div>

</div>


<style type="text/css">

.gdw-wid-box {
  background: none repeat scroll 0 0 #29D883;
  border-bottom: 2px solid #117042;
  border-top: 2px solid #117042;
  padding-bottom: 30px;
}

.gdw-wid-box .box-title {
  font-size: 14px;
  font-weight: bold;
  padding: 10px 0 10px 15px;
}

.wp-settings-pro {
  background: none repeat scroll 0 0 #FFFFFF;
  margin-bottom: 20px;
  padding-bottom: 20px;
  width: 100%;
}

.wp-settings-pro .heading {
  border-bottom: 2px solid #666666;
}

.wp-settings-pro .heading h2 {
  color: #333333;
  font-size: 20px;
  font-weight: bold;
  padding-left: 20px;
}

.wp-settings-pro .heading .updated {
  margin-left: 20px;
}


.wp-settings-pro .submit {
	margin-left: 20px;
}

.wp-settings-pro .setting-descriptions p {
  border-bottom: 1px solid;
  color: #999999;
  font-size: 13px;
  margin-bottom: 15px;
  margin-left: 20px;
  margin-top: 15px;
  padding-bottom: 5px;
}

.wp-settings-pro .option-area {
  margin: 30px 0;
}


.wp-settings-pro .option-area .option-title {
  font-size: 15px;
  margin: 10px 0;
  padding-left: 20px;
}

.wp-settings-pro .option-area .option-descriptions {
  font-size: 13px;
  padding-left: 20px;
}

.wp-settings-pro .option-area .option-input {
  border-bottom: 1px solid #DDDDDD;
  margin-left: 20px;
  padding: 20px 0;
}

</style>


</div>
