(function ($) {

    $.fn.html5Uploader = function (options) {

        var settings = {
            "name": "uploadedFile",
            "postUrl": "Upload.aspx",            
            "onSuccess": null,
            "checkFile": null,
            "onError": function(data, textStatus){
            	if(data.message){
            		Alerts.error(data.message);            		
            	} else {
            		Alerts.error(textStatus);
            	}		    	
		    },
        };

        if (options) {
            $.extend(settings, options);
        }

        return this.each(function (options) {
            var $this = $(this);
            if ($this.is("[type='file']")) {
                $this
                .bind("change", function () {
                    var files = this.files;
                    for (var i = 0; i < files.length; i++) {
                        fileHandler(files[i]);
                    }
                });
            } else {
                $this
                .bind("dragenter dragover", function () {
                    $(this).addClass("file-upload-hover");
                    return false;
                })
                .bind("dragleave", function () {
                    $(this).removeClass("file-upload-hover");
                    return false;
                })
                .bind("drop", function (e) {
                    $(this).removeClass("file-upload-hover");
                    var files = e.originalEvent.dataTransfer.files;
                    for (var i = 0; i < files.length; i++) {
                        fileHandler(files[i]);
                    }
                    return false;
                });
            }
        });

        function fileHandler(file) {
        	if(!settings.checkFile || !settings.checkFile(file) == false){
        		return;
        	}
        	var data = new FormData();
			data.append(settings.name, file);
			
			$.ajax({
			    url: settings.postUrl,
			    type: 'POST',
			    data: data,
			    cache: false,
			    dataType: 'json',
			    processData: false,
			    contentType: false,
			    success: function(data, textStatus, jqXHR){
			    	if(data.result == 'ok'){
			    		settings.onSuccess(data);			    		
			    	} else {
			    		settings.onError(data, textStatus);
			    	}
			    },
			    error: settings.onError
			});
        }

    };

})(jQuery);