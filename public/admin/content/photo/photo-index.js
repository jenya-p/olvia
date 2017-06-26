"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var uploadInput = $('.lay-left-sidebar #fileUploadInput');
	var itemListTbody = $('.item-list tbody');
	var uploadAlbumDescription =  $('.upload-album-description');
	
	$("body").add(uploadInput).html5Uploader({
		name: "image",
		postUrl: uploadInput.data('url'),
		checkFile: function(file){
			if(file.type != "image/jpeg" && file.type != "image/png" && file.type != "image/bmp"){
				console.log(file.type);
				Alerts.error("Невозможно загрузить " + file.name + ". Допустимы только изображения");
				return false;
			}
			if(file.size > 5242880){
				console.log(file.type);
				Alerts.error("Невозможно загрузить " + file.name + ". Размер файла не может быть более 5Mb");
				return false;
			}
		},
		onSuccess: function(d){
			itemListTbody.prepend($(d.html));
		}
	});
		
	$('.lay-left-sidebar .upload-link').click(function(){
		uploadInput.click();
	});
	
})