<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var ArrayDataProvider $result
 */

use cusodede\permissions\commands\DefaultController;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\web\View;

?>

<?= GridView::widget([
	'dataProvider' => $result,
	'rowOptions' => static function(array $permissionItem) {
		return $permissionItem['saved']
			?['class' => 'success']
			:['class' => 'warning'];
	},
	'columns' => [
		[
			'class' => DataColumn::class,
			'attribute' => 'saved',
			'format' => 'boolean',
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'item',
			'value' => static function(array $permissionItem):string {
				return $permissionItem['saved']
					?"{$permissionItem['item']->name} добавлено"
					:"{$permissionItem['item']->name} пропущено: (".DefaultController::Errors2String($permissionItem['permission']->errors).")";
			}
		],
	]
]) ?>