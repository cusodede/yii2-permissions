<?php
declare(strict_types = 1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;
use JsonException;
use Yii;

/**
 * Helper module to clear Yii2 cache before each test.
 * This replaces the functionality of the custom Yii2Module which can no longer
 * extend the final Yii2 class in newer versions of codeception/module-yii2.
 */
class CacheCleaner extends Module {
	/**
	 * **HOOK** executed before test
	 *
	 * @param TestInterface $test
	 * @throws JsonException
	 */
	public function _before(TestInterface $test):void {
		if (class_exists('Yii') && Yii::$app && Yii::$app->has('cache')) {
			$this->debugSection("Cache", "Clear cache");
			Yii::$app->cache->flush();
		}
	}
}