<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
			setInterval(function(){
				jQuery.ajax(
						{
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_visitors2"},
					success: function(data)
							{
								jQuery(".visitors2").html(data);
							}
						});	
			}, 300000)
	});
			
</script>


<div class="visitors2"></div>


</div>