'use strict';

$.fn.courseSelect = function(options) {
	$(this).each(function() {
		var wrapper = $(this).parent('.field-inner');
		var inpId = $('[type=hidden]', wrapper);
		var inpName = $('.course-select', wrapper);
		if(options && options.serviceUrl){
			var serviceUrl = options.serviceUrl;
		} else {
			var serviceUrl = '/private/course-ajax-select';
		}
		inpName.autocomplete({
			serviceUrl : serviceUrl,
			paramName : 'q',
			onSelect : function(suggestion) {
				inpId.val(suggestion.id);
				inpId.trigger('change', suggestion);
			},
			onInvalidateSelection : function() {
				inpId.val("");
			}
		});
	});
}