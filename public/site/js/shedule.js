'use strict'
jQuery(function (){
	
	var filterForm = $('#sheduleFilterForm'); 
	
	var inpTag = $('[name=tag]', filterForm);
	var inpTag2 = $('[name=tag2]', filterForm);
	var inpMaster = $('[name=master]', filterForm);
	
	filterForm.submit(function(e){
		e.preventDefault();
		var href = filterForm.attr('action');
		if(inpTag.val() != ''){
			href += '/tag-'+inpTag.val()
		}
		if(inpTag2.val() != ''){
			href += '/tag2-'+inpTag2.val()
		}
		if(inpMaster.val() != ''){
			href += '/master-'+inpMaster.val()
		}
		document.location = href ;
		return false;
	});
	
	$('.type-switcher', filterForm).click(function(e){
		e.preventDefault();
		filterForm.attr('action', $(this).attr('href'));
		filterForm.submit();		
		return false;
	});
	
	var list = $('.shedule-blocks.column-view');
	if(list.length == 1){
		var columns = [$('.column-1',list), $('.column-2',list), $('.column-3',list)];
		var columnCount = columns.length;
		
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
		

		$('.column-view-item',list).each(processColumnItem);
	}
	
	
})