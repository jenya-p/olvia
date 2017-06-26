"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');
	
	$('[name=created]').dateRangePicker({
//		startDate: moment().add(-5, 'month').format('DD.MM.YYYY'),
//		endDate: moment().add(5, 'month').format('DD.MM.YYYY'),
		language:'ru', 
		format: 'DD.MM.YYYY',
		startOfWeek: 'monday',
		
		inline:false,
		
		showTopbar: false,
		singleMonth: true,
		
		singleDate : true,
		showShortcuts: false
	});
	
	
	$('#author', form).userSelect();
	
	$('input[name=thumb]').imageUpload();
})