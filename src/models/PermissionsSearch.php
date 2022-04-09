<?php
declare(strict_types = 1);

namespace cusodede\permissions\models;

use cusodede\permissions\PermissionsModule;
use Throwable;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * Class PermissionsSearch
 * @property null|string $user
 * @property null|string $collection
 * @property null|string $controllerPath
 */
final class PermissionsSearch extends Permissions {

	public ?string $user = null;
	public ?string $collection = null;
	public ?string $controllerPath = null;

	/**
	 * @inheritDoc
	 */
	public function rules():array {
		return [
			['id', 'integer'],
			['priority', 'integer', 'min' => 0, 'max' => 100],
			[['name', 'module', 'controller', 'action', 'verb', 'user', 'collection', 'controllerPath'], 'string', 'max' => 255]
		];
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public function search(array $params):ActiveDataProvider {
		$query = Permissions::find()->distinct()->active();

		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);

		$this->setSort($dataProvider);
		$this->load($params);

		if (!$this->validate()) return $dataProvider;

		$query->joinWith(['relatedUsers', 'relatedPermissionsCollections']);
		$this->filterData($query);

		return $dataProvider;
	}

	/**
	 * @param $query
	 * @return void
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	private function filterData($query):void {
		$query->andFilterWhere([self::tableName().'.id' => $this->id])
			->andFilterWhere([self::tableName().'.priority' => $this->priority])
			->andFilterWhere(['like', new Expression('CONCAT("'.self::tableName().'"."module"'.',\'/\',"'.self::tableName().'"."controller")'), $this->controllerPath])
			->andFilterWhere(['like', self::tableName().'.module', $this->module])
			->andFilterWhere(['like', self::tableName().'.name', $this->name])
			->andFilterWhere(['like', self::tableName().'.controller', $this->controller])
			->andFilterWhere(['like', self::tableName().'.action', $this->action])
			->andFilterWhere([self::tableName().'.verb' => $this->verb])
			->andFilterWhere(['like', PermissionsModule::UserIdentityClass()::tableName().'.username', $this->user])
			->andFilterWhere(['like', PermissionsCollections::tableName().'.name', $this->collection]);
	}

	/**
	 * @param $dataProvider
	 */
	private function setSort($dataProvider):void {
		$dataProvider->setSort([
			'defaultOrder' => ['id' => SORT_ASC],
			'attributes' => ['id', 'module', 'name', 'controller', 'action', 'verb', 'priority',
				'controllerPath' => [
					'asc' => [self::tableName().'.module' => SORT_ASC, self::tableName().'.controller' => SORT_ASC],
					'desc' => [self::tableName().'.module' => SORT_DESC, self::tableName().'.controller' => SORT_DESC]
				]
			]
		]);
	}

}