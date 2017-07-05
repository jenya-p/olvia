'use strict';
jQuery(function (){
	
	var form = $('.main .form-user-commons');
	
	$('#phone' , form).mask("+7 (999) 999-99-99", {placeholder:" "});
	
	$('#birthday' , form).mask("99.99.9999", {placeholder:" "});
	
	
	var showError = function ($elem, text){
		$elem.text(text);
		$elem.fadeIn();
		var tm = setTimeout(function(){
			$elem.fadeOut()
		}, 5000);
		$elem.one('click', function(){
			$elem.fadeOut();
			resetTimeout(tm);
		})
	}
	
	// Image
	var imageWrp = $('.main .image-wrp');
	var image = $('img', imageWrp);
	var imageInput = $('.main .image-panel input[type=file]');
	var imageInputElement = imageInput[0];
	var imageError = $('.main .image-panel .error');

	imageWrp.click(function() {
		imageInput.click();
	});
	
	imageInput.change(function() {

		var url = imageInput.val();
		var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();

		if (imageInputElement.files && imageInputElement.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {

			var data = new FormData();
			data.append('image', imageInputElement.files[0]);
			
			$.ajax({
			    url: imageInput.data('url'),
			    type: 'POST',
			    data: data,
			    cache: false,
			    dataType: 'json',
			    processData: false,
			    contentType: false,
			    success: function(data, textStatus, jqXHR){
			    	if(data.result == 'ok'){
			    		var d = new Date();
			    		image.attr('src', data.image + '?' + d.getTime());
			    	} else {
			    		showError(imageError, data.message);
			    	}
			    },
			    error: function(jqXHR, textStatus, errorThrown){
			    	console.log(errorThrown);
			    	console.log(jqXHR);
			    	showError(imageError, textStatus);
			    }
			});
			
		} else {
			showError(imageError, 'Сюда можно загружать только картинки (jpeg, png, bmp)');
		}				
	});

	var showFieldError = function (input, msg){
		var field = input.parent('.field');		
		field.addClass('has-error');
		if(msg){
			$('.error', field).text(msg).show();	
		}
		input.focus();
		setTimeout(function(){
			input.one('keydown', function(){
				setTimeout(function(){
					field.removeClass('has-error');
					$('.error', field).hide();				
				}, 250);
			});			
		},1);
				
	}

	// Password
	var formPassword = $('.main .form-password');
	var inpPass1 = $('[name=password1]', formPassword);
	var inpPass2 = $('[name=password2]', formPassword);
	var msgPassSuccess =  $('.success', formPassword);
	var btnPassSubmit =  $('[type=submit]', formPassword);
	formPassword.submit(function(e){
		e.preventDefault();
		var pass1 = inpPass1.val();
		var pass2 = inpPass2.val();
		var hasError = false;
		if(pass1.length < 5){
			showFieldError(inpPass1, 'Слишком короткий пароль, минимум 5 символов');
			hasError = true;
			
		} else if(pass1 != pass2){
			showFieldError(inpPass2, 'Пароли не совпадают' );
			hasError = true;
			
		}
		if(!hasError){
			btnPassSubmit.prop('disabled', true).text('Сохранение...');			
			var data = formPassword.serializeArray();
			$.post(formPassword.attr('action'), {
						password1: pass1,
			            password2: pass2 }, 
			                      function(d){
					btnPassSubmit.prop('disabled', false).text('Поменять пароль');
					console.log(d);
			    	if(d.result == 'ok'){			    		
			    		msgPassSuccess.fadeIn('fast');			    		
			    		var tm = setTimeout(function(){
			    			msgPassSuccess.fadeOut();	
			    		}, 2500);
			    		msgPassSuccess.one('click', function(){
			    			clearTimeout(tm);
			    			msgPassSuccess.fadeOut();
			    		});
			    		
			    	} else {
			    		alert(d.message);
			    	}
			    });
			
		}
		return false;
	})
	
	
	
	
})