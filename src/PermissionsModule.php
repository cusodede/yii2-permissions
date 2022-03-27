<?php
declare(strict_types = 1);

namespace cusodede\permissions;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\traits\UsersPermissionsTrait;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\traits\traits\ModuleTrait;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\UnknownClassException;
use yii\db\ActiveRecordInterface;
use yii\web\Controller;
use yii\web\IdentityInterface;

/**
 * Class PermissionsModule
 */
class PermissionsModule extends Module {
	use ModuleTrait;

	public $controllerPath = '@vendor/cusodede/yii2-permissions/src/controllers';

	private static ?string $_userIdentityClass = null;
	private static ?IdentityInterface $_userCurrentIdentity = null;

	public const VERBS = [
		'GET' => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'PATCH' => 'PATCH',
		'DELETE' => 'DELETE'
	];

	/**
	 * @return string|ActiveRecordInterface
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function UserIdentityClass():string|ActiveRecordInterface {
		if (null === static::$_userIdentityClass) {
			$identity = static::param('userIdentityClass', Yii::$app->user->identityClass);
			static::$_userIdentityClass = (is_callable($identity))
				?$identity()
				:$identity;
		}
		return static::$_userIdentityClass;
	}

	/**
	 * @return IdentityInterface|UsersPermissionsTrait
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	public static function UserCurrentIdentity():IdentityInterface {
		if (null === static::$_userCurrentIdentity) {
			$identity = static::param('userCurrentIdentity', Yii::$app->user->identity);
			static::$_userCurrentIdentity = (is_callable($identity))
				?$identity()
				:$identity;
		}
		return static::$_userCurrentIdentity;
	}

	/**
	 * @param mixed $id
	 * @return IdentityInterface|null|UsersPermissionsTrait
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	public static function FindIdentityById(mixed $id):?IdentityInterface {
		return (null === $id)
			?static::UserCurrentIdentity()
			:static::UserIdentityClass()::findOne($id);
	}

	/**
	 * Возвращает список контроллеров в указанном каталоге, обрабатываемых модулем (в формате конфига)
	 * @return string[]
	 * @throws Throwable
	 */
	public static function GetControllersList(array $controllerDirs = ['@app/controllers']):array {
		$result = [];
		foreach ($controllerDirs as $controllerDir => $idPrefix) {
			$controllers = ControllerHelper::GetControllersList((string)$controllerDir, null, [Controller::class]);
			$result[$controllerDir] = ArrayHelper::map($controllers, static function(Controller $model) use ($idPrefix) {
				return ('' === $idPrefix)?$model->id:$idPrefix.'/'.$model->id;
			}, static function(Controller $model) use ($idPrefix) {
				return ('' === $idPrefix)?$model->id:$idPrefix.'/'.$model->id;
			});
		}
		return $result;
	}

	/**
	 * @param callable|null $initHandler
	 * @return void
	 * @throws Throwable
	 */
	public static function InitConfigPermissions(?callable $initHandler = null):void {
		$configPermissions = Permissions::GetConfigurationPermissions();
		foreach ($configPermissions as $permissionAttributes) {
			$permission = new Permissions($permissionAttributes);
			if (null !== $initHandler) {
				$initHandler($permission, $permission->save());
			}
		}
	}

	/**
	 * @param string $path Путь к каталогу с контроллерами (рекурсивный корень).
	 * @param string|null $moduleId Модуль, которому принадлежат контроллеры, null для автоматического определения
	 * @param callable|null $initPermissionHandler
	 * @param callable|null $initPermissionCollectionHandler
	 * @return void
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public static function InitControllersPermissions(string $path = "@app/controllers", ?string $moduleId = null, ?callable $initPermissionHandler = null, ?callable $initPermissionCollectionHandler = null):void {
		/** @var Controller[] $foundControllers */
		$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), $moduleId, [Controller::class]);
		foreach ($foundControllers as $controller) {
			$module = ($controller?->module?->id === Yii::$app->id)
				?null/*для приложения не сохраняем модуль, для удобства*/
				:$controller?->module?->id;
			$controllerActions = ControllerHelper::GetControllerActions(get_class($controller));
			$controllerPermissions = [];
			foreach ($controllerActions as $action) {
				$permission = new Permissions([
					'name' => "{$controller->id}:{$action}",
					'module' => $module,
					'controller' => $controller->id,
					'action' => $action,
					'comment' => "Разрешить доступ к действию {$action} контроллера {$controller->id}"
				]);
				if (null !== $initPermissionHandler) {
					$initPermissionHandler($permission, $permission->save());
				}
				$controllerPermissions[] = $permission;
			}
			$controllerPermissionsCollection = new PermissionsCollections([
				'name' => "Доступ к контроллеру {$controller->id}",
				'comment' => "Доступ ко всем действиям контроллера {$controller->id}",
			]);
			$controllerPermissionsCollection->relatedPermissions = $controllerPermissions;
			if (null !== $initPermissionCollectionHandler) {
				$initPermissionCollectionHandler($controllerPermissionsCollection, $controllerPermissionsCollection->save());
			}
		}
	}
}
