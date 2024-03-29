<?php
declare(strict_types = 1);

namespace cusodede\permissions\models;

use cusodede\permissions\models\active_record\PermissionsCollectionsAR;
use cusodede\permissions\models\active_record\relations\RelPermissionsCollectionsToPermissionsCollections;
use cusodede\permissions\models\active_record\relations\RelUsersToPermissionsCollections;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
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
				TagDependency::invalidate(PermissionsModule::Cache(), [CacheHelper::MethodSignature('Users::allPermissions', ['id' => $userId])]);
			}
		}
		parent::afterSave($insert, $changedAttributes);
		$this->refresh();
	}

	/**
	 * Удаляем связи перед удалением записи
	 * @inheritDoc
	 */
	public function delete():false|int {
		RelPermissionsCollectionsToPermissionsCollections::deleteAll(['slave_id' => $this->id]);
		RelUsersToPermissionsCollections::deleteAll(['collection_id' => $this->id]);
		return parent::delete();
	}
}