<?php
declare(strict_types = 1);

use app\models\Users;
use cusodede\permissions\PermissionsModule;

return [
	'class' => PermissionsModule::class,
	'params' => [
		'userIdentityClass' => Users::class,
		'viewPath' => [
			'permissions' => './src/views/permissions',
			'permissions-collections' => './src/views/permissions-collections'
		],
		'controllerDirs' => [
			'@app/controllers' => null,
			'./src/controllers' => 'permissions',
			'@app/modules/test/controllers' => '@api',
			'@app/modules/non_existent_module/controllers' => 'fake'
		],
		'ignorePaths' => [//file masks are supported
			'@app/test_controllers/excluded_dir/*',//ignore by directory path
			'@app/test_controllers/excluded/ExcludedPermissionsCollectionsController.php',//ignore by file path
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
			]
		]
	]
];