<?php
declare(strict_types = 1);

namespace app\modules\test\controllers\ajax;

use yii\web\Controller;

/**
 * Class DefaultController
 */
class DefaultAjaxController extends Controller {

	/**
	 * @return string
	 */
	public function actionIndex():string {
		return 'ajax-index-test';
	}
}