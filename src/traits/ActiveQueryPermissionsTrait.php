<?php
declare(strict_types = 1);

namespace cusodede\permissions\traits;

use cusodede\permissions\PermissionsModule;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\ActiveQueryInterface;
use yii\web\IdentityInterface;

/**
 * Trait ActiveQueryPermissionsTrait
 * Управление областями видимости в ActiveQuery
 */
trait ActiveQueryPermissionsTrait {
	/**
	 * Возвращает область видимости пользователя $user для модели $modelClass (если та реализует метод self::scope);
	 * @param object|string|null $modelObjectOrClass
	 * @param ?IdentityInterface|UsersPermissionsTrait $user
	 * @return ActiveQueryPermissionsTrait
	 * @throws Throwable
	 * @throws InvalidConfigException
	 * @noinspection PhpDocSignatureInspection
	 */
	public function scope(object|string $modelObjectOrClass = null, ?IdentityInterface $user = null):self {
		//todo if (Options::getValue(Options::SCOPE_IGNORE_ENABLE)) return $this;

		/** @var ActiveQueryInterface $this */
		$modelObjectOrClass = $modelObjectOrClass??$this->modelClass;
		if (method_exists($modelObjectOrClass, 'scope')) {
			$user = $user??PermissionsModule::UserCurrentIdentity();
			if (null === $user) return $this;
			/** @var ActiveRecordPermissionsTrait $modelObjectOrClass */
			return ($modelObjectOrClass::scope($this, $user));
		}

		return $this;
	}

}