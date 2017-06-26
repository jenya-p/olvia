jQuery(function(){
	$('body').on('twm-popup-load', function(e, panel){
		panel = $(panel);
		if(!panel.hasClass('course-order-popup')) return;
		
		$('.field-tarif input[type=radio]', panel).change(function(){
			$('.tarif-details', panel).hide();
			var id = $('.field-tarif input[type=radio]:checked', panel).val()
			$('.tarif-details-' + id, panel).show();			
			
		});
		
		var form = $('form', panel);
		var btnSubmit = $('[type=submit]', form);
		form.submit(function(e){
			btnSubmit.prop('disabled', true).text('отравка...');			
		});
		
		
	});
})
	