<script>		
	jQuery(document).ready(function()
		{

			function gdw_find_online_user_details(){
				jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_visitors_page"},
					success: function(data)
							{
								jQuery(".gdw_visitors_details").html(data);
							}
				});	
			}

			gdw_find_online_user_details();
			
			setInterval(function(){
				gdw_find_online_user_details();
			}, 300000)
	});
			
</script>

	<div class="gdw_visitors_details"></div>
