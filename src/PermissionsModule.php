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
use yii\console\Application as ConsoleApplication;
use yii\db\ActiveRecordInterface;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\IdentityInterface;

/**
 * Class PermissionsModule
 */
class PermissionsModule extends Module {
	use ModuleTrait;

	public $controllerPath = '@vendor/cusodede/yii2-permissions/src/controllers';

	private static ?string $_userIdentityClass = null;

	public const VERBS = ['GET' => 'GET', 'HEAD' => 'HEAD', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE'];

	/**
	 * @inheritDoc
	 */
	public function init():void {
		if (Yii::$app instanceof ConsoleApplication) {
			$this->controllerNamespace = 'cusodede\permissions\commands';
			$this->setControllerPath('@vendor/cusodede/yii2-permissions/src/commands');
		}
		parent::init();
	}

	/**
	 * @return string|ActiveRecordInterface
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function UserIdentityClass():string|ActiveRecordInterface {
		if (null === static::$_userIdentityClass) {
			$identity = static::param('userIdentityClass', Yii::$app->user->identityClass);
			static::$_userIdentityClass = (is_callable($identity))?$identity():$identity;
		}
		return static::$_userIdentityClass;
	}

	/**
	 * @return null|IdentityInterface|UsersPermissionsTrait
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	public static function UserCurrentIdentity():?IdentityInterface {
		$identity = static::param('userCurrentIdentity', Yii::$app->user->identity);
		return (is_callable($identity))?$identity():$identity;
	}

	/**
	 * @param mixed $id
	 * @return IdentityInterface|null|UsersPermissionsTrait
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	public static function FindIdentityById(mixed $id):?IdentityInterface {
		return (null === $id)?static::UserCurrentIdentity():static::UserIdentityClass()::findOne($id);
	}

	/**
	 * Возвращает список контроллеров в указанном каталоге, обрабатываемых модулем (в формате конфига)
	 * @return string[]
	 * @throws Throwable
	 */
	public static function GetControllersList(array $controllerDirs = ['@app/controllers']):array {
		$result = [];
		foreach ($controllerDirs as $controllerDir => $moduleId) {
			/*Если модуль указан в формате @moduleId, модуль не загружается, идентификатор подставится напрямую*/
			if (null !== $moduleId && '@' === $moduleId[0]) {
				$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($controllerDir), null, [Controller::class]);
				$module = substr($moduleId, 1);
			} else {
				$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($controllerDir), $moduleId, [Controller::class]);
				$module = null;
			}
			$result[$controllerDir] = ArrayHelper::map($foundControllers, static function(Controller $model) use ($module) {
				return (null === $module)?$model->id:$module.'/'.$model->id;
			}, static function(Controller $model) use ($module) {
				return (null === $module)?$model->id:$module.'/'.$model->id;
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
		foreach ($configPermissions as $permission) {
			$saved = $permission->save();
			if (null !== $initHandler) {
				$initHandler($permission, $saved);
			}
		}
	}

	/**
	 * Generates a permission name for module-controller-action
	 * @param string|null $module
	 * @param Controller $controller
	 * @param string $actionName
	 * @return string
	 */
	protected static function GetControllerActionPermissionName(?string $module, Controller $controller, string $actionName):string {
		return sprintf("%s%s:%s", null === $module?"":"{$module}:", $controller->id, $actionName);
	}

	/**
	 * Generates a permission collection name for module-controller pair
	 * @param string|null $module
	 * @param Controller $controller
	 * @return string
	 */
	protected static function GetControllerPermissionCollectionName(?string $module, Controller $controller):string {
		return sprintf("Доступ к контроллеру %s%s", null === $module?'':"{$module}:", $controller->id);
	}

	/**
	 * @param string $path Путь к каталогу с контроллерами (рекурсивный корень).
	 * @param string|null $moduleId Модуль, которому принадлежат контроллеры (null для контроллеров приложения)
	 * @param callable|null $initPermissionHandler
	 * @param callable|null $initPermissionCollectionHandler
	 * @return void
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public static function InitControllersPermissions(string $path = "@app/controllers", ?string $moduleId = null, ?callable $initPermissionHandler = null, ?callable $initPermissionCollectionHandler = null):void {
		$module = null;
		if ('' === $moduleId)
			$moduleId = null;//для совместимости со старым вариантом конфига
		/*Если модуль указан в формате @moduleId, модуль не загружается, идентификатор подставится напрямую*/
		if (null !== $moduleId && '@' === $moduleId[0]) {
			$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), null, [Controller::class]);
			$module = substr($moduleId, 1);
		} else {
			$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), $moduleId, [Controller::class]);
		}

		/** @var Controller[] $foundControllers */
		foreach ($foundControllers as $controller) {
			$module = $module??(($controller?->module?->id === Yii::$app->id)?null/*для приложения не сохраняем модуль, для удобства*/:$controller?->module?->id);
			$controllerActions = ControllerHelper::GetControllerActions(get_class($controller));
			$controllerPermissions = [];
			foreach ($controllerActions as $action) {
				$permission = new Permissions([
					'name' => static::GetControllerActionPermissionName($module, $controller, $action),
					'module' => $module,
					'controller' => $controller->id,
					'action' => $action,
					'comment' => "Разрешить доступ к действию {$action} контроллера {$controller->id}".(null === $module?"":" модуля {$module}")
				]);
				$saved = $permission->save();
				if (null !== $initPermissionHandler) {
					$initPermissionHandler($permission, $saved);
				}
				$controllerPermissions[] = $permission;
			}
			$controllerPermissionsCollection = new PermissionsCollections([
				'name' => static::GetControllerPermissionCollectionName($module, $controller),
				'comment' => sprintf("Доступ ко всем действиям контроллера %s%s", $controller->id, null === $module?'':" модуля {$module}"),]);
			$controllerPermissionsCollection->relatedPermissions = $controllerPermissions;
			if (null !== $initPermissionCollectionHandler) {
				$initPermissionCollectionHandler($controllerPermissionsCollection, $controllerPermissionsCollection->save());
			}
		}
	}

	/**
	 * Удаляет все ранее сгенерированные ненужные пермиссии.
	 * @param string $path Путь к каталогу с контроллерами (рекурсивный корень).
	 * @param string|null $moduleId Модуль, которому принадлежат контроллеры (null для контроллеров приложения)
	 * @param callable|null $deletePermissionHandler Опциональный обработчик удаления доступа
	 * @param callable|null $deletePermissionCollectionHandler Опциональный обработчик удаления коллекции
	 * @return void
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 * @throws StaleObjectException
	 */
	public static function DropUnusedControllersPermissions(string $path = "@app/controllers", ?string $moduleId = null, ?callable $deletePermissionHandler = null, ?callable $deletePermissionCollectionHandler = null):void {
		$currentPermissionNames = [];
		$currentPermissionsCollectionsNames = [];

		$module = null;
		if ('' === $moduleId)
			$moduleId = null;//для совместимости со старым вариантом конфига
		/*Если модуль указан в формате @moduleId, модуль не загружается, идентификатор подставится напрямую*/
		if (null !== $moduleId && '@' === $moduleId[0]) {
			$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), null, [Controller::class]);
			$module = substr($moduleId, 1);
		} else {
			$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), $moduleId, [Controller::class]);
		}

		/** @var Controller[] $foundControllers */
		foreach ($foundControllers as $controller) {
			$module = $module??(($controller?->module?->id === Yii::$app->id)?null/*для приложения не сохраняем модуль, для удобства*/:$controller?->module?->id);
			$controllerActions = ControllerHelper::GetControllerActions(get_class($controller));
			foreach ($controllerActions as $action) {
				$currentPermissionNames[] = static::GetControllerActionPermissionName($module, $controller, $action);
			}
			$currentPermissionsCollectionsNames[] = static::GetControllerPermissionCollectionName($module, $controller);
		}

		if (null !== $deletePermissionHandler) {
			foreach (Permissions::find()->where(['not', ['name' => $currentPermissionNames]])->andWhere(['module' => $moduleId])->all() as $unusedPermission) {
				/** @var Permissions $unusedPermission */
				$deletePermissionHandler($unusedPermission, false !== $unusedPermission->delete());
			}
		} else {
			Permissions::deleteAll(['not', ['name' => $currentPermissionNames]]);
		}

		if (null !== $deletePermissionCollectionHandler) {
			foreach (PermissionsCollections::find()->where(['not', ['name' => $currentPermissionsCollectionsNames]])->all() as $unusedCollection) {
				/** @var PermissionsCollections $unusedCollection */
				if ([] === $unusedCollection->relatedPermissions) {//Нельзя удалять коллекции по имени, нужно удалять те, в которых не осталось правил
					$deletePermissionCollectionHandler($unusedCollection, false !== $unusedCollection->delete());
				}

			}
		} else {
			PermissionsCollections::deleteAll(['not', ['name' => $currentPermissionsCollectionsNames]]);
		}
	}
}
