jQuery(function (){
	var list = $('.master-list');  
	var columns = [$('.column-1',list), $('.column-2',list)];
	var columnCount = columns.length;
	var win = $(window);
	var block = false;
	
	var processColumnItem = function(){
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
	
	$('.block',list).each(processColumnItem);
	
	win.scroll(function(){
		if(!block){
			var h = list.offset().top + list.outerHeight();
			var s = win.scrollTop() + win.innerHeight();
			if(h+100 < s){
				block = true;
				var p = list.data('page') + 1;
				$.get(list.data('url'), {p: p}, function(d){
					var items = $('<div>' + d + '</div>').find('.block');
					if(items.length > 0){
						items.each(processColumnItem);
						block = false;
						list.data('page', p);
					} else {
						list.next('.ajax-list-loading').slideUp();
					}					
				});
			}
		}
	});
	
	
	
	
})