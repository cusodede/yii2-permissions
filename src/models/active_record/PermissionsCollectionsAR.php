<?php
declare(strict_types = 1);

namespace cusodede\permissions\models\active_record;

use cusodede\permissions\models\active_record\relations\RelPermissionsCollectionsToPermissions;
use cusodede\permissions\models\active_record\relations\RelPermissionsCollectionsToPermissionsCollections;
use cusodede\permissions\models\active_record\relations\RelUsersToPermissionsCollections;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\traits\traits\ActiveRecordTrait;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "sys_permissions_collections".
 *
 * @property int $id
 * @property string|null $name Название группы доступа
 * @property string|null $comment Описание группы доступа
 * @property bool $default Флаг использования группы по умолчанию
 *
 * @property RelPermissionsCollectionsToPermissions[] $relatedPermissionsCollectionsToPermissions Связь к промежуточной таблице к правам доступа
 * @property RelPermissionsCollectionsToPermissionsCollections[] $relatedPermissionsCollectionsToPermissionsCollections Связь к промежуточной таблице к ВКЛЮЧЁННЫМ группам доступа
 * @property RelPermissionsCollectionsToPermissionsCollections[] $relatedMasterPermissionsCollectionsToPermissionsCollections Связь к промежуточной таблице к РОДИТЕЛЬСКИМ группам доступа
 * @property PermissionsCollections[] $relatedSlavePermissionsCollections ВКЛЮЧЁННЫЕ группы доступа
 * (родительские нам не нужны ни для чего)
 * @property RelUsersToPermissionsCollections[] $relatedUsersToPermissionsCollections Связь к промежуточной таблице к пользователям
 * @property Permissions[] $relatedPermissions Входящие в группу доступа права доступа
 * @property IdentityInterface[] $relatedUsers Все пользователи, у которых есть эта группа доступа
 * @property IdentityInterface[] $relatedUsersRecursively Все пользователи, с учетом вложенности групп
 * @property-read Permissions[] $unrelatedPermissions Права доступа, которые не включены в набор
 * @property RelPermissionsCollectionsToPermissions[] $relatedSlavePermissionsCollectionsToPermissions Связь к промежуточной таблице к правам доступа для всех ВКЛЮЧЁННЫХ групп
 * @property-read Permissions[] $relatedPermissionsViaSlaveGroups Права доступа, попавшие в группу из дочерних групп
 */
class PermissionsCollectionsAR extends ActiveRecord {
	use ActiveRecordTrait;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return 'sys_permissions_collections';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['comment'], 'string'],
			[['name'], 'string', 'max' => 128],
			[['name'], 'unique'],
			[['name'], 'required'],
			[['default'], 'boolean'],
			[['relatedPermissions', 'relatedUsers', 'relatedSlavePermissionsCollections'], 'safe']
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'name' => 'Название',
			'comment' => 'Комментарий',
			'default' => 'По умолчанию',
			'relatedUsers' => 'Присвоено пользователям',
			'relatedPermissions' => 'Доступы',
			'relatedSlavePermissionsCollections' => 'Включённые группы доступов',
			'relatedPermissionsViaSlaveGroups' => 'Доступы из включённых групп'
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedUsersToPermissionsCollections():ActiveQuery {
		return $this->hasMany(RelUsersToPermissionsCollections::class, ['collection_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissionsCollectionsToPermissions():ActiveQuery {
		return $this->hasMany(RelPermissionsCollectionsToPermissions::class, ['collection_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissions():ActiveQuery {
		return $this->hasMany(Permissions::class, ['id' => 'permission_id'])->via('relatedPermissionsCollectionsToPermissions');
	}

	/**
	 * @param mixed $relatedPermissions
	 * @throws Throwable
	 */
	public function setRelatedPermissions(mixed $relatedPermissions):void {
		if (empty($relatedPermissions)) {
			RelPermissionsCollectionsToPermissions::clearLinks($this);
		} else {
			RelPermissionsCollectionsToPermissions::linkModels($this, $relatedPermissions);
		}
	}

	/**
	 * @return Permissions[]
	 */
	public function getUnrelatedPermissions():array {
		return Permissions::find()->where(['not in', 'id', ArrayHelper::getColumn($this->relatedPermissions, 'id')])->all();
	}

	/**
	 * @return ActiveQuery
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function getRelatedUsers():ActiveQuery {
		return $this->hasMany(PermissionsModule::UserIdentityClass(), ['id' => 'user_id'])->via('relatedUsersToPermissionsCollections');
	}

	/**
	 * Проблематично построить связь для join'а при использовании CTE, поэтому только live-получение.
	 * CTE нужен, чтобы рекурсивно вычислять группы, включённые в группы.
	 * Выборка не проверялась в поисковых моделях, но должно будет работать.
	 * @return IdentityInterface[]
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function getRelatedUsersRecursively():array {
		return PermissionsModule::UserIdentityClass()::find()
			->alias('users')
			->innerJoin('t', 't.user_id = users.id')
			->withQuery(
			//initial query
				RelUsersToPermissionsCollections::find()
					->alias('users_to_cols')
					->select(['users_to_cols.collection_id', 'users_to_cols.user_id'])
					->union(
					//recursive query
						RelPermissionsCollectionsToPermissionsCollections::find()
							->alias('cols_to_cols')
							->select(['cols_to_cols.slave_id', 't.user_id'])
							->innerJoin('t', 't.collection_id = cols_to_cols.master_id')
					),
				't',
				true
			)
			->where(['t.collection_id' => $this->id])
			->all();
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissionsCollectionsToPermissionsCollections():ActiveQuery {
		return $this->hasMany(RelPermissionsCollectionsToPermissionsCollections::class, ['master_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedMasterPermissionsCollectionsToPermissionsCollections():ActiveQuery {
		return $this->hasOne(RelPermissionsCollectionsToPermissionsCollections::class, ['slave_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedSlavePermissionsCollections():ActiveQuery {
		return $this->hasMany(self::class, ['id' => 'slave_id'])->via('relatedPermissionsCollectionsToPermissionsCollections');
	}

	/**
	 * @param mixed $relatedSlavePermissionsCollections
	 * @throws Throwable
	 */
	public function setRelatedSlavePermissionsCollections(mixed $relatedSlavePermissionsCollections):void {
		if (empty($relatedSlavePermissionsCollections)) {
			RelPermissionsCollectionsToPermissionsCollections::clearLinks($this);
		} else {
			RelPermissionsCollectionsToPermissionsCollections::linkModels($this, $relatedSlavePermissionsCollections);
		}
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedSlavePermissionsCollectionsToPermissions():ActiveQuery {
		return $this->hasMany(RelPermissionsCollectionsToPermissions::class, ['collection_id' => 'id'])->via('relatedSlavePermissionsCollections');
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelatedPermissionsViaSlaveGroups():ActiveQuery {
		return $this->hasMany(Permissions::class, ['id' => 'permission_id'])->via('relatedSlavePermissionsCollectionsToPermissions');
	}

}
