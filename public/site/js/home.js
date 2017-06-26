'use strict'; 
jQuery(function ($){
	$('.banner').bxSlider({
		mode: 'fade',
		responsive: true,
		controls: false
	});
	setTimeout(function(){
		$('ul.diplomas').lightSlider({autoWidth: true});	
	}, 1000)
	
})