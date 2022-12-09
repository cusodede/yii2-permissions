<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var ArrayDataProvider $result
 */

use cusodede\permissions\controllers\DefaultController;
use cusodede\permissions\helpers\CommonHelper;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\web\View;

?>

<?= GridView::widget([
	'dataProvider' => $result,
	'rowOptions' => static function(array $permissionItem) {
		return $permissionItem['deleted']
			?['class' => 'alert-success']
			:['class' => 'alert-warning'];
	},
	'columns' => [
		[
			'class' => DataColumn::class,
			'attribute' => 'type',
			'label' => 'Тип',
			'value' => static fn(array $permissionItem):string => match ($permissionItem['type']) {
				DefaultController::PERMISSION => 'Разрешение',
				DefaultController::PERMISSIONS_COLLECTION => 'Группа разрешений',

			}
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'deleted',
			'label' => 'Разрешение удалено',
			'format' => 'boolean',
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'item',
			'label' => 'Название',
			'value' => static fn(array $permissionItem):string => $permissionItem['item']->name
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'item',
			'label' => 'Дополнительно',
			'value' => static fn(array $permissionItem):string => CommonHelper::Errors2String($permissionItem['item']->errors)
		]
	]
]) ?>