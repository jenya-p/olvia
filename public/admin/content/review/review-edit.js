"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');

	$('[name=userpic]', form).imageUpload();
	
	$('[name=date]', form).dateRangePicker({
		startDate: moment().add(-5, 'month').format('DD.MM.YYYY'),
		endDate: moment().add(5, 'month').format('DD.MM.YYYY'),
		language:'ru', 
		format: 'DD.MM.YYYY',
		startOfWeek: 'monday',		
		inline:false,		
		showTopbar: false,
		singleMonth: true,		
		singleDate : true,
		showShortcuts: false
	});
	
	var refsList = $('.refs-group .item-list', form);
		
	refsList.on('click','a.delete', function(e){
		e.preventDefault();
		var t = $(this), ok = true;
		var tr = t.parents('.item-list tr');
		console.log(tr);
		tr.css({'opacity': 0.5});				
		$.getJSON(t.attr('href'), function(d){
			if(d.result == 'ok'){					
				tr.remove();
			} else {							
				tr.css('opacity', 1);
				if(d.message){
					Alerts.error(d.message);
				} else {
					Alerts.error("Ошибка удаления");
				}
			}				
		});	
		return false;				
	});
	
	var inpRefAdd = $('.refs-group #add_ref_input', form);
	
	inpRefAdd.autocomplete({
		serviceUrl : '/private/review-ref-suggestion', 
		paramName : 'q',
		onSelect : function(suggestion) {			
			$.get('/private/review-ref-add/' + inpRefAdd.data('review_id') + '/' + suggestion.entity + '/' + suggestion.item_id, function(d){
	    		if(d.result == 'ok'){					
	    			refsList.append($(d.html));
				} else {							
					if(d.message){
						Alerts.error(d.message);
					} else {
						Alerts.error("Ошибка добавления связи");
					}
				}
	    		inpRefAdd.val("");
	    	})
		}			
	});
	
	
	
})