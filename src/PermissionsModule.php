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
	 * @param callable|null $initHandler
	 * @return void
	 * @throws Throwable
	 */
	public static function InitConfigPermissions(?callable $initHandler = null):void {
		$configPermissions = Permissions::GetConfigurationPermissions();
		foreach ($configPermissions as $permissionAttributes) {
			$permission = new Permissions($permissionAttributes);
			$saved = $permission->save();
			if (null !== $initHandler) {
				$initHandler($permission, $saved);
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
            $module = $module ?? (($controller?->module?->id === Yii::$app->id)
                    ? null/*для приложения не сохраняем модуль, для удобства*/
                    : $controller?->module?->id);
            $controllerActions = ControllerHelper::GetControllerActions(get_class($controller));
            $controllerPermissions = [];
            $classReflex = new \ReflectionClass($controller);
            $classConstants = $classReflex->getConstants();

            $controllerName = $controller->id;
            if (array_key_exists('DEFAULT_TITLE', $classConstants) && null !== $classConstants['DEFAULT_TITLE']) {
                $controllerName = $classConstants['DEFAULT_TITLE'];
            }
            foreach ($controllerActions as $action) {
                $actionName = $action;
                if (array_key_exists('ACTION_TITLES', $classConstants)) {
                    $uniqueActionName = array_unique($classConstants['ACTION_TITLES']);
                    if (array_key_exists($action, $uniqueActionName)) {
                        $actionName = $uniqueActionName[$action];
                    }
                }
                $permissionConfig = [
                    'name' => sprintf("%s%s:%s", null === $module ? "" : "{$module}:", $controllerName, $actionName),
                    'module' => $module,
                    'controller' => $controllerName,
                    'action' => $actionName,
                    'comment' => "Разрешить доступ к действию {$actionName} контроллера {$controllerName}" . (null === $module ? "" : " модуля {$module}")
                ];

                $permission = new Permissions($permissionConfig);
                $saved = $permission->save();
                if (null !== $initPermissionHandler) {
                    $initPermissionHandler($permission, $saved);
                }
                $controllerPermissions[] = $permission;
            }
            $controllerPermissionsCollection = new PermissionsCollections([
                'name' => sprintf("Доступ к контроллеру %s%s", null === $module ? '' : "{$module}:", $controllerName),
                'comment' => sprintf("Доступ ко всем действиям контроллера %s%s", $controllerName, null === $module ? '' : " модуля {$module}"),
            ]);
            $controllerPermissionsCollection->relatedPermissions = $controllerPermissions;
            if (null !== $initPermissionCollectionHandler) {
                $initPermissionCollectionHandler($controllerPermissionsCollection, $controllerPermissionsCollection->save());
            }
        }
	}
}
