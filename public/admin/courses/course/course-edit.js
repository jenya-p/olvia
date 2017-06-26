"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	$('[name=image]', form).imageUpload();
	
	$('[name=tags]', form).tagSelect();
	
})