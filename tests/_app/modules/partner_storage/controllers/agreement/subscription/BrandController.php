<?php

declare(strict_types = 1);

namespace app\modules\partner_storage\controllers\agreement\subscription;

use cusodede\permissions\traits\ControllerPermissionsTrait;
use Throwable;
use yii\web\Controller;

/**
 *
 */
class BrandController extends Controller {
	use ControllerPermissionsTrait;

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionIndex():string {
		return 'brand-index';
	}
}
