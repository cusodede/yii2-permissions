<?php
declare(strict_types = 1);

namespace cusodede\permissions\filters;

use cusodede\permissions\PermissionsModule;
use Throwable;
use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

/**
 * Class PermissionFilter
 * @property callable $denyCallback;
 */
class PermissionFilter extends ActionFilter {
	/**
	 * @var callable a callback that will be called if the access should be denied
	 * to the current user. This is the case when either no rule matches, or a rule with
	 * [[AccessRule::$allow|$allow]] set to `false` matches.
	 * If not set, [[denyAccess()]] will be called.
	 *
	 * The signature of the callback should be as follows:
	 *
	 * ```php
	 * function ($user, $action)
	 * ```
	 *
	 * where `$user` is the current user model, and `$action` is the current [[Action|action]] object.
	 */
	public $denyCallback;

	/**
	 * @param Action $action
	 * @return bool
	 * @throws Throwable
	 */
	public function beforeAction($action):bool {
		$user = PermissionsModule::UserCurrentIdentity();
		if (true === $user->hasActionPermission($action)) return true;

		if (null !== $this->denyCallback) {
			call_user_func($this->denyCallback, $user, $action);
		} else {
			$this->denyAccess();
		}
		return false;
	}

	/**
	 * @throws ForbiddenHttpException if the user is already logged in or in case of detached User component.
	 */
	protected function denyAccess():void {
		throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
	}

}