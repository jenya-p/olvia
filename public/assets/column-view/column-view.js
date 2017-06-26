jQuery(function(){
	var wrp = $('.column-view');
	var columns =[$('.column-1',wrp), $('.column-2',wrp), $('.column-3',wrp)]
	var items = $('.column-view-item',wrp);
	
	
	var oldColumnCount = -1;
	$(window).resize(function(e, pass){		
		if(window.matchMedia){
			// lucky modern browsers
			columnCount = (window.matchMedia('(min-width: 1024px)').matches) ? 3: (
				(window.matchMedia('(min-width: 660px)').matches) ? 2: 1 );
		} else {
			// unlucky old explorer
			var w = $(document).width();
			columnCount = w > 1024 ? 3 : (w > 660 ? 2 : 1);
		}
		if(oldColumnCount == columnCount && pass!='secondPass') return;
		oldColumnCount = columnCount;
		
		if(pass == 'firstPass'){
			$('.column',wrp).html('');
			itemsToProcess = items.slice(0, -10);
			columnWidth = $('.column:first',wrp).innerWidth();
			items.each(function(){
				var img = $('img', this);
				if(columnWidth > 380){
					img.attr('src', img.attr('data-medium-src'));	
				} else {
					img.attr('src', img.attr('data-small-src'));
				}				
			})
		} else if(pass == 'secondPass'){
			itemsToProcess = items.slice(-10);
		} else {
			$('.column',wrp).html('');
			itemsToProcess = items;	
		}		
		
		itemsToProcess.each(function(){
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
		});				
		
	}).trigger('resize', 'firstPass');
	
	
	window.onload = function() {
		$(window).trigger('resize', 'secondPass');
	};
})