'use strict';

$.fn.tagSelect = function() {
	var createRow = function(item){
		return `<tr class="${ item.active ? "active": "" }" data-id="${item.id}">' +
			<td class="name">${item.name}</td>
			<td class="group-name">${item.group_name}</td>
			<td class="options">
				<a href="javascript:;" class="fa fa-remove tag-refs-remove"></a>
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

		var wrapper = $(this).parents();
		var inpId = $('.tag-refs-hidden', wrapper);
		var inpName = $('.tag-refs-add', wrapper);
		var table = $('table.item-list', wrapper);
		
		inpName.autocomplete({
			serviceUrl : '/private/tag-ajax-select',
			paramName : 'q',
			onSelect : function(suggestion) {				
				table.append($(createRow(suggestion)));
				inpId.val(recalcIds(table));
				inpName.val("");				
			}			
		}).keypress(function(e) {
		    var code = (e.keyCode ? e.keyCode : e.which);
		    if(code == 13) {
		    	$.get('/private/tag-ajax-create', {'name': inpName.val()}, function(d){
		    		if(d.result == 'ok'){					
		    			table.append($(createRow(d.item)));
						inpId.val(recalcIds(table));
					} else {							
						if(d.message){
							Alerts.error(d.message);
						} else {
							Alerts.error("Ошибка добавления тега");
						}
					}
		    		inpName.val("");
		    	})
		    	
		    	return false;
		    }
		});
		
		table.on('click', '.tag-refs-remove', function(){
			$(this).parents('.item-list tr[data-id]' ).remove();
			console.log(inpId);
			inpId.val(recalcIds(table));
		});
		
		
		
	});
}