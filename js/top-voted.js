jQuery(document).ready(function() {
		jQuery('#nggv_date_from, #nggv_date_to').datepicker({
				dateFormat : 'yy-mm-dd'
		});
		
		jQuery('a.nggv-top-vote-item').click(function(e) {
				jQuery(this).parents('tr').next().toggle();
				e.preventDefault();
		});
});

