'use strict';

var Layout;

(function($){
	Layout = {
		init: function(){
			this.win = $(window);
			this.mainNav = $('#lay-main-navigation');
			this.mainMenu = $('.main-menu', this.mainNav);
			$('.menu-link').click(this.toggleMenu);
			this.outerWrp = $('.lay-content-outer');
			this.contentWrp = $('.lay-content', this.outerWrp);			 
			this.sidebars = {};
			this.leftSidebar = $('.lay-left-sidebar', this.outerWrp);
			if(this.leftSidebar.length){				
				this.sidebars['left'] = {
					inner: $('.lay-sidebar-inner', this.leftSidebar),
					outer: this.leftSidebar
				} 
			}
			this.rightSidebar = $('.lay-right-sidebar', this.outerWrp);
			if(this.rightSidebar.length){
				this.sidebars['right'] = {
					inner: $('.lay-sidebar-inner', this.rightSidebar),
					outer: this.rightSidebar
				} 
			}		
			this.scrollButtons = $('.lay-left-sidebar .buttons');
			this.resize();
			
			this.win.scroll(Layout.scroll);
			this.win.resize(Layout.resize);
			$('body').mousewheel(Layout.mousewheel);
		},	
		
		resize: function(){
			Layout.scrollTop = null;
			Layout.scrollLeft = null;
			Layout.wrapperOffset = Math.round(Layout.outerWrp.offset().top);
			Layout.winHeight = Layout.win.height();
			Layout.winWidth = Layout.win.width();
			Layout.docWidth = $(document).outerWidth();
			var height = Math.max(Layout.winHeight - Layout.wrapperOffset,  Layout.contentWrp.outerHeight());
			if(Layout.leftSidebar.length){
				height = Math.max(height, Layout.sidebars['left'].inner.outerHeight());
			}
			if(Layout.rightSidebar.length){
				height = Math.max(height, Layout.sidebars['right'].inner.outerHeight());
			}
			Layout.outerWrp.css('min-height',height);
			Layout.scroll();
		},
		
		scroll: function(){
			var scrollTop 	= Math.round(Layout.win.scrollTop());
			var delta;
			if(Layout.scrollTop != scrollTop){
				delta = scrollTop - Layout.scrollTop;
				Layout.scrollTop = scrollTop; 
				Layout.vScroll(delta);
			}
			var scrollLeft 	= Math.round(Layout.win.scrollLeft());
			if(Layout.scrollLeft != scrollLeft){
				delta = scrollLeft - Layout.scrollLeft;
				Layout.scrollLeft = scrollLeft; 
				Layout.hScroll(delta);
			}
			Layout.adjustSidebarVerticals();
		},
	
		vScroll: function(delta){
			Layout.win.height()
			for ( var sidebar in Layout.sidebars) {
				var wrapper = Layout.sidebars[sidebar].outer;
				var panel = Layout.sidebars[sidebar].inner;		
				
				if(Layout.scrollTop < Layout.wrapperOffset){					
					panel.removeClass('fixed-top fixed-bottom').css('top','').css('bottom','');
					Layout.scrollButtons.fadeOut();
				} else { 
					var top = panel.offset().top - Layout.wrapperOffset;
					var dTop = panel.offset().top - Layout.scrollTop;
					var dBottom = - dTop - panel.outerHeight() + Layout.winHeight
					var surplus = panel.outerHeight() - Layout.winHeight;
					
					if(delta > 0){ // мотаем вниз
						if(panel.hasClass('fixed-top') && surplus > 0){						
							panel.css('top', top).removeClass('fixed-top');
						} else if(dBottom > 0 && !panel.hasClass('fixed-bottom')){
							if(surplus > 0){
								panel.css('top', '').css('bottom', 0 ).addClass('fixed-bottom');	
							} else {
								panel.css('bottom', '' ).css('top', 0).addClass('fixed-bottom');	
							}																				
						}				
					} else { // мотаем вверх					
						if(dTop > 0){
							if(!panel.hasClass('fixed-top')){
								panel.css('top', 0).addClass('fixed-top').removeClass('fixed-bottom');
							}
						} else if(panel.hasClass('fixed-bottom') && dBottom < 0){
							panel.css('top', top).css('bottom','')
								.removeClass('fixed-bottom').removeClass('fixed-top');
						}
					}
					Layout.scrollButtons.fadeIn();
				}
			};
		},
			
		adjustSidebarVerticals: function(){
			
			if(Layout.sidebars['left']){
				var panel = Layout.sidebars['left'].inner;
				if((panel.hasClass('fixed-top') || panel.hasClass('fixed-bottom')) && Layout.scrollLeft != 0){
					panel.css('left', -Layout.scrollLeft);
				} else {
					panel.css('left', '');
				}				
			}
			
			if(Layout.sidebars['right']){
				var outer = Layout.sidebars['right'].outer;
				var panel = Layout.sidebars['right'].inner;
				 
				var winLeft = Layout.winWidth - outer.outerWidth();
				var docLeft = winLeft + Layout.scrollLeft;
				outer.css('left', docLeft);
					
				if(panel.hasClass('fixed-top') || panel.hasClass('fixed-bottom')){
					panel.css('left', winLeft);
				} else {
					panel.css('left', '');
				}
			}
						
		},
		
		
		hScroll: function(delta){
			if(Layout.scrollLeft != 0){
				var left = Math.min(Layout.scrollLeft, Layout.docWidth-Layout.mainNav.outerWidth());
				Layout.mainNav.css('left', left);
				Layout.scrollButtons.css('left', -Layout.scrollLeft)			
			} else {
				Layout.mainNav.css('left', '');
				Layout.scrollButtons.css('left', '');
			}
	
		},
				
		toggleMenu: function (){
			Layout.win.scrollTop(0); Layout.win.scrollLeft(0);
			Layout.mainNav.toggleClass('expanded');
			return false;	
		},	
		
		mouseweelTense: 0,	
		MENU_SCROLL_TRESHHOLD: 4,
		
		mousewheel: function(event){
			var isExpanded = Layout.mainNav.hasClass('expanded');
			if(Layout.win.scrollTop() == 0 
					&& !event.altKey 
					&& !event.ctrlKey 
					&& !event.shiftKey 
					&& Layout.mouseweelTense < Layout.MENU_SCROLL_TRESHHOLD
					&& !isExpanded 
					&& event.deltaY == 1){
				Layout.mouseweelTense++;			
				setTimeout(function(){if(Layout.mouseweelTense>0){Layout.mouseweelTense--;}},250);
			} else if(event.deltaY == -1 && isExpanded){
				Layout.mainNav.removeClass('expanded');
				Layout.mainMenu.slideUp(200);
				Layout.mouseweelTense = -1;
				setTimeout(function(){if(Layout.mouseweelTense<0){Layout.mouseweelTense++;}},250);
				Layout.win.scrollTop(0);
				event.preventDefault();
			} else if (Layout.mouseweelTense == -1){
				event.preventDefault();
			}
			if(Layout.mouseweelTense == Layout.MENU_SCROLL_TRESHHOLD){
				Layout.mainNav.addClass('expanded');
				Layout.mainMenu.slideDown(200);
			}
		}		
	}
}(jQuery));


jQuery(function($){
	var win = $(window);
	
	Layout.init();

	
	var goToTopLink = $('.go-to-top-link');	
	goToTopLink.click(function(){
		Layout.win.scrollTop(0); win.scrollLeft(0); return false;
	});

	window.Alerts = {
		messageWrapper: $('.lay-message-wrapper'),
		create: function(message, cl, ico){
			return $('<li class="alert ' + cl + '" style="top: -50px; opacity: 0"><i class="fa fa-' + ico + '"></i><span>' + message + '</span></li>')
		}, 
		append: function(li){
			this.init.apply(li);
			this.messageWrapper.prepend(li);
			li.animate({top: 0, opacity:1});
		},
		init: function(){
			var t = $(this);		
			var timeout = null;
			var close = function(){
				t.animate({top: 50, opacity:0}, function(){
					t.remove();
				});
			} 
			t.click(close);
			timeout = setTimeout(close, 3000);
			t.mouseover(function(){
				clearTimeout(timeout);
			});
			t.mouseout(function(){
				clearTimeout(timeout);
				timeout = setTimeout(close, 3000);
			});
		},		
		info: function (message){
			var li = this.create(message, 'info', 'info');
			this.append(li);
		},
		success: function (message){
			var li = this.create(message, 'success', 'thumbs-up');
			this.append(li);
		},
		error: function (message){
			var li = this.create(message, 'error', 'exclamation-circle');
			this.append(li);
		}
	};
	$('li', Alerts.messageWrapper).each(Alerts.init);
	
	var List = {
		init: function(listNode){			
			var list = $(this);

			list.on('click','a.item-delete', function(e){
				e.preventDefault();
				var t = $(this), ok = true;
				var tr = t.closest('tr');
				tr.css({'opacity': 0.5});				
				if(list.data('deletion-confirm')){	
					ok = confirm(list.data('deletion-confirm'));
				}
				if(ok){
					$.getJSON(t.attr('href'), function(d){
						if(d.result == 'ok'){					
							tr.remove();
						} else {							
							tr.css('opacity', 1);
							if(d.message){
								Alerts.error(d.message);
							} else {
								Alerts.error("Ошибка удаления");
							}
						}				
					});
				} else {
					tr.css('opacity', 1);		
				}	
				return false;				
			});
			
			
			list.on('click','a.item-status', function(e){
				e.preventDefault();
				var t = $(this);
				var i = $('i', t);
				i.addClass('fa-spinner');		
				$.getJSON(t.attr('href'), function(d){
					if(d.result == 'ok'){
						if(d.status == 1){					
							i.addClass('active');	
						} else {
							i.removeClass('active');
						}	
					}
					i.removeClass('fa-spinner');
				});
				return false;
			})
			
			list.on('click','tr[data-href]', function(e){
				var elem = e.target;
				while(elem != this && elem != null ){
					if(elem.tagName == 'A'){
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
			});
			
			var sorterTimer = null; 
			list.on('click','th .sorter', function(e){				
				e.preventDefault();				
				var t = $(this);
				var sort;
				$('th .sorter',list).not(t).removeClass('asc desc');
				if(t.hasClass('asc')){
					t.removeClass('asc').addClass('desc');
					sort = t.data('name')+'||desc';
				} else if(t.hasClass('desc')){
					t.removeClass('desc');
					sort = 'no';
				} else {
					t.addClass('asc');
					sort = t.data('name')+'||asc';
				}
				clearTimeout(sorterTimer);
				setTimeout(function(){
					document.location = '?sort=' + sort;
				}, 180)
				return false;
			})
			

		} 
	};
	
	$('table.item-list').each(List.init);
	
	
	var Form = {			
			init: function(formNode){			
				var form = $(this);
//				form.submit((e) => {
//					e.preventDefault();
//					return false;
//				})
				var buttonGroup =  $('.group.sticky:last', form);				
				if(buttonGroup.length){
					var btnsWrp = $('<div />');
					buttonGroup.after(btnsWrp);
					var showed = true;					 
					function scrollFormButtons(){
						var treshhold = btnsWrp.offset().top;
						var s = win.scrollTop() + win.height();
						if(s > treshhold + 50 && showed==false){			
							buttonGroup.removeClass('fixed').css({'padding-left': '', 'width': ''});
							btnsWrp.height(0);							
							showed = true;
							
						} else if(s < treshhold - 50 && showed==true){			
							buttonGroup.css({
									'padding-left': buttonGroup.offset().left,
									'width': buttonGroup.innerWidth()
								}).addClass('fixed');							
							btnsWrp.height(buttonGroup.outerHeight());
							showed = false;				
						}
					}
					win.scroll(scrollFormButtons);
					scrollFormButtons();
				}
				$('.has-error:first input', form).focus();	
				autosize($('textarea', form));
				$('.field-type-phone .field-inner input[type=text]').mask("+7 (999) 999-99-99", {placeholder:" "});
				
			},	
			resize: function(){
				var buttonGroup =  $('.group.sticky.fixed:last');
				if(buttonGroup.length){
					buttonGroup.removeClass('fixed').css({
						'padding-left': buttonGroup.offset().left,
						'width': buttonGroup.innerWidth()
					}).addClass('fixed');	
				}				
				win.trigger('scroll');
			}
		};
		
	$('form.form').each(Form.init);
	Layout.win.resize(Form.resize);
	
	
	$('.minimizable .minimize-link').click(function(){
		$(this).parents('.minimizable').toggleClass('minimized');
	})
	
});
