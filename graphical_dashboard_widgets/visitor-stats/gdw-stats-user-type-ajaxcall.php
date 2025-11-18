<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
		function gdw_user_type_guest_or_registered(){
			jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_user_type"},
					success: function(data)
							{
								jQuery(".gdw_user_type").html(data);
							}
				});
		}
			gdw_user_type_guest_or_registered();
	
			setInterval(function(){
					gdw_user_type_guest_or_registered();
			}, 300000)
			
	        jQuery(document).on('click', "#gdw_user_type_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_user_type").html("Loading...");
						gdw_user_type_guest_or_registered();
						//console.log("recall");
	                            }
	        });
	});
			
</script>

<div class="gdw_user_type"><?php echo __("Loading...","gdwlang"); ?></div>


</div>