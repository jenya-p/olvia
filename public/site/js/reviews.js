'use strict';
jQuery(function (){

	var form = $('#reviewForm');
	var doneMessage = $('#reviewFormDone');
	var inpName = $('[name=name]',form);
	var inpNameWrp = inpName.parent('.field');
	var inpMessage = $('[name=message]',form)
	var inpMessageWrp = inpMessage.parent('.field');
	var inpPhone = $('[name=phone]',form)
		.mask("+7 (999) 999-99-99", {placeholder:" "});
	
	var regPhone = /\+7\s\(\d\d\d\)\s\d\d\d\-\d\d\-\d\d/;	
	var error = $('.error-msg',form);
		
	
	form.submit(function (e){
		e.preventDefault();
		var hasError = false;
		var t = $(this);
		
		
		if(inpMessage.val().trim() == ''){
			inpMessageWrp.addClass('has-error').focus();
			hasError = true;
			error.slideDown().text("Заполните текст отзыва");
		} else {
			inpMessageWrp.removeClass('has-error');
		}	
		
		
		if(inpName.length != 0){
			if(inpName.val().trim() == ''){
				inpNameWrp.addClass('has-error').focus();
				hasError = true;
				error.slideDown().text("Укажите Ваше имя");
			} else {
				inpNameWrp.removeClass('has-error');
			}
		}
			
		if(!hasError){
			error.slideUp().text("");			
			$.post(form.attr('action'), form.serializeArray(), function(d){
				if(d.result == 'ok'){
					doneMessage.slideDown();
					form.slideUp();
					t.hide();
				} else {
					$('.error-msg', dialog).html(d.message[0]).slideDown();
				}
			}, 'json')
		}
		return false;
	});
	
	
	var list = $('.review-list');  
	var columns = [$('.column-1',list), $('.column-2',list)];
	var columnCount = columns.length;
	var win = $(window);
	var block = false;
	
	var processReviewItem = function(){
		var t = $(this);
		var i = 0;
		var h = columns[i].height();		
		if(columnCount > 1){
			if(columns[1].height() < h){
				i = 1;
				h = columns[1].height();				 
			}
			if(columnCount > 2){
				if(columns[2].height() < h){
					i = 2;
					h = columns[2].height();				 
				}
			}
		}
		columns[i].append(t);		
	}	
	
	$('.review-block',list).each(processReviewItem);

	win.scroll(function(){
		if(!block){
			var h = list.offset().top + list.outerHeight();
			var s = win.scrollTop() + win.innerHeight();
			if(h+200 < s){
				block = true;
				var p = list.data('page') + 1;
				$.get(list.data('url'), {p: p}, function(d){
					var items = $('<div>' + d + '</div>').find('.review-block');
					if(items.length > 0){
						items.each(processReviewItem);
						block = false;
						list.data('page', p);
					} else {
						list.next('.ajax-list-loading').hide();
					}
				});
			}
		}
	});
	
	
})