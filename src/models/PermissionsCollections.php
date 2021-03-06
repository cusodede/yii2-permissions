<?php
declare(strict_types = 1);

namespace cusodede\permissions\models;

use cusodede\permissions\models\active_record\PermissionsCollectionsAR;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use Yii;
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
}