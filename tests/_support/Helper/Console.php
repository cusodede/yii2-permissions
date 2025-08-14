<?php
declare(strict_types = 1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use app\models\Users;
use Codeception\Module;
use Codeception\TestInterface;
use cusodede\permissions\commands\DefaultController;
use JsonException;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use Yii;

/**
 * Class Console
 */
class Console extends Module {

	/**
	 * **HOOK** executed before test
	 * @param TestInterface $test
	 * @throws JsonException
	 */
	public function _before(TestInterface $test): void {
		parent::_before($test);
		
		$this->debugSection("Cache", "Clear cache");
		if (Yii::$app && Yii::$app->cache) {
			Yii::$app->cache->flush();
		}
	}
	/**
	 * @return DefaultController
	 * @throws InvalidConfigException
	 */
	public static function initDefaultController():DefaultController {
		/*Я не могу создать контроллер через методы createController*, т.к. они полагаются на совпадение неймспейсов с путями, а это условие в тестах не выполняется*/
		return Yii::createObject(DefaultController::class);
	}

	/**
	 * @return Users
	 * @throws Exception
	 */
	public static function initUser():Users {
		return Users::CreateUser()->saveAndReturn();
	}
}
