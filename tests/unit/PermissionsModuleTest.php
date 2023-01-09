<?php
declare(strict_types = 1);

use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use yii\base\UnknownClassException;
use yii\web\Controller;

/**
 *
 */
class PermissionsModuleTest extends Unit {

	/**
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function testAddActionToTestController():void {
		$controllerOne = new class('demo', Yii::$app) extends Controller {
			/**
			 * @return string
			 */
			public function actionTest():string {
				return '';
			}
		};

		$controllerTwo = new class('demo', Yii::$app) extends Controller {
			/**
			 * @return string
			 */
			public function actionTest():string {
				return '';
			}

			/**
			 * @return string
			 */
			public function actionTestTwo():string {
				return '';
			}
		};

		PermissionsModule::GenerateControllersPermissions([$controllerOne]);

		static::assertCount(1, Permissions::find()->all());
		static::assertCount(1, PermissionsCollections::find()->all());
		/** @var PermissionsCollections $createdPermissionCollection */
		$createdPermissionCollection = PermissionsCollections::find()->one();
		static::assertEquals(Permissions::find()->all(), $createdPermissionCollection->relatedPermissions);

		PermissionsModule::GenerateControllersPermissions([$controllerTwo]);
		static::assertCount(2, Permissions::find()->all());
		static::assertCount(1, PermissionsCollections::find()->all());
	}
}