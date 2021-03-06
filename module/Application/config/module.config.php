<?php

namespace Application;

return [   
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [            
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
        	'error/403'               => __DIR__ . '/../view/error/403.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    	'strategies' => array (
    		'ViewJsonStrategy'
    	),
    ],
	'translator' => [
		'locale' => 'ru_RU',
		'translation_file_patterns' => [
				[
						'type'     => 'gettext',
						'base_dir' => __DIR__ . '/../language',
						'pattern'  => '%s.mo',
				],
		],
		
	],
];
