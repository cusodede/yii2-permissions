<?php
declare(strict_types = 1);

namespace cusodede\permissions\commands;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use ReflectionException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class DefaultController
 */
class DefaultController extends Controller {

	/**
	 * Добавляет разрешения, описанные в файле конфигурации, в БД
	 */
	public function actionInitConfigPermissions():void {
		PermissionsModule::InitConfigPermissions(static function(Permissions $permission, bool $saved) {
			Console::output(Console::renderColoredString($saved
				?"%b{$permission->name}%g добавлено%n"
				:"%b{$permission->name}%r пропущено: (".static::Errors2String($permission->errors).")%n"));
		});
	}

	/**
	 * Для всех контроллеров по пути $path добавляет наборы правил доступа в БД. Если путь не указан, берётся маппинг из параметра controllerDirs конфига.
	 * @param null|string $path Путь к каталогу с контроллерами (рекурсивный корень). Если null, берётся маппинг из параметра controllerDirs конфига.
	 * @param string|null $moduleId Модуль, которому принадлежат контроллеры, null для автоматического определения
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function actionInitControllersPermissions(?string $path = null, ?string $moduleId = null):void {
		$pathMapping = [];
		if (is_string($path)) $pathMapping = [$path => $moduleId];
		if (null === $path) $pathMapping = PermissionsModule::param(Permissions::CONTROLLER_DIRS);
		foreach ($pathMapping as $controller_dir => $module_id) {
			PermissionsModule::InitControllersPermissions($controller_dir, $module_id, static function(Permissions $permission, bool $saved) {
				Console::output(Console::renderColoredString($saved
					?"%gДоступ %b{$permission->name}%g добавлен%n"
					:"%rДоступ %b{$permission->name}%r пропущен (".static::Errors2String($permission->errors).")%n"));
			}, static function(PermissionsCollections $permissionsCollection, bool $saved) {
				Console::output(Console::renderColoredString($saved
					?"%gКоллекция %b{$permissionsCollection->name} %gдобавлена%n"
					:"%rКоллекция %b{$permissionsCollection->name} %rпропущена (".static::Errors2String($permissionsCollection->errors).")%n"));
			});
		}
	}

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
}