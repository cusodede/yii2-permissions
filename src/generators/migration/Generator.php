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

/**
 * Generates a migration which stores current permission data
 */
class Generator extends YiiGenerator {

	public bool $includePermissions = true;
	public bool $includePermissionsCollections = true;
	public bool $includeRelationsToUserAccounts = true;

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'includePermissions' => 'Create atomic permissions migration',
			'includePermissionsCollections' => 'Create permissions collections migration',
			'includeUserAccounts' => 'Create migration with relations to users accounts'
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
		return 'This generator helps you to quickly store system permissions as migration file';
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
			$permissions = str_replace(['{', '}'], ['[', ']'], json_encode($permissionsData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT));
			$files[] = new CodeFile(
				$className,
				$this->render('permissions_migration.php', compact('className', 'permissions'))
			);
		}

		if ($this->includePermissionsCollections) {
			$className = $this->getMigrationFileName('_permissions_collections');
			$permissionsData = ArrayHelper::getColumn(PermissionsCollections::find()->all(), 'attributes');
			array_walk($permissionsData, static function(&$a, $k) {
				unset($a['id']);
			});
			$permissions_collections = str_replace(['{', '}'], ['[', ']'], json_encode($permissionsData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT));
			$files[] = new CodeFile(
				$className,
				$this->render('permissions_collections_migration.php', compact('className', 'permissions_collections'))
			);

			$className = $this->getMigrationFileName('_permissions_collections_to_collections');
			$codeLines = [];
			/** @var PermissionsCollections $permissionCollection */
			foreach (PermissionsCollections::find()->all() as $permissionCollection) {
				if ([] === $names = ArrayHelper::getColumn($permissionCollection->relatedSlavePermissionsCollections, 'name')) continue;
				$names = str_replace(['{', '}'], ['[', ']'], json_encode($names, JSON_UNESCAPED_UNICODE));
				$codeLines[] = implode("\n\t\t", [
					"\$collection = PermissionsCollections::find()->where(['name' => '{$permissionCollection->name}'])->one();",
					"\$collection->relatedSlavePermissionsCollections = PermissionsCollections::find()->where(['name' => {$names}])->all();",
					"\$collection->save();"
				]);
			}

			$files[] = new CodeFile(
				$className,
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
					$names = str_replace(['{', '}'], ['[', ']'], json_encode($names, JSON_UNESCAPED_UNICODE));
					$codeLines[] = implode("\n\t\t", [
						"\$collection = PermissionsCollections::find()->where(['name' => '{$permissionCollection->name}'])->one();",
						"\$collection->relatedPermissions = Permissions::find()->where(['name' => {$names}])->all();",
						"\$collection->save();"
					]);
				}

				$files[] = new CodeFile(
					$className,
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
				$names = str_replace(['{', '}'], ['[', ']'], json_encode($names, JSON_UNESCAPED_UNICODE));
				$codeLines[] = implode("\n\t\t", [
					"\$user = PermissionsModule::UserIdentityClass()::find()->where(['id' => '{$user->id}'])->one();",
					"\$user->relatedPermissions = Permissions::find()->where(['name' => {$names}])->all();",
					"\$user->save();"
				]);
			}

			$files[] = new CodeFile(
				$className,
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
				$names = str_replace(['{', '}'], ['[', ']'], json_encode($names, JSON_UNESCAPED_UNICODE));
				$codeLines[] = implode("\n\t\t", [
					"\$user = PermissionsModule::UserIdentityClass()::find()->where(['id' => '{$user->id}'])->one();",
					"\$user->relatedPermissions = PermissionsCollections::find()->where(['name' => {$names}])->all();",
					"\$user->save();"
				]);
			}

			$files[] = new CodeFile(
				$className,
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
		return sprintf("%s%s.php", date('ymd_000000'), $postfix);//Gii doesn't allow to use high accurate timestamps in filenames, because file ids generated from them
	}
}