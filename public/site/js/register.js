'use strict';
jQuery(function (){
	
	var loginFrom = $('.main .form-login');
	var registerFrom = $('.main .form-register');
	
	$('[name=phone]' , registerFrom).mask("+7 (999) 999-99-99", {placeholder:" "});
	
})