<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\ReflectionHelper;
use ReflectionException;
use ReflectionMethod;
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
	public static function IsControllerPathExists(?string $moduleId, ?string $controllerId, ?string $actionId):?bool {
		if (null === $controllerId) return null;
		if (null !== $moduleId && !Yii::$app->hasModule($moduleId)) return false;
		/** @var Controller|null $controller */
		if (null === $controller = ControllerHelper::GetControllerByControllerId($controllerId, $moduleId)) return false;
		if (null === $controller->createAction($actionId)) return false;
		return true;
	}

	/**
	 * Returns all loadable controller actions
	 * @param Controller $controller
	 * @param bool $asRequestName Cast action name to request name
	 * @return string[]
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 * @throws Throwable
	 */
	public static function GetControllerActions(Controller $controller, bool $asRequestName = true):array {
		$actionsNames = preg_filter('/^action([A-Z])(\w+?)$/', '$1$2', array_merge(
				ArrayHelper::getColumn(ReflectionHelper::GetMethods($controller::class), 'name'),
				array_keys($controller->actions())
			)
		);
		foreach ($actionsNames as &$actionName) {
			$actionName = static::IsControllerHasAction($controller, ControllerHelper::GetActionRequestName($actionName))
				?$actionName
				:null;
		}
		unset ($actionName);
		$actionsNames = array_filter($actionsNames);
		if ($asRequestName) $actionsNames = array_map(static fn($actionName):string => ControllerHelper::GetActionRequestName($actionName), $actionsNames);

		return $actionsNames;
	}

	/**
	 * Checks if controller has a loadable action method (without creation of a Action object itself)
	 * @param Controller $controller
	 * @param string $actionName
	 * @return bool
	 * @throws Throwable
	 */
	public static function IsControllerHasAction(Controller $controller, string $actionName):bool {
		return null !== ArrayHelper::getValue($controller->actions(), $actionName) || static::IsControllerHasActionMethod($controller, $actionName);
	}

	/**
	 * @param Controller $controller
	 * @param string $actionName
	 * @return bool
	 * @throws ReflectionException
	 * @throws UnknownClassException
	 */
	public static function IsControllerHasActionMethod(Controller $controller, string $actionName):bool {
		if (preg_match('/^(?:[a-z\d_]+-)*[a-z\d_]+$/', $actionName)) {
			$actionName = 'action'.str_replace(' ', '', ucwords(str_replace('-', ' ', $actionName)));
			if (method_exists($controller, $actionName) && (!property_exists($controller, 'disabledActions') || !in_array($actionName, ReflectionHelper::getValue($controller, 'disabledActions', $controller), true))) {
				$method = new ReflectionMethod($controller, $actionName);
				if ($method->isPublic() && $method->getName() === $actionName) return true;
			}
		}
		return false;
	}
}