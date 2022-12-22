<?php
declare(strict_types = 1);

namespace cusodede\permissions\models;

use cusodede\permissions\helpers\CommonHelper;
use cusodede\permissions\models\active_record\PermissionsAR;
use cusodede\permissions\models\active_record\relations\RelPermissionsCollectionsToPermissions;
use cusodede\permissions\models\active_record\relations\RelUsersToPermissions;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

/**
 * Class Permissions
 * todo:
 * 4) Флаг deleted
 *
 * @property string $controllerPath "Виртуальный" путь к контроллеру, учитывающий, при необходимости, модуль.
 * @see Permissions::setControllerPath()
 * @see Permissions::getControllerPath()
 *
 * @property-read int $warningFlags Флаги возможных проблем доступа, см WARN_*
 */
class Permissions extends PermissionsAR {
	/*Любое из перечисленных прав*/
	public const LOGIC_OR = 0;
	/*Все перечисленные права*/
	public const LOGIC_AND = 1;
	/*Ни одно из перечисленных прав*/
	public const LOGIC_NOT = 2;

	/*Минимальный/максимальный приоритет*/
	public const PRIORITY_MIN = 0;
	public const PRIORITY_MAX = 100;

	/*Параметры разрешения, для которых пустой фильтр приравнивается к любому значению*/
	public const ALLOWED_EMPTY_PARAMS = ['action', 'verb'];

	public const GRANT_ALL = 'grantAll';
	public const CONTROLLER_DIRS = 'controllerDirs';
	/*Название параметра с преднастроенными правилами доступов*/
	public const CONFIGURATION_PERMISSIONS = 'permissions';
	/*Перечисление назначений конфигураций через конфиги, id => ['...', '...']*/
	public const GRANT_PERMISSIONS = 'grant';

	/*Флаги возможных проблем доступа */
	public const WARN_NO_PATH = 0x1;//пермиссия отвечает за доступ к несуществующему контроллеру
	public const WARN_NOT_USED = 0x2;//пермиссия не используется (ни напрямую, ни на коллекцию)

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return array_merge(parent::rules(), [[['controllerPath'], 'string']]);
	}

	/**
	 * @inheritDoc
	 */
	public function attributeLabels():array {
		return parent::attributeLabels() + [
				'controllerPath' => 'Маршрут контроллера',
				'warnFlags' => 'Проблемы'
			];
	}

	/**
	 * Вернуть список преднастроенных правил из конфига
	 * @param array|null $filter
	 * @return self[]
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public static function GetConfigurationPermissions(?array $filter = null):array {
		$permissionsConfig = PermissionsModule::param(self::CONFIGURATION_PERMISSIONS, []);
		if (null !== $filter) $permissionsConfig = ArrayHelper::filter($permissionsConfig, $filter);
		return static::GetPermissionsFromArray($permissionsConfig);

	}

	/**
	 * @param string[][] $permissionsArray
	 * @return self[]
	 */
	public static function GetPermissionsFromArray(array $permissionsArray):array {
		$result = [];
		foreach ($permissionsArray as $name => $permissionConfig) {
			$permissionConfig['name'] = $name;
			$result[] = new static($permissionConfig);
		}
		return $result;
	}

	/**
	 * Все доступы пользователя из БД
	 * @param int $user_id
	 * @param string[] $permissionFilters
	 * @param bool $asArray
	 * @return self[]
	 */
	public static function allUserPermissions(int $user_id, array $permissionFilters = [], bool $asArray = true):array {
		$mainQuery = self::find()
			->alias('perms')
			->innerJoinWith('relatedPermissionsCollectionsToPermissions cols_to_perms')
			->innerJoin('recursive_collections', 'recursive_collections.id = cols_to_perms.collection_id')
			->withQuery(
			//initial query
				PermissionsCollections::find()
					->alias('cols')
					->joinWith('relatedUsersToPermissionsCollections users_to_cols')
					->where(['users_to_cols.user_id' => $user_id])
					->orWhere(['cols.default' => true])/*всегда добавляем права из коллекций с галкой default*/
					->union(
					//recursive query
						PermissionsCollections::find()
							->alias('cols')
							->innerJoinWith('relatedMasterPermissionsCollectionsToPermissionsCollections cols_to_cols')
							->innerJoin('recursive_collections', 'recursive_collections.id = cols_to_cols.master_id')
					),
				'recursive_collections',
				true
			)
			->union(
			//direct permissions
				self::find()
					->alias('perms')
					->joinWith('relatedUsersToPermissions users_to_perms')
					->where(['users_to_perms.user_id' => $user_id])
			);

		$query = self::find()->from(['q' => $mainQuery])->orderBy(['priority' => SORT_DESC, 'id' => SORT_ASC]);

		foreach ($permissionFilters as $paramName => $paramValue) {
			$paramValues = [$paramValue];
			/* Для перечисленных параметров пустое значение в БД приравнивается к любому, например verb = null => доступ с любым verb */
			if (in_array($paramName, self::ALLOWED_EMPTY_PARAMS, true)) {
				$paramValues[] = null;
			}
			$query->andWhere(["q.$paramName" => $paramValues]);
		}
		return $query->asArray($asArray)->all();
	}

	/**
	 * Все доступы пользователя из конфига (без фильтрации, просто всё, что назначено)
	 * @param int $user_id
	 * @return self[]
	 * @throws Throwable
	 * @throws Throwable
	 */
	public static function allUserConfigurationPermissions(int $user_id /*, array $permissionFilters = [], bool $asArray = true*/ /*todo*/):array {
		/** @var array $userConfigurationGrantedPermissions */
		$userConfigurationGrantedPermissions = ArrayHelper::getValue(PermissionsModule::param(self::GRANT_PERMISSIONS, []), $user_id, []);
		return self::GetConfigurationPermissions($userConfigurationGrantedPermissions);
	}

	/**
	 * При изменении права, нужно удалить кеши прав всем пользователям, у которых:
	 *    - право назначено напрямую
	 *    - право есть в  группе прав, назначенной пользователю
	 * @inheritDoc
	 */
	public function afterSave($insert, $changedAttributes):void {
		if (false === $insert && [] !== $changedAttributes) {
			$usersIds = array_unique(array_merge(
				ArrayHelper::getColumn($this->relatedUsers, 'id'),
				ArrayHelper::getColumn($this->relatedUsersViaPermissionsCollections, 'id')
			));

			foreach ($usersIds as $userId) {
				TagDependency::invalidate(Yii::$app->cache, [CacheHelper::MethodSignature('Users::allPermissions', ['id' => $userId])]);
			}
		}
		parent::afterSave($insert, $changedAttributes);
		$this->refresh();
	}

	/**
	 * @return string|null
	 */
	public function getControllerPath():?string {
		return (null === $this->module)?$this->controller:"{$this->module}/{$this->controller}";
	}

	/**
	 * @param null|string $controllerPath
	 */
	public function setControllerPath(?string $controllerPath):void {
		$this->module = null;
		$this->controller = $controllerPath;/*by default*/
		/*Если контроллер пришёл в виде foo/bar или @foo/bar - foo указывает на модуль*/
		if ((!empty($path = explode('/', $this->controller))) && 2 === count($path)) {
			/** @var array $matches */
			$this->module = $path[0];
			$this->module = '@' === $this->module[0]?substr($this->module, 1):$this->module;//strip @ if presents
			$this->controller = $path[1];
		}
	}

	/**
	 * Удаляем связи перед удалением записи
	 * @inheritDoc
	 */
	public function delete():false|int {
		RelPermissionsCollectionsToPermissions::deleteAll(['permission_id' => $this->id]);
		RelUsersToPermissions::deleteAll(['permission_id' => $this->id]);
		return parent::delete();
	}

	/**
	 * @param int $flags Optional: what flags require to check
	 * @return int
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public function getWarningFlags(int $flags = self::WARN_NO_PATH + self::WARN_NOT_USED):int {
		$result = 0;
		/*check if it is a permission controller, and its path still actual*/
		if ($flags & static::WARN_NO_PATH && !empty($this->controller) && false === CommonHelper::IsControllerPathExists($this->module, $this->controller, $this->action)) $result += static::WARN_NO_PATH;
		if ($flags & static::WARN_NOT_USED && [] === $this->relatedUsers && [] === $this->relatedUsersViaPermissionsCollections) $result += static::WARN_NOT_USED;
		return $result;
	}
}