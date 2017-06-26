<?php

use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\ControllerAnnotationListener;
use Zend\Log\Logger;

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */
define('DS', DIRECTORY_SEPARATOR);

return [
    'db' => array(
		'driver'         => 'Pdo',
		'dsn'            => 'mysql:dbname=olvia;host=localhost',
		'driver_options' => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
		),
		'username' => '...',
		'password' => '...',
	),
	'zf_annotation' => [
		'scan_modules' => ['Common', 'Admin', 'Application'],
			'annotations' => [
					Roles::class, Layout::class
			],
			'event_listeners' => [
					ControllerAnnotationListener::class
			]
	],
	'log' => [
		'DefaultLogger' => [
			'writers' => [[
				'name' => 'stream',
				'priority' => Logger::DEBUG,
				'options' => [
					'stream' => 'php://output',				
				],
			],
		]],
	],
	'path' => [
			'root' => dirname(__DIR__).DS,
			'data' => dirname(__DIR__).DS.'data'.DS,
			'public' => dirname(__DIR__).DS.'public'.DS,
			'images' => dirname(__DIR__).DS.'public'.DS.'images'.DS,
	],
	'imagesUrl' => '/images/',
	'robotEmail' => 'info@olvia.ru',
	'robotName' => 'Olvia Center',
	'mailLayout' => 'layout/mail.phtml',
	'google-api-key' => 'AIzaSyAjWb7RoNq-gGMcfWaSNmMTJcN_8FoV2RA',
		
	'caches' => array(
		'Cache/Default' => array(
			
			'adapter' => 'filesystem',
			'options' => [
					'cache_dir' => './data/cache',
			],
			'plugins' => array(
					'exception_handler' => array('throw_exceptions' => false),
					'serializer'
			)
		),
	),
		
	
		
	'service_manager' => array(
			'abstract_factories' => array(
					'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
			)
	),
		
	'vk_client_id' => '6047884',
	'vk_secret_key' => 'qYmSnjxS2NHpPz5kmD7R',
	
	'fb_client_id' => '433455637019588',
	'fb_secret_key' => '613ca715a7262b0e03c74a51c43190fd',
];
