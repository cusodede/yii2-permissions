<?php
declare(strict_types = 1);

namespace cusodede\permissions\traits;

use Throwable;
use yii\db\ActiveQueryInterface;
use yii\web\IdentityInterface;

/**
 * Trait ActiveRecordPermissionsTrait
 * Управление областями видимости в ActiveRecord
 * @method static tableName()
 */
trait ActiveRecordPermissionsTrait {

	/**
	 * Интерфейс функции установки области доступа пользователя в этой таблице
	 * @param ActiveQueryInterface $query
	 * @param IdentityInterface $user
	 * @return mixed
	 * @throws Throwable
	 * @see ActiveQueryPermissionsTrait::scope()
	 */
	public static function scope(ActiveQueryInterface $query, IdentityInterface $user):ActiveQueryInterface {
		/** @var IdentityInterface|UsersPermissionsTrait $user */
		return ($user->isAllPermissionsGranted())
			?$query
			:$query->where([static::tableName().'.id' => '0']);
	}

}