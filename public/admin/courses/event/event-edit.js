"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	var inpCourse = $('[name=course_id]');
	inpCourse.courseSelect();

	$('[name=masters]').userMultiSelect();
	
	$('[name=expiration_date]').dateRangePicker({
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
	
	
	var priceList = $('.price-item-list', form);

	var currentCourseId = inpCourse.val();
	
	inpCourse.change(function(e, d){
		if(currentCourseId == d.id) return;
		currentCourseId = d.id;
		$('tbody',priceList).html('');
		$.get(priceList.data('url'), {'course_id': d.id}, function(res){
			if(res.result == 'ok'){
				$('tbody',priceList).html(res.html);
			} else {
				Alerts.error(res.message);			 
			}
		})
	});
	
//	$('#add_date_week_range').dateRangePicker({
//		startDate: moment().add(-1, 'month').format('DD.MM.YYYY'),
//		endDate: moment().add(12, 'month').format('DD.MM.YYYY'),
//		language:'ru', 
//		format: 'DD.MM.YYYY',
//		startOfWeek: 'monday',		
//		inline:false,		
//		showTopbar: false,
//		singleMonth: true,		
//		singleDate : false,
//		showShortcuts: false,
//		separator: ' - '
//	});
	
	$('.shedule-item-list a.item-edit').each(function(){
		$(this).dateRangePicker({		
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
			defaultTime: '00:00',
			getValue: function(){
				return $(this).data('value');
			},
			setValue: function(s){
				var t = $(this);
				if(t.data('value') != s){					
					var m = moment(s, 'DD.MM.YYYY HH:mm');			
					var tds = t.parent().parent().find('td');					
					t.addClass('fa-spinner fa-spin');
					jQuery.post(t.attr('href'), {'date': s}, function(d){
						if(d.result == 'ok'){
							$(tds[0]).text(m.format('D MMM YYYY, dddd'));
							$(tds[1]).text(m.format('HH:mm'));							
							t.data('value', s);	
						} else {
							alert(d.message);
						}
						t.removeClass('fa-spinner fa-spin');						
					})
				}				
			}
		});
	});
	$('.shedule-item-list a.item-edit').click(function(e){
		e.preventDefault();
		return false;
	})
	
})