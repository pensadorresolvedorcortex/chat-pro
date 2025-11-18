<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
		function platforms_used_by_visitors(){
			jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_platform_type"},
					success: function(data)
							{
								jQuery(".gdw_platform_type").html(data);
							}
				});
		}
			platforms_used_by_visitors();
	
			setInterval(function(){
					platforms_used_by_visitors();
			}, 300000)

			
	        jQuery(document).on('click', "#gdw_platform_type_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_platform_type").html("Loading...");
						platforms_used_by_visitors();
						//console.log("recall");
	                            }
	        });

	});
			
</script>

<div class="gdw_platform_type"><?php echo __("Loading...","gdwlang"); ?></div>


</div>