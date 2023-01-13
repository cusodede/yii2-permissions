<?php
declare(strict_types = 1);

use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\caching\DummyCache;
use yii\caching\FileCache;
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


	/**
	 * @covers PermissionsModule::Cache
	 * @return void
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function testCacheDefault():void {
		/* flush static storage variable */
		$propertyReflection = new ReflectionProperty(PermissionsModule::class, '_cacheComponent');
		$propertyReflection->setAccessible(true);
		$propertyReflection->setValue(null);

		Yii::$app->setModule('cache', [
			'class' => DummyCache::class,
		]);

		static::assertInstanceOf(DummyCache::class, PermissionsModule::Cache());
	}

	/**
	 * @covers PermissionsModule::Cache
	 * @return void
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function testCacheOverride():void {
		/* flush static storage variable */
		$propertyReflection = new ReflectionProperty(PermissionsModule::class, '_cacheComponent');
		$propertyReflection->setAccessible(true);
		$propertyReflection->setValue(null);

		Yii::$app->setModule('cache', [
			'class' => DummyCache::class,
		]);

		Yii::$app->setModule('permissions', [
			'class' => PermissionsModule::class,
			'params' => [
				'cache' => FileCache::class
			]
		]);

		static::assertInstanceOf(FileCache::class, PermissionsModule::Cache());
	}
}