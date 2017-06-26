"use strict"
jQuery(function(){
	
	var form = $('#itemForm');
	
	$('[name=image]', form).imageUpload();
	
	var diplomList = $('.diplomas-wrp', form);
	
	
	diplomList.on('click','a.delete', function(e){
		e.preventDefault();
		var t = $(this), ok = true;
		var tr = t.parents('.photo-block');
		tr.css({'opacity': 0.5});				
		if(diplomList.data('deletion-confirm')){	
			ok = confirm(diplomList.data('deletion-confirm'));
		}
		if(ok){
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
		} else {
			tr.css('opacity', 1);		
		}	
		return false;				
	});
	
	
	diplomList.on('click','a.status', function(e){
		e.preventDefault();
		var t = $(this);
		var i = $('i', t);
		i.addClass('fa-spinner');		
		$.getJSON(t.attr('href'), function(d){
			if(d.result == 'ok'){
				if(d.status == 1){					
					i.addClass('fa-eye').removeClass('fa-eye-slash');	
				} else {
					i.removeClass('fa-eye').addClass('fa-eye-slash');
				}	
			}
			i.removeClass('fa-spinner');
		});
		return false;
	})
	
})