<div class="gdw-wid-admin">

<script>		
	jQuery(document).ready(function(){
		function user_registrations_by_year(){
			jQuery.ajax(
						{
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_userstats"},
					success: function(data)
							{
								jQuery(".gdw_userstats").html(data);
							}
						});
		}
		user_registrations_by_year();
			setInterval(function(){
					user_registrations_by_year();
			}, 300000)
			
	        jQuery(document).on('click', "#userstats_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_userstats").html("Loading...");
						user_registrations_by_year();
						//console.log("recall");
	                            }
	        });
	});
			
</script>


<div class="gdw_userstats">
	
</div>


</div>