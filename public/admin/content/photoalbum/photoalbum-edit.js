"use strict"
jQuery(function(){
	moment.locale('ru');
	
	var form = $('#itemForm');
	var photoList = $('.photo-wrp', form);	
	var uploadInput = $('.lay-left-sidebar #fileUploadInput');
	
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
			photoList.append($(d.html));
		}
	});
		
	$('.lay-left-sidebar .upload-link').click(function(){
		uploadInput.click();
	});
	

	photoList.on('click','a.photo-delete', function(e){
		e.preventDefault();
		var t = $(this), ok = true;
		var tr = t.parents('.photo-block');
		tr.css({'opacity': 0.5});				
		if(photoList.data('deletion-confirm')){	
			ok = confirm(photoList.data('deletion-confirm'));
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
	
	
	
	photoList.on('click','a.photo-status', function(e){
		e.preventDefault();
		var t = $(this);
		var i = $('i', t);
		i.addClass('fa-spinner');		
		$.getJSON(t.attr('href'), function(d){
			if(d.result == 'ok'){
				if(d.status == 1){					
					i.addClass('fa-eye').removeClass('fa-eye-slash');	
				} else {
					i.removeClass('fa-eye').addClass('fa-eye-slash');
				}	
			}
			i.removeClass('fa-spinner');
		});
		return false;
	})
	
})