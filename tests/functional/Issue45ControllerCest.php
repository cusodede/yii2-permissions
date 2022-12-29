<?php
declare(strict_types = 1);
use app\models\Users;
use Codeception\Exception\ModuleException;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use yii\db\Exception as BaseException;

/**
 * issue #45 test
 */
class Issue45ControllerCest {

	/**
	 * @param FunctionalTester $I
	 * @return void
	 * @throws ModuleException
	 * @throws BaseException
	 */
	public function LoadIssue45ControllerCest(FunctionalTester $I):void {
		$user = Users::CreateUser()->saveAndReturn();
		Yii::$app->setModule('permissions', [
			'class' => PermissionsModule::class,
			'params' => [
				'viewPath' => [
					'default' => './src/views/default',
				],
				'controllerDirs' => [
					'@app/controllers_issue_45' => null,
				],
			]
		]);

		$I->amLoggedInAs($user);
		$I->amOnRoute('permissions/default');
		$I->seeResponseCodeIs(200);

		$I->amOnRoute('permissions/default/init-controllers-permissions');

		$I->assertCount(9, Permissions::find()->all());
		$I->assertCount(1, PermissionsCollections::find()->all());
		/** @var PermissionsCollections $createdPermissionCollection */
		$createdPermissionCollection = PermissionsCollections::find()->one();
		$I->assertEquals(Permissions::find()->all(), $createdPermissionCollection->relatedPermissions);
	}
}