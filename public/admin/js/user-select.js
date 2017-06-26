'use strict';
(function(){
	$.fn.userSelect = function(options) {
		$(this).each(function() {
			var wrapper = $(this).parent('.field-inner');
			var inpId = $('[type=hidden]', wrapper);
			var inpName = $('.user-select', wrapper);
			var type = inpName.data('type');
			var currentValue = inpId.val();
			inpName.autocomplete({
				serviceUrl : '/private/user-ajax-select',
				paramName : 'q',
				params: {'t': type},
				onSelect : function(suggestion) {
					inpId.val(suggestion.id);
					if(currentValue != suggestion.id){
						currentValue = suggestion.id;
						inpId.trigger('change', suggestion);	
					}					
				},
				onInvalidateSelection : function() {
					inpId.val("");
				}
			});
		});
	}

	var createRow = function(item){
		return `<tr data-id="${item.id}">' +
		<td class="name">${item.value}</td>		
		<td class="options">
			<a href="javascript:;" class="fa fa-remove remove"></a>
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
	
	$.fn.userMultiSelect = function(options) {
		
		$(this).each(function() {
			var wrapper = $(this).parent('.field-type-multi-user');
			var inpIds = $('.multiuser-hidden', wrapper);
			var inpName = $('.multiuser-select', wrapper);
			var type = inpName.data('type') 
			var table = $('table.item-list', wrapper);

			inpName.autocomplete({
				serviceUrl : '/private/user-ajax-select',
				paramName : 'q',
				params: {'t': type},
				onSelect : function(suggestion) {				
					table.append($(createRow(suggestion)));
					inpIds.val(recalcIds(table));
					inpName.val("");
				}
			});
			
			table.on('click', '.remove', function(){
				$(this).parents('.item-list tr[data-id]' ).remove();
				inpIds.val(recalcIds(table));
			});
			
		});
	}
	
}());

