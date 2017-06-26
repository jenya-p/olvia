'use strict';

jQuery(function($){
	var commentsWrp = $('.field-comments-wrp');
	var inpText = $('[name=new_comment]',commentsWrp);
	var inpSubmit = $('.new-comment-add',commentsWrp);
	var inpReset = $('.new-comment-reset',commentsWrp);
	
	inpSubmit.click(function(e){
		e.preventDefault();
		var text = inpText.val();
		if(text.trim() == '') return false;
		
		$.post(inpSubmit.data('url'), {'body': text}, function(d){
			if(d.result == 'ok'){					
				$('.field-inner:FIRST',commentsWrp).after($(d.html));
			} else {							
				if(d.message){
					Alerts.error(d.message);
				} else {
					Alerts.error("Ошибка добавления комментария");
				}
			}	
		})
		inpText.val('');
		return false;
	});
	
	
	inpReset.click(function(e){
		e.preventDefault()
		inpText.val('');
		return false;
	});
	
	
	commentsWrp.on('click','a.item-delete', function(e){
		e.preventDefault();
		var t = $(this);
		var tr = t.parents('.comment-item');
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
	
	
	
});
