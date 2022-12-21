<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use pozitronik\helpers\ControllerHelper;
use Throwable;
use Yii;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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
		if (null === $controller = ControllerHelper::GetControllerByControllerId($controllerId, $moduleId)) return false;
		return ControllerHelper::IsControllerHasAction($controller, $actionId);
	}

	/**
	 * Выгружает список контроллеров в указанном неймспейсе
	 * @param string $path
	 * @param string|null $moduleId
	 * @param string[]|null $parentClassFilter Фильтр по классу родителя
	 * @param string[] $ignoredFilesList
	 * @return Controller[]
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
				&& false === static::isControllerIgnored($file->getRealPath(), $ignoredFilesList)
				&& null !== $controller = ControllerHelper::LoadControllerClassFromFile($file->getRealPath(), $moduleId, $parentClassFilter)) {
				$result[] = $controller;
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

}