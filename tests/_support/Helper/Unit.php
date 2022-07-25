<?php
declare(strict_types = 1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;
use cusodede\permissions\PermissionsModule;
use Yii;

/**
 * Class Unit
 */
class Unit extends Module {
	/**
	 * Перезагружает модуль с указанными параметрами
	 * @param array $params
	 * @return PermissionsModule
	 */
	public static function ModuleWithParams(array $params):PermissionsModule {
		$module = new PermissionsModule('permissions', null, [
			'params' => $params
		]);
		Yii::$app->setModule('permissions', $module);
		return $module;
	}
}
