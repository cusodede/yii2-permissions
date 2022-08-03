<?php
declare(strict_types = 1);

namespace cusodede\permissions\models\active_record\relations;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use pozitronik\relations\traits\RelationsTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "sys_relation_permissions_collections_to_permissions".
 *
 * @property int $id
 * @property int $collection_id Ключ коллекции
 * @property int $permission_id Ключ разрешения
 *
 * @property null|PermissionsCollections $relatedPermissionsCollections Связанная коллекция
 * @property null|Permissions $relatedPermissions Связанное разрешение
 */
class RelPermissionsCollectionsToPermissions extends ActiveRecord {
	use RelationsTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return 'sys_relation_permissions_collections_to_permissions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['collection_id', 'permission_id'], 'required'],
			[['collection_id', 'permission_id'], 'integer'],
			[['collection_id', 'permission_id'], 'unique', 'targetAttribute' => ['collection_id', 'permission_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'collection_id' => 'Collection ID',
			'permission_id' => 'Permission ID',
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissionsCollections():ActiveQuery {
		return $this->hasOne(PermissionsCollections::class, ['id' => 'collection_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissions():ActiveQuery {
		return $this->hasOne(Permissions::class, ['id' => 'permission_id']);
	}
}
