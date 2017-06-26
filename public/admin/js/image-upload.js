'use strict';
$.fn.imageUpload = function(){
	$(this).each(function() {
		var wrp = $(this).parent('.field-inner')
		var $input = $('[type=hidden]',wrp)
		var $fileInput = $('.image-upload_file',wrp);			
		var $image = $('.image-upload_img',wrp);
		var $original = $('.original',wrp);
		$image.click(function() {				
			$fileInput.click();
		});
		$original.fancybox();
		$fileInput.change(function() {
			var fileInput = $fileInput[0];
			var url = $fileInput.val();
			var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();

			if (fileInput.files && fileInput.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {

				var data = new FormData();
				data.append($input.attr('name'), fileInput.files[0]);
				
				$.ajax({
				    url: $input.data('url'),
				    type: 'POST',
				    data: data,
				    cache: false,
				    dataType: 'json',
				    processData: false,
				    contentType: false,
				    success: function(data, textStatus, jqXHR){
				    	if(data.result == 'ok'){
				    		var d = new Date();
				    		$image.css('background-image', 'url(' + data.preview + '?' + d.getTime() + ')');
				    		$input.val(data.original);
				    		$original.attr('href',data.original ).show();
				    	} else {
				    		Alerts.error(data.message);
				    	}
				    },
				    error: function(jqXHR, textStatus, errorThrown){
				    	Alerts.error(textStatus);
				    }
				});
				
			} else {
				Alerts.error('Сюда может быть загружена только картинка');
			}				
		});
	});
}	
