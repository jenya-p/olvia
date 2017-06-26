"use strict"
jQuery(function(){

	$('.item-list').on('click','a.item-top', function(e){
		e.preventDefault();
		var t = $(this);
		var i = $('i', t);
		i.addClass('fa-spinner');		
		$.getJSON(t.attr('href'), function(d){
			if(d.result == 'ok'){				
				if(d.top == 1){					
					i.addClass('active');	
				} else {
					i.removeClass('active');
				}	
			}
			i.removeClass('fa-spinner');
		});
		return false;
	})
	
	
})