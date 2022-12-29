<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\ModuleHelper;
use pozitronik\helpers\ReflectionHelper;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\Action;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use yii\base\UnknownClassException;
use yii\helpers\FileHelper;

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
		if (null === $controllerFileName = static::GetControllerClassFileByControllerId($controllerId, $moduleId)) return false;
		if (!file_exists($controllerFileName)) return false;
		return null === $actionId || static::IsControllerHasAction($controllerFileName, $actionId);
	}

	/**
	 * Выгружает список контроллеров в указанном неймспейсе
	 * @param string $path
	 * @param string|null $moduleId
	 * @param string[]|null $parentClassFilter Фильтр по классу родителя
	 * @param string[] $ignoredFilesList
	 * @return Controller[]|string[] Array of loaded controllers/error strings
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetControllersList(string $path, ?string $moduleId = null, ?array $parentClassFilter = null, array $ignoredFilesList = []):array {
		$result = [];
		$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Yii::getAlias($path)), RecursiveIteratorIterator::SELF_FIRST);
		/** @var RecursiveDirectoryIterator $file */
		foreach ($files as $file) {
			if ($file->isFile()
				&& 'php' === $file->getExtension()
				&& false === static::isControllerIgnored($file->getRealPath(), $ignoredFilesList)) {
				try {
					if (null !== $controller = ControllerHelper::LoadControllerClassFromFile($file->getRealPath(), $moduleId, $parentClassFilter)) {
						$result[] = $controller;
					}
				} catch (Throwable $t) {
					$message = sprintf("File %s can't be processed due to error %s", $file->getRealPath(), $t->getMessage());
					$result[] = $message;
					Yii::debug($message);
				}

			}
		}
		return $result;
	}

	/**
	 * Checks if file ignored in config
	 * @param string $filePath
	 * @param string[] $ignoredFilesList
	 * @return bool
	 */
	public static function isControllerIgnored(string $filePath, array $ignoredFilesList):bool {
		foreach ($ignoredFilesList as $ignoredFile) {
			if (fnmatch(FileHelper::normalizePath(Yii::getAlias($ignoredFile)), FileHelper::normalizePath($filePath), FNM_NOESCAPE)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Gets controller class filename by its id and module
	 * @param string $controllerId
	 * @param string|null $moduleId
	 * @return string|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetControllerClassFileByControllerId(string $controllerId, ?string $moduleId = null):?string {
		$module = (null === $moduleId)?Yii::$app:ModuleHelper::GetModuleById($moduleId);
		if (null === $module) throw new InvalidConfigException("Module $moduleId not found or module not configured properly.");
		$controllerId = implode('', array_map('ucfirst', preg_split('/-/', $controllerId, -1, PREG_SPLIT_NO_EMPTY)));
		return FileHelper::normalizePath("{$module->controllerPath}/{$controllerId}Controller.php");
	}

	/**
	 * Checks if controller has a loadable action method (without creation of a Action object itself)
	 * @param string $controllerClassFileName
	 * @param string $actionName
	 * @return bool
	 * @throws Throwable
	 * @throws UnknownClassException
	 * @throws ReflectionException
	 */
	public static function IsControllerHasAction(string $controllerClassFileName, string $actionName):bool {
		$className = ReflectionHelper::GetClassNameFromFile(Yii::getAlias($controllerClassFileName));
		if ((null === $controllerReflection = ReflectionHelper::New($className)) || (null === $actions = $controllerReflection?->getMethod('actions')?->invoke(static::FakeNewController($className)))) {
			return false;
		}
		return ((null !== $class = ArrayHelper::getValue($actions, $actionName)) && is_subclass_of($class, Action::class)) ||
			static::IsControllerHasActionMethod($controllerReflection, ControllerHelper::GetActionRequestName($actionName));
	}

	/**
	 * @param ReflectionClass $controllerReflection
	 * @param string $actionName
	 * @return bool
	 * @throws ReflectionException
	 */
	public static function IsControllerHasActionMethod(ReflectionClass $controllerReflection, string $actionName):bool {
		if (preg_match('/^(?:[a-z\d_]+-)*[a-z\d_]+$/', $actionName)) {
			$actionName = 'action'.str_replace(' ', '', ucwords(str_replace('-', ' ', $actionName)));
			if (null !== $actionMethod = $controllerReflection->getMethod($actionName)) {
				if (($controllerReflection->hasProperty('disabledActions')) && in_array($actionName, $controllerReflection->getProperty('disabledActions')->getValue(static::FakeNewController($controllerReflection->name)), true)) {
					return false;
				}
				return ($actionMethod->isPublic() && $actionMethod->getName() === $actionName);
			}
		}
		return false;
	}

	/**
	 * Returns all loadable controller actions
	 * @param string $controllerClassFileName
	 * @param bool $asRequestName Cast action name to request name
	 * @return string[]
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public static function GetControllerClassActions(string $controllerClassFileName, bool $asRequestName = true):array {
		$className = ReflectionHelper::GetClassNameFromFile(Yii::getAlias($controllerClassFileName));
		$controllerReflection = ReflectionHelper::New($className);
		$actions = $controllerReflection?->getMethod('actions')?->invoke(static::FakeNewController($className));
		$actionsNames = array_merge(preg_filter('/^action([A-Z])(\w+?)$/', '$1$2',
			ArrayHelper::getColumn(ReflectionHelper::GetMethods($className), 'name')
		), array_keys($actions));
		foreach ($actionsNames as &$actionName) {
			$actionName = static::IsControllerHasAction($controllerClassFileName, $actionName)
				?$actionName
				:null;
		}
		unset ($actionName);
		$actionsNames = array_filter($actionsNames);
		if ($asRequestName) $actionsNames = array_map(static fn($actionName):string => ControllerHelper::GetActionRequestName($actionName), $actionsNames);
		return array_unique($actionsNames);
	}

	/**
	 * We doesn't care, how exactly controller class will be loaded, we just need to do some simple calls
	 * @param string $className
	 * @return object
	 */
	private static function FakeNewController(string $className):object {
		return new $className($className, Yii::$app);
	}

}