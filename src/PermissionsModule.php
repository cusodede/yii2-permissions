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
use yii\web\Controller;
use yii\web\IdentityInterface;

/**
 * Class PermissionsModule
 */
class PermissionsModule extends Module {
	use ModuleTrait;

	public $controllerPath = '@vendor/cusodede/yii2-permissions/src/controllers';

	private static ?string $_userIdentityClass = null;

	/*Любое из перечисленных прав*/
	public const LOGIC_OR = 0;
	/*Все перечисленные права*/
	public const LOGIC_AND = 1;
	/*Ни одно из перечисленных прав*/
	public const LOGIC_NOT = 2;

	/*Минимальный/максимальный приоритет*/
	public const PRIORITY_MIN = 0;
	public const PRIORITY_MAX = 100;

	/*Параметры разрешения, для которых пустой фильтр приравнивается к любому значению*/
	public const ALLOWED_EMPTY_PARAMS = ['action', 'verb'];

	public const GRANT_ALL = 'grantAll';
	public const CONTROLLER_DIRS = 'controllerDirs';
	/*Название параметра с преднастроенными правилами доступов*/
	public const CONFIGURATION_PERMISSIONS = 'permissions';
	/*Название параметра с преднастроенными коллекциями*/
	public const CONFIGURATION_PERMISSIONS_COLLECTIONS = 'collections';
	/*Перечисление назначений конфигураций через конфиги, id => ['...', '...']*/
	public const GRANT_PERMISSIONS = 'grant';

	public const VERBS = [
		'GET' => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'PATCH' => 'PATCH',
		'DELETE' => 'DELETE'
	];

	/*Загружать из конфигурации список разрешений*/
	public const PERMISSIONS = 0b0001;
	/*Загружать из конфигурации список коллекций*/
	public const PERMISSIONS_COLLECTIONS = 0b0010;
	/*Загружать из конфигурации список вложенных конфигураций (todo)*/
	public const INCLUDES = 0b0100;

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
				return (null === $module)?$model->id:$module.'/'.$model->id;
			}, static function(Controller $model) use ($module) {
				return (null === $module)?$model->id:$module.'/'.$model->id;
			});
		}
		return $result;
	}

	/**
	 * Загружает в БД доступы из файла конфигурации
	 * @param callable|null $initHandler Обработчик, вызываемый после обработки конфигурации.
	 *        function (Permission|PermissionCollection $permission, bool $isSaved, int $type);
	 * @return void
	 * @throws Throwable
	 */
	public static function ImportConfigPermissions(?callable $initHandler = null, int $mode = self::PERMISSIONS + self::PERMISSIONS_COLLECTIONS + self::INCLUDES):void {
		if ($mode & self::PERMISSIONS) {
			foreach (Permissions::GetConfigurationPermissions() as $permission) {
				$saved = $permission->save();
				if (null !== $initHandler) {
					$initHandler($permission, $saved, self::PERMISSIONS);
				}
			}
		}
		if ($mode & self::PERMISSIONS_COLLECTIONS) {
			foreach (PermissionsCollections::GetConfigurationPermissionsCollections() as $permissionsCollection) {
				$saved = $permissionsCollection->save();
				if (null !== $initHandler) {
					$initHandler($permissionsCollection, $saved, self::PERMISSIONS);
				}
			}
		}
		if ($mode & self::INCLUDES) {
			//todo
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
		$module = null;
		/*Если модуль указан в формате @moduleId, модуль не загружается, идентификатор подставится напрямую*/
		if (null !== $moduleId && '@' === $moduleId[0]) {
			$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), null, [Controller::class]);
			$module = substr($moduleId, 1);
		} else {
			$foundControllers = ControllerHelper::GetControllersList(Yii::getAlias($path), $moduleId, [Controller::class]);
		}

		/** @var Controller[] $foundControllers */
		foreach ($foundControllers as $controller) {
			$module = $module??(($controller?->module?->id === Yii::$app->id)
					?null/*для приложения не сохраняем модуль, для удобства*/
					:$controller?->module?->id);
			$controllerActions = ControllerHelper::GetControllerActions(get_class($controller));
			$controllerPermissions = [];
			foreach ($controllerActions as $action) {
				$permission = new Permissions([
					'name' => sprintf("%s%s:%s", null === $module?"":"{$module}:", $controller->id, $action),
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
				'name' => sprintf("Доступ к контроллеру %s%s", null === $module?'':"{$module}:", $controller->id),
				'comment' => sprintf("Доступ ко всем действиям контроллера %s%s", $controller->id, null === $module?'':" модуля {$module}"),
			]);
			$controllerPermissionsCollection->relatedPermissions = $controllerPermissions;
			$saved = $controllerPermissionsCollection->save();
			if (null !== $initPermissionCollectionHandler) {
				$initPermissionCollectionHandler($controllerPermissionsCollection, $saved);
			}
		}
	}

}
