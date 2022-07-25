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
	 * @covers PermissionsModule::ImportConfigPermissions
	 */
	public function testImportConfigPermissions():void {
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
		PermissionsModule::ImportConfigPermissions();

		$this::assertCount(3, Permissions::find()->all());
		$this::assertEquals(
			['test_permission_2', 'test_permission_1', 'site:error:post'],
			ArrayHelper::getColumn(Permissions::find()->select(['name'])->orderBy(['name' => SORT_DESC])->asArray(true)->all(), 'name')
		);
	}

	/**
	 * Тест переноса конфигурации доступов из файловых конфигураций в БД (с коллекциями)
	 * @return void
	 * @covers PermissionsModule::ImportConfigPermissions
	 */
	public function testInitConfigPermissionsWithCollections():void {
		UnitHelper::ModuleWithParams([
			PermissionsModule::CONFIGURATION_PERMISSIONS => [
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

		/*Доступы переносятся в БД*/
		PermissionsModule::ImportConfigPermissions();

		$this::assertEquals(
			['choke_with_force', 'execute_order_66', 'new_permission', 'site:error:post'],
			ArrayHelper::getColumn(Permissions::find()->select(['name'])->orderBy(['name' => SORT_ASC])->asArray(true)->all(), 'name')
		);

		$checkAttributesOnPermission = Permissions::find()->where(['name' => 'site:error:post'])->one();

		$this::assertEquals('Разрешение POST для actionError в SiteController', $checkAttributesOnPermission->comment);
		$this::assertEquals('site', $checkAttributesOnPermission->controller);
		$this::assertEquals('error', $checkAttributesOnPermission->action);
		$this::assertEquals('post', $checkAttributesOnPermission->verb);

		$this::assertEquals(
			['sith_collection', 'palpatin_collection', 'default_collection'],
			ArrayHelper::getColumn(PermissionsCollections::find()->select(['name'])->orderBy(['name' => SORT_DESC])->asArray(true)->all(), 'name')
		);

		$checkAttributesOnCollection = PermissionsCollections::find()->where(['name' => 'default_collection'])->one();

		$this::assertTrue($checkAttributesOnCollection->default);
		$this::assertEquals('Коллекция по умолчанию', $checkAttributesOnCollection->comment);
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