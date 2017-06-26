<?php

namespace Application;

return [   
		'view_manager' => [
				'template_map' => [
						'layout/private'          => __DIR__ . '/../view/layout/private.phtml',
				],
				'template_path_stack' => [
						__DIR__ . '/../view',
				],
		],
];
