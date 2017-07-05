
//Форма обратной связи
jQuery(function($){
	var dialog = 	$('#callMePopup');
	var error = 	$('.error-msg'	 , dialog);	
	var form = 		$('form'		 , dialog);	
	var inpName = 	$('[name=name]'  , form);	
	var inpPhone = 	$('[name=phone]' , form).mask("+7 (999) 999-99-99", {placeholder:" "});	
	var submit = 	$('[type=submit]', form);
	
	var regPhone = /\+7\s\(\d\d\d\)\s\d\d\d\-\d\d\-\d\d/;	
	
	dialog.on('twm-popup-open', function(){
		inpPhone.focus();
	});
		
	form.submit(function (e){
		e.preventDefault();
		var hasError = false;
		if(inpName.val().trim() == ''){
			inpName.addClass('error').focus();
			hasError = true;
			error.slideDown().text("Укажите Ваше имя");
		} else {
			inpName.removeClass('error');
		}
		if(!regPhone.test(inpPhone.val()) || inpPhone.val().trim() == ''){
			inpPhone.addClass('error').focus();
			hasError = true;
			error.slideDown().text("Укажите Ваш телефон");
		} else {
			inpPhone.removeClass('error');
		}
		if(!hasError){
			error.slideUp().text("");
			submit.hide();
			$.post(form.attr('action'), form.serializeArray(), function(d){
				if(d.result == 'ok'){
					$('.done', dialog).show();
					form.hide();
				} else {
					$('.error-msg', dialog).html(d.message).slideDown();
				}
			}, 'json');
		}
		return false;
	});
	
	$('a.close', dialog).click(function(){
		$.twmPopupClose();
	})
	
});
