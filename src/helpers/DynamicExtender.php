<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use yii\base\Controller;
use yii\base\Exception;

/**
 * Class DynamicExtender
 */
class DynamicExtender extends Controller {

	public ?object $parentInstance = null;

	/**
	 * Construct a class with it's parent class chosen dynamically.
	 *
	 * @param string $parentClassName The parent class to extend.
	 */
	public function __construct(string $parentClassName) {
		$parentClassName = base64_encode($parentClassName);

		$this->parentInstance = new $parentClassName($this);
	}

	/**
	 * Magic __call method is triggered whenever the child class tries to call a method that doesn't
	 * exist in the child class. This is the case whenever the child class tries to call a method of
	 * the parent class. We then redirect the method call to the parentInstance.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($name, $arguments) {
		if (null === $this->parentInstance) {
			throw new Exception("parentInstance is null in dynamic class.");
		}
		return call_user_func_array([$this->parentInstance, $name], $arguments);
	}

}