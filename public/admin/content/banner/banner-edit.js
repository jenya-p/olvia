"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');
	
	$('[name=date_from]', form).dateRangePicker({
		startDate: moment().add(-15, 'month').format('DD.MM.YYYY HH:mm'),
		endDate: moment().add(15, 'month').format('DD.MM.YYYY HH:mm'),
		language:'ru', 
		format: 'DD.MM.YYYY HH:mm',
		startOfWeek: 'monday',		
		inline:false,		
		showTopbar: false,
		singleMonth: true,		
		singleDate : true,
		showShortcuts: false,
		time: {
			enabled: true
		},
		defaultTime: '00:00'
	});
	
	$('[name=date_to]', form).dateRangePicker({
		startDate: moment().add(-15, 'month').format('DD.MM.YYYY HH:mm'),
		endDate: moment().add(15, 'month').format('DD.MM.YYYY HH:mm'),
		language:'ru', 
		format: 'DD.MM.YYYY HH:mm',
		startOfWeek: 'monday',		
		inline:false,		
		showTopbar: false,
		singleMonth: true,		
		singleDate : true,
		showShortcuts: false,
		time: {
			enabled: true
		},
		defaultTime: moment().startOf('day').toDate()
	});
	
	$('[name=image]', form).imageUpload();
	
	$('[name=image_m]', form).imageUpload();
	
})