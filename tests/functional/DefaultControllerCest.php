<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Exception\ModuleException;
use cusodede\permissions\controllers\DefaultController;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use yii\db\Exception as DbException;

/**
 * @covers DefaultController
 */
class DefaultControllerCest {

	/**
	 * @param FunctionalTester $I
	 * @return void
	 * @throws ModuleException
	 * @throws DbException
	 */
	public function DropControllerPermissions(FunctionalTester $I):void {
		$appDir = Yii::getAlias('@app');
		$user = Users::CreateUser()->saveAndReturn();
		Yii::$app->setModule('permissions', [
			'class' => PermissionsModule::class,
			'params' => [
				'viewPath' => [
					'default' => './src/views/default',
					'permissions' => './src/views/permissions',
					'permissions-collections' => './src/views/permissions-collections'
				],
				'controllerDirs' => [
					'@app/controllers_test' => null,
				],
				'grantAll' => [$user->id],
			]
		]);

		$I->amLoggedInAs($user);
		$I->amOnRoute('permissions/default');
		$I->seeResponseCodeIs(200);

		$I->amOnRoute('permissions/default/init-controllers-permissions');

		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(15, $allPermissions);
		$I->assertCount(2, $allPermissionsCollections);

		//rename tests controllers dir, to look, what will happen

		rename("{$appDir}/controllers_test", "{$appDir}/controllers_test_in_progress");

		$I->amOnRoute('permissions/default/drop-unused-controllers-permissions', ['confirm' => true]);
		//rename it back
		rename("{$appDir}/controllers_test_in_progress", "{$appDir}/controllers_test");

		$allPermissions = Permissions::find()->all();
		$allPermissionsCollections = PermissionsCollections::find()->all();
		$I->assertCount(0, $allPermissions);
		$I->assertCount(0, $allPermissionsCollections);
	}

	/**
	 * @return void
	 */
	public function _before():void {
		$appDir = Yii::getAlias('@app');
		if (file_exists("{$appDir}/controllers_test_in_progress")) {
			rename("{$appDir}/controllers_test_in_progress", "{$appDir}/controllers_test");
		}
	}

	/**
	 * @return void
	 */
	public function _failed():void {
		$appDir = Yii::getAlias('@app');
		if (file_exists("{$appDir}/controllers_test_in_progress")) {
			rename("{$appDir}/controllers_test_in_progress", "{$appDir}/controllers_test");
		}
	}
}