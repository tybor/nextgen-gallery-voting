jQuery(document).ready(function() {
		jQuery("a#nggv_more_results").click(function(e) { //button click to open more detail on the voting
				tb_show("", "#TB_inline?width=640&height=300&inlineId=nggvShowList&modal=true", false); //thick box seems to be included, so lets use it :)
				
				jQuery.get(nggv_ajax_url, 'gid='+nggv_gid, function(data, status) {
						if(status == 'success') {
							jQuery("div#nggvShowList_content").html(data);
						}else{
							jQuery("div#nggvShowList_content").html("There was a problem retrieving the list of votes, please try again in a momement.");
						}
				});
				e.preventDefault();
				return false; //cancel click
		});
		
		jQuery("a#nggv_more_results_close").click(function(e) {
				tb_remove();
				e.preventDefault();
				return false;
		});
		
		jQuery("a.nggv_more_results_image").click(function(e) { //button click to open more detail on the voting
				var pid = parseInt(this.id.substr(24));
				tb_show("", "#TB_inline?width=640&height=300&inlineId=nggvShowList&modal=true", false); //thick box seems to be included, so lets use it :)
				
				jQuery.get(nggv_ajax_url, 'pid='+pid, function(data, status) {
						if(status == 'success') {
							jQuery("div#nggvShowList_content").html(data);
						}else{
							jQuery("div#nggvShowList_content").html("There was a problem retrieving the list of votes, please try again in a momement.");
						}
				});
				
				e.preventDefault();
				return false; //cancel click
		});
		
		/*
		jQuery("a.nggv_clear_image_results").click(function(e) { //button click to clear all votes per image. Just add a quick confirm to it
				if(!confirm('Are you sure you want to delete all votes for this image? This cannot be undone!')) {
					e.preventDefault();
					return false;
				}
		});
		*/
});