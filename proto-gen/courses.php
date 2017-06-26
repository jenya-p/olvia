<?
include 'Entity.php';

// ***************************************************** курс
$entity = new Entity();
$entity->name = 		'Course';
$entity->namespace = 	'Courses';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	false;
$entity->historyAlias = 'course';
$entity->title = 		'Курс';
$entity->source = 		'courses';
$entity->sourceAlias = 	'c';
$entity->nameField = 	'title';
$entity->route 		= 	'course';
$entity->fields([
		["title", 	true, "text", "Название"],
		"status","priority","image",
		["summary", true, "ckeditor", 	"Краткое описание"],
		["body", 	true, "ckeditor", 	"Описание"],
		["shedule", false, "text", 		"Расписание мероприятий"],
		'image',		
]);
                                                                               
$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Курс сохранен');
$entity->message("success_save", 	'Курс сохранен');
$entity->message("new_object", 		'Новый курс');
$entity->message("list_title", 		'Список курсов');
$entity->message("edit_left_header", 'Редактировать курс');
// $entity->clientRoute = 'clnt';
generate($entity);

// ***************************************************** мероприятие
$entity = new Entity();
$entity->name = 		'Event';
$entity->namespace = 	'Courses';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	true;
$entity->historyAlias = 'event';
$entity->title = 		'Мероприятие';
$entity->source = 		'course_events';
$entity->sourceAlias = 	'ev';
$entity->nameField = 	null; //'name';
$entity->route 		= 	'event';
$entity->fields([
		["course_id", false, 'number', "Курс"],
		"status",
		["type", false, 'select', 	"Периодичность мероприятия"],
		["date", false, 'date', 	"Дата начала"],
		["count", false, 'number', 	"Количество мест"]]);
 
$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Мероприятие сохранено');
$entity->message("success_save", 	'Мероприятие сохранено');
$entity->message("new_object", 		'Новое мероприятие');
$entity->message("list_title", 		'Список мероприятий');
$entity->message("edit_left_header", 'Редактировать мероприятие');
// $entity->clientRoute = 'clnt';
generate($entity);

// ***************************************************** тарифы
$entity = new Entity();
$entity->name = 		'Tarifs';
$entity->namespace = 	'Courses';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	true;
$entity->historyAlias = 'event';
$entity->title = 		'Тарифы';
$entity->source = 		'course_tarifs';
$entity->sourceAlias = 	'ct';
$entity->nameField = 	'title';
$entity->route 		= 	'event';
$entity->fields([
		'title',
		["course_id", false, 'number', "Курс"],
		"status","priority",
		['price', 		false, 	'number', 	'Цена'],
		['discounts', 	false, 	'text', 	'Скидки'],
		["type", false, 'select', "Тип тарифа"],
	]);

$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Тариф сохранен');
$entity->message("success_save", 	'Тариф сохранен');
$entity->message("new_object", 		'Новый тариф');
$entity->message("list_title", 		'Список тарифов');
$entity->message("edit_left_header", 'Редактировать тариф');
// $entity->clientRoute = 'clnt';
generate($entity);
