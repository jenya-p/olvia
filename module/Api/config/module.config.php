<?php

return [ 
		'console' => [ 
				'router' => [ 
						'routes' => [
								'initial-import' => [
										'options' => [
												'route'    => 'initial-import',
												'defaults' => [
														'controller' => 'Api\InitailImport\InitialImport',
														'action'     => 'process'
												]
										]
								]
						] 
				] 
		],
		'controllers' => array(
				'invokables' => array(
						'Api\InitailImport\InitialImport' => \Api\InitialImport\InitialImportController::class,
				),
		),
];
