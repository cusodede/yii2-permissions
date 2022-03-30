<?php
declare(strict_types = 1);

use cusodede\permissions\PermissionsModule;

return [
	'class' => PermissionsModule::class,
	'params' => [
//		'userIdentityClass' => Yii::$app->user->identityClass,
//		'userCurrentIdentity' => Yii::$app->user->identity,
		'controllerDirs' => [
			'@app/controllers' => null,
			'@vendor/cusodede/yii2-permissions/src/controllers' => 'permissions',
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