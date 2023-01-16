<?php
declare(strict_types = 1);

namespace cusodede\permissions\generators\migration;

use cusodede\permissions\models\Permissions;
use Yii;
use yii\gii\CodeFile;
use yii\gii\Generator as YiiGenerator;

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
			$files[] = new CodeFile(
				$className,
				$this->render('permissions_migration.php', [
					'className' => $className,
					'permissions' => Permissions::find()->all()
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