
jQuery(document).ready(function()
	{




      var hook = true;
      window.onbeforeunload = function() {
        if (hook) {
			
		  document.cookie="knp_landing=0; path=/";
		  
				var knp_online_count = -1;
				jQuery.ajax(
					{
				type: 'POST',
				url: gdwwid_ajax.gdwwid_ajaxurl,
				data: {"action": "gdwwid_offline_visitors", "knp_online_count":knp_online_count},
				success: function(data)
						{
							
						}
					});	
		  
		  
		  
		  
		  
        }
      }

		
		
		
		
	
	});	







