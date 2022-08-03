<?php
declare(strict_types = 1);

namespace cusodede\permissions\models\active_record\relations;

use cusodede\permissions\models\PermissionsCollections;
use pozitronik\relations\traits\RelationsTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Коллекции доступов, включённые в другие коллекции доступов.
 *
 * @property int $id
 * @property int $master_id Ключ базовой коллекции доступа
 * @property int $slave_id Ключ включаемой коллекции доступа
 *
 * @property null|PermissionsCollections $relatedMasterPermissionsCollections Связанная базовая коллекции доступов
 * @property null|PermissionsCollections $relatedSlavePermissionsCollections Связанная включённая коллекции доступов
 */
class RelPermissionsCollectionsToPermissionsCollections extends ActiveRecord {
	use RelationsTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return 'sys_relation_permissions_collections_to_permissions_collections';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['master_id', 'slave_id'], 'required'],
			[['master_id', 'slave_id'], 'integer'],
			[['master_id', 'slave_id'], 'unique', 'targetAttribute' => ['master_id', 'slave_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'master_id' => 'Master collection ID',
			'slave_id' => 'Slave collections ID',
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedMasterPermissionsCollections():ActiveQuery {
		return $this->hasOne(PermissionsCollections::class, ['id' => 'master_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedSlavePermissionsCollections():ActiveQuery {
		return $this->hasOne(PermissionsCollections::class, ['id' => 'slave_id']);
	}

}
