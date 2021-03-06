jQuery(function (){

	var maxHeight = $('section.education').height(); 
	
	var list = $('.diplomas').css('max-height', maxHeight - 300);  
	var columns = [$('.column-1',list), $('.column-2',list)];
	var columnCount = columns.length;
	var win = $(window);
	var block = false;
	
	var processReviewItem = function(){
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
	
	$('.column-view-item',list).each(processReviewItem);
	
});

// Форма записи
jQuery(function (){
	
	var form = $('section.consult .signe-form');
	var fldSkype = $('.field-skype',form);
	var inpSkype = $('[name=skype]',fldSkype);
	var wrpButtons = $('.buttons', form);

	var changeTarif = function(){
		var current = $('input[name=tarif_id]:checked', form);
		if(current.data('skype') == 1){
			fldSkype.slideDown();
		} else {
			fldSkype.slideUp();
		}				
	}
	var radios = $('input[name=tarif_id]', form).change(changeTarif)
	
	var wrpFast = $('.fast-order-fields', form);
	var fldName = $('.field-name',form);
	var inpName = $('[name=name]',form);
	var fldPhone = $('.field-phone',form);
	var inpPhone = $('[name=phone]',form)
		.mask("+7 (999) 999-99-99", {placeholder:" "});
	
	var regPhone = /\+7\s\(\d\d\d\)\s\d\d\d\-\d\d\-\d\d/;
	
	var isFast = false;
	
	$('.fast-order-open', form).click(function(){
		wrpButtons.slideUp();
		wrpFast.slideDown();
		inpName.focus();
		isFast = true;
	})
	$('.fast-order-cancel', form).click(function(){
		wrpButtons.slideDown();
		wrpFast.slideUp();
		isFast = false;
	})

	form.submit(function(e){
		
		var hasErrors = false;

		if(isFast){

			if(!regPhone.test(inpPhone.val()) || inpPhone.val().trim() == ''){
				hasErrors = true;
				fldPhone.addClass('has-error');
				inpPhone.focus().one('keyup',function(){fldPhone.removeClass('has-error');});
			}
			
			if(inpName.val().trim() == ''){
				hasErrors = true;
				fldName.addClass('has-error');
				inpName.focus().one('keyup',function(){fldName.removeClass('has-error');});
			}
				
		}
		
		var inpTarif = $('input[name=tarif_id]:checked', form);
		if(inpTarif.data('skype')){
			if(inpSkype.val() == ''){
				hasErrors = true;
				fldSkype.addClass('has-error');
				inpSkype.focus().one('keyup',function(){fldSkype.removeClass('has-error');});
			}
		}
		
		if(hasErrors){
			e.preventDefault();
			return false;
		}
		
		if(isFast){
			$.post(form.attr('action'), form.serializeArray(), function(d){
				if(d.result == 'ok'){
					form.next('.fast-done').show();
					form.hide();
				} else {
					$('.error-msg', form).html(d.message).slideDown();
				}
			}, 'json');
			e.preventDefault();
			return false;			
		} else {			
			return true;
		}
	})
	
})