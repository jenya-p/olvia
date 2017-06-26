'use strict';

jQuery(function(){
	$('[data-href]').click(function(e){		
		var elem = e.target;
		while(elem != this && elem != null ){
			if(elem.tagName == 'A' || (window.isAdministerMan && elem.hasAttribute("data-editable"))){
				return;
			}
			elem = elem.parentElement;
		}
		if(e.ctrlKey){
			window.open($(this).data('href'), '_blank');
		} else {
			document.location = $(this).data('href');
		}
		return false;
	})
});


function updateQueryString(key, value, url) {
    if (!url) url = window.location.href;
    var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
        hash;

    if (re.test(url)) {
        if (typeof value !== 'undefined' && value !== null)
            return url.replace(re, '$1' + key + "=" + value + '$2$3');
        else {
            hash = url.split('#');
            url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
            if (typeof hash[1] !== 'undefined' && hash[1] !== null) 
                url += '#' + hash[1];
            return url;
        }
    }
    else {
        if (typeof value !== 'undefined' && value !== null) {
            var separator = url.indexOf('?') !== -1 ? '&' : '?';
            hash = url.split('#');
            url = hash[0] + separator + key + '=' + value;
            if (typeof hash[1] !== 'undefined' && hash[1] !== null) 
                url += '#' + hash[1];
            return url;
        }
        else
            return url;
    }
}


//Всплывашки
(function(){
	var win = $(window);
	var lastScrollTop = -1;
	var currentPanel = null;
	var panelMode = null;
	var MARGIN = 40;
	var lastHeight = null;
	
	var link = function link(e){
			e.preventDefault();	
			var t = $(this);
			var href = t.attr('href');			
			
			if(href.substr(0, 1) == '/' || href.substr(0, 4) == 'http'){
				overlay.fadeIn(150).addClass('loading');
				$.get(href, function(d){
					console.log(wrapper);
					var newId = 'twm_panel_' + ((new Date()).getTime());
					t.attr('href', '#' + newId);
					d = $(d)
						.addClass('twm-popup')
						.attr('id', newId)
						.appendTo(wrapper)
						.prepend($('<div class="twm-popup-close"></div>'));		
					$('.twm-popup-link', d).click(link);	
					$('.twm-popup-close', d).click(hide);
					$('.twm-close', d).click(hide);
					overlay.removeClass('loading');					
					t.trigger('twm-popup-load', d);
					show(d);	
				});
			} else {
				var panel = $(href);
				show(panel);
			}
			return false;
		},			
		show = function (panel){
			if(panel.hasClass('twm-popup-open')){
				return;
			}
			hide();											
			panel.addClass('twm-popup-open').fadeIn(150);
			currentPanel = panel;
			resize();
			panel.trigger('twm-popup-open');			
			overlay.fadeIn(150);
			win
				.bind('keyup.twm',keyUp)
				.bind('scroll.twm',scroll)
				.bind('resize.twm',resize);			
		},		
		hide = function (){
			$('.twm-popup-open').fadeOut(150).removeClass('twm-popup-open').trigger('twm-popup-close')
				.css({'top': '', 'bottom': '', 'position': '', 'margin-top': ''});
			overlay.fadeOut(150)
			win.unbind('keyup.twm')
				.unbind('scroll.twm')
				.unbind('resize.twm');
			currentPanel = null;
			panelMode = null;
			lastHeight = null;
		},
		keyUp = function keyUp(e){			
			if(e.keyCode==27){
				hide();
			}
		},		
		resize = function(){
			if(currentPanel == null) return;
			var h = currentPanel.outerHeight();			
			if(currentPanel.data('position') != 'absolute'){
				currentPanel.css({'margin-left': -currentPanel.outerWidth()/2});
				if(h > win.height() - 2*MARGIN){
					if(panelMode == null || panelMode == 'center') {
						lastScrollTop = Math.round(win.scrollTop());
						var bodyHeight = $('body').height();
						if(lastScrollTop + 2*MARGIN + h > bodyHeight){
							lastScrollTop = bodyHeight - 2*MARGIN - h - 20;
							win.scrollTop(lastScrollTop);
						}
						panelMode = 'fixed';
						currentPanel.css({'top': lastScrollTop + MARGIN, 'position': 'absolute', 'margin-top': '', 'bottom': ''});	
					}					
				} else if(panelMode != 'center'){
					panelMode = 'center';
					currentPanel.css({'margin-top': -h/2, top: '50%', 'position': 'fixed', 'bottom': ''});
				}
			} else {
				panelMode = 'abs';
			}
			lastHeight = h;
		},
		scroll = function(){			
			if (currentPanel == null || panelMode == 'abs') {
				return;
			}
			var panelHeight = currentPanel.outerHeight();
			if(lastHeight != panelHeight){
				resize()
			};
			if (win.outerHeight() > currentPanel.outerHeight() + 2*MARGIN) {
				return;
			}
			var scrollTop 	= Math.round(win.scrollTop());
			var delta;
			if(lastScrollTop != scrollTop){
				delta = scrollTop - lastScrollTop;
				lastScrollTop = scrollTop; 
				vScroll(delta,panelHeight);
			}			
		},
		vScroll = function(delta,panelHeight){			
			var panel = currentPanel;			
			var winHeight = win.outerHeight();
			var surplus = panelHeight - winHeight + 2*MARGIN;			
			var top = panel.offset().top;
			var dTop = top - lastScrollTop;
			var dBottom = winHeight - panelHeight - dTop;
			
			if(delta > 0){ // мотаем вниз
				if(panelMode == 'top' && surplus > 0){
					console.log(panel);
					panelMode = 'fixed';
					panel.css({'top': top, 'position': 'absolute'});
				} else if(dBottom > MARGIN && panelMode != 'bottom'){
					panelMode = 'bottom';					
					if(surplus < 0){
						panel.css({bottom:'', top:MARGIN, 'position': 'fixed'});	
					} else {
						panel.css({top:'', bottom:MARGIN, 'position': 'fixed'});
					}
				}				
			} else { // мотаем вверх
				if(dTop > MARGIN){
					if(panelMode != 'top'){
						panelMode = 'top';
						panel.css({'top': MARGIN, 'position': 'fixed'});
					}
				} else if(panelMode == 'bottom' && dBottom <= MARGIN){
					console.log(top);
					panel.css({'top':top, 'bottom':'','position':'absolute'});
					panelMode = 'fixed';
				}
			}
		};
	
	$.twmPopupClose = hide;
	$.twmPopup = function(id){
		var panel = $(id);
		show(panel);			
	};	
	
	var overlay = $('<div class="twm-popup-overlay"><i class="icon-spin1 loading animate-spin"></i></div>');
	overlay.appendTo($('body')).click(hide);
	var wrapper = $('<div class="twm-popup-wrapper"></div>').appendTo($('body'));
	jQuery(function(){
		$('.twm-popup').appendTo(wrapper).prepend($('<div class="twm-popup-close"></div>'));		
		$('.twm-popup-link').click(link);	
		$('.twm-popup-close').click(hide);
		$('.twm-close').click(hide);
	});		
})();

//Форма логина
(function(){
	var wrp = $('#loginWrapper');
	$('#loginLink, .stub, .login-dialog-overlay', wrp).click(function(){
		wrp.toggleClass('opened');
		if(wrp.hasClass('opened')){
			$('[name=login]').focus();
		}
	});
})();



//тултипы
(function(){
	var tm = null;
	$('.tooltip').each(function(){
		$(this).mouseenter(function(){
			$('.tooltip-box', this).stop().fadeIn('fast');
		}).mouseleave(function(){			
			$('.tooltip-box', this).stop().fadeOut('fast');
		})
	});
})();
