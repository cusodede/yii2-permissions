<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;

/**
 * Class PermissionsTest
 * Тесты модуля доступов
 */
class PermissionsTest extends Unit {

	/**
	 * Проверяет генерацию доступов из конфигурационного массива
	 * @return void
	 * @see Permissions::GetPermissionsFromArray
	 */
	public function testGetPermissionsFromArray():void {
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
	 * @see Permissions::allUserPermissions
	 */
	public function testAllUserPermissions():void {

	}

	/**
	 * @return void
	 * @see Permissions::getControllerPath
	 * @see Permissions::setControllerPath
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