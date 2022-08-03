<?php
declare(strict_types = 1);

namespace cusodede\permissions\traits;

use cusodede\permissions\models\active_record\relations\RelUsersToPermissions;
use cusodede\permissions\models\active_record\relations\RelUsersToPermissionsCollections;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use Throwable;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\Controller;

/**
 * Class Permissions
 * Управление правами доступа
 * @property int $id Model primary key attribute name
 *
 * @property RelUsersToPermissions[] $relatedUsersToPermissions Связь к промежуточной таблице пользовательских разрешений
 * @property RelUsersToPermissionsCollections[] $relatedUsersToPermissionsCollections Связь к промежуточной таблице пользовательских коллекций
 * @property Permissions[] $relatedPermissions Назначенные напрямую разрешения
 * @property PermissionsCollections[] $relatedPermissionsCollections Назначенные коллекции разрешений
 */
trait UsersPermissionsTrait {

	/**
	 * Проверяет, имеет ли пользователь разрешение или набор разрешений с указанной логикой проверки.
	 * Примеры:
	 * $user->hasPermission(['execute_order_66'])
	 * $user->hasPermission(['rule_galaxy', 'lose_arm'], Permissions::LOGIC_AND)
	 *
	 * @param string|string[] $permissions Названия проверяемых разрешений
	 * @param int $logic Логика проверки
	 * @return bool
	 * @throws Throwable
	 */
	public function hasPermission(array|string $permissions, int $logic = PermissionsModule::LOGIC_OR):bool {
		if (is_string($permissions)) $permissions = [$permissions];
		$cacheKey = CacheHelper::MethodSignature(__METHOD__, func_get_args(), ['id' => $this->id]);
		return Yii::$app->cache->getOrSet($cacheKey, function() use ($permissions, $logic) {
			$result = false;
			$allUserPermissionsNames = ArrayHelper::getColumn(self::allPermissions(), 'name');
			foreach ($permissions as $current_permission_name) {
				if (false === $result = $this->isAllPermissionsGranted()) {
					$result = in_array(trim($current_permission_name), $allUserPermissionsNames, true);
				}

				switch ($logic) {
					case PermissionsModule::LOGIC_OR:
						if ($result) return true; //при первом же найденном совпадении рапортуем о удаче
					break;
					case PermissionsModule::LOGIC_AND:
						if (!$result) return false;//при первом же не найденном совпадении рапортуем о неудаче
					break;
					case PermissionsModule::LOGIC_NOT:
						if ($result) return false;//при первом же найденном совпадении рапортуем о неудаче
					break;
				}
			}
			return ($logic === PermissionsModule::LOGIC_NOT)?true:$result;
		}, null, new TagDependency(['tags' => [
			CacheHelper::MethodSignature('Users::allPermissions', ['id' => $this->id]),
			CacheHelper::MethodSignature('Users::hasPermission', ['id' => $this->id]),
		]]));//тег ставится на все варианты запроса ролей пользователя для сброса скопом

	}

	/**
	 * Все разрешения пользователя, отсортированные по приоритету от большего к меньшему
	 * Учитываются разрешения коллекций пользователя + прямые разрешения, без разделения
	 * @param bool $force false (default): получить кешированный набор разрешений; true: получить актуальный набор разрешений с обновлением кеша
	 * @return self[]
	 * @throws Throwable
	 */
	public function allPermissions(bool $force = false):array {
		$cacheKey = CacheHelper::MethodSignature('Users::allPermissions', ['id' => $this->id]);
		if ($force) Yii::$app->cache->delete($cacheKey);
		return Yii::$app->cache->getOrSet($cacheKey, function() {
			return array_merge(Permissions::allUserPermissions($this->id), Permissions::allUserConfigurationPermissions($this->id));
		}, null, new TagDependency(['tags' => $cacheKey]));
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedUsersToPermissions():ActiveQuery {
		return $this->hasMany(RelUsersToPermissions::class, ['user_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissions():ActiveQuery {
		return $this->hasMany(Permissions::class, ['id' => 'permission_id'])->via('relatedUsersToPermissions');
	}

	/**
	 * @param mixed $relatedPermissions
	 * @throws Throwable
	 */
	public function setRelatedPermissions(mixed $relatedPermissions):void {
		/** @var ActiveRecord $this */
		if (empty($relatedPermissions)) {
			RelUsersToPermissions::clearLinks($this);
		} else {
			RelUsersToPermissions::linkModels($this, $relatedPermissions);
		}
		$this->invalidateUserTag('Users::allPermissions');
	}

	/**
	 * Добавить пользователю разрешение по id, имени, или напрямую.
	 * Метод не проверяет существование связи.
	 * @param int|string|Permissions $permission
	 * @return bool False, если разрешение не существует.
	 * @throws Throwable
	 * @noinspection CallableParameterUseCaseInTypeContextInspection
	 */
	public function grantPermission(int|string|Permissions $permission):bool {
		if (is_string($permission)) {
			if (null === $permission = Permissions::find()->where(['name' => $permission])->one()) return false;
		} elseif (is_int($permission)) {
			if (null === $permission = Permissions::findModel($permission)) return false;
		}
		$this->setRelatedPermissions($permission);
		return true;
	}

	/**
	 * Убрать у пользователя разрешение по id, имени, или напрямую.
	 * Метод не проверяет существование связи.
	 * @param int|string|Permissions $permission
	 * @return bool False, если разрешение не существует.
	 * @throws Throwable
	 * @noinspection CallableParameterUseCaseInTypeContextInspection
	 */
	public function revokePermission(int|string|Permissions $permission):bool {
		if (is_string($permission)) {
			if (null === $permission = Permissions::find()->where(['name' => $permission])->one()) return false;
		} elseif (is_int($permission)) {
			if (null === $permission = Permissions::findModel($permission)) return false;
		}
		RelUsersToPermissions::unlinkModel($this, $permission);
		return true;
	}

	/**
	 * Добавить пользователю коллекцию по id, имени, или напрямую.
	 * Метод не проверяет существование связи.
	 * @param int|string|PermissionsCollections $collection
	 * @return bool False, если коллекция не существует.
	 * @throws Throwable
	 * @noinspection CallableParameterUseCaseInTypeContextInspection
	 */
	public function grantCollection(int|string|PermissionsCollections $collection):bool {
		if (is_string($collection)) {
			if (null === $collection = PermissionsCollections::find()->where(['name' => $collection])->one()) return false;
		} elseif (is_int($collection)) {
			if (null === $collection = PermissionsCollections::findModel($collection)) return false;
		}
		$this->setRelatedPermissionsCollections($collection);
		return true;
	}

	/**
	 * Убрать у пользователя коллекцию по id, имени, или напрямую.
	 * Метод не проверяет существование связи.
	 * @param int|string|PermissionsCollections $collection
	 * @return bool False, если разрешение не существует.
	 * @throws Throwable
	 * @noinspection CallableParameterUseCaseInTypeContextInspection
	 */
	public function revokeCollection(int|string|PermissionsCollections $collection):bool {
		if (is_string($collection)) {
			if (null === $collection = Permissions::find()->where(['name' => $collection])->one()) return false;
		} elseif (is_int($collection)) {
			if (null === $collection = Permissions::findModel($collection)) return false;
		}
		RelUsersToPermissionsCollections::unlinkModel($this, $collection);
		return true;
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedUsersToPermissionsCollections():ActiveQuery {
		return $this->hasMany(RelUsersToPermissionsCollections::class, ['user_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissionsCollections():ActiveQuery {
		return $this->hasMany(PermissionsCollections::class, ['id' => 'collection_id'])->via('relatedUsersToPermissionsCollections');
	}

	/**
	 * @param mixed $relatedPermissionsCollections
	 * @throws Throwable
	 */
	public function setRelatedPermissionsCollections(mixed $relatedPermissionsCollections):void {
		/** @var ActiveRecord $this */
		if (empty($relatedPermissionsCollections)) {
			RelUsersToPermissionsCollections::clearLinks($this);
		} else {
			RelUsersToPermissionsCollections::linkModels($this, $relatedPermissionsCollections);
		}
		$this->invalidateUserTag('Users::allPermissions');
	}

	/**
	 * Есть ли у пользователя доступ к экшену
	 * @param Action $action
	 * @return bool
	 * @throws Throwable
	 */
	public function hasActionPermission(Action $action):bool {
		if ($this->isAllPermissionsGranted()) return true;
		return $this->hasControllerPermission($action->controller->id, $action->id, Yii::$app->request->method, ($action->controller->module->id === Yii::$app->id)?null:$action->controller->module->id);
	}

	/**
	 * Проверка наличия разрешения на доступ к контроллеру, и, опционально, экшену с указанным методом
	 * @param string $controllerId
	 * @param string|null $actionId
	 * @param string|null $verb
	 * @param string|null $moduleId
	 * @return bool
	 * @throws Throwable
	 */
	public function hasControllerPermission(string $controllerId, ?string $actionId = null, ?string $verb = null, ?string $moduleId = null):bool {
		if (null !== $moduleId && $moduleId === Yii::$app->id) $moduleId = null;//защита на случай, если забыли проверить выше
		if ($this->isAllPermissionsGranted()) return true;
		$cacheKey = CacheHelper::MethodSignature(__METHOD__, [
			'id' => $this->id,
			'module' => $moduleId,
			'controller' => $controllerId,
			'action' => $actionId,
			'verb' => $verb
		]);
		return Yii::$app->cache->getOrSet($cacheKey, fn() => [] !== array_merge(Permissions::allUserPermissions($this->id, [
				'module' => $moduleId,
				'controller' => $controllerId,
				'action' => $actionId,
				'verb' => $verb
			]), Permissions::allUserConfigurationPermissions($this->id)), null, new TagDependency([
			'tags' => [
				CacheHelper::MethodSignature('Users::allPermissions', ['id' => $this->id]),//сброс кеша при изменении прав пользователя
			]
		]));
	}

	/**
	 * Проверяет перегрузку доступов через конфиг
	 * @return bool
	 * @throws Throwable
	 */
	public function isAllPermissionsGranted():bool {
		return in_array($this->id, PermissionsModule::param(PermissionsModule::GRANT_ALL, []), true);
	}

	/**
	 * Проверяет доступность url
	 * @param string $url
	 * @return bool
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function hasUrlPermission(string $url):bool {
		/** @var Controller $controller */
		[$controller, $actionId] = Yii::$app->createController($url);
		if (null === $controller) return false;//url не принадлежит приложению, мы не можем его проверить, и по нашей логике "запрещено всё, что не разрешено", в доступе отказывается.
		if (false === $actionId = strtok($actionId, '?')) $actionId = null;//strip GET vars from action id
		return $this->hasControllerPermission($controller->id, $actionId, null, $controller->module->id);
	}

	/**
	 * Если мы попытаемся инвалидировать тег для нового пользователя, то обломимся: id будет null, тег не будет корректен.
	 * В этом случае мы отложим сброс кеша до сохранения.
	 * @param string $methodSignature
	 * @throws Throwable
	 */
	protected function invalidateUserTag(string $methodSignature):void {
		if ($this->isNewRecord) {
			$this->on(ActiveRecord::EVENT_AFTER_INSERT, function($event) {//отложим сброс кеша до сохранения
				$this->invalidateUserTag($event->data[0]);
			}, [$methodSignature]);
			return;
		}
		TagDependency::invalidate(Yii::$app->cache, [CacheHelper::MethodSignature($methodSignature, ['id' => $this->id])]);
	}

}