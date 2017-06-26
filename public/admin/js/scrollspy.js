'use strict';

var Scrollspy;

(function($){
	Scrollspy= {
			
		init: function(){
			Scrollspy.container = $('.scrollspy:first');
			if(Scrollspy.container.length){
				Scrollspy.win = $(window);
				Scrollspy.scrollTop = null;
				clearTimeout(Scrollspy.timeout);
				Scrollspy.timeout = setTimeout(Scrollspy.build, 400);	
			}						
		},	
		
		refresh: function(){
			if(Scrollspy.container.length){
				clearTimeout(Scrollspy.timeout);
				Scrollspy.timeout = setTimeout(Scrollspy.build, 900);
			}
		},
		
		build: function(){
			Scrollspy.container.html('').removeClass('animated');
			Scrollspy.items = $('.lay-content h2');
			var ul = $('<ul>'); 
			var ind = 0; 
			Scrollspy.items.each(function(){
				var t = $(this);
				var li = $('<li>');
				if(t.is(':visible')){
					ind ++;
					var a = $('<a href="#scrollspy_item_' + ind + '" id="scrollspy_link_'+ ind +'" title="' + t.text() + '">' + t.text() + '</a>')
						.click(t, Scrollspy.scrollTo);
					t.attr('id', 'scrollspy_item_' + ind).data('scrollspy_index', ind);
				} else {
					var a = $('<span title="' + t.text() + '">' + t.text() + '</span>');
				}				
				a.appendTo(li);
				li.append();
				ul.append(li);				
			});
			if(Scrollspy.items.length != 0){
				Scrollspy.container.append(ul);
				Scrollspy.pointer = $('<i class="fa pointer"></i>');
				Scrollspy.container.append(Scrollspy.pointer);
				Scrollspy.win.scroll(Scrollspy.scroll);
				Scrollspy.scroll();
				setTimeout(function(){
					Scrollspy.container.addClass('animated');	
				}, 150);	
			}			
		},
		
		scroll: function(){
			var scrollTop 	= Math.round(Layout.win.scrollTop());
			var wh = Math.round(scrollTop + Scrollspy.win.height() / 4);
			var activeLink = null;
			var minDelta = 1000000;
			var itemPosSum = 0;
			var itemCount = 0;
			Scrollspy.container.find('a.active').removeClass('active');				
			Scrollspy.items.each(function(){
				var t = $(this);
				var link = $('#scrollspy_link_' + t.data('scrollspy_index'), Scrollspy.container);
				var offset = t.offset().top;
				var delta = Math.abs(offset - wh);
				if(delta < minDelta){
					activeLink = link;
					minDelta = delta;
				}	
				
				if(offset > scrollTop && offset < scrollTop + wh){
					itemPosSum += link.offset().top;
					itemCount ++;
					link.addClass('active');
				} else {
					link.removeClass('active');
				}					
			});		
			if(activeLink != null){
				itemPosSum += activeLink.offset().top;
				itemCount ++;
				activeLink.addClass('active');
				var pos = Math.round(itemPosSum / itemCount) - Scrollspy.container.offset().top;
				Scrollspy.pointer.css('top',pos).show();					
			} else {
				Scrollspy.pointer.hide();
			}
			
		},
		
		scrollTo: function(e){
			e.preventDefault();
			$('body').stop().animate({scrollTop:e.data.offset().top}, '500', 'swing', Scrollspy.scroll);
			return false;
		}
	}
}(jQuery));

jQuery(Scrollspy.init);


