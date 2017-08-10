"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	$('[name=course_id]').courseSelect();

	$('[name=discounts]').discountsElement();
	
})