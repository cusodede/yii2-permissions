<?php
declare(strict_types = 1);

namespace cusodede\permissions\generators\migration;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use yii\db\Query;
use yii\gii\CodeFile;
use yii\gii\Generator as YiiGenerator;
use yii\helpers\ArrayHelper;

/**
 * Generates a migration which stores current permission data
 */
class Generator extends YiiGenerator {

	public bool $includePermissions = true;
	public bool $includePermissionsCollections = true;
	public bool $includeUserAccounts = true;

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'includePermissions' => 'Include atomic permissions in migration',
			'includePermissionsCollections' => 'Include permissions collections in migration',
			'includeUserAccounts' => 'Include users accounts in migration'
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

			if ($this->includePermissions) {
				$className = $this->getMigrationFileName('_permissions_collections_to_permissions');
				$relationInsertData = [];
				/** @var PermissionsCollections $permissionCollection */
				foreach (PermissionsCollections::find()->all() as $permissionCollection) {
					$names = ArrayHelper::getColumn($permissionCollection->relatedPermissions, 'name');
					$relationInsertData[] = [
						$permissionCollection->name => $names
					];
				}
				array_walk($relationInsertData, static function(&$a, $k) {
					unset($a['id']);
				});
				$permissions_collections_to_permissions = str_replace(['{', '}'], ['[', ']'], json_encode($relationInsertData, JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT));
				$files[] = new CodeFile(
					$className,
					$this->render('permissions_collections_to_permissions_migration.php', compact('className', 'permissions_collections_to_permissions'))
				);
			}
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