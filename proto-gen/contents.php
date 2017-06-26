<?
include 'Entity.php';

// ***************************************************** группы тегов

$tagGroup = new Entity();
$tagGroup->name 			= 'TagGroup';
$tagGroup->namespace 		= 'Content';
$tagGroup->multilingual 	= true;
$tagGroup->historical 		= false;
$tagGroup->optionModel 		= true;
$tagGroup->historyAlias 	= null;
$tagGroup->title 			= 'Группы тегов';
$tagGroup->source 			= 'content_tag_groups';
$tagGroup->sourceAlias 		= 'ctg';
$tagGroup->route 			= 'tag-groups';
$tagGroup->fields(["name"]);
$tagGroup->nameField 		= 'name';
$tagGroup->message("error_insert", 		'Ошибка сохранения');
$tagGroup->message("success_insert", 	'Группа создана');
$tagGroup->message("success_save", 		'Группа сохранена');
$tagGroup->message("new_object", 		'Новая группа');
$tagGroup->message("list_title", 		'Группы тегов');
$tagGroup->message("edit_left_header", 	'Редактирование группы');
// $entity->clientRoute = 'tag-group';

generate($tagGroup);


// ***************************************************** теги

$tag = new Entity();
$tag->name 				= 'Tag';
$tag->namespace 		= 'Content';
$tag->multilingual 		= true;
$tag->historical 		= false;
$tag->optionModel 		= true;
$tag->historyAlias 		= null;
$tag->title 			= 'Теги';
$tag->source 			= 'content_tags';
$tag->sourceAlias 		= 'ct';
$tag->route 			= 'tag';
$tag->fields(["name", "alias", ['group_id', false, 'select', 'Группа'], "status", "seo", 'created']);
$tag->nameField 		= 'name';
$tag->message("error_insert", 		'Ошибка сохранения');
$tag->message("success_insert", 	'Тэг создан');
$tag->message("success_save", 		'Тэг сохранен');
$tag->message("new_object", 		'Новый тег');
$tag->message("list_title", 		'Теги');
$tag->message("edit_left_header", 	'Редактирование тега');
// $entity->clientRoute = 'tag';

generate($tag);

// ***************************************************** видео альбом
$entity = new Entity(); 
$entity->name = 			'Videoalbum';
$entity->namespace = 		'Content';
$entity->multilingual = 	true;
$entity->historical = 		true;
$entity->optionModel = 		true;
$entity->historyAlias = 	'videoalbum';
$entity->title = 			'Разделы видео';
$entity->source = 			'content_videoalbums';
$entity->sourceAlias = 		'va';
$entity->nameField = 		'name';
$entity->route = 			'videoalbum';
$entity->fields(["name", "alias", "active", "seo",'views','author','created']);
$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Раздел создан');
$entity->message("success_save", 	'Раздел сохранен');
$entity->message("new_object", 		'Новый раздел');
$entity->message("list_title", 		'Разделы видео');
$entity->message("edit_left_header", 'Редактирование раздела');
// $entity->clientRoute = 'videoalbum';
generate($entity);

// ***************************************************** видео 
$entity = new Entity();
$entity->name = 		'Video';
$entity->namespace = 	'Content';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	false;
$entity->historyAlias = 'video';
$entity->title = 		'Видео';
$entity->source = 		'content_videos';
$entity->sourceAlias = 	'v';
$entity->nameField = 	'title';
$entity->route 		= 	'video';
$entity->fields(["title", "alias", "active", "body", 
			["link", true, 'text', "Ссылка на ролик"], 
		"seo", 'views', 'author', 'created']);

$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Видео сохранено');
$entity->message("success_save", 	'Видео сохранено');
$entity->message("new_object", 		'Новой видео');
$entity->message("list_title", 		'Видео');
$entity->message("edit_left_header", 'Редактирование видео');
// $entity->clientRoute = 'clnt';
generate($entity);

// ***************************************************** фото альбом
$entity = new Entity();
$entity->name = 		'Photoalbum';
$entity->namespace = 	'Content';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	true;
$entity->historyAlias = 'photoalbum';
$entity->title = 		'Фотоальбом';
$entity->source = 		'content_photoalbums';
$entity->sourceAlias = 	'fa';
$entity->nameField = 	'name';
$entity->route = 		'photoalbum';
$entity->fields(["title","body","active","seo","created"]);

$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Фотоальбом создан');
$entity->message("success_save", 'Фотоальбом сохранен');
$entity->message("new_object", 'Новый фотоальбом');
$entity->message("list_title", 'Все фотоальбомы');
$entity->message("edit_left_header", 'Редактирование фотоальбома');
// $entity->clientRoute = 'clnt';
generate($entity);

// ***************************************************** фото
$entity = new Entity();
$entity->name = 		'Photo';
$entity->namespace = 	'Content';
$entity->multilingual = true;
$entity->historical = 	false;
$entity->optionModel = 	false;
$entity->historyAlias = 'photo';
$entity->title = 		'Фото';
$entity->source = 		'content_photos';
$entity->sourceAlias = 	'f';
$entity->nameField = 	'title';
$entity->route 		= 	'photo';
$entity->fields(["title","active","image",'views']);

$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Фото сохранено');
$entity->message("success_save", 	'Фото сохранено');
$entity->message("new_object", 		'Новой фото');
$entity->message("list_title", 		'Фото');
$entity->message("edit_left_header", 'Редактирование фото');
// $entity->clientRoute = 'clnt';
generate($entity);

// ***************************************************** отзыв
$entity = new Entity();
$entity->name = 		'Review';
$entity->namespace = 	'Content';
$entity->multilingual = false;
$entity->historical = 	false;
$entity->optionModel = 	false;
$entity->historyAlias = 'review';
$entity->title = 		'Фото';
$entity->source = 		'content_reviews';
$entity->sourceAlias = 	'r';
$entity->nameField = 	'title';
$entity->route 		= 	'review';
$entity->fields(["author","name","date","body","active",
		['social', false, "text", "Ссылка в соцсети"],
		['userpic', false, "image-upload", "Юзерпик"]
]);

$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Отзыв сохранен');
$entity->message("success_save", 	'Отзыв сохранен');
$entity->message("new_object", 		'Новый отзыв');
$entity->message("list_title", 		'Отзывы');
$entity->message("edit_left_header", 'Редактировать отзыв');
// $entity->clientRoute = 'clnt';
generate($entity);
