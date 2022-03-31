<?php
declare(strict_types = 1);

namespace console\commands;

use ConsoleTester;
use cusodede\permissions\commands\DefaultController;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;

/**
 * Class DefaultControllerCest
 */
class DefaultControllerCest {

	/**
	 * @return Controller
	 * @throws InvalidConfigException
	 */
	private function initDefaultController():Controller {
		/*Я не могу создать контроллер через методы createController*, т.к. они полагаются на совпадение неймспейсов с путями, а это условие в тестах не выполняется*/
		return Yii::createObject(DefaultController::class);
	}

	/**
	 * Проверяем корректность команды отработки генератора доступов по конфигу
	 * @param ConsoleTester $I
	 * @return void
	 * @throws InvalidConfigException
	 */
	public function InitControllerPermissionsFromConfig(ConsoleTester $I):void {
		/*Генерирует доступы для всех контроллеров в конфиге: три контроллера в tests/_app/controllers и два - в /src/controllers */
		$this->initDefaultController()->actionInitControllersPermissions();
		$I->assertCount(29, Permissions::find()->all());
		$I->assertCount(5, PermissionsCollections::find()->all());
	}

	/**
	 * Проверяем корректность команды отработки генератора доступов по пути к дефолтным контроллерам приложения
	 * @param ConsoleTester $I
	 * @return void
	 * @throws InvalidConfigException
	 */
	public function InitControllerPermissionsByPath(ConsoleTester $I):void {
		$this->initDefaultController()->actionInitControllersPermissions('@app/controllers');
		$I->assertCount(15, Permissions::find()->all());
		$I->assertCount(3, PermissionsCollections::find()->all());
	}

	/**
	 * Проверяем корректность команды отработки генератора доступов по пути к контроллерам модуля
	 * @param ConsoleTester $I
	 * @return void
	 * @throws InvalidConfigException
	 */
	public function InitControllerPermissionsByPathInModule(ConsoleTester $I):void {
		$this->initDefaultController()->actionInitControllersPermissions('./src/controllers', 'permissions');
		$I->assertCount(14, Permissions::find()->all());
		$I->assertCount(2, PermissionsCollections::find()->all());
	}
}