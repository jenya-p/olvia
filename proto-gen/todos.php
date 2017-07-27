<?

include 'Entity.php';

// ***************************************************** напоминания
$entity = new Entity();
$entity->name = 		'Todo';
$entity->namespace = 	'Users';
$entity->multilingual = true;
$entity->historical = 	true;
$entity->optionModel = 	false;
$entity->historyAlias = 'todo';
$entity->title = 		'Баннер';
$entity->source = 		'todos';
$entity->sourceAlias = 	't';
$entity->nameField = 	null;
$entity->route 		= 	'todos';
$entity->fields(['title', "body",
		"status",
		"priority",
		['user', 		false, "user", "Cотрудник"],
		['intensity', 	false, "number", "Трудоемкость"],
		['till_date', 	false, "date", "Дата"],
	]);
                                                                               
$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Задача сохранена');
$entity->message("success_save", 	'Задача сохранена');
$entity->message("new_object", 		'Новая задача');
$entity->message("list_title", 		'Напоминания');
$entity->message("edit_left_header", 'Редактировать задачу');
// $entity->clientRoute = 'clnt';
generate($entity);
