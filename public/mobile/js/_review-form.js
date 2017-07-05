jQuery(function (){

	var dialog = $('#reviewPopup');
	
	var inpName = $('[name=name]',dialog);
	var inpMessage = $('[name=message]',dialog)
	var inpPhone = $('[name=phone]',dialog)
		.mask("+7 (999) 999-99-99", {placeholder:" "});
	
	var regPhone = /\+7\s\(\d\d\d\)\s\d\d\d\-\d\d\-\d\d/;	
	var error = $('.error-msg',dialog);
	
	dialog.on('twm-popup-open', function(){
		inpName.focus();	
	});
	
	
	$('form', dialog).submit(function (e){
		e.preventDefault();
		var hasError = false;
		var t = $(this);
		
		
		if(inpMessage.val().trim() == ''){
			inpMessage.addClass('error').focus();
			hasError = true;
			error.slideDown().text("Заполните текст отзыва");
		} else {
			inpMessage.removeClass('error');
		}	
		
		
		if(inpName.length != 0){
			if(inpName.val().trim() == ''){
				inpName.addClass('error').focus();
				hasError = true;
				error.slideDown().text("Укажите Ваше имя");
			} else {
				inpName.removeClass('error');
			}
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
