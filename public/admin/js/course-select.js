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
			},
			minChars: 3,
			deferRequestBy: 250
			
		});
	});
}


$.fn.courseMultiSelect = function(options) {
	
	var createRow = function(item){
		return `<tr data-id="${item.id}">' +
			<td class="name">${item.value}</td>
			<td class="options">
				<a href="javascript:;" class="fa fa-remove course-remove"></a>
			</td>
		</tr>`;
	}
	
	var recalcIds = function(table){
		var ids = '';
		$('tr[data-id]', table).each(function (){			
			ids = ids  + ',' + $(this).data('id');
		});
		return ids.substr(1);
	}
	
	
	$(this).each(function() {

		var wrapper = $(this).parents('.courses-field-wrapper');
		var inpId = $('.courses-hidden', wrapper);
		var inpName = $('.courses-add', wrapper);
		var table = $('table.item-list', wrapper);
		
		inpName.autocomplete({
			serviceUrl : '/private/course-ajax-select',
			paramName : 'q',
			onSelect : function(suggestion) {				
				table.append($(createRow(suggestion)));
				inpId.val(recalcIds(table));
				inpName.val("");				
			},
			minChars: 3,
			deferRequestBy: 250			
		});
		
		table.on('click', '.course-remove', function(){
			$(this).parents('.item-list tr[data-id]' ).remove();
			console.log(inpId);
			inpId.val(recalcIds(table));
		});
		
		
	});
}