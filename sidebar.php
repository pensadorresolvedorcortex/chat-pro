<?php
$current_user_id = get_current_user_id();
$pid = get_user_meta( $current_user_id, 'employer_id' , true );
$account_status = get_user_meta( $current_user_id, '_account_status', true );
global $exertio_theme_options;

$user_info = get_userdata($current_user_id);
$page_name ='';
if(isset($_GET['ext']) && $_GET['ext'] !="")
{
	$page_name = $_GET['ext'];	
}
$alt_id ='';
?>
<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
    <li class="profile ff">
    	<div>
            <span class="pro-img">
            <?php
                $pro_img_id = get_post_meta( $pid, '_profile_pic_attachment_id', true );
                $pro_img = wp_get_attachment_image_src( $pro_img_id, 'thumbnail' );
                

				if(wp_attachment_is_image($pro_img_id))
                {
                    ?>
                    <img src="<?php echo esc_url($pro_img[0]); ?>" alt="<?php echo esc_attr(get_post_meta($pro_img_id, '_wp_attachment_image_alt', TRUE)); ?>" class="img-fluid" loading="lazy">
                    <?php
                }
                else
                {
                    ?>
                    <img src="<?php echo esc_url($exertio_theme_options['employer_df_img']['url']); ?>" alt="<?php echo esc_attr(get_post_meta($alt_id, '_wp_attachment_image_alt', TRUE)); ?>" class="img-fluid" loading="lazy">
                    <?php	
                }
            ?>
            </span>
        </div>
        <h4 class="mt-4"><?php echo exertio_get_username('employer', $pid, 'badge', 'right'); ?></h4>
        <p><?php echo esc_html($user_info->user_email); ?></p>
      </li>
	<?php
		$employer_sidebar_menu = array();
		if (function_exists('fl_framework_get_options')) {
			$employer_sidebar_menu = fl_framework_get_options('employer_dashboard_sidebar_sortable');
		}
		if (!is_array($employer_sidebar_menu) || empty($employer_sidebar_menu)) {
			$employer_sidebar_menu = isset($exertio_theme_options['employer_dashboard_sidebar_sortable']) && is_array($exertio_theme_options['employer_dashboard_sidebar_sortable']) ? $exertio_theme_options['employer_dashboard_sidebar_sortable'] : array();
		}
		if (!is_array($employer_sidebar_menu) || empty($employer_sidebar_menu)) {
			$employer_sidebar_menu = array(
				'Dashboard' => esc_html__('Dashboard', 'exertio_theme'),
				'Profile' => esc_html__('Profile', 'exertio_theme'),
				'RmaMapDirectory' => esc_html__('Mapa de ONGs', 'exertio_theme'),
				'Projects' => esc_html__('Projects', 'exertio_theme'),
				'Services' => esc_html__('Services', 'exertio_theme'),
				'chat_dashboard' => esc_html__('SB Chat Dashboard', 'exertio_theme'),
				'ChatDashboard' => esc_html__('Chat Dashboard', 'exertio_theme'),
				'SavedServices' => esc_html__('Saved Services', 'exertio_theme'),
				'Logout' => esc_html__('Logout', 'exertio_theme'),
			);
		}
		foreach($employer_sidebar_menu as $key => $val)
		{
			if($key == 'Dashboard' && $val != "")
			{
			?>
				<li class="nav-item <?php if($page_name == 'dashboard') { echo 'active';} ?>">
					<a class="nav-link" href="<?php echo get_the_permalink();?>">
						<i class="fas fa-home menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
					</a>
				</li>
			<?php
			}
			if (!empty($exertio_theme_options['referral_system']) && $exertio_theme_options['referral_system']) {

				if ($key == 'Referral' && $val != "") {
					?>
					<li class="nav-item <?php if ($page_name == 'referral') {
						echo 'active';
					} ?>">
						<a class="nav-link" href="<?php echo get_the_permalink(); ?>?ext=referral">
							<i class="fas fa-gift menu-icon"></i>
							<span class="menu-title"><?php echo esc_html($val); ?></span>
						</a>
					</li>
				<?php
				}
			}
			if($key == 'Profile' && $val != "")
			{
			?>
				<li class="nav-item <?php if($page_name == 'edit-profile') { echo 'active';} ?>">
					<a class="nav-link" data-toggle="collapse" aria-expanded="false"  href="#profile" aria-controls="profile">
					  <i class="fas fa-user menu-icon"></i>
					  <span class="menu-title"><?php echo esc_html($val); ?></span>
					  <i class="fas fa-chevron-down menu-arrow"></i>
					</a>
					<div class="collapse <?php if($page_name == 'edit-profile' ){ echo 'show';} ?>" id="profile">
					  <ul class="nav flex-column sub-menu">
						<li class="nav-item <?php if($page_name == 'edit-profile') { echo 'active';} ?>"> <a class="nav-link" href="<?php  echo esc_url(get_permalink($pid)); ?>"> <?php echo esc_html__( 'View Profile', 'exertio_theme' ); ?> </a></li>
						<li class="nav-item"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=edit-profile"> <?php echo esc_html__( 'Edit Profile', 'exertio_theme' ); ?> </a></li>
					  </ul>
					</div>
				</li>
				<?php
			}
			if($key == 'RmaMapDirectory' && $val != "")
			{
				$rma_map_url = isset($exertio_theme_options['rma_map_iframe_url']) ? trim($exertio_theme_options['rma_map_iframe_url']) : '';
				$rma_map_has_valid_url = !empty($rma_map_url) && wp_http_validate_url($rma_map_url);
				$rma_map_target = $rma_map_has_valid_url ? $rma_map_url : get_the_permalink() . '?ext=edit-profile';
			?>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo esc_url($rma_map_target); ?>"<?php echo $rma_map_has_valid_url ? ' target="_blank" rel="noopener"' : ''; ?>>
						<i class="fas fa-map-marked-alt menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
					</a>
				</li>
			<?php
			}
			if(($exertio_theme_options['user_status_check'] == true && $account_status !='deactivate') || $exertio_theme_options['user_status_check'] == false){
				if($key == 'Projects' && $val != "")
				{
				?>
					<li class="nav-item <?php if($page_name == 'create-project' || $page_name == 'projects' || $page_name == 'ongoing-projects' || $page_name == 'expired-project' || $page_name == 'completed-projects' || $page_name == 'completed-project-detail' || $page_name == 'canceled-projects' || $page_name == 'pending-projects' || $page_name == 'project-propsals' || $page_name == 'ongoing-project' || $page_name == 'ongoing-project-detail' || $page_name == 'ongoing-project-proposals') { echo 'active';} ?>">
						<a class="nav-link" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
						<i class="fas fa-briefcase menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
						<i class="fas fa-chevron-down menu-arrow"></i>
						</a>
						<div class="collapse <?php if($page_name == 'create-project' || $page_name == 'projects' || $page_name == 'expired-project' || $page_name == 'ongoing-projects' || $page_name == 'completed-projects' ||  $page_name == 'completed-project-detail' ||  $page_name == 'canceled-projects' || $page_name == 'pending-projects' || $page_name == 'project-propsals' || $page_name == 'ongoing-project' || $page_name == 'ongoing-project-detail' || $page_name == 'ongoing-project-proposals') { echo 'show';} ?>" id="ui-basic">
						<ul class="nav flex-column sub-menu">
							<li class="nav-item <?php if($page_name == 'create-project') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=create-project"><?php echo esc_html__( 'Create Project', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'projects') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=projects"><?php echo esc_html__( 'Posted Projects', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'pending-projects') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=pending-projects"><?php echo esc_html__( 'Pending Projects', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'ongoing-project') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=ongoing-project"><?php echo esc_html__( 'Ongoing Project', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'expired-project') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=expired-project"><?php echo esc_html__( 'Expired Project', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'completed-projects') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=completed-projects"><?php echo esc_html__( 'Completed Projects', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'canceled-projects') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=canceled-projects"><?php echo esc_html__( 'canceled Projects', 'exertio_theme' ); ?></a></li>
							<li class="nav-item <?php if($page_name == 'disputed-projects') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=disputed-projects"><?php echo esc_html__( 'Disputed Projects', 'exertio_theme' ); ?></a></li>
						</ul>
						</div>
					</li>
				<?php
				}
			
           if($key == 'Offers' && $val != "" && fl_framework_get_options('allow_projects_offers'))
			{?>
							<li class="nav-item <?php if($page_name == 'project-offers') { echo 'active';} ?>">
					<a class="nav-link" data-toggle="collapse" href="#offers" aria-expanded="false" aria-controls="offers">
                        <i class="fas fa-address-book  menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
						<i class="fas fa-chevron-down menu-arrow"></i>
					 </a>
						<div class="collapse <?php if($page_name == 'accepted-offers' || $page_name == 'rejected-offers' || $page_name == 'cancelled-offers') { echo 'show';} ?>" id="offers">
					  <ul class="nav flex-column sub-menu">
						<li class="nav-item <?php if($page_name == 'project-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=project-offers"><?php echo esc_html__( 'All offers', 'exertio_theme' ); ?> </a></li>
						<li class="nav-item <?php if($page_name == 'accepted-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=accepted-offers"><?php echo esc_html__( 'Accepted offers', 'exertio_theme' ); ?> </a></li>
						<li class="nav-item <?php if($page_name == 'rejected-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=rejected-offers"><?php echo esc_html__( 'Rejected Offers', 'exertio_theme' ); ?> </a></li>
                        <li class="nav-item <?php if($page_name == 'cancelled-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=cancelled-offers"><?php echo esc_html__( 'cancelled offers', 'exertio_theme' ); ?></a></li>
                      </ul>
					</div>
				</li>
		<?php 	}
     

				if($key == 'Services' && $val != "")
				{
				?>
					<li class="nav-item <?php if($page_name == 'ongoing-services' || $page_name == 'ongoing-service-detail' || $page_name == 'completed-services' || $page_name == 'completed-service-detail' || $page_name == 'canceled-services' || $page_name == 'canceled-service-detail') { echo 'active'; }?>">
						<a class="nav-link" data-toggle="collapse" href="#services" aria-expanded="false" aria-controls="services">
						<i class="fas fa-user-cog menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
						<i class="fas fa-chevron-down menu-arrow"></i>
						</a>
						<div class="collapse <?php if($page_name == 'ongoing-services' || $page_name == 'ongoing-service-detail' || $page_name == 'completed-services' || $page_name == 'completed-service-detail' || $page_name == 'canceled-services' || $page_name == 'canceled-service-detail') { echo 'show';}?>" id="services">
						<ul class="nav flex-column sub-menu">
							<li class="nav-item <?php if($page_name == 'ongoing-services') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=ongoing-services"><?php echo esc_html__( 'Ongoing Services', 'exertio_theme' ); ?> </a></li>
							<li class="nav-item <?php if($page_name == 'completed-services') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=completed-services"><?php echo esc_html__( 'Completed Services', 'exertio_theme' ); ?> </a></li>
							<li class="nav-item <?php if($page_name == 'canceled-services') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=canceled-services"><?php echo esc_html__( 'Canceled Services', 'exertio_theme' ); ?> </a></li>
							<li class="nav-item <?php if($page_name == 'disputed-services') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=disputed-services"><?php echo esc_html__( 'Disputed Services', 'exertio_theme' ); ?></a></li>
						</ul>
						</div>
					</li>
				<?php
				}
			}
			if($key == 'JobInvitations' && $val != "")
			{
				?>
				<li class="nav-item <?php if($page_name == 'invitations') { echo 'active';} ?>">
					<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=invitations">
					  <i class="fas fa-bell menu-icon"></i>
					  
					  <span class="menu-title"><?php echo esc_html($val); ?></span>
					</a>
				</li>	
				<?php
			}
			

			if($key == 'ChatDashboard' && $val != "")
			{
				if(in_array('whizz-chat/whizz-chat.php', apply_filters('active_plugins', get_option('active_plugins'))))
				{
					global $whizzChat_options;
					$dashboard_page = isset($whizzChat_options['whizzChat-dashboard-page']) && $whizzChat_options['whizzChat-dashboard-page'] != '' ? $whizzChat_options['whizzChat-dashboard-page'] : 'javascript:void(0)';
					if ($dashboard_page != '')
					{
						?>
						<li class="nav-item">
							<a class="nav-link" href="<?php echo esc_url(get_permalink($dashboard_page));?>" target="_blank">
							  <i class="fas fa-comments menu-icon"></i>
							  <span class="menu-title"><?php echo esc_html($val); ?></span>
							</a>
						</li>
						<?php
					}
				}
			}

           	if($key == 'chat_dashboard' && $val != "")
			{
					$inbox_link   =  get_option('sb_plugin_options');
				$inbox_link  =  isset($inbox_link['sb-dashboard-page']) ? get_the_permalink($inbox_link['sb-dashboard-page']) : home_url();

               if(class_exists('SB_Chat')){
                      ?>
                     	<li class="nav-item">
							<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=inbox">
							  <i class="far fa-comment-dots  menu-icon"></i>
							  <span class="menu-title"><?php echo esc_html($val); ?></span>
							</a>
						</li>


                  <?php 
                   }

			}
			if($key == 'SavedServices' && $val != "")
			{
			?>
				<li class="nav-item <?php if($page_name == 'saved-services') { echo 'active';} ?>">
					<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=saved-services">
					  <i class="far fa-bookmark menu-icon"></i>
					  <span class="menu-title"><?php echo esc_html($val); ?></span>
					</a>
				</li>
			<?php
			}
			if($key == 'FollowedFreelancers' && $val != "")
			{
			?>
			<li class="nav-item <?php if($page_name == 'followed-freelancers') { echo 'active';} ?>">
				<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=followed-freelancers">
				  <i class="fas fa-share menu-icon"></i>
				  <span class="menu-title"><?php echo esc_html($val); ?></span>
				</a>
			</li>
			<?php
			}
			if(($exertio_theme_options['user_status_check'] == true && $account_status !='deactivate') || !is_super_admin($current_user_id)){
				if($key == 'FundDepositInvoices' && $val != "")
				{
				?>
					<li class="nav-item <?php if($page_name == 'invoices') { echo 'active';} ?>">
						<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=invoices">
						<i class="fas fa-receipt menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
						</a>
					</li>
				<?php
				}
			}
			if($key == 'MeetingSettings' && $val != "")
			{
			?>
				<li class="nav-item <?php if($page_name == 'meetings-settings') { echo 'active';} ?>">
					<a class="nav-link" data-toggle="collapse" aria-expanded="false"  href="#meeting" aria-controls="meeting">
					  <i class="fas fa-users menu-icon"></i>
					  <span class="menu-title"><?php echo esc_html($val); ?></span>
					  <i class="fas fa-chevron-down menu-arrow"></i>
					</a>
					<div class="collapse <?php if($page_name == 'meetings-settings' ){ echo 'show';} ?>" id="meeting">
					  <ul class="nav flex-column sub-menu">
						<li class="nav-item <?php if($page_name == 'all-meetings') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=meetings-settings"> <?php echo esc_html__( 'Meetings Settings', 'exertio_theme' ); ?> </a></li>
						<li class="nav-item"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=all-meetings"> <?php echo esc_html__( 'All Meetings', 'exertio_theme' ); ?> </a></li>
					  </ul>
					</div>
				</li>
			<?php
			}
			if(($exertio_theme_options['user_status_check'] == true && $account_status !='deactivate') || $exertio_theme_options['user_status_check'] == false){
				if($key == 'Disputes' && $val != "")
				{
				?>
					<li class="nav-item <?php if($page_name == 'disputes') { echo 'active';} ?>">
						<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=disputes">
						<i class="fas fa-shield-alt menu-icon"></i>
						<span class="menu-title"><?php echo esc_html($val); ?></span>
						</a>
					</li>
				<?php
				}
			}
			if($key == 'VerifyIdentity' && $val != "")
			{
			?>
			<li class="nav-item <?php if($page_name == 'identity-verification') { echo 'active';} ?>">
				<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=identity-verification">
					<i class="fas fa-user-shield menu-icon"></i>
					<span class="menu-title"><?php echo esc_html($val); ?></span>
				</a>
			</li>
			<?php
			}
			if($key == 'Statements' && $val != "")
			{
			?>
			<li class="nav-item <?php if($page_name == 'statements') { echo 'active';} ?>">
				<a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=statements">
					<i class="far fa-list-alt menu-icon"></i>
					<span class="menu-title"><?php echo esc_html($val); ?></span>
				</a>
			</li>
			<?php
			}
			if($key == 'Logout' && $val != "")
			{
			?>
				<li class="nav-item">
					<a class="nav-link" href="<?php echo wp_logout_url( get_the_permalink( $exertio_theme_options['login_page'] ) ); ?>">
					  <i class="fas fa-sign-out-alt menu-icon"></i>
					  <span class="menu-title"><?php echo esc_html($val); ?></span>
					</a>
				</li>
			<?php
			}

			//custom offers page 
			$custom_offer_feature = fl_framework_get_options('custom_offer_option');
			if($custom_offer_feature == 'yes'){
			if($key == 'CustomOffers' && $val != "")
			{
			?>
				<li class="nav-item <?php if($page_name == 'custom-offers') { echo 'active';} ?>">
					<a class="nav-link" data-toggle="collapse" href="#custom" aria-expanded="false" aria-controls="custom">
					<i class="fa-solid fa fa-handshake  menu-icon"></i>
					  <span class="menu-title"><?php echo esc_html($val); ?></span>
					  <i class="fas fa-chevron-down menu-arrow"></i>
					</a>
					<div class="collapse <?php if($page_name == 'custom-offers') { echo 'show';} ?>" id="custom">
					  <ul class="nav flex-column sub-menu">
						<li class="nav-item <?php if($page_name == 'custom-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=custom-offers"><?php echo esc_html__( 'All offers', 'exertio_theme' ); ?></a></li>
						
                        <li class="nav-item <?php if($page_name == 'accepted-custom-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=accepted-custom-offers"><?php echo esc_html__( 'Accepted Offers', 'exertio_theme' ); ?></a></li>
						<li class="nav-item <?php if($page_name == 'rejected-custom-offers') { echo 'active';} ?>"> <a class="nav-link" href="<?php echo esc_url(get_the_permalink());?>?ext=rejected-custom-offers"><?php echo esc_html__( 'Rejected Offers', 'exertio_theme' ); ?></a></li>
                      </ul>
					</div>
				</li>
			



					
			<?php
			}
		}

		}
		?>
    </ul>
</nav>
          
