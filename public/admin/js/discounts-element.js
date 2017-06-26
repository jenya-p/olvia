'use strict';

$.fn.discountsElement = function(options) {
	$(this).each(function() {
		var wrapper = $(this).parent('.field-inner');
		var inpId = $('[type=hidden]', wrapper);
		var discountWrp = $('.discount-wrp', wrapper);

		$('a.button.add-discount', wrapper).click(function(){		
			discountWrp.append($('<p>Скидка <input type="text" value="" class="discount-inp discount"/> рублей за <input type="text" value="" class="discount-inp days"/> дня до начала <a href="javascript:" class="delete" title="Удалить скидку"><i class="fa fa-remove"></i></a></p>'));
		});
		
		discountWrp.on('click', 'a.delete', function(){
			$(this).parent('p').remove();
		})
		
		discountWrp.on('blur', 'input[type=text]', function(){
			var t = $(this);
			var v = parseInt(t.val());
			if(Number.isInteger(v)){
				t.val(v);	
			} else {
				t.val('').addClass('error');
			}		
		});
		
		discountWrp.on('keyup', 'input[type=text]', function(){
			var t = $(this);
			t.removeClass('error');		
		});
		
		
		discountWrp.parents('form').submit(function(e){
			
			var discountJson = [];
			var daysArr = [];
			var hasError = false;
			discountWrp.find('p').each(function(){
				var t = $(this);
				var days = parseInt($('.days', t).val());
				if(jQuery.inArray(days, daysArr) != -1){
					$('.days', t).addClass('error');
					hasError = true;
				} else {
					daysArr.push(days);
					discountJson.push({
						days: days,
						discount: parseInt($('.discount', t).val()),
					});	
				}
				
			});
			if(hasError){
				e.preventDefault();
				return false;
			} else {
				inpId.val(JSON.stringify(discountJson));			
			}	
		});
		
	});
}