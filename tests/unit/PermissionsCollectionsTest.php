<?php
declare(strict_types = 1);
use app\models\Users;
use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use Helper\Unit as UnitHelper;

/**
 * Class PermissionsCollectionsTest
 */
class PermissionsCollectionsTest extends Unit {

	/**
	 * @return void
	 * Проверяет подключение коллекций из конфигурационного массива
	 * @covers PermissionsCollections::GetPermissionsCollectionsFromArray
	 * @covers PermissionsCollections::GetConfigurationPermissionsCollections
	 */
	public function testGetConfigurationPermissions():void {
		$user = Users::CreateUser()->saveAndReturn();

		UnitHelper::ModuleWithParams([
			PermissionsModule::GRANT_PERMISSIONS => [
				$user->id => ['default_granted_permission']
			],
			PermissionsModule::CONFIGURATION_PERMISSIONS => [
				'default_granted_permission' => [],
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
			PermissionsModule::CONFIGURATION_PERMISSIONS_COLLECTIONS => [
				'sith_collection' => [
					'permissions' => ['choke_with_force', 'execute_order_66'],
				],
				'palpatin_collection' => [
					'permissions' => ['execute_order_66', 'site:error:post']
				],
				'default_collection' => [
					'permissions' => ['site:error:post', 'new_permission'],//<== доступ добавляется отсюда
					'default' => true,
					'comment' => 'Коллекция по умолчанию'
				]
			]
		]);

		$this::assertEmpty(Permissions::find()->all());
		$this::assertEmpty(PermissionsCollections::find()->all());

		$this::assertEmpty(Permissions::allUserPermissions($user->id));

		$configurationPermissions = Permissions::allUserConfigurationPermissions($user->id);

		/*Один доступ через grant, два доступа из default_collection*/
		$this::assertEquals(3, count($configurationPermissions));

		$this::assertFalse($user->hasPermission('execute_order_66'));
		$this::assertTrue($user->hasPermission('new_permission'));
		$this::assertTrue($user->hasControllerPermission('site', 'error', 'post'));

		/*Коллекция из конфигурации не может быть присвоена*/
		$this::assertFalse($user->grantCollection('palpatin_collection'));

		$this::assertTrue($user->hasPermission('execute_order_66'));

		$this::assertTrue($user->revokeCollection('palpatin_collection'));

		$this::assertFalse($user->hasPermission('execute_order_66'));

		$this::assertFalse($user->hasPermission('choke_with_force'));
		$this::assertTrue($user->grantPermission('choke_with_force'));
		$this::assertTrue($user->hasPermission('choke_with_force'));
		$this::assertTrue($user->revokePermission('choke_with_force'));
		$this::assertFalse($user->hasPermission('choke_with_force'));
	}

	/**
	 * Проверяет генерацию доступов из конфигурационного массива
	 * @return void
	 * @covers Permissions::GetPermissionsFromArray
	 * @covers Permissions::GetConfigurationPermissions
	 */
	public function testGetPermissionsFromArray():void {
		UnitHelper::ModuleWithParams([
			'permissions' => [
				'some-controller:some-action:post' => [
					'controller' => 'some-controller',
					'action' => 'some-action',
					'verb' => 'post',
					'comment' => 'Разрешение POST для some-action в some-controller'
				]
			]
		]);
		$configPermissions = Permissions::GetConfigurationPermissions();
		$permission = $configPermissions[0];
		$this::assertTrue($permission->save());
		$user = Users::CreateUser()->saveAndReturn();

		$this::assertFalse($user->hasControllerPermission('some-controller', 'some-action', 'post'));
		$this::assertTrue($user->grantPermission('some-controller:some-action:post'));

		$this::assertTrue($user->hasControllerPermission('some-controller', 'some-action', 'post'));

		$this::assertTrue($user->revokePermission($permission));
		$this::assertFalse($user->hasControllerPermission('some-controller', 'some-action', 'post'));
	}

}