"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	$('input[name=image]').imageUpload();
	
	$('input[name=master_id]').userSelect();
	
})