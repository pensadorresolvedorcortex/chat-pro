<div class="gdw-stats">

<script>		
	jQuery(document).ready(function(){
		function countrys_used_by_visitors(){
			jQuery.ajax({
					type: 'POST',
					url: gdwwid_ajax.gdwwid_ajaxurl,
					data: {"action": "gdwwid_country_type"},
					success: function(data)
							{
								jQuery(".gdw_country_type").html(data);
							}
				});
		}
			countrys_used_by_visitors();
	
			setInterval(function(){
					countrys_used_by_visitors();
			}, 300000)


	        jQuery(document).on('click', "#gdw_country_type_wp_dashboard .ui-sortable-handle", function () {
	                            if(!jQuery(this).parent().hasClass("closed")){
						jQuery(".gdw_country_type").html("Loading...");
						countrys_used_by_visitors();
						//console.log("recall");
	                            }
	        });


	});
			
</script>

<div class="gdw_country_type"><?php echo __("Loading...","gdwlang"); ?></div>


</div>