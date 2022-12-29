<?php
declare(strict_types = 1);

namespace console;
use ConsoleTester;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use Helper\Console as ConsoleHelper;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\db\Exception;

/**
 * Tries to load a controller, which can't be properly loaded in module ways
 */
class BuggyControllerCest {

	/**
	 * @param ConsoleTester $I
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws InvalidConfigException
	 * @throws UnknownClassException
	 * @throws Exception
	 */
	public function LoadBuggyControllerCest(ConsoleTester $I):void {
		Yii::$app->setModule('permissions', [
			'class' => PermissionsModule::class,
			'params' => [
				'controllerDirs' => [
					'@app/controllers_buggy' => null,
				],
			]
		]);
		ConsoleHelper::initDefaultController()->actionInitControllersPermissions();
		$I->assertCount(0, Permissions::find()->all());
		$I->assertCount(0, PermissionsCollections::find()->all());
	}
}
