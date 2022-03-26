<?php
declare(strict_types = 1);
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use pozitronik\helpers\ControllerHelper;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\console\Controller;
use yii\helpers\Console;
use yii\web\Controller as WebController;

/**
 * Class DefaultController
 */
class DefaultController extends Controller {

	/**
	 * Добавляет разрешения, описанные в файле конфигурации, в БД
	 */
	public function actionInitConfigPermissions():void {
		$configPermissions = Permissions::GetConfigurationPermissions();
		foreach ($configPermissions as $permissionAttributes) {
			$permission = new Permissions($permissionAttributes);
			Console::output(Console::renderColoredString($permission->save()?"%g{$permission->name}: добавлено%n":"%r{$permission->name}: пропущено (".static::Errors2String($permission->errors).")%n"));
		}
	}

	/**
	 * Для всех контроллеров по пути $path добавляет наборы правил доступа в БД
	 * @param string $path
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws InvalidConfigException
	 * @throws UnknownClassException
	 */
	public function actionInitControllersPermissions(string $path = "@app/controllers"):void {
		/** @var WebController[] $foundControllers */
		$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), null, [WebController::class]);
		foreach ($foundControllers as $controller) {
			$controllerActions = ControllerHelper::GetControllerActions(get_class($controller));
			$controllerPermissions = [];
			foreach ($controllerActions as $action) {
				$permission = new Permissions([
					'name' => "{$controller->id}:{$action}",
					'controller' => $controller->id,
					'action' => $action,
					'comment' => "Разрешить доступ к действию {$action} контроллера {$controller->id}"
				]);
				Console::output(Console::renderColoredString($permission->save()?"%gДоступ {$permission->name}: добавлен%n":"%rДоступ {$permission->name}: пропущен (".static::Errors2String($permission->errors).")%n"));
				$controllerPermissions[] = $permission;
			}
			$controllerPermissionsCollection = new PermissionsCollections([
				'name' => "Доступ к контроллеру {$controller->id}",
				'comment' => "Доступ ко всем действиям контроллера {$controller->id}",
			]);
			$controllerPermissionsCollection->relatedPermissions = $controllerPermissions;
			Console::output(Console::renderColoredString($controllerPermissionsCollection->save()?"%g{$controllerPermissionsCollection->name}: добавлено%n":"%r{$controllerPermissionsCollection->name}: пропущено (".static::Errors2String($controllerPermissionsCollection->errors).")%n"));
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