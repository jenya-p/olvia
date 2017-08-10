'use strict';

var RText = function(elem, options){
	
	var element = elem;
	var lastValue = element.val();
	var tm = null;
	var busy = false;
	
	var change = function(){
		clearTimeout(tm);
		
		tm = setTimeout(process, 500); 
	};
	
	var process = function(){
		console.log(lastValue);
		console.log(element.val());
		lastValue = element.val();
	}
	
	element.change(change);
	element.keyup(change);
	
	
};

$.fn.rText = function(options){	
	$(this).each(function() {
		var element = $(this);		
		new RText(element, options);		
	});
};
