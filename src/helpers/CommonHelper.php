<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use pozitronik\helpers\ControllerHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Yii;
use yii\base\Controller;
use yii\base\InvalidConfigException;

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
			if (fnmatch(Yii::getAlias($ignoredFile), $filePath)) {
				return true;
			}
		}
		return false;
	}

}