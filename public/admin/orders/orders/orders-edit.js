"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	var inpUser = $('[name=user_id]', form);
	inpUser.userSelect();
		
	var updateUserInfo = function(queryData){
		console.log(queryData);
		$.get('/private/customer-details-ajax', queryData, function(res){
			if(res.result == 'ok'){
				console.log(res.customer);
				var name = $('[name=name]', form);
				if(name.val().trim() == ''){
					name.val(res.customer.name);
				}
				
				var skype = $('[name=skype]', form).val(res.customer.skype);				
				if(skype.val().trim() == ''){
					skype.val(res.customer.skype);
				}	
				
				var phone = $('[name=phone]', form).val(res.customer.phone_formated);
				if(phone.val().trim() == ''){
					phone.val(res.customer.phone_formated);
				}
				
				if(queryData.id == undefined && inpUser.val() == ''){
					inpUser.val(res.customer.id);
					inpUser.siblings('.user-select').val(res.customer.displayname);
				}
						 
			}			
		});
	}
	
	inpUser.change(function(){
		var id = inpUser.val();
		if(id == '') return;
		updateUserInfo({'id': id});
	});
	
	$('[name=phone]', form).change(function(){
		var val = $(this).val().trim();
		if(val != ''){
			updateUserInfo({'phone': val});	
		}		
	});
	
	$('[name=skype]', form).change(function(){
		var val = $(this).val().trim();
		if(val != ''){
			updateUserInfo({'skype': val});	
		}		
	});
	
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
	
	tarifsList.on('change', '[name=tarif_id]', function(){
		var t = $('[name=tarif_id]:checked', tarifsList);
		var e = $('[name=event_id]:checked', eventList);
		if(e.data('type') == 'perm'){
	 		var subs = t.data('subscription');
			var state = 0;
			$('[type=checkbox]', sheduleList).each(function(){
				var chkDate = $(this);
				if(chkDate.prop('checked')){
					if(state > subs - 1 && state > 0){
						chkDate.prop('checked', false);
					} else {
						state++;	
					}					
				} else if(state < subs && state > 0){
					chkDate.prop('checked', true);
					state++;
				} 
			});
		}

		var payed = $('[name=payed]', form).val().trim();
		if( payed == "" || payed == 0){
			$('[name=price]', form).val(t.data('price'));	
		}		
	})
	
})