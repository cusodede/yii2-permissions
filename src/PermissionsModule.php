<?php
declare(strict_types = 1);

namespace cusodede\permissions;

use pozitronik\traits\traits\ModuleTrait;
use yii\base\Module;

/**
 * Class PermissionsModule
 */
class PermissionsModule extends Module {
	use ModuleTrait;

	public const VERBS = [
		'GET' => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'PATCH' => 'PATCH',
		'DELETE' => 'DELETE'
	];
}
