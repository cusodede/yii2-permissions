<?php
declare(strict_types = 1);

namespace cusodede\permissions;

use cusodede\permissions\traits\UsersPermissionsTrait;
use pozitronik\traits\traits\ModuleTrait;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\db\ActiveRecordInterface;
use yii\web\IdentityInterface;

/**
 * Class PermissionsModule
 */
class PermissionsModule extends Module {
	use ModuleTrait;

	private static ?string $_userIdentityClass = null;
	private static ?string $_userCurrentIdentity = null;

	public const VERBS = [
		'GET' => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'PATCH' => 'PATCH',
		'DELETE' => 'DELETE'
	];

	/**
	 * @return string|ActiveRecordInterface
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public static function UserIdentityClass():string|ActiveRecordInterface {
		if (null === static::$_userCurrentIdentity) {
			$identity = static::param('userIdentityClass', Yii::$app->user->identityClass);
			static::$_userCurrentIdentity = (is_callable($identity))
				?$identity()
				:$identity;
		}
		return static::$_userCurrentIdentity;
	}

	/**
	 * @return IdentityInterface|UsersPermissionsTrait
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	public static function UserCurrentIdentity():IdentityInterface {
		if (null === static::$_userIdentityClass) {
			$identity = static::param('userCurrentIdentity', Yii::$app->user->identity);
			static::$_userIdentityClass = (is_callable($identity))
				?$identity()
				:$identity;
		}
		return static::$_userIdentityClass;
	}

	/**
	 * @param mixed $id
	 * @return IdentityInterface|null|UsersPermissionsTrait
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpDocSignatureInspection
	 */
	public static function FindIdentityById(mixed $id):?IdentityInterface {
		return (null === $id)
			?static::UserCurrentIdentity()
			:static::UserIdentityClass()::findOne($id);
	}
}
