<?
include 'Entity.php';

// ***************************************************** баннер
$entity = new Entity();
$entity->name = 		'Banner';
$entity->namespace = 	'Content';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	false;
$entity->historyAlias = 'banner';
$entity->title = 		'Баннер';
$entity->source = 		'content_banners';
$entity->sourceAlias = 	'b';
$entity->nameField = 	'title';
$entity->route 		= 	'banner';
$entity->fields(['title', "body",
		["link", 	false, "text", "Ссылка"],
		"status",
		"image",
		['date_from', 	false, "date", "Показывать насиная с"],
		['date_to', 	false, "date", "Заверщить показ"],
		"body",
		["countdown", 	false, "number", "Показывать обратный отсчет времени"],
]);
                                                                               
$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Баннер сохранен');
$entity->message("success_save", 	'Баннер сохранен');
$entity->message("new_object", 		'Новый баннер');
$entity->message("list_title", 		'Баннеры на главной');
$entity->message("edit_left_header", 'Редактировать баннер');
// $entity->clientRoute = 'clnt';
generate($entity);
