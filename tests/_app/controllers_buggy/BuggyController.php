<?php
declare(strict_types = 1);

namespace app\controllers_buggy;

use yii\web\Controller;
use Yii;

/**
 * This controller can't be properly initialized within permissions module
 */
class BuggyController extends Controller {

	/**
	 * @inheritDoc
	 */
	public function init() {
		/*do something inappropriate in console app*/
		Yii::$app->request->get('');//oopsie
		parent::init();
	}

}