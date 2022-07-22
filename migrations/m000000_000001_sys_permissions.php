<?php
declare(strict_types = 1);

use yii\db\Migration;

/**
 * Class m000000_000001_sys_permissions
 */
class m000000_000001_sys_permissions extends Migration {
	private const SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME = 'sys_permissions_collections';

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->addColumn(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME, 'priority', $this->integer()->defaultValue(0)->comment('Приоритет использования (больше - выше)'));
		$this->createIndex(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME.'_priority', self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME, ['priority']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropIndex(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME.'_priority', self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME);
		$this->dropColumn(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME, 'priority');
	}

}
