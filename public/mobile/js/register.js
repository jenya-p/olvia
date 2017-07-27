'use strict';
jQuery(function (){
	
	var loginFrom = $('.main .form-login');
	var registerFrom = $('.main .form-register');
	
	$('.main .switcher a').click(function(e){
		$('.main .switcher a').removeClass('active');
		var t = $(this);
		t.addClass('active');
		if(t.hasClass('login')){
			loginFrom.show();
			registerFrom.hide();
		} else {
			loginFrom.hide();
			registerFrom.show();
		}
		
	})
	
	$('[name=phone]' , registerFrom).mask("+7 (999) 999-99-99", {placeholder:" "});
	
})