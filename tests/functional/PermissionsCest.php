<?php
declare(strict_types = 1);
use app\tests\models\sys\users\UsersTest;
use Codeception\Exception\ModuleException;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\db\Exception as DbException;

/**
 * Class PermissionsCest
 */
class PermissionsCest {

	/**
	 * @param FunctionalTester $I
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws ModuleException
	 * @throws InvalidConfigException
	 * @throws UnknownClassException
	 * @throws DbException
	 */
	public function checkPermissionsFilter(FunctionalTester $I):void {
		$user = UsersTest::CreateUser()->saveAndReturn();

		$I->amOnPage(['home/home']);
		$I->seeResponseCodeIs(403);
		$I->canSee("Пользователь не авторизован");

		$I->amLoggedInAs($user);
		/*Фильтр должен отбрить*/
		$I->amOnRoute('permissions/permissions/index');
		$I->seeResponseCodeIs(403);

		PermissionsModule::InitControllersPermissions('@vendor/cusodede/yii2-permissions/src/controllers',
			'permissions',
			static function(Permissions $permission, bool $saved) use (&$generatedPermissions, $I) {
				$I->assertTrue($saved);
				$generatedPermissions[] = $permission;
			}, static function(PermissionsCollections $permissionsCollection, bool $saved) use (&$generatedPermissionsCollections, $I) {
				$I->assertTrue($saved);
				$generatedPermissionsCollections[] = $permissionsCollection;
			});
		$user->setRelatedPermissions($generatedPermissions);
		$user->save();
		/*Фильтр должен пустить*/
		$I->amOnRoute('permissions/permissions/index');
		$I->seeResponseCodeIs(200);
	}

}