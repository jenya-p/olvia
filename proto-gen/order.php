<?
include 'Entity.php';

// ***************************************************** баннер
$entity = new Entity();
$entity->name = 		'Orders';
$entity->namespace = 	'Orders';
$entity->multilingual = false;
$entity->historical = 	true;
$entity->optionModel = 	false;
$entity->historyAlias = 'order_orders';
$entity->title = 		'Заявка на мероприятие';
$entity->source = 		'order_orders';
$entity->sourceAlias = 	'o';
$entity->nameField = 	null;// 'name';
$entity->route 		= 	'order';
$entity->fields([
		['customer_id', false, 	'user', 	'Клиент'],
		['event_id', 	false, 	'user', 	'Мероприятие'],
		['date', 		false, 	'date', 	'Дата'],
		['tarif_id', 	false, 	'date', 	'Тариф'],
		['price', 		false, 	'number', 	'Цена'],
		['discounts', 	false, 	'text', 	'Скидки'],
		"status",
		["message", false, 'textarea', "Сообщение клиента"],
		["comment", false, 'textarea', "Заметка менеджера"],
]);
                                                                               
$entity->message("error_insert", 	'Ошибка сохранения');
$entity->message("success_insert", 	'Заявка сохранена');
$entity->message("success_save", 	'Заявка сохранена');
$entity->message("new_object", 		'Новая заявка');
$entity->message("list_title", 		'Заявки на мероприятия');
$entity->message("edit_left_header", 'Редактировать заявку');
// $entity->clientRoute = 'clnt';
generate($entity);
