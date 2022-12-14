<?php
declare(strict_types = 1);

namespace app\controllers_test;

use cusodede\permissions\controllers\PermissionsController as VendorPermissionsController;

/**
 *
 */
class SecondTestController extends VendorPermissionsController {

	/**
	 * @return string
	 */
	public function actionTest():string {
		return '';
	}
}