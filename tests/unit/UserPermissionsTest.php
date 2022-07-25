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
use yii\web\Controller;

/**
 * Class UserPermissionsTest
 * Проверки отработки доступов, назначенных пользователю
 */
class UserPermissionsTest extends Unit {

	/**
	 * Тест работы доступов, определённых в конфигурации, но не в БД
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testUserDirectPermissions():void {
		UnitHelper::ModuleWithParams([
			'permissions' => [
				'choke_with_force' => [
					'comment' => 'Разрешение душить силой'
				],
				'execute_order_66' => [
					'comment' => 'Разрешение душить силой'
				],
				'site:error:post' => [
					'controller' => 'site',
					'action' => 'error',
					'verb' => 'post',
					'comment' => 'Разрешение POST для actionError в SiteController'
				]
			],
			'grant' => [
				1 => ['choke_with_force']
			],
		]);
		/*Проверим, что в БД нет доступов*/
		$this::assertEmpty(Permissions::find()->all());

		$user = Users::CreateUser()->saveAndReturn();
		/*В конфигурации задано, что у пользователя с id=1 есть доступ 'choke_with_force'*/
		$this::assertCount(1, $user->allPermissions());

		/*Этот доступ не в БД*/
		$this::assertTrue($user->allPermissions()[0]->isNewRecord);

		/*Доступ, которого нет*/
		$this::assertFalse($user->hasPermission(['this_permission_does_not_exist']));

		/*Доступ, который есть в конфигурации, но не присвоен пользователю */
		$this::assertFalse($user->hasPermission(['execute_order_66']));

		/*Доступ есть в конфигурации, и присвоен пользователю*/
		$this::assertTrue($user->hasPermission(['choke_with_force']));
	}

	/**
	 * Присвоить пользователю в конфигурации несуществующий доступ
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testGrantNonExistedPermission():void {
		UnitHelper::ModuleWithParams([
			'grant' => [
				1 => ['this-permission-is-not-existed']
			]
		]);

		/*Проверим, что в БД нет доступов*/
		$this::assertEmpty(Permissions::find()->all());

		$user = Users::CreateUser()->saveAndReturn();

		/*Несуществующий доступ игнорируется */
		$this::assertCount(0, $user->allPermissions());
	}

	/**
	 * Комплексная проверка доступов пользователя к контроллерам (и наоборот)
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
		UnitHelper::ModuleWithParams([]);
		$this::assertEmpty(Permissions::find()->all());
		/*Генерируем доступы к контроллерам приложения, см PermissionsModuleTest::testInitControllersPermissions*/
		PermissionsModule::InitControllersPermissions();
		/** @var Permissions[] $generatedPermissions */
		$generatedPermissions = Permissions::find()->all();
		/** @var PermissionsCollections[] $generatedPermissionsCollections */
		$generatedPermissionsCollections = PermissionsCollections::find()->all();

		$user = Users::CreateUser()->saveAndReturn();
		$this::assertFalse($user->hasControllerPermission('index'));
		$this::assertEmpty($user->relatedPermissions);

		$user->setRelatedPermissions($generatedPermissions);
		$user->save();

		/*Количество пермиссий пользователя равно количеству пермиссий из релейшенов*/
		$this::assertCount(15, $user->allPermissions());

		/*Пользователь имеет доступ к каждому действию в контроллере (проверка от пользователя)*/
		foreach ($generatedPermissions as $permission) {
			$this::assertTrue($user->hasControllerPermission($permission->controller, $permission->action, null, $permission->module));
		}

		/*Пользователь имеет все назначенные пермиссии*/
		$this::assertTrue($user->hasPermission(ArrayHelper::getColumn($generatedPermissions, 'name')));

		/*Прибьём все доступы пользователя*/
		$user->setRelatedPermissions([]);
		$user->save();

		/*Для каждого из наших контроллеров проверим доступы к каждому действию*/
		foreach (['site', 'permissions', 'permissions-collections'] as $controllerId) {
			/** @var ControllerPermissionsTrait|Controller $controller Загрузим контроллер для проверки */
			$controller = ControllerHelper::GetControllerByControllerId($controllerId);

			$this::assertInstanceOf(Controller::class, $controller);
			/*Пользователь не имеет доступа к контроллеру*/
			$this::assertFalse($controller::hasPermission(null, $user->id));

			$user->setRelatedPermissions($generatedPermissions);
			$user->save();

			$controllerActions = ControllerHelper::GetControllerActions($controller::class);
			$this::assertNotEmpty($controllerActions);

			/*Пользователь имеет доступ к каждому действию в контроллере (проверка от контроллера)*/
			foreach ($controllerActions as $action) {
				$this::assertTrue($controller::hasPermission($action, $user->id));
			}

			/*Прибьём все доступы пользователя*/
			$user->setRelatedPermissions([]);
			$user->save();
		}

		/*Количество пермиссий пользователя равно количеству пермиссий - через коллекции*/
		$this::assertCount(0, $user->allPermissions());

		/*Пользователь больше не имеет доступов*/
		foreach ($generatedPermissions as $permission) {
			$this::assertFalse($user->hasControllerPermission($permission->controller, $permission->action, null, $permission->module));
		}

		/*Добавим пользователю все коллекции доступов*/

		$user->setRelatedPermissionsCollections($generatedPermissionsCollections);
		$user->save();

		/*Количество пермиссий пользователя равно количеству пермиссий - через коллекции*/
		$this::assertCount(15, $user->allPermissions());

		/*Пользователь снова имеет доступ к каждому действию в контроллере, но уже через коллекции*/
		foreach ($generatedPermissions as $permission) {
			$this::assertTrue($user->hasControllerPermission($permission->controller, $permission->action, null, $permission->module));
		}

		/** @var PermissionsCollections $permissionCollectionsCollection */
		$permissionCollectionsCollection = PermissionsCollections::find()->where(['name' => 'Доступ к контроллеру permissions-collections'])->one();
		/*Убираем из коллекции доступов для контроллера пермиссий все доступы*/
		$permissionCollectionsCollection->setRelatedPermissions([]);
		$permissionCollectionsCollection->save();

		$this::assertCount(0, $permissionCollectionsCollection->relatedPermissions);

		/*Количество пермиссий пользователя должно уменьшиться соответственно уменьшению пермиссий в коллекции*/
		$this::assertCount(8, $user->allPermissions());
	}

}