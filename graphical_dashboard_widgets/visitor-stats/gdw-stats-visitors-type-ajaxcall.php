<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
		function online_today_visitors(){
			jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_visitors_type"},
					success: function(data)
							{
								jQuery(".gdw_visitors_type").html(data);
							}
				});
		}
			online_today_visitors();
	
			setInterval(function(){
					online_today_visitors();
			}, 300000)


	        jQuery(document).on('click', "#gdw_visitors_type_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_visitors_type").html("Loading...");
						online_today_visitors();
						//console.log("recall");
	                            }
	        });			
	});
			
</script>

<div class="gdw_visitors_type"><?php echo __("Loading...","gdwlang"); ?></div>


</div>