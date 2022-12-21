<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use cusodede\permissions\traits\ControllerPermissionsTrait;
use Helper\Unit as UnitHelper;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ControllerHelper;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\db\Exception;
use yii\helpers\Console;
use yii\web\Controller;

/**
 * Class PermissionsTest
 * Тесты модуля доступов
 */
class PermissionsTest extends Unit {

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testGetConfigurationPermissions():void {
		$permissionsArray = [
			'some-controller:some-action:post' => [
				'controller' => 'some-controller',
				'action' => 'some-action',
				'verb' => 'post',
				'comment' => 'Разрешение POST для some-action в some-controller'
			]
		];

		$configPermissions = Permissions::GetPermissionsFromArray($permissionsArray);
		$permission = $configPermissions[0];
		$this::assertTrue($permission->save());
		$user = Users::CreateUser()->saveAndReturn();

		$this::assertFalse($user->hasControllerPermission('some-controller', 'some-action', 'post'));
		$this::assertTrue($user->grantPermission('some-controller:some-action:post'));

		$this::assertTrue($user->hasControllerPermission('some-controller', 'some-action', 'post'));

		$this::assertTrue($user->revokePermission($permission));
		$this::assertFalse($user->hasControllerPermission('some-controller', 'some-action', 'post'));
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testWithCacheGetConfigurationPermissions():void {
		UnitHelper::useCache();
		$this->testGetConfigurationPermissions();
		UnitHelper::useCache(false);
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testUserDirectPermissions():void {
		$user = Users::CreateUser()->saveAndReturn();
		PermissionsModule::InitConfigPermissions();
		/*В конфиге у юзера прибит один один пермишшен*/
		$this::assertCount(1, $user->allPermissions());

		/*Доступ, которого нет*/
		$this::assertFalse($user->hasPermission(['this_permission_does_not_exist']));

		/*Доступ, который есть в конфиге, но не у юзера */
		$this::assertFalse($user->hasPermission(['execute_order_66']));
		/*Доступ есть в конфиге, и присвоен юзеру добавлен в конфиге*/
		$this::assertTrue($user->hasPermission(['choke_with_force']));
	}

	/**
	 * Важно: тест требует modules: config: Db: cleanup: true в codeception.yml, иначе поедет индекс создаваемого юзера, и на него не отмапятся доступы из конфига.
	 * Либо можно убрать проверку $this::assertCount(15, $user->allPermissions());
	 *
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws InvalidConfigException
	 * @throws UnknownClassException
	 * @throws Exception
	 */
	public function testUserControllerPermissions():void {
		$user = Users::CreateUser()->saveAndReturn();
		$this::assertFalse($user->hasControllerPermission('index'));
		/*Прямо*/
		$this::assertEmpty($user->relatedPermissions);

		/** @var Permissions[] $generatedPermissions */
		$generatedPermissions = [];
		/** @var PermissionsCollections[] $generatedPermissionsCollections */
		$generatedPermissionsCollections = [];
		/*Для теста используются контроллеры внутри модуля, потому что они а) точно есть; б) соответствуют всем требованиям; в) известны все их параметры*/
		PermissionsModule::InitControllersPermissions('@app/controllers',
			null,
			static function(Permissions $permission, bool $saved) use (&$generatedPermissions) {
				static::assertTrue($saved);
				$generatedPermissions[] = $permission;
			}, static function(PermissionsCollections $permissionsCollection, bool $saved) use (&$generatedPermissionsCollections) {
				static::assertTrue($saved);
				$generatedPermissionsCollections[] = $permissionsCollection;
			});

		/*Мы знаем, сколько сгенерится доступов и коллекций*/
		$this::assertCount(15, $generatedPermissions);
		$this::assertCount(3, $generatedPermissionsCollections);

		/*Загрузим один из контроллеров для проверки*/
		/** @var ControllerPermissionsTrait|Controller $controller */
		$controller = ControllerHelper::GetControllerByControllerId($generatedPermissions[0]->controller);
		$this::assertInstanceOf(Controller::class, $controller);
		/*Пользователь не имеет доступа к контроллеру*/
		$this::assertFalse($controller::hasPermission(null, $user->id));

		$user->setRelatedPermissions($generatedPermissions);
		$user->save();

		/*Количество пермиссий пользователя равно количеству пермиссий из релейшенов + 1 из конфига*/
		$this::assertCount(16, $user->allPermissions());

		/*Пользователь имеет все назначенные пермиссии*/
		$this::assertTrue($user->hasPermission(ArrayHelper::getColumn($generatedPermissions, 'name')));

		$controllerActions = ControllerHelper::GetControllerActions($controller);
		$this::assertNotEmpty($controllerActions);

		/*Пользователь имеет доступ к каждому действию в контроллере (проверка от контроллера)*/
		foreach ($controllerActions as $action) {
			$this::assertTrue($controller::hasPermission($action, $user->id));
		}
		/*Пользователь имеет доступ к каждому действию в контроллере (проверка от пользователя)*/
		foreach ($generatedPermissions as $permission) {
			$this::assertTrue($user->hasControllerPermission($permission->controller, $permission->action, null, $permission->module));
		}
		/*Прибьём все доступы пользователя*/
		$user->setRelatedPermissions([]);
		$user->save();

		/*Количество пермиссий пользователя равно количеству пермиссий - через коллекции*/
		$this::assertCount(1, $user->allPermissions());

		/*Пользователь больше не имеет доступов*/
		foreach ($generatedPermissions as $permission) {
			$this::assertFalse($user->hasControllerPermission($permission->controller, $permission->action, null, $permission->module));
		}

		/*Добавим пользователю все коллекции доступов*/

		$user->setRelatedPermissionsCollections($generatedPermissionsCollections);
		$user->save();

		/*Количество пермиссий пользователя равно количеству пермиссий - через коллекции*/
		$this::assertCount(16, $user->allPermissions());

		/*Пользователь снова имеет доступ к каждому действию в контроллере, но уже через коллекции*/
		foreach ($generatedPermissions as $permission) {
			$this::assertTrue($user->hasControllerPermission($permission->controller, $permission->action, null, $permission->module));
		}

		Console::output($generatedPermissionsCollections[1]->name);
		Console::output(var_export($generatedPermissionsCollections[1]->getRelatedPermissions()->all(), true));

		/*Убираем из коллекции доступов для контроллера пермиссий все доступы*/
		$generatedPermissionsCollections[1]->setRelatedPermissions([]);
		$generatedPermissionsCollections[1]->save();

		Console::output(var_export($generatedPermissionsCollections[1]->getRelatedPermissions()->all(), true));

		$this::assertCount(0, $generatedPermissionsCollections[1]->getRelatedPermissions()->all());

		/*Количество пермиссий пользователя должно уменьшиться соответственно уменьшению пермиссий в коллекции*/
		$p = $user->allPermissions();
		$this::assertCount(9, $p, var_export($p, true));
	}

	/**
	 * @return void
	 * @throws Exception
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function testExcludedUserControllerPermissions():void {
//		if ('github' === getenv('CI')) static::markTestSkipped("This test doesn't run in github CI");//temporary!
		$user = Users::CreateUser()->saveAndReturn();
		$this::assertFalse($user->hasControllerPermission('index'));
		/*Прямо*/
		$this::assertEmpty($user->relatedPermissions);

		/** @var Permissions[] $generatedPermissions */
		$generatedPermissions = [];
		/** @var PermissionsCollections[] $generatedPermissionsCollections */
		$generatedPermissionsCollections = [];
		/*Для теста используются контроллеры внутри модуля, потому что они а) точно есть; б) соответствуют всем требованиям; в) известны все их параметры*/
		PermissionsModule::InitControllersPermissions('@app/test_controllers',
			null,
			static function(Permissions $permission, bool $saved) use (&$generatedPermissions) {
				static::assertTrue($saved);
				$generatedPermissions[] = $permission;
			}, static function(PermissionsCollections $permissionsCollection, bool $saved) use (&$generatedPermissionsCollections) {
				static::assertTrue($saved);
				$generatedPermissionsCollections[] = $permissionsCollection;
			});
		/*Мы знаем, сколько сгенерится доступов и коллекций*/
		$this::assertCount(15, $generatedPermissions);
		$this::assertCount(3, $generatedPermissionsCollections);
	}

}