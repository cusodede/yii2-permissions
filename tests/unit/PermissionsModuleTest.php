<?php
declare(strict_types = 1);

use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\PermissionsModule;
use yii\helpers\ArrayHelper;

/**
 * Class PermissionsModuleTests
 * Тесты методов модуля PermissionsTests
 */
class PermissionsModuleTest extends Unit {

	/**
	 * Перезагружает модуль с указанными параметрами
	 * @param array $params
	 * @return PermissionsModule
	 */
	private static function ModuleWithParams(array $params):PermissionsModule {
		$module = new PermissionsModule('permissions', null, [
			'params' => $params
		]);
		Yii::$app->setModule('permissions', $module);
		return $module;
	}

	/**
	 * @return void
	 * @covers PermissionsModule::UserIdentityClass
	 */
	public function testUserIdentityClass():void {

	}

	/**
	 * @return void
	 * @covers PermissionsModule::UserCurrentIdentity
	 */
	public function testUserCurrentIdentity():void {

	}

	/**
	 * @return void
	 * @covers PermissionsModule::FindIdentityById
	 */
	public function testFindIdentityById():void {

	}

	/**
	 * @return void
	 * @covers PermissionsModule::GetControllersList
	 */
	public function testGetControllersList():void {

	}

	/**
	 * Тест переноса конфигурации доступов из файловых конфигураций в БД
	 * @return void
	 * @covers PermissionsModule::InitConfigPermissions
	 */
	public function testInitConfigPermissions():void {
		static::ModuleWithParams([
			'permissions' => [
				'test_permission_1' => [],
				'test_permission_2' => [
					'comment' => 'test_permission_2 comment'
				],
				'site:error:post' => [
					'controller' => 'site',
					'action' => 'error',
					'verb' => 'post',
					'comment' => 'Разрешение POST для actionError в SiteController'
				]
			]
		]);

		$this::assertEmpty(Permissions::find()->all());

		/*Доступы переносятся в БД*/
		PermissionsModule::InitConfigPermissions();

		$this::assertCount(3, Permissions::find()->all());
		$this::assertEquals(
			['test_permission_2', 'test_permission_1', 'site:error:post'],
			ArrayHelper::getColumn(Permissions::find()->select(['name'])->orderBy(['name' => SORT_DESC])->asArray(true)->all(), 'name'));

	}

	/**
	 * @return void
	 * @covers PermissionsModule::InitControllersPermissions
	 */
	public function testInitControllersPermissions():void {

	}

}