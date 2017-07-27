'use strict' 

jQuery(function (){

	var filterForm = $('.page-catalog-index form.searchForm');
	
	var win = $(window);
	var buttons = $('.buttons', filterForm);
	var buttonsFixed = false; 
	win.scroll(function(e){
		var delta = filterForm.offset().top + filterForm.outerHeight() - Math.round(win.scrollTop()) - win.height();
		if(buttonsFixed == false && delta < 0){
			buttons.addClass('normal').removeClass('fixed');
			buttonsFixed = true;
		} else if(buttonsFixed && delta > 0){
			buttons.removeClass('normal').addClass('fixed');
			buttonsFixed = false;
		}		
	})
	
	
	var filterOpener = $('.breadcrumb-bar .icon-search');
	filterOpener.click(function(){
		if(filterOpener.hasClass('opened')){
			filterForm.slideUp();
			filterOpener.removeClass('opened');
		} else {
			filterForm.slideDown();
			filterOpener.addClass('opened');
		}
	})
	
	
	$('select.tag-selector', filterForm).change(function(){
		var t = $(this);
		var opt = t.find('option:selected');
		if(t.val() == ''){
			t.attr('name', '');
		} else {
			t.attr('name', 'tag' + t.val());	
		}			
	}).trigger('change');
	
	
	$('.range-container', filterForm).each(function(){
		var $range = $(this);		
		var $inpValue = $($range.data('to'));	
		var $spanRight = $('.range .range-right', filterForm);
		var $spanRightValue = $('.value',$spanRight);

		var currencyFormat = wNumb({thousand: ' ',	decimals: 0});
		
		noUiSlider.create(this,{
			range: {'min': 0, 'max': parseFloat($range.data('max'))},
			start: $inpValue.val(),
			step: 1000,
			format: wNumb({decimals: 0})			
		});	
		
		this.noUiSlider.on('update', function(values, handle){
			var v = values[handle];
			$inpValue.val(v);
			if(v == 0){
				$spanRight.fadeOut();
			} else {
				$spanRight.fadeIn();
				$spanRightValue.text(currencyFormat.to(parseInt(v)));
			}
			updateFilters.apply($inpValue);
		});
	});
	
//	$('#tutorSearch', filterForm).autocomplete({
//		serviceUrl: '/ajax/masters.json',
//		paramName: 'q',
//		onSelect: function (suggestion) {
//			var t = $(this);
//			var label = t.siblings('label[data-id=' + suggestion.id + ']');
//			if(label.length!=0){
//				label.find('[type=checkbox]').prop('checked', true);
//			} else {
//				var label = $('<label class="checkbox" data-id="' + suggestion.id + '">' +
//						'<input type="checkbox" checked="checked">' +
//						'<span>'+suggestion.value+'</span>' +
//						'<span class="count" title="5 курсов">5</span>' +						
//						'</label>');
//				t.before(label);
//			}
//			updateFilters.apply(label);
//			
//			t.val('');
//		}
//	});
	
	moment.locale('ru');
	var nextMonth = moment().add(1, 'month');
	var nextWeek = moment().add(7, 'day');
	
	String.prototype.capitalizeFirstLetter = function() {
	    return this.charAt(0).toUpperCase() + this.slice(1);
	}
	var inpDateRange = $('[name=date_range]', filterForm);
	var inpDateRangeText = $('.date-range-text', filterForm).dateRangePicker({
		showShortcuts: true,
		shortcuts : null,
		startDate: '2017-03-15',
		language:'ru', 
		format: 'D MMM YYYY',  // http://momentjs.com/docs/#/displaying/format/
		startOfWeek: 'monday',
		separator: ' - ',
		customShortcuts:
			[
				{
					name: 'Эта неделя',
					dates : function(){
						return [moment().startOf('week').toDate(),
						        moment().endOf('week').toDate()];
					}
				},{
					name: 'Следующая неделя',
					dates : function(){
						return [nextWeek.startOf('week').toDate(),
						        nextWeek.endOf('week').toDate()];
					}
				},{
					name: moment().format('MMMM').capitalizeFirstLetter(),
					dates : function(){
						return [moment().startOf('month').toDate(),
						        moment().endOf('month').toDate()];
					}
				},{
					name: nextMonth.format('MMMM').capitalizeFirstLetter(),
					dates : function(){
						return [nextMonth.startOf('month').toDate(),
						        nextMonth.endOf('month').toDate()];
					}
				}
			]
	});
	inpDateRangeText.bind('datepicker-change',function(event,obj){		
		inpDateRange.val(moment(obj.date1.getTime()).format('YY-MM-DD') + '_' + moment(obj.date2.getTime()).format('YY-MM-DD'));
	});
	
	inpDateRangeText.bind('change',function(){
		if($(this).val().trim() == ''){}
		inpDateRange.val('');
	});
	
	var datesStr = inpDateRange.val();
	if(inpDateRange.val() != ''){		
		var dates = datesStr.split('_');
		if(dates.length == 2){
			inpDateRangeText.data('dateRangePicker').setStart(moment(dates[0], 'YY-MM-DD').toDate());
			inpDateRangeText.data('dateRangePicker').setEnd(moment(dates[1], 'YY-MM-DD').toDate());
		}
	}
	
	
	var preview = $('.preview', filterForm);
	$('.normal a',preview).click(function(){filterForm.submit()})
	var previewValue = $('.value', preview);
	var tsHide = null, tsLoad = null, ajaxProcess = null;
	
	function updateFilters(){		
		
		if(preview == undefined) return;
		var target;
		var target = $(this);
		if(! target.is(":visible") || target.height() == 0){
			target = $(this).parent(':visible');
		}		
		var top = target.offset().top + Math.round(target.outerHeight()/2) - filterForm.offset().top;
		
		preview.removeClass('normal zero').addClass('loading').fadeIn('fast').css({'top': top})
		clearTimeout(tsHide);
		clearTimeout(tsLoad);
		
		tsHide = setTimeout(function(){
				preview.fadeOut('slow');
			}, 8000);
		
		tsLoad = setTimeout(
			function(){				
				
				if (ajaxProcess) ajaxProcess.abort();
				
				ajaxProcess = $.get(filterForm.data('search-preview-url'), filterForm.serializeArray(), function(d){
					previewValue.text(d.count);
					preview.removeClass('loading normal zero');
					if(d.count == 0){
						preview.addClass('zero');
					} else {
						preview.addClass('normal');	
					};
					ajaxProcess = null;
				});		
			}, 1000);
	}
	
	$('[type=reset]',filterForm).click(function(){
		document.location = $(this).data('href');;
	});
	
	$('.list .block[data-href]').click(function(){
		document.location = $(this).data('href');
	})
	
})