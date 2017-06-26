'use strict';

jQuery(function (){
	moment.locale('ru');
	
	var form = $('#courseOrderForm'); 
	var rightAsside = $('aside.right');
	var selectors = $('h3 a', rightAsside);
	
	var panels = $('#panelToggleCalendar, #panelToggleList');
	var panelWrapper = $('.date-field-toggle-wrapper');
	
	
	selectors.click(function(e){		
		e.preventDefault();
		var selector = $(this);
		var panel = $(selector.attr('href'));

		selectors.removeClass('active');				
		selector.addClass('active');		
		
		panels.hide();		
		panel.show();
		
		panelWrapper.css({'height': panelWrapper.height()});
		panelWrapper.animate({'height': panel.height()});
		
		return false;
	});
	
	
	var calendarWrp = $('#calendar_selector_wrapper', rightAsside);
	
	calendarWrp.on('click', '.calendar-pager a', function(e){
		e.preventDefault();
		var t = $(this);
		$('.tarif-details',form).remove();
		$.get(t.attr('href'), function(d){
			calendarWrp.html(d);
			changeTarif();
		});
		return false;
	})
	
	calendarWrp.on('change', '[name=date]', function(e){
		var t = $(this);
		$('.tarif-details',form).remove();
		$.get(t.data('tarifs-url'), function(d){
			calendarWrp.find('#tarif_selector_wrapper').html(d)
			changeTarif();
		});
	});
	
	var sheduleWrp = $('#shedule_selector_wrapper');
	sheduleWrp.on('click', '.month-pager a', function(e){
		e.preventDefault();
		var t = $(this);
		$('.tarif-details',form).remove();
		$.get(t.attr('href'), function(d){
			sheduleWrp.html(d);			
			changeTarif();
		});
		return false;
	});
	
	
	var changeTarif = function (){
		$('.tarif-details',form).hide();
		var tarifId = $('.field-tarif [name=tarif]:checked').val();
		console.log();
		$('.field-message',form).after(
				$('.tarif-details-'+tarifId,form).show()
				)
		;

	}	
	calendarWrp.on('change', '[name=tarif]', changeTarif);	
	changeTarif();
	

	/* 	//////////////////////////////////////////////////////////// 
	
	$('#calendarInput', form).dateRangePicker({
		startDate: moment().format('YYYY-MM-DD'),
		endDate: moment().add(5, 'month').format('YYYY-MM-DD'),
		language:'ru', 
		format: 'YYYY-MM-DD',
		startOfWeek: 'monday',
		
		inline:true,
		container: $('#calendarSelector', form),
		alwaysOpen:true,
		
		showTopbar: false,
		singleMonth: true,
		
		singleDate : true,
		showShortcuts: false,
		beforeShowDay: function(t){
			var valid = (t.getDay() == 2 || t.getDay() == 5); 
			var _class = '';
			var _tooltip = valid ? '19:00 - Карпенков Юрий' : '';
			return [valid,_class,_tooltip];
		}
	});
	*/
})