<div class="gdw-wid-admin">

<script>		
	jQuery(document).ready(function(){
		function post_registrations_by_year(){
			jQuery.ajax(
						{
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_poststats"},
					success: function(data)
							{
								jQuery(".gdw_poststats").html(data);
							}
						});
		}
		post_registrations_by_year();
			setInterval(function(){
					post_registrations_by_year();
			}, 300000)
			
	        jQuery(document).on('click', "#poststats_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_poststats").html("Loading...");
						post_registrations_by_year();
						//console.log("recall");
	                            }
	        });
	});
			
</script>


<div class="gdw_poststats">
	
</div>


</div>