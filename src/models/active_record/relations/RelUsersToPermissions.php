<?php
declare(strict_types = 1);

namespace cusodede\permissions\models\active_record\relations;

use cusodede\permissions\models\Permissions;
use pozitronik\relations\traits\RelationsTrait;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "sys_relation_users_to_permissions".
 *
 * @property int $id
 * @property int $user_id Ключ объекта доступа
 * @property int $permission_id Ключ правила доступа
 *
 * @property null|Users $relatedUsers Связанная модель пользователя
 * @property null|Permissions $relatedPermissions Связанное право доступа
 */
class RelUsersToPermissions extends ActiveRecord {
	use RelationsTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return 'sys_relation_users_to_permissions';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['user_id', 'permission_id'], 'required'],
			[['user_id', 'permission_id'], 'integer'],
			[['user_id', 'permission_id'], 'unique', 'targetAttribute' => ['user_id', 'permission_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'user_id' => 'User ID',
			'permission_id' => 'Permission ID',
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedUsers():ActiveQuery {
		return $this->hasOne(Users::class, ['id' => 'user_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissions():ActiveQuery {
		return $this->hasOne(Permissions::class, ['id' => 'permission_id']);
	}
}
