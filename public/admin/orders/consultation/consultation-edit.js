"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');
	
	$('[name=user_id]', form).userSelect();
	
	var inpMaster = $('[name=master_id]', form);
	
	inpMaster.userSelect();
	
	$('[name=meet_date]', form).dateRangePicker({
		startDate: moment().add(-12, 'month').format('DD.MM.YYYY HH:mm'),
		endDate: moment().add(12, 'month').format('DD.MM.YYYY HH:mm'),
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
	
	var priceList = $('.price-item-list', form);
	priceList.on('click', 'tr.item', function(e){
		e.preventDefault();
		$(this).find('td:first label.radio input[type=radio]').prop('checked', true);
		return false;
	});
	
	priceList.on('click', 'label.radio', function(e){	
		e.preventDefault();
		$(this).find('input[type=radio]').prop('checked', true);
		return false;
	});
	
	var currentMasterId = inpMaster.val();
	
	inpMaster.change(function(e, d){
		if(currentMasterId == d.id) return;
		currentMasterId = d.id;
		$('tbody',priceList).html('');
		$.get(priceList.data('url'), {'master_id': d.id}, function(res){
			if(res.result == 'ok'){
				$('tbody',priceList).html(res.html);
			} else {
				Alerts.error(res.message);			 
			}
		})
	});
	
})