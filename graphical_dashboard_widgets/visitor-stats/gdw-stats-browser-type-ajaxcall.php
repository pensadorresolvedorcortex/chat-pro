<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
		function browsers_used_by_visitors(){

			jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_browser_type"},
					success: function(data)
							{
								jQuery(".gdw_browser_type").html(data);
							}
				});
		}
			browsers_used_by_visitors();
	
			setInterval(function(){
					browsers_used_by_visitors();
			}, 300000)
	

	        jQuery(document).on('click', "#gdw_browser_type_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_browser_type").html("Loading...");
						browsers_used_by_visitors();
						//console.log("recall");
	                            }
	        });

	});




			
</script>

<div class="gdw_browser_type"><?php echo __("Loading...","gdwlang"); ?></div>


</div>