<?php
declare(strict_types = 1);

namespace app\controllers_buggy;

use yii\web\Controller;
use yii\web\ErrorAction;

/**
 * This controller has constructor with unusual number of parameters, so it can't be initialized in CommonHelper::FakeNewController
 */
class UnusualController extends Controller {

	private string $foo;

	/**
	 * @inheritDoc
	 */
	public function __construct($id, $module, string $foo, $config = []) {
		parent::__construct($id, $module, $config);
		$this->foo = $foo;
	}

	/**
	 * Because controller can't be initialized as usual, helper will not introspect its actions() method
	 * @inheritDoc
	 */
	public function actions():array {
		return [
			'test' => ErrorAction::class
		];
	}

	/**
	 * @return string
	 */
	public function actionFoo():string {
		return $this->foo;
	}

}