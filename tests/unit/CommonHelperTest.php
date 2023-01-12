<?php
declare(strict_types = 1);
use Codeception\Test\Unit;
use cusodede\permissions\helpers\CommonHelper;
use yii\base\UnknownClassException;

/**
 * @covers CommonHelper
 */
class CommonHelperTest extends Unit {

	/**
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 * @covers CommonHelper::IsControllerHasAction
	 */
	public function testIsControllerHasAction():void {
		static::assertTrue(CommonHelper::IsControllerHasAction('@app/controllers/SiteController.php', 'error'));
		static::assertFalse(CommonHelper::IsControllerHasAction('@app/controllers/SiteController.php', 'success'));
	}
}
