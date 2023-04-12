<?php

declare(strict_types = 1);

namespace app\modules\partner_storage\controllers\agreement\subscription;

use Throwable;
use yii\web\Controller;

/**
 *
 */
class BrandController extends Controller {

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionIndex():string {
		return 'brand-index';
	}
}
