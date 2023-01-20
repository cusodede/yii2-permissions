<?php
declare(strict_types = 1);

namespace cusodede\permissions\generators\migration;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use cusodede\permissions\traits\UsersPermissionsTrait;
use yii\gii\CodeFile;
use yii\gii\Generator as YiiGenerator;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * Generates a migration which stores current permission data
 * @property bool $includePermissions
 * @property bool $includePermissionsCollections
 * @property bool $includeRelationsToUserAccounts
 * @property string $savePath
 *
 */
class Generator extends YiiGenerator {
	protected bool $_includePermissions = true;
	protected bool $_includePermissionsCollections = true;
	protected bool $_includeRelationsToUserAccounts = true;
	protected string $_savePath = '@app/migrations';

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return [
			[['includePermissions', 'includePermissionsCollections', 'includeRelationsToUserAccounts'], 'boolean'],
			[['savePath'], 'string']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'includePermissions' => 'Create atomic permissions migration',
			'includePermissionsCollections' => 'Create permissions collections migration',
			'includeRelationsToUserAccounts' => 'Create migration with relations to users accounts',
			'savePath' => 'Where to save generated files'
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getName():string {
		return 'Permissions migration generator';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription():string {
		return 'This generator allows you to store system permissions as migration files';
	}

	/**
	 * @inheritDoc
	 */
	public function generate():array {
		$files = [];

		if ($this->includePermissions) {
			$className = $this->getMigrationFileName('_permissions');
			$permissionsData = ArrayHelper::getColumn(Permissions::find()->all(), 'attributes');
			array_walk($permissionsData, static function(&$a, $k) {
				unset($a['id']);
			});
			$columnsData = array_keys(ArrayHelper::getValue($permissionsData, 0, []));
			$permissions = static::array2php($permissionsData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT);
			$columns = static::array2php($columnsData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT);
			$files[] = new CodeFile(
				FileHelper::normalizePath(sprintf("%s%s%s.php", $this->savePath, DIRECTORY_SEPARATOR, $className)),
				$this->render('permissions_migration.php', compact('className', 'columns', 'permissions')),
				['path' => $this->savePath]
			);
		}

		if ($this->includePermissionsCollections) {
			$className = $this->getMigrationFileName('_permissions_collections');
			$permissionsData = ArrayHelper::getColumn(PermissionsCollections::find()->all(), 'attributes');
			array_walk($permissionsData, static function(&$a, $k) {
				unset($a['id']);
			});
			$columnsData = array_keys(ArrayHelper::getValue($permissionsData, 0, []));
			$permissions_collections = static::array2php($permissionsData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT);
			$columns = static::array2php($columnsData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT);
			$files[] = new CodeFile(
				FileHelper::normalizePath(sprintf("%s%s%s.php", $this->savePath, DIRECTORY_SEPARATOR, $className)),
				$this->render('permissions_collections_migration.php', compact('className', 'columns', 'permissions_collections'))
			);

			$className = $this->getMigrationFileName('_permissions_collections_to_collections');
			$codeLines = [];
			/** @var PermissionsCollections $permissionCollection */
			foreach (PermissionsCollections::find()->all() as $permissionCollection) {
				if ([] === $names = ArrayHelper::getColumn($permissionCollection->relatedSlavePermissionsCollections, 'name')) continue;
				$names = static::array2php($names);
				$codeLines[] = implode("\n\t\t", [
					"\$collection = PermissionsCollections::find()->where(['name' => '{$permissionCollection->name}'])->one();",
					"\$collection->relatedSlavePermissionsCollections = PermissionsCollections::find()->where(['name' => {$names}])->all();",
					"\$collection->save();"
				]);
			}

			$files[] = new CodeFile(
				FileHelper::normalizePath(sprintf("%s%s%s.php", $this->savePath, DIRECTORY_SEPARATOR, $className)),
				$this->render('permissions_collections_to_permissions_collections_migration.php', [
					'className' => $className,
					'code' => implode("\n\t\t", $codeLines)
				])
			);

			if ($this->includePermissions) {
				$className = $this->getMigrationFileName('_permissions_collections_to_permissions');
				$codeLines = [];
				/** @var PermissionsCollections $permissionCollection */
				foreach (PermissionsCollections::find()->all() as $permissionCollection) {
					if ([] === $names = ArrayHelper::getColumn($permissionCollection->relatedPermissions, 'name')) continue;
					$names = static::array2php($names);
					$codeLines[] = implode("\n\t\t", [
						"\$collection = PermissionsCollections::find()->where(['name' => '{$permissionCollection->name}'])->one();",
						"\$collection->relatedPermissions = Permissions::find()->where(['name' => {$names}])->all();",
						"\$collection->save();"
					]);
				}

				$files[] = new CodeFile(
					FileHelper::normalizePath(sprintf("%s%s%s.php", $this->savePath, DIRECTORY_SEPARATOR, $className)),
					$this->render('permissions_collections_to_permissions_migration.php', [
						'className' => $className,
						'code' => implode("\n\t\t", $codeLines)
					])
				);
			}
		}

		if ($this->includeRelationsToUserAccounts) {
			$className = $this->getMigrationFileName('_users_to_permissions');
			$codeLines = [];
			/** @var UsersPermissionsTrait $user */
			foreach (PermissionsModule::UserIdentityClass()::find()->all() as $user) {
				if ([] === $names = ArrayHelper::getColumn($user->relatedPermissions, 'name')) continue;
				$names = static::array2php($names);
				$codeLines[] = implode("\n\t\t", [
					"\$user = PermissionsModule::UserIdentityClass()::find()->where(['id' => '{$user->id}'])->one();",
					"\$user->relatedPermissions = Permissions::find()->where(['name' => {$names}])->all();",
					"\$user->save();"
				]);
			}

			$files[] = new CodeFile(
				$className.".php",
				$this->render('users_to_permissions_migration.php', [
					'className' => $className,
					'code' => implode("\n\t\t", $codeLines)
				])
			);

			$className = $this->getMigrationFileName('_users_to_permissions_collections');
			$codeLines = [];
			/** @var UsersPermissionsTrait $user */
			foreach (PermissionsModule::UserIdentityClass()::find()->all() as $user) {
				if ([] === $names = ArrayHelper::getColumn($user->relatedPermissionsCollections, 'name')) continue;
				$names = static::array2php($names);
				$codeLines[] = implode("\n\t\t", [
					"\$user = PermissionsModule::UserIdentityClass()::find()->where(['id' => '{$user->id}'])->one();",
					"\$user->relatedPermissionsCollections = PermissionsCollections::find()->where(['name' => {$names}])->all();",
					"\$user->save();"
				]);
			}

			$files[] = new CodeFile(
				$className.".php",
				$this->render('users_to_permissions_collections_migration.php', [
					'className' => $className,
					'code' => implode("\n\t\t", $codeLines)
				])
			);
		}

		return $files;
	}

	/**
	 * @return string the migration file path
	 */
	public function getMigrationFileName(string $postfix):string {
		return sprintf("m%s%s", date('ymd_000000'), $postfix);//Gii doesn't allow to use high accurate timestamps in filenames, because file ids generated from them
	}

	/**
	 * @param array $data
	 * @param int $params
	 * @return string
	 */
	private static function array2php(array $data, int $params = JSON_UNESCAPED_UNICODE):string {
		return str_replace(['{', '}', '": ', '    '], ['[', ']', '" => ', "\t"], json_encode($data, $params));
	}

	/**
	 * @return bool
	 */
	public function getIncludePermissions():bool {
		return $this->_includePermissions;
	}

	/**
	 * @param bool $includePermissions
	 */
	public function setIncludePermissions(bool $includePermissions):void {
		$this->_includePermissions = $includePermissions;
	}

	/**
	 * @return bool
	 */
	public function getIncludePermissionsCollections():bool {
		return $this->_includePermissionsCollections;
	}

	/**
	 * @param bool $includePermissionsCollections
	 */
	public function setIncludePermissionsCollections(bool $includePermissionsCollections):void {
		$this->_includePermissionsCollections = $includePermissionsCollections;
	}

	/**
	 * @return bool
	 */
	public function getIncludeRelationsToUserAccounts():bool {
		return $this->_includeRelationsToUserAccounts;
	}

	/**
	 * @param bool $includeRelationsToUserAccounts
	 */
	public function setIncludeRelationsToUserAccounts(bool $includeRelationsToUserAccounts):void {
		$this->_includeRelationsToUserAccounts = $includeRelationsToUserAccounts;
	}

	/**
	 * @return string
	 */
	public function getSavePath():string {
		return $this->_savePath;
	}

	/**
	 * @param string $savePath
	 */
	public function setSavePath(string $savePath):void {
		$this->_savePath = $savePath;
	}


}