'use strict'; 
jQuery(function ($){
	$('.banner').bxSlider({
		mode: 'horizontal',
		responsive: true,
		controls: false,
		touchEnabled: true,
		pager: true
	});
	
	setTimeout(function(){
		$('ul.diplomas').lightSlider({
			autoWidth: true,
			controls: false	
		});	
	}, 1000)
	
})