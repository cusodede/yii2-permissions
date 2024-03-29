<?php
declare(strict_types = 1);

namespace cusodede\permissions\commands;

use cusodede\permissions\helpers\CommonHelper;
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
				:"%b{$permission->name}%r пропущено: (".CommonHelper::Errors2String($permission->errors).")%n"));
		});
	}

	/**
	 * Для всех контроллеров по пути $path добавляет наборы правил доступа в БД. Если путь не указан, берётся маппинг из параметра controllerDirs конфига.
	 * @param null|string $path Путь к каталогу с контроллерами (рекурсивный корень). Если null, берётся маппинг из параметра controllerDirs конфига.
	 * @param string|null $moduleId Модуль, которому принадлежат контроллеры, null для автоматического определения.
	 * @param bool $showAll Включить отображение вывода для корректно пропущенных доступов (например, для повторно создаваемых).
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public function actionInitControllersPermissions(?string $path = null, ?string $moduleId = null, bool $showAll = false):void {
		$pathMapping = [];
		if (is_string($path)) $pathMapping = [$path => $moduleId];
		if (null === $path) $pathMapping = PermissionsModule::param(Permissions::CONTROLLER_DIRS);
		foreach ($pathMapping as $controller_dir => $module_id) {
			PermissionsModule::InitControllersPermissions($controller_dir, $module_id, static function(Permissions $permission, bool $saved, bool $alreadyExists = false) use ($showAll) {
				if ($alreadyExists && !$showAll) return;
				Console::output(Console::renderColoredString(
					$alreadyExists
						?($showAll?"%yДоступ %b{$permission->name}%y уже есть%n":"")
						:($saved?"%gДоступ %b{$permission->name}%g добавлен%n":"%rДоступ %b{$permission->name}%r пропущен (".CommonHelper::Errors2String($permission->errors).")%n")
				));
			}, static function(PermissionsCollections $permissionsCollection, bool $saved, bool $alreadyExists = false) use ($showAll) {
				if ($alreadyExists && !$showAll) return;
				Console::output(Console::renderColoredString(
					$alreadyExists
						?($showAll?"%yКоллекция %b{$permissionsCollection->name}%y уже есть%n":"")
						:($saved?"%gКоллекция %b{$permissionsCollection->name}%g добавлена%n":"%rКоллекция %b{$permissionsCollection->name}%r пропущена (".CommonHelper::Errors2String($permissionsCollection->errors).")%n")
				));
			});
		}
	}

}