	<script>		
		jQuery(document).ready(function()
			{

				function gdw_find_online_users(){
					jQuery.ajax({
						type: 'POST',
						url: gdwwid_ajax.gdwwid_ajaxurl,
						data: {"action": "gdwwid_ajax_online_total"},
						success: function(data)
								{
									jQuery(".onlinecount .count").html(data);
								}
					});
				}
				
				gdw_find_online_users();
				
				setInterval(function(){
					gdw_find_online_users();	
				}, 60000)
			});
				
	</script>
	<div class="onlinecount">
		<span class="count">...</span>
		<span class='onlinelabel'><?php echo __("Users","gdwlang"); ?><br><?php echo __("Online","gdwlang"); ?></span>
	</div>
