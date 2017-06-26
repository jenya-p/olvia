"use strict"
jQuery(function(){
	
	var filter = $('.lay-sidebar-inner form');
	var filterCourseHidden = 	$('#course_id_hidden', filter);
	var filterCourse = 			$('#course_id', filter);
	
	filterCourse.autocomplete({
		serviceUrl : '/private/course-ajax-select',
		paramName : 'q',
		width: 700,
		onSelect : function(suggestion) {
			filterCourseHidden.val(suggestion.id);
			filter.submit();			
		},
		onInvalidateSelection : function() {
			filterCourseHidden.val("");
		}
	});	
	
})