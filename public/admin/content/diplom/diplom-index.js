"use strict"
jQuery(function(){

	$('.item-list').on('click','a.item-home', function(e){
		e.preventDefault();
		var t = $(this);
		var i = $('i', t);
		i.addClass('fa-spinner');		
		$.getJSON(t.attr('href'), function(d){
			if(d.result == 'ok'){				
				if(d.home == 1){					
					i.addClass('active');	
				} else {
					i.removeClass('active');
				}	
			}
			i.removeClass('fa-spinner');
		});
		return false;
	})

	
	$('#filter_master_select').userSelect();
	
})