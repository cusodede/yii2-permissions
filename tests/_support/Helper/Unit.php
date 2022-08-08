<?php
declare(strict_types = 1);

namespace Helper;

use Codeception\Module;
use Yii;
use yii\caching\DummyCache;
use yii\caching\FileCache;

/**
 * here you can define custom actions
 * all public methods declared in helper class will be available in $I
 */
class Unit extends Module {

	public static function useCache(bool $use = true):void {
		Yii::$app->set('cache', [
			'class' => $use?FileCache::class:DummyCache::class
		]);
	}

}
