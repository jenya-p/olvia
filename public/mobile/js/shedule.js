'use strict'
jQuery(function (){
	
	var filterForm = $('#sheduleFilterForm'); 
	
	var filterOpener = $('.breadcrumb-bar .icon-search');
	filterOpener.click(function(){
		if(filterOpener.hasClass('opened')){
			filterForm.slideUp();
			filterOpener.removeClass('opened');
		} else {
			filterForm.slideDown();
			filterOpener.addClass('opened');
		}
	});
	
	
	$('.week-select-pager').change(function(){
		document.location = $(':selected', this).data('url');
	});
	
	var inpTag = $('[name=tag]', filterForm);
	var inpTag2 = $('[name=tag2]', filterForm);
	var inpMasterId = $('[name=master]', filterForm);
	var inpMaster = $('.master-select', filterForm )
	
	filterForm.submit(function(e){
		e.preventDefault();
		var href = filterForm.attr('action');
		if(inpTag.val() != ''){
			href += '/tag-'+inpTag.val()
		}
		if(inpTag2.val() != ''){
			href += '/tag2-'+inpTag2.val()
		}
		if(inpMasterId.val() != ''){
			href += '/master-'+inpMasterId.val()
		}
		document.location = href ;
		return false;
	});
	
	
})