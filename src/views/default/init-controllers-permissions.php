<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var ArrayDataProvider $result
 */

use cusodede\permissions\helpers\CommonHelper;
use cusodede\permissions\PermissionsModule;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\web\View;

?>

<?= GridView::widget([
	'dataProvider' => $result,
	'rowOptions' => static function(array $permissionItem) {
		return $permissionItem['saved']
			?['class' => 'alert-success']
			:['class' => 'alert-warning'];
	},
	'columns' => [
		[
			'class' => DataColumn::class,
			'attribute' => 'type',
			'label' => 'Тип',
			'value' => static fn(array $permissionItem):string => match ($permissionItem['type']) {
				PermissionsModule::PERMISSIONS => 'Разрешение',
				PermissionsModule::PERMISSIONS_COLLECTIONS => 'Коллекция',

			}
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'saved',
			'label' => 'Разрешение добавлено',
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