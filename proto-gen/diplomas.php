<?
include 'Entity.php';

// ***************************************************** группы тегов

$diplomas = new Entity();
$diplomas->name 			= 'Diplom';
$diplomas->namespace 		= 'Content';
$diplomas->multilingual 	= true;
$diplomas->historical 		= false;
$diplomas->optionModel 		= false;
$diplomas->historyAlias 	= 'diplom';
$diplomas->title 			= 'Дипломы';
$diplomas->source 			= 'content_diplomas';
$diplomas->sourceAlias 		= 'cd';
$diplomas->route 			= 'diplomas';

$diplomas->fields(["title", "status", "image"]);
$diplomas->field('master_id', false, 'user', 'Преподаватель');
$diplomas->nameField = 'title';
$diplomas->message("error_insert", 		'Ошибка сохранения');
$diplomas->message("success_insert", 	'Диплом сохранен');
$diplomas->message("success_save", 		'Диплом сохранен');
$diplomas->message("new_object", 		'Новый диплом');
$diplomas->message("create_new_label", 	'Новый диплом');
$diplomas->message("list_title", 		'Дипломы');
$diplomas->message("edit_left_header", 	'Редактирование диплома');
// $entity->clientRoute = 'tag-group';

generate($diplomas);
