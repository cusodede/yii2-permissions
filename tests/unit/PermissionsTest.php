<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use Helper\Unit as UnitHelper;

/**
 * Class PermissionsTest
 * Тесты модуля доступов
 */
class PermissionsTest extends Unit {

	/**
	 * Проверяет генерацию доступов из конфигурационного массива
	 * @return void
	 * @covers Permissions::GetPermissionsFromArray
	 * @covers Permissions::GetConfigurationPermissions
	 */
	public function testGetConfigurationPermissions():void {
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

	/**
	 * @return void
	 */
	public function testWithCacheGetConfigurationPermissions():void {
		UnitHelper::useCache();
		$this->testGetConfigurationPermissions();
		UnitHelper::useCache(false);
	}

	/**
	 * @return void
	 * @covers Permissions::allUserPermissions
	 */
	public function testAllUserPermissions():void {

	}

	/**
	 * @return void
	 * @covers Permissions::getControllerPath
	 * @covers Permissions::setControllerPath
	 */
	public function testControllerPath():void {
		$testPermission = new Permissions();

		$this::assertEmpty($testPermission->module);
		$this::assertEmpty($testPermission->controller);

		$testPermission->controllerPath = 'some-controller';

		$this::assertEmpty($testPermission->module);
		$this::assertEquals('some-controller', $testPermission->controller);

		$testPermission->controllerPath = 'some-module/module-controller';

		$this::assertEquals('some-module', $testPermission->module);
		$this::assertEquals('module-controller', $testPermission->controller);

		$testPermission->controllerPath = '@some-other-module/other-module-controller';

		$this::assertEquals('some-other-module', $testPermission->module);
		$this::assertEquals('other-module-controller', $testPermission->controller);
	}

}