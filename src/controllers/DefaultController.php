<?php
declare(strict_types = 1);

namespace cusodede\permissions\controllers;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use cusodede\web\default_controller\models\DefaultController as VendorDefaultController;
use ReflectionException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\base\UnknownClassException;
use yii\data\ArrayDataProvider;
use yii\db\StaleObjectException;

/**
 * Class ServiceController
 * Визуальная админка генераторов
 */
class DefaultController extends VendorDefaultController {

	public const PERMISSION = 1;
	public const PERMISSIONS_COLLECTION = 2;

	/**
	 * Название контроллера
	 */
	protected const DEFAULT_TITLE = 'Сервис доступов';

	protected array $disabledActions = [
		'actionCreate',
		'actionView',
		'actionDelete',
		'actionUpdate',
		'actionEdit'
	];

	protected const ACTION_TITLES = [
		'init-config-permissions' => 'Импорт из конфига',
		'init-controllers-permissions' => 'Генерация доступов по контроллерам',
	];

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return '@vendor/cusodede/yii2-permissions/src/views/default';
	}

	/**
	 * @return string
	 */
	public function actionIndex():string {
		return $this->render('index');
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionInitConfigPermissions():string {
		$result = [];
		PermissionsModule::InitConfigPermissions(static function(Permissions $permission, bool $saved) use (&$result) {
			$result[] = [
				'saved' => $saved,
				'item' => $permission
			];
		});
		return $this->render('init-config-permissions', [
			'result' => new ArrayDataProvider([
				'allModels' => $result,
				'pagination' => false
			])
		]);
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
	public function actionInitControllersPermissions(?string $path = null, ?string $moduleId = null):string {
		$result = [];
		$pathMapping = [];
		if (is_string($path)) $pathMapping = [$path => $moduleId];
		if (null === $path) $pathMapping = PermissionsModule::param(Permissions::CONTROLLER_DIRS);
		foreach ($pathMapping as $controller_dir => $module_id) {
			PermissionsModule::InitControllersPermissions($controller_dir, $module_id, static function(Permissions $permission, bool $saved) use (&$result) {
				$result[] = [
					'type' => self::PERMISSION,
					'saved' => $saved,
					'item' => $permission
				];
			}, static function(PermissionsCollections $permissionsCollection, bool $saved) use (&$result) {
				$result[] = [
					'type' => self::PERMISSIONS_COLLECTION,
					'saved' => $saved,
					'item' => $permissionsCollection
				];
			});
		}
		return $this->render('init-controllers-permissions', [
			'result' => new ArrayDataProvider([
				'allModels' => $result,
				'pagination' => false
			])
		]);
	}

	/**
	 * Удаляет все неиспользуемые наборы правил доступа в БД
	 * @param bool $confirm
	 * @return string
	 * @throws InvalidConfigException
	 * @throws ReflectionException
	 * @throws StaleObjectException
	 * @throws Throwable
	 * @throws UnknownClassException
	 * @throws NotSupportedException
	 */
	public function actionDropUnusedControllersPermissions(bool $confirm = false):string {
		$result = [];
		PermissionsModule::DropUnusedControllersPermissions($confirm, static function(Permissions $permission, bool $deleted) use (&$result) {
			$result[] = [
				'type' => self::PERMISSION,
				'deleted' => $deleted,
				'item' => $permission
			];
		}, static function(PermissionsCollections $permissionsCollection, bool $deleted) use (&$result) {
			$result[] = [
				'type' => self::PERMISSIONS_COLLECTION,
				'deleted' => $deleted,
				'item' => $permissionsCollection
			];
		});
		return $this->render('delete-controllers-permissions', [
			'result' => new ArrayDataProvider([
				'allModels' => $result,
				'pagination' => false
			]),
			'confirm' => $confirm
		]);
	}
}