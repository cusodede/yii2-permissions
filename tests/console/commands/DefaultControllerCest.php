<?php
declare(strict_types = 1);

namespace console\commands;

use app\models\Users;
use ConsoleTester;
use cusodede\permissions\commands\DefaultController;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\db\Exception;
use yii\helpers\Console;

/**
 * Class DefaultControllerCest
 */
class DefaultControllerCest {

	/**
	 * @return DefaultController
	 * @throws InvalidConfigException
	 */
	private function initDefaultController():DefaultController {
		/*Я не могу создать контроллер через методы createController*, т.к. они полагаются на совпадение неймспейсов с путями, а это условие в тестах не выполняется*/
		return Yii::createObject(DefaultController::class);
	}

	/**
	 * @return Users
	 * @throws Exception
	 */
	private function initUser():Users {
		return Users::CreateUser()->saveAndReturn();
	}

	/**
	 * Проверяем корректность команды отработки генератора доступов по конфигу
	 * @param ConsoleTester $I
	 * @return void
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function InitControllerPermissionsFromConfig(ConsoleTester $I):void {
		/**
		 * Генерирует доступы для всех контроллеров в конфиге:
		 * три контроллера в tests/_app/controllers
		 * два - в /src/controllers
		 * один в @app/modules/test/controllers
		 */
		$this->initDefaultController()->actionInitControllersPermissions();
		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(37, $allPermissions);
		$I->assertCount(9, $allPermissionsCollections);

		$user = $this->initUser();
		$user->setRelatedPermissions($allPermissions);
		$user->save();

		/*Потыкаем в разные сгенеренные пермиссии*/
		$I->assertTrue($user->hasPermission([
			'permissions-collections:index', 'permissions:index', 'permissions:permissions-collections:edit', 'permissions:permissions:view',
			'permissions-collections:create', 'permissions:ajax-search', 'site:error', 'api:default:index',
			'api:default-ajax:index', 'api:ajax/default-ajax:index'
		], Permissions::LOGIC_AND));

		/*Потыкаем в доступы к контроллерам*/
		$I->assertTrue($user->hasControllerPermission('permissions', 'index'));
		$I->assertTrue($user->hasControllerPermission('site', 'error'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'GET'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'POST'));

		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', null, 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions', 'ajax-search', 'POST', 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'edit', 'GET', 'permissions'));

		/*Уберём пермиссии, добавим коллекции*/
		$user->setRelatedPermissions([]);
		$user->setRelatedPermissionsCollections($allPermissionsCollections);
		$user->save();

		/*Потыкаем в доступы к контроллерам снова*/
		$I->assertTrue($user->hasControllerPermission('permissions', 'index'));
		$I->assertTrue($user->hasControllerPermission('site', 'error'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'GET'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'POST'));

		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', null, 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions', 'ajax-search', 'POST', 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'edit', 'GET', 'permissions'));
	}

	/**
	 * Проверяет корректность добавления нового контроллера в конфигурации
	 * @param ConsoleTester $I
	 * @return void
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function InitControllerPermissionsFromConfigUpdate(ConsoleTester $I):void {
		Yii::$app->setModule('permissions', [
			'class' => PermissionsModule::class,
			'params' => [
				'controllerDirs' => [
					'@app/controllers' => null,
					'./src/controllers' => 'permissions',
				],
			]
		]);
		$this->initDefaultController()->actionInitControllersPermissions();
		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(34, $allPermissions);
		$I->assertCount(6, $allPermissionsCollections);
		Console::output(Console::renderColoredString('%b------------------------%n'));

		Yii::$app->setModule('permissions', [
			'class' => PermissionsModule::class,
			'params' => [
				'controllerDirs' => [
					'@app/controllers' => null,
					'./src/controllers' => 'permissions',
					'@app/modules/test/controllers' => '@api'
				],
			]
		]);
		$this->initDefaultController()->actionInitControllersPermissions();
		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(37, $allPermissions);
		$I->assertCount(9, $allPermissionsCollections);
	}

	/**
	 * Проверяем корректность команды отработки генератора доступов по пути к дефолтным контроллерам приложения
	 * @param ConsoleTester $I
	 * @return void
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function InitControllerPermissionsByPath(ConsoleTester $I):void {
		$this->initDefaultController()->actionInitControllersPermissions('@app/controllers');

		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(15, $allPermissions);
		$I->assertCount(3, $allPermissionsCollections);

		$user = $this->initUser();
		$user->setRelatedPermissions($allPermissions);
		$user->save();

		/*Потыкаем в разные сгенеренные пермиссии*/
		$I->assertTrue($user->hasPermission(
			['permissions-collections:index', 'permissions:index', 'permissions-collections:create', 'permissions:ajax-search', 'site:error'],
			Permissions::LOGIC_AND)
		);

		$I->assertFalse($user->hasPermission(['permissions:permissions-collections:edit', 'permissions:permissions:view'], Permissions::LOGIC_AND));

		/*Потыкаем в доступы к контроллерам*/
		$I->assertTrue($user->hasControllerPermission('permissions', 'index'));
		$I->assertTrue($user->hasControllerPermission('site', 'error'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'GET'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'POST'));

		/*А контроллеров модуля нет*/
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'create', null, 'permissions'));
		$I->assertFalse($user->hasControllerPermission('permissions', 'ajax-search', 'POST', 'permissions'));
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'edit', 'GET', 'permissions'));

		/*Уберём пермиссии, добавим коллекции*/
		$user->setRelatedPermissions([]);
		$user->setRelatedPermissionsCollections($allPermissionsCollections);
		$user->save();

		/*Потыкаем в доступы к контроллерам снова*/
		$I->assertTrue($user->hasControllerPermission('permissions', 'index'));
		$I->assertTrue($user->hasControllerPermission('site', 'error'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'GET'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', 'POST'));

		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'create', null, 'permissions'));
		$I->assertFalse($user->hasControllerPermission('permissions', 'ajax-search', 'POST', 'permissions'));
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'edit', 'GET', 'permissions'));
	}

	/**
	 * Проверяем корректность команды отработки генератора доступов по пути к контроллерам модуля
	 * @param ConsoleTester $I
	 * @return void
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function InitControllerPermissionsByPathInModule(ConsoleTester $I):void {
		$this->initDefaultController()->actionInitControllersPermissions('./src/controllers', 'permissions');

		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(19, $allPermissions);
		$I->assertCount(3, $allPermissionsCollections);

		$user = $this->initUser();
		$user->setRelatedPermissions($allPermissions);
		$user->save();

		/*Потыкаем в разные сгенеренные пермиссии*/
		$I->assertFalse($user->hasPermission(
			['permissions-collections:index', 'permissions:index', 'permissions-collections:create', 'permissions:ajax-search', 'site:error'],
			Permissions::LOGIC_AND)
		);

		$I->assertTrue($user->hasPermission(['permissions:permissions-collections:edit', 'permissions:permissions:view'], Permissions::LOGIC_AND));

		/*Потыкаем в доступы к контроллерам*/
		$I->assertFalse($user->hasControllerPermission('permissions', 'index'));
		$I->assertFalse($user->hasControllerPermission('site', 'error'));
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'create', 'GET'));
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'create', 'POST'));

		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', null, 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions', 'ajax-search', 'POST', 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'edit', 'GET', 'permissions'));

		/*Уберём пермиссии, добавим коллекции*/
		$user->setRelatedPermissions([]);
		$user->setRelatedPermissionsCollections($allPermissionsCollections);
		$user->save();

		/*Потыкаем в доступы к контроллерам снова*/
		$I->assertFalse($user->hasControllerPermission('permissions', 'index'));
		$I->assertFalse($user->hasControllerPermission('site', 'error'));
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'create', 'GET'));
		$I->assertFalse($user->hasControllerPermission('permissions-collections', 'create', 'POST'));

		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'create', null, 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions', 'ajax-search', 'POST', 'permissions'));
		$I->assertTrue($user->hasControllerPermission('permissions-collections', 'edit', 'GET', 'permissions'));

	}

	/**
	 * Проверяет присвоение и отработку назначения привилегий через конфиг
	 * @param ConsoleTester $I
	 * @return void
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public function InitConfigPermissions(ConsoleTester $I):void {
		$user = $this->initUser();
		$I->assertEquals(1, $user->id);
		$this->initDefaultController()->actionInitConfigPermissions();
		/*В конфиге у юзера прибит один один пермишшен*/
		$I->assertCount(1, $user->allPermissions());

		/*Доступ, которого нет*/
		$I->assertFalse($user->hasPermission(['this_permission_does_not_exist']));

		/*Доступ, который есть в конфиге, но не у юзера */
		$I->assertFalse($user->hasPermission(['execute_order_66']));
		/*Доступ есть в конфиге, и присвоен юзеру добавлен в конфиге*/
		$I->assertTrue($user->hasPermission(['choke_with_force']));
	}

}