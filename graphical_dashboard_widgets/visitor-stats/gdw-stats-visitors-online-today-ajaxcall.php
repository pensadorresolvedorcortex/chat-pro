<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
		function online_today_visitors(){
			jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_online_today_visitors"},
					success: function(data)
							{
								jQuery(".gdw_online_today_visitors").html(data);
							}
				});
		}
			online_today_visitors();
	
			setInterval(function(){
					online_today_visitors();
			}, 300000)

	        jQuery(document).on('click', "#gdw_today_visitors_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_online_today_visitors").html("Loading...");
						online_today_visitors();
						//console.log("recall");
	                            }
	        });

			
	});
			
</script>

<div class="gdw_online_today_visitors"><?php echo __("Loading...","gdwlang"); ?></div>


</div>