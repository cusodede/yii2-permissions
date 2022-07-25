<?php
declare(strict_types = 1);

namespace cusodede\permissions\models;

use cusodede\permissions\models\active_record\PermissionsCollectionsAR;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

/**
 * Class PermissionsCollections
 */
class PermissionsCollections extends PermissionsCollectionsAR {
	/**
	 * При изменении группы, нужно удалить кеши прав всем пользователям, у которых:
	 *    - право есть в группе прав, назначенной пользователю
	 * @inheritDoc
	 */
	public function afterSave($insert, $changedAttributes):void {
		if (false === $insert) {
			$usersInGroup = ArrayHelper::getColumn($this->relatedUsersRecursively, 'id');
			foreach ($usersInGroup as $userId) {
				TagDependency::invalidate(Yii::$app->cache, [CacheHelper::MethodSignature('Users::allPermissions', ['id' => $userId])]);
			}
		}
		parent::afterSave($insert, $changedAttributes);
	}

	/**
	 * Вернуть список коллекций из конфига
	 * @param array|null $filter
	 * @return self[]
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public static function GetConfigurationPermissionsCollections(?array $filter = null):array {
		$collectionsConfig = PermissionsModule::param(PermissionsModule::CONFIGURATION_PERMISSIONS_COLLECTIONS, []);
		if (null !== $filter) $collectionsConfig = ArrayHelper::filter($collectionsConfig, $filter);
		return static::GetPermissionsCollectionsFromArray($collectionsConfig);
	}

	/**
	 * Из конфигурации коллекций создаёт коллекции.
	 * Если доступы, указанные в конфигурации, не существуют, то они будут созданы.
	 * @param string[][] $collectionsArray
	 * @return self[]
	 * @throws Throwable
	 */
	protected static function GetPermissionsCollectionsFromArray(array $collectionsArray):array {
		$result = [];
		foreach ($collectionsArray as $name => $collectionConfig) {
			$collection = new static(['name' => $name]);
			$collection->loadArray(array_intersect_key($collectionConfig, $collection->attributes));
			$permissions = [];
			foreach (ArrayHelper::getValue($collectionConfig, PermissionsModule::CONFIGURATION_PERMISSIONS) as $permissionName) {
				$permissions[] = Permissions::Upsert(['name' => $permissionName]);
			}
			$collection->relatedPermissions = $permissions;
			$result[] = $collection;
		}
		return $result;
	}
}