<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\ReflectionHelper;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;

/**
 * Class CommonHelper
 */
class CommonHelper {
	/**
	 * @param array $errors
	 * @param array|string $separator
	 * @return string
	 */
	public static function Errors2String(array $errors, array|string $separator = "\n"):string {
		$output = [];
		foreach ($errors as $attribute => $attributeErrors) {
			$error = is_array($attributeErrors)?implode($separator, $attributeErrors):$attributeErrors;
			$output[] = "{$attribute}: {$error}";
		}
		return implode($separator, $output);
	}

	/**
	 * Checks, if module/controller/action path is exists
	 * @param string|null $moduleId
	 * @param string|null $controllerId
	 * @param string|null $actionId
	 * @return null|bool true/false: actuality of the checked path, null: it is not a controller permission
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public static function IsControllerPathExits(?string $moduleId, ?string $controllerId, ?string $actionId):?bool {
		if (null === $controllerId) return null;
		if (null !== $moduleId && !Yii::$app->hasModule($moduleId)) return false;
		/** @var Controller|null $controller */
		if (null === $controller = ControllerHelper::GetControllerByControllerId($controllerId, $moduleId)) return false;
		if (null === $controller->createAction($actionId)) return false;
		return true;
	}

	/**
	 * Returns all controller actions
	 * @param string $controller_class
	 * @param bool $asRequestName Cast action name to request name
	 * @return string[]
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 */
	public static function GetControllerActions(string $controller_class, bool $asRequestName = true, bool $checkActionExists = true):array {
		$names = ArrayHelper::getColumn(ReflectionHelper::GetMethods($controller_class), 'name');
		$names = preg_filter('/^action([A-Z])(\w+?)/', '$1$2', $names);
		if ($asRequestName) {
			foreach ($names as &$name) {
				if ($checkActionExists) {
					if (null !== ReflectionHelper::LoadClassByName($controller_class)?->createAction($name)) {
						$name = ControllerHelper::GetActionRequestName($name);
					}
				} else {
					$name = ControllerHelper::GetActionRequestName($name);
				}
			}
		}
		return $names;
	}
}