<?php
declare(strict_types = 1);

use yii\db\Migration;

/**
 * Class m000000_000000_sys_permissions
 */
class m000000_000000_sys_permissions extends Migration {
	private const SYS_PERMISSIONS_TABLE_NAME = 'sys_permissions';
	private const SYS_RELATION_USERS_TO_PERMISSIONS_TABLE_NAME = 'sys_relation_users_to_permissions';
	private const SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME = 'sys_permissions_collections';
	private const SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_TABLE_NAME = 'sys_relation_permissions_collections_to_permissions';
	private const SYS_RELATION_USERS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME = 'sys_relation_users_to_permissions_collections';
	private const SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME = 'sys_relation_permissions_collections_to_permissions_collections';

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::SYS_PERMISSIONS_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'name' => $this->string(128)->notNull()->comment('Название доступа'),
			'controller' => $this->string()->null()->comment('Контроллер, к которому устанавливается доступ, null для внутреннего доступа'),
			'action' => $this->string()->null()->comment('Действие, для которого устанавливается доступ, null для всех действий контроллера'),
			'verb' => $this->string()->null()->comment('REST-метод, для которого устанавливается доступ'),
			'module' => $this->string()->null()->comment('Модуль, для которого устанавливается доступ, null для дефолтного'),
			'comment' => $this->text()->null()->comment('Описание доступа'),
			'priority' => $this->integer()->notNull()->defaultValue(0)->comment('Приоритет использования (больше - выше)')
		]);

		$this->createIndex(self::SYS_PERMISSIONS_TABLE_NAME.'_controller_action_verb', self::SYS_PERMISSIONS_TABLE_NAME, ['controller', 'action', 'verb']);
		$this->createIndex(self::SYS_PERMISSIONS_TABLE_NAME.'_module', self::SYS_PERMISSIONS_TABLE_NAME, ['module']);/*Составного ключа не получится, вылезаем за размеры*/
		$this->createIndex(self::SYS_PERMISSIONS_TABLE_NAME.'_priority', self::SYS_PERMISSIONS_TABLE_NAME, ['priority']);
		$this->createIndex(self::SYS_PERMISSIONS_TABLE_NAME.'_name', self::SYS_PERMISSIONS_TABLE_NAME, ['name'], true);

		$this->createTable(self::SYS_RELATION_USERS_TO_PERMISSIONS_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'user_id' => $this->integer()->notNull()->comment('Ключ объекта доступа'),
			'permission_id' => $this->integer()->notNull()->comment('Ключ правила доступа'),
		]);

		$this->createIndex(self::SYS_RELATION_USERS_TO_PERMISSIONS_TABLE_NAME.'_user_id_permission_id', self::SYS_RELATION_USERS_TO_PERMISSIONS_TABLE_NAME, ['user_id', 'permission_id'], true);

		$this->createTable(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'name' => $this->string(128)->notNull()->comment('Название группы доступа'),
			'comment' => $this->text()->null()->comment('Описание группы доступа'),
			'default' => $this->boolean()->defaultValue(false)->notNull()->comment('Группа доступа по умолчанию')
		]);

		$this->createIndex(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME.'_name', self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME, ['name'], true);

		$this->createTable(self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'collection_id' => $this->integer()->notNull()->comment('Ключ группы доступа'),
			'permission_id' => $this->integer()->notNull()->comment('Ключ правила доступа'),
		]);

		$this->createIndex(self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_TABLE_NAME.'_collection_id_permission_id', self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_TABLE_NAME, ['collection_id', 'permission_id'], true);

		$this->createTable(self::SYS_RELATION_USERS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'user_id' => $this->integer()->notNull()->comment('Ключ объекта доступа'),
			'collection_id' => $this->integer()->notNull()->comment('Ключ группы доступа'),
		]);

		$this->createIndex(self::SYS_RELATION_USERS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME.'_user_id_collection_id', self::SYS_RELATION_USERS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME, ['user_id', 'collection_id'], true);

		$this->createTable(self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME, [
			'id' => $this->primaryKey(),
			'master_id' => $this->integer()->notNull()->comment('Первичная группа'),
			'slave_id' => $this->integer()->notNull()->comment('Вторичная группа'),
		]);

		$this->createIndex('idx_master_id_slave_id',//иначе слишком большой идентификатор
			self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME, ['master_id', 'slave_id'], true);

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::SYS_PERMISSIONS_TABLE_NAME);
		$this->dropTable(self::SYS_RELATION_USERS_TO_PERMISSIONS_TABLE_NAME);
		$this->dropTable(self::SYS_PERMISSIONS_COLLECTIONS_TABLE_NAME);
		$this->dropTable(self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_TABLE_NAME);
		$this->dropTable(self::SYS_RELATION_USERS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME);
		$this->dropTable(self::SYS_RELATION_PERMISSIONS_COLLECTIONS_TO_PERMISSIONS_COLLECTIONS_TABLE_NAME);
	}

}
