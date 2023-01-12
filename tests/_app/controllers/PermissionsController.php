<?php
declare(strict_types = 1);

namespace app\controllers;

use cusodede\permissions\controllers\PermissionsController as VendorPermissionsController;

/**
 * Class PermissionsController
 */
class PermissionsController extends VendorPermissionsController {

	/**
	 * @inheritdoc
	 */
	protected array $disabledActions = ['actionDisabled'];

	/**
	 * @return string
	 */
	public function actionDisabled():string {
		return 'this action is disabled';
	}

}