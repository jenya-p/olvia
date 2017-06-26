"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	var inpUser = $('[name=user_id]', form);
	inpUser.userSelect();
	
	inpUser.change(updateUserInfo);
		
	var updateUserInfo = function(){
		var id = inpUser.val();
		if(id == '') return;
		$.get('/private/get-user-info', {'id': id}, function(res){
			if(res.result == 'ok'){
				
				$('[name=name]', form).val(res.item.name);
				$('[name=skype]', form).val(res.item.skype);
				$('[name=phone]', form).val(res.item.phone_formated);
			} else {
				Alerts.error(res.message);			 
			}			
		});
	}
	
	// $('.update_user_info_link').click(updateUserInfo);
	
	var eventList = 	$('.event-item-list', form);
	var tarifsList = 	$('.tarifs-item-list', form);
	var sheduleList = 	$('.shedule-item-list', form);
	

	var inpCourse = 	$('[name=course_id]');

	inpCourse.courseSelect({
		serviceUrl : '/private/order-edit-course-suggestion'
	});
	
	var currentCourseId = inpCourse.val();
	
	inpCourse.change(function(e, d){
		if(currentCourseId == d.id) return;
		currentCourseId = d.id;
		
		eventList.hide();
		tarifsList.hide();
		sheduleList.hide();
		
		$.get(eventList.data('url'), {'course_id': d.id}, function(res){
			if(res.result == 'ok'){
				$('tbody',eventList.show()).html(res.html_events);
				$('tbody',tarifsList.show()).html(res.html_tarifs);
				$('tbody',sheduleList.show()).html(res.html_shedule);
			} else {
				Alerts.error(res.message);			 
			}
		});
	});
	
	
	eventList.on('change', '[name=event_id]', function(){
		var eventId = $('[name=event_id]:checked', eventList).val();
				
		tarifsList.hide();
		sheduleList.hide();
		
		$.get(tarifsList.data('url'), {'event_id': eventId}, function(res){
			if(res.result == 'ok'){
				
				$('tbody',tarifsList.show()).html(res.html_tarifs);
				$('tbody',sheduleList.show()).html(res.html_shedule);
				
			} else {
				Alerts.error(res.message);			 
			}
		});		
	})
	
	
	
})