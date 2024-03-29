<?php
declare(strict_types = 1);

namespace cusodede\permissions;

use cusodede\permissions\helpers\CommonHelper;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\traits\UsersPermissionsTrait;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use pozitronik\helpers\ControllerHelper;
use pozitronik\helpers\ModuleHelper;
use pozitronik\traits\traits\ModuleTrait;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\base\NotSupportedException;
use yii\base\UnknownClassException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
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

	public const REGISTERED_CONTROLLERS_LIST = 'REGISTERED_CONTROLLERS_LIST';

	public $controllerPath = '@vendor/cusodede/yii2-permissions/src/controllers';

	private static ?string $_userIdentityClass = null;
	/**
	 * Caching class, used by module. null for global cache.
	 * @var string|null
	 */
	private static mixed $_cacheComponent = null;

	public const VERBS = [
		'GET' => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'PATCH' => 'PATCH',
		'DELETE' => 'DELETE'
	];

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
			$identity = static::param('userIdentityClass')??Yii::$app->user->identityClass;
			static::$_userIdentityClass = (is_callable($identity))
				?$identity()
				:$identity;
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
		return (is_callable($identity))
			?$identity()
			:$identity;
	}

	/**
	 * @return CacheInterface
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function Cache():CacheInterface {
		if (null === static::$_cacheComponent) {
			static::$_cacheComponent = static::param('cache', Yii::$app->cache);
			if (!is_object(static::$_cacheComponent)) {
				static::$_cacheComponent = Yii::createObject(static::$_cacheComponent);
			}
		}
		return static::$_cacheComponent;
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
				return (null === $module)
					?$model->id
					:$module.'/'.$model->id;
			}, static function(Controller $model) use ($module) {
				return (null === $module)
					?$model->id
					:$module.'/'.$model->id;
			});
		}
		return $result;
	}

	/**
	 * Collects all registered controllers paths and returns them, grouped by modules.
	 * @return string[]
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function GetRegisteredControllerPathsList():array {
		$cacheKey = CacheHelper::MethodSignature(__METHOD__, func_get_args());
		return static::Cache()->getOrSet($cacheKey, function() {
			$registeredRoutes = Permissions::find()
				->distinct()
				->select(['module', 'controller'])
				->where(['not', ['controller' => null]])
				->orderBy(['module' => SORT_ASC, 'controller' => SORT_ASC])
				->asArray()
				->all();
			$appRoutes = [];
			$moduleRoutes = [];
			foreach ($registeredRoutes as $route) {
				$module = $route['module'];
				$controller = $route['controller'];
				if (null === $module) {
					$appRoutes['@app'][$controller] = $controller;
				} else {
					$moduleRoutes["@{$module}"]["@{$module}/{$controller}"] = $controller;
				}
			}
			//made app controllers at the top
			return ($appRoutes + $moduleRoutes);
		}, null, new TagDependency(['tags' => static::REGISTERED_CONTROLLERS_LIST]));
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
	 * @param string|null $moduleId
	 * @param string $controllerId
	 * @param string $actionId
	 * @return string
	 */
	protected static function GetControllerActionPermissionName(?string $moduleId, string $controllerId, string $actionId):string {
		return sprintf("%s%s:%s", null === $moduleId?"":"{$moduleId}:", $controllerId, $actionId);
	}

	/**
	 * Generates a permission collection name for module-controller pair
	 * @param string|null $moduleId
	 * @param string $controllerId
	 * @return string
	 */
	protected static function GetControllerPermissionCollectionName(?string $moduleId, string $controllerId):string {
		return sprintf("Доступ к контроллеру %s%s", null === $moduleId?'':"{$moduleId}:", $controllerId);
	}

	/**
	 * @param string $path
	 * @param string|null $moduleId
	 * @param callable|null $initPermissionCollectionHandler
	 * @return Controller[]|string[]
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private static function CollectControllersFromPath(string $path = "@app/controllers", ?string &$moduleId = null, ?callable $initPermissionCollectionHandler = null):array {
		if ('' === $moduleId) $moduleId = null;//для совместимости со старым вариантом конфига
		$ignoredFilesList = static::param('ignorePaths', []);
		/*Если модуль указан в формате @moduleId, модуль не загружается, идентификатор подставится напрямую*/
		if (null !== $moduleId && '@' === $moduleId[0]) {
			$moduleId = substr($moduleId, 1);
			return CommonHelper::GetControllersList(Yii::getAlias($path), null, [Controller::class], $ignoredFilesList);
		}
		if (null !== $moduleId && null === ModuleHelper::GetModuleById($moduleId)) {
			$fakePermission = new PermissionsCollections([
				'name' => static::GetControllerPermissionCollectionName($moduleId, ''),
				'comment' => "Module '$moduleId' not found",
			]);
			$fakePermission->addError('id', "Module '$moduleId' not found");
			$initPermissionCollectionHandler($fakePermission, false, false);
			return [];
		}
		return CommonHelper::GetControllersList(Yii::getAlias($path), $moduleId, [Controller::class], $ignoredFilesList);
	}

	/**
	 * @param Controller[]|string[] $controllers
	 * @param string|null $module
	 * @param callable|null $initPermissionHandler
	 * @param callable|null $initPermissionCollectionHandler
	 * @return void
	 * @throws ReflectionException
	 * @throws Throwable
	 * @throws UnknownClassException
	 */
	public static function GenerateControllersPermissions(array $controllers, ?string $module = null, ?callable $initPermissionHandler = null, ?callable $initPermissionCollectionHandler = null):void {
		foreach ($controllers as $controller) {
			if (is_string($controller)) {
				/* When an error happens, it's description passed as string */
				$eCollection = new PermissionsCollections([
					'name' => '',
					'comment' => null
				]);
				$eCollection->addError('name', $controller);
				$initPermissionCollectionHandler($eCollection, false);
			} else {
				$module = $module??(($controller?->module?->id === Yii::$app->id)
					?null/*для приложения не сохраняем модуль, для удобства*/
					:$controller?->module?->id);
				$controllerActionsNames = ControllerHelper::GetControllerActions($controller);
				$controllerPermissions = [];
				foreach ($controllerActionsNames as $action) {
					$permission = Permissions::Upsert([
						'name' => static::GetControllerActionPermissionName($module, $controller->id, $action),
						'module' => $module,
						'controller' => $controller->id,
						'action' => $action,
						'comment' => "Разрешить доступ к действию {$action} контроллера {$controller->id}".(null === $module?"":" модуля {$module}")
					], false);
					$alreadyExist = !$permission->isNewRecord;
					if (true === $saved = $permission->save()) $controllerPermissions[] = $permission;
					if (null !== $initPermissionHandler) {
						$initPermissionHandler($permission, $saved, $alreadyExist);
					}
				}
				$controllerPermissionsCollection = PermissionsCollections::Upsert([
					'name' => static::GetControllerPermissionCollectionName($module, $controller->id),
					'comment' => sprintf("Доступ ко всем действиям контроллера %s%s", $controller->id, null === $module?'':" модуля {$module}")], false);
				$controllerPermissionsCollection->relatedPermissions = $controllerPermissions;
				$alreadyExist = !$controllerPermissionsCollection->isNewRecord;
				$saved = $controllerPermissionsCollection->save();
				if (null !== $initPermissionCollectionHandler) {
					$initPermissionCollectionHandler($controllerPermissionsCollection, $saved, $alreadyExist);
				}
			}
		}
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
		$foundControllers = static::CollectControllersFromPath($path, $moduleId, $initPermissionCollectionHandler);
		static::GenerateControllersPermissions($foundControllers, $moduleId, $initPermissionHandler, $initPermissionCollectionHandler);
	}

	/**
	 * Удаляет все ранее сгенерированные ненужные пермиссии.
	 * @param callable|null $deletePermissionHandler Опциональный обработчик удаления доступа
	 * @param callable|null $deletePermissionCollectionHandler Опциональный обработчик удаления коллекции
	 * @param bool $doDelete true: удалить разрешения, false: передать в обработчик без удаления. При false коллекции не обрабатываются.
	 * @return void
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws StaleObjectException
	 * @throws Throwable
	 * @throws UnknownClassException
	 * @throws NotSupportedException
	 */
	public static function DropUnusedControllersPermissions(bool $doDelete = true, ?callable $deletePermissionHandler = null, ?callable $deletePermissionCollectionHandler = null):void {
		$checkedPermissionsCollectionsNames = [];
		/** @var Permissions[] $allControllersPermissions */
		$allControllersPermissions = Permissions::find()->where(['not', ['controller' => null]])->all();
		foreach ($allControllersPermissions as $permission) {
			if ($permission->getWarningFlags(Permissions::WARN_NO_PATH) & Permissions::WARN_NO_PATH) {
				$deleted = $doDelete && $permission->delete();
				$checkedPermissionsCollectionsNames[] = static::GetControllerPermissionCollectionName($permission->module, $permission->controller);
				/** @var Permissions $unusedPermission */
				if (null !== $deletePermissionHandler) $deletePermissionHandler($permission, false !== $deleted);
			}
		}

		if ($doDelete) {
			$allUnusedPermissionsCollections = PermissionsCollections::find()
				->where(['name' => $checkedPermissionsCollectionsNames])
				->andFilterWhereRelation(['id' => null], 'relatedPermissions')//Нельзя удалять коллекции по имени, нужно удалять те, в которых не осталось правил
				->all();
			foreach ($allUnusedPermissionsCollections as $unusedCollection) {
				/** @var PermissionsCollections $unusedCollection */
				$deleted = $unusedCollection->delete();
				if (null !== $deletePermissionCollectionHandler) $deletePermissionCollectionHandler($unusedCollection, false !== $deleted);
			}
		}
	}

	/**
	 * Returns the sorted list of all modules names, required by inline editor
	 * @return array
	 */
	public static function GetModulesList():array {
		$modules = array_keys(Yii::$app->modules);
		sort($modules);
		return array_combine($modules, $modules);
	}
}
