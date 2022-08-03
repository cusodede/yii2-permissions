<?php
declare(strict_types = 1);

namespace cusodede\permissions\models\active_record\relations;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\PermissionsModule;
use pozitronik\relations\traits\RelationsTrait;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "sys_relation_users_to_permissions".
 *
 * @property int $id
 * @property int $user_id Ключ объекта разрешения
 * @property int $permission_id Ключ разрешения
 *
 * @property null|IdentityInterface $relatedUsers Связанная модель пользователя
 * @property null|Permissions $relatedPermissions Связанное разрешение
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
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function getRelatedUsers():ActiveQuery {
		return $this->hasOne(PermissionsModule::UserIdentityClass(), ['id' => 'user_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissions():ActiveQuery {
		return $this->hasOne(Permissions::class, ['id' => 'permission_id']);
	}
}
