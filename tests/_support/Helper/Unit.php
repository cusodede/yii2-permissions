<?php
declare(strict_types = 1);

namespace Helper;

use Codeception\Module;
use cusodede\permissions\PermissionsModule;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\DummyCache;
use yii\caching\FileCache;

/**
 * here you can define custom actions
 * all public methods declared in helper class will be available in $I
 */
class Unit extends Module {

	/**
	 * @param bool $use
	 * @return void
	 * @throws InvalidConfigException
	 */
	public static function useCache(bool $use = true):void {
		Yii::$app->set('cache', [
			'class' => $use?FileCache::class:DummyCache::class
		]);
	}

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
