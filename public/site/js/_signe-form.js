//Форма записи на консультацию
jQuery(function(){
	var dialog = $('#signPopup');
	
	var inpName = $('[name=name]',dialog);
	var inpPhone = $('[name=phone]',dialog)
			.mask("+7 (999) 999-99-99", {placeholder:" "});
	var regPhone = /\+7\s\(\d\d\d\)\s\d\d\d\-\d\d\-\d\d/;	
	var error = $('.error-msg',dialog);
	
	dialog.on('twm-popup-open', function(){
		inpPhone.focus();
	});	
	
	$('form', dialog).submit(function (e){
		e.preventDefault();
		var hasError = false;
		var t = $(this);

		if(!regPhone.test(inpPhone.val()) || inpPhone.val().trim() == ''){
			inpPhone.addClass('error').focus();
			hasError = true;
			error.slideDown().text("Укажите Ваш телефон");;
		} else {
			inpPhone.removeClass('error');
		}
		
		if(!hasError){
			error.slideUp().text("");			
			$.post(t.attr('action'), t.serializeArray(), function(d){
				if(d.result == 'ok'){
					$('.done', dialog).show();
					t.hide();
				} else {
					$('.error-msg', dialog).html(d.message[0]).slideDown();
				}
			}, 'json')
		}
		
		return false;
	});
});