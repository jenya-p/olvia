<?
use Common\CRUDController;

return [ 
		'navigation' => [ 
				'default' => [
					'private' => [
							'label' => 'Приватная область',
							'route' => 'private'
					],
					'registration' => [
						'label' => 'Регистрация',
						'route' => 'register'					
					],
					'login' => [
						'label' => 'Вход',
						'route' => 'login',
					],
					'logout' => [
						'label' => 'Выход',
						'route' => 'logout'
							
					]
				],
				'master' => [ ],				
				'admin' => [ 
						'users' => [
								'label' => 'Пользователи',
								'uri' => '#',
								'icon' => 'fa-users',
								'pages' => [
										'accounts' => [
												'label' => 'Все пользователи',
												'route' => 'private/user-index',
												'icon' => 'fa-key'
										],
										'admins' => [
												'label' => 'Администраторы',
												'route' => 'private/admin-index',
												'icon' => 'fa-user-secret'
										],
										'masters' => [
												'label' => 'Специалисты',
												'route' => 'private/master-index',
												'icon' => 'fa-user-md'
										],
										'customers' => [
												'label' => 'Клиенты',
												'route' => 'private/customer-index',
												'icon' => 'fa-graduation-cap'
										]										,
										'users-create' => [
												'label' => 'Новый клиент',
												'route' => 'private/customer-edit',
												'params' => [
													'id' => CRUDController::NEWID
												],
												'icon' => 'fa-user-plus'
										]
								]
						],
						'content' => [ 
								'label' => 'Контент',
								'ulClass' => 'two-columns',
								'uri' => '#',
								'icon' => 'fa-newspaper-o',
								'pages' => [										
										
										'divisions' => [
												'label' => 'Разделы',
												'route' => 'private/division-index',
												'icon' => 'fa-folder-open-o'
										],
										'articles' => [
												'label' => 'Статьи',
												'route' => 'private/content-index',
												'icon' => 'fa-file-text-o'
										],
										
										'photoalbums' => [
												'label' => 'Фото-альбомы',
												'route' => 'private/photoalbum-index',
												'icon' => 'fa-folder-open-o'
										],
										'photos' => [
												'label' => 'Все фотографии',
												'route' => 'private/photo-index',
												'icon' => 'fa-picture-o'
										],

										'banners' => [
												'label' => 'Банеры',
												'route' => 'private/banner-index',
												'icon' => 'fa-bomb'
										],
										
										'video-divisions' => [
												'label' => 'Разделы видео',
												'route' => 'private/videoalbum-index',
												'icon' => 'fa-folder-open-o'
										],
										'videos' => [
												'label' => 'Все видео',
												'route' => 'private/video-index',
												'icon' => 'fa-youtube-play'
										],
										
										'rewiews' => [
												'label' => 'Отзывы',
												'route' => 'private/review-index',
												'icon' => 'fa-envelope-square'
										],
										
										'tags' => [
												'label' => 'Теги',
												'route' => 'private/tag-index',
												'icon' => 'fa-tag'
										],
										
										'diplomas' => [
												'label' => 'Дипломы',
												'route' => 'private/diplomas-index',
												'icon' => 'fa-certificate'
										],
								] 
						],
						'order' => [ 
								'label' => 'Заявки',
								'uri' => '#',								
								'icon' => 'fa-inbox',
								'pages' => [ 
										'call' => [ 
												'label' => 'на Обратный звонок',
												'route' => 'private/order-call-index',
												'params' => [ 
														'type' => 'call' 
												],
												'icon' => 'fa-phone notification-call' 
										],
										'personal' => [ 
												'label' => 'на Консультацию',
												'route' => 'private/order-consult-index',
												'params' => [ 
														'type' => 'personal' 
												],
												'icon' => 'fa-user-md notification-consultations' 
										],
										'events' => [ 
												'label' => 'на Мероприятие',
												'route' => 'private/order-index',
												'params' => [ 
														'type' => 'events' 
												],
												'icon' => 'fa-calendar notification-orders' 
										],
										'operations' => [ 
												'label' => 'Оплата',
												'route' => 'home',
												'icon' => 'fa-ruble' 
										]
								] 
						],
						'learning' => [
								'label' => 'Обучение',
								'uri' => '#',
								'icon' => 'fa-book',
								'pages' => [
														
										'courses' => [
												'label' => 'Курсы',
												'route' => 'private/course-index',
												'icon' => 'fa-book'
										],
										'events' => [
												'label' => 'Расписание',
												'route' => 'private/event-index',
												'icon' => 'fa-calendar'
										],
										'tarifs' => [
												'label' => 'Тарифы',
												'route' => 'private/tarifs-index',
												'icon' => 'fa-shopping-bag'
										],
										'master-prices' => [
												'label' => 'Перс. консультации',
												'route' => 'private/master-prices-index',
												'icon' => 'fa-user-md'
										],						
// 										'settings' => [ 
// 												'label' => 'Настройки',
// 												'route' => 'home',
// 												'icon' => 'fa-wrench'
// 										],						
										'remiders' => [ 
												'label' => 'Напоминания',
												'route' => 'private/todos-index',
												'icon' => 'fa-bell'
										] 
								],
						]						
						 
				],
				'admin-top' => [						
						'order' => [
								'label' => 'Заявки',
								'route' => 'private/order-call-index',
								'params' => [
									'type' => 'call'
								],
								'params' => [
										'id' => 'new'
								],
								'icon' => 'fa-inbox',
								'pages' => [										
										'call' => [
												'label' => 'на Обратный звонок',
												'route' => 'private/order-call-index',
												'params' => [
														'type' => 'call'
												],
												'icon' => 'fa-phone notification-call'
										],
										'personal' => [
												'label' => 'на Консультацию',
												'route' => 'private/order-consult-index',
												'params' => [
														'type' => 'personal'
												],
												'icon' => 'fa-user-md notification-consultations'
										],
										'events' => [
												'label' => 'на Мероприятие',
												'route' => 'private/order-index',
												'params' => [
														'type' => 'events'
												],
												'icon' => 'fa-calendar notification-orders'
										]
								]
						],
						'events' => [
								'label' => 'Расписание',
								'route' => 'private/event-index',
								'icon' => 'fa-calendar'
						],
						'remiders' => [
								'label' => 'Напоминания',
								'route' => 'private/todos-index',
								'icon' => 'fa-bell'
						]
				]
		] 
];