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
 * @property RelUsersToPermissions[] $relatedUsersToPermissions Связь к промежуточной таблице пользовательских доступов
 * @property RelUsersToPermissionsCollections[] $relatedUsersToPermissionsCollections Связь к промежуточной таблице наборов пользовательских доступов
 * @property Permissions[] $relatedPermissions Назначенные напрямую доступы
 * @property PermissionsCollections[] $relatedPermissionsCollections Назначенные группы разрешений
 */
trait UsersPermissionsTrait {

	/**
	 * Проверяет, имеет ли пользователь указанный набор прав с указанной логикой проверки.
	 * Примеры:
	 * $user->hasPermission(['execute_order_66'])
	 * $user->hasPermission(['rule_galaxy', 'lose_arm'], Permissions::LOGIC_AND)
	 *
	 * @param string[] $permissions Названия прав, к которым проверяются доступы
	 * @param int $logic Логика проверки
	 * @return bool
	 * @throws Throwable
	 */
	public function hasPermission(array $permissions, int $logic = Permissions::LOGIC_OR):bool {
		$cacheKey = CacheHelper::MethodSignature(__METHOD__, func_get_args(), ['id' => $this->id]);
		return Yii::$app->cache->getOrSet($cacheKey, function() use ($permissions, $logic) {
			$result = false;
			$allUserPermissionsNames = ArrayHelper::getColumn(self::allPermissions(), 'name');
			foreach ($permissions as $current_permission_name) {
				if (false === $result = $this->isAllPermissionsGranted()) {
					$result = in_array(trim($current_permission_name), $allUserPermissionsNames, true);
				}

				switch ($logic) {
					case Permissions::LOGIC_OR:
						if ($result) return true; //при первом же найденном совпадении рапортуем о удаче
					break;
					case Permissions::LOGIC_AND:
						if (!$result) return false;//при первом же не найденном совпадении рапортуем о неудаче
					break;
					case Permissions::LOGIC_NOT:
						if ($result) return false;//при первом же найденном совпадении рапортуем о неудаче
					break;
				}
			}
			return ($logic === Permissions::LOGIC_NOT)?true:$result;
		}, null, new TagDependency(['tags' => [
			CacheHelper::MethodSignature('Users::allPermissions', ['id' => $this->id]),
			CacheHelper::MethodSignature('Users::hasPermission', ['id' => $this->id]),
		]]));//тег ставится на все варианты запроса ролей пользователя для сброса скопом

	}

	/**
	 * Все доступы пользователя, отсортированные по приоритету от большего к меньшему
	 * Учитываются доступы групп пользователя + прямые доступы, без разделения
	 * @param bool $force false (default): получить кешированный набор прав; true: получить актуальный набор прав с обновлением кеша
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
		/** @var ActiveRecord $this */
		return $this->hasMany(RelUsersToPermissions::class, ['user_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissions():ActiveQuery {
		/** @var ActiveRecord $this */
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
	 * @return ActiveQuery
	 */
	public function getRelatedUsersToPermissionsCollections():ActiveQuery {
		/** @var ActiveRecord $this */
		return $this->hasMany(RelUsersToPermissionsCollections::class, ['user_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissionsCollections():ActiveQuery {
		/** @var ActiveRecord $this */
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
		return Yii::$app->cache->getOrSet($cacheKey, function() use ($controllerId, $actionId, $verb, $moduleId) {
			return [] !== Permissions::allUserPermissions($this->id, [
					'module' => $moduleId,
					'controller' => $controllerId,
					'action' => $actionId,
					'verb' => $verb
				]) || [] !== Permissions::allUserConfigurationPermissions($this->id);
		}, null, new TagDependency([
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
		return in_array($this->id, PermissionsModule::param(Permissions::GRANT_ALL, []), true);
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
		/** @var ActiveRecord $this */
		if ($this->isNewRecord) {
			$this->on(ActiveRecord::EVENT_AFTER_INSERT, function($event) {//отложим сброс кеша до сохранения
				$this->invalidateUserTag($event->data[0]);
			}, [$methodSignature]);
			return;
		}
		TagDependency::invalidate(Yii::$app->cache, [CacheHelper::MethodSignature($methodSignature, ['id' => $this->id])]);
	}

}