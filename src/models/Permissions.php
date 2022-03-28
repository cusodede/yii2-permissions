<?php
declare(strict_types = 1);

namespace cusodede\permissions\models;

use cusodede\permissions\models\active_record\PermissionsAR;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\CacheHelper;
use Throwable;
use Yii;
use yii\caching\TagDependency;

/**
 * Class Permissions
 * todo:
 * 4) Флаг deleted
 *
 * @property string $controllerPath "Виртуальный" путь к контроллеру, учитывающий, при необходимости, модуль.
 * @see Permissions::setControllerPath()
 * @see Permissions::getControllerPath()
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
				'controllerPath' => 'Контроллер'
			];
	}

	/**
	 * Вернуть список преднастроенных правил из конфига
	 * @return self[]
	 * @throws Throwable
	 */
	public static function GetConfigurationPermissions(?array $filter = null):array {
		$permissionsConfig = PermissionsModule::param(self::CONFIGURATION_PERMISSIONS, []);
		if (null !== $filter) $permissionsConfig = ArrayHelper::filter($permissionsConfig, $filter);
		$result = [];
		/*convert to models*/
		foreach ($permissionsConfig as $name => $permissionConfig) {
			$permissionConfig['name'] = $name;
			$result[] = array_filter((new self($permissionConfig))->attributes);
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
			/*Для перечисленных параметров пустое значение приравнивается к любому*/
			if (!(null === $paramValue && in_array($paramName, self::ALLOWED_EMPTY_PARAMS, true))) {
				$query->andWhere(["q.$paramName" => [$paramValue]]);
			}
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
	}

	/**
	 * @return string|null
	 */
	public function getControllerPath():?string {
		return (null === $this->module)?$this->controller:"@{$this->module}/{$this->controller}";
	}

	/**
	 * @param null|string $controllerPath
	 */
	public function setControllerPath(?string $controllerPath):void {
		$this->module = null;
		$this->controller = $controllerPath;/*by default*/
		/*Если контроллер пришёл в виде @foo/bar - foo указывает на модуль*/
		if ((!empty($path = explode('/', $this->controller))) && (false !== $matches = preg_grep('/^@(\w+)/', $path)) && 1 === count($matches)) {
			/** @var array $matches */
			$this->module = substr($matches[0], 1);
			$this->controller = substr($this->controller, strlen($this->module) + 2); //@foo/bar => bar
		}
	}
}