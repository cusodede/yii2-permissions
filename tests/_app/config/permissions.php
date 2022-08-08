<?php
declare(strict_types = 1);

use cusodede\permissions\PermissionsModule;

return [
	'class' => PermissionsModule::class,
	'params' => [
		'viewPath' => [
			'permissions' => './src/views/permissions',
			'permissions-collections' => './src/views/permissions-collections'
		],
		'controllerDirs' => [
			'@app/controllers' => null,
			'./src/controllers' => 'permissions',
			'@app/modules/test/controllers' => '@api'
		],
		/*каталоги, в которых будут искаться конфиги пермиссий todo*/
		'includeDirs' => [

		],
		'grantAll' => [],
		'grant' => [
			1 => ['choke_with_force']
		],
		'permissions' => [
			'choke_with_force' => [
				'comment' => 'Разрешение душить силой'
			],
			'execute_order_66' => [
				'comment' => 'Разрешение душить силой'
			],
			'site:error:post' => [
				'controller' => 'site',
				'action' => 'error',
				'verb' => 'post',
				'comment' => 'Разрешение POST для actionError в SiteController'
			]
		],
		'collections' => [
			'sith_collection' => [
				'permissions' => ['choke_with_force', 'execute_order_66'],
			],
			'palpatin' => [
				'permissions' => ['execute_order_66', 'site:error:post']
			],
			'default_collection' => [
				'permissions' => ['site:error:post', 'new_permission'],
				'default' => true,
				'comment' => 'Коллекция по умолчанию'
			]
		]
	]
];