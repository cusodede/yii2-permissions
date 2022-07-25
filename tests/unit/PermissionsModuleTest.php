<?php
declare(strict_types = 1);

use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use Helper\Unit as UnitHelper;
use yii\helpers\ArrayHelper;

/**
 * Class PermissionsModuleTests
 * Тесты методов модуля PermissionsTests
 */
class PermissionsModuleTest extends Unit {

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
		UnitHelper::ModuleWithParams([
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
	 * Тест генератора доступов к контроллерам
	 * @return void
	 * @covers PermissionsModule::InitControllersPermissions
	 */
	public function testInitControllersPermissions():void {
		/** @var Permissions[] $generatedPermissions */
		$generatedPermissions = [];
		/** @var PermissionsCollections[] $generatedPermissionsCollections */
		$generatedPermissionsCollections = [];

		/**
		 * Для теста используются контроллеры внутри модуля, потому что они
		 * а) точно есть;
		 * б) соответствуют всем требованиям;
		 * в) известны все их параметры.
		 * + к ним добавлен произвольный контроллер для некоторой энтропии.
		 */
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
		$this::assertEquals(["Доступ к контроллеру permissions-collections", "Доступ к контроллеру permissions", "Доступ к контроллеру site"], ArrayHelper::getColumn($generatedPermissionsCollections, 'name'));
	}

}