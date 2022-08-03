<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var PermissionsCollectionsSearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */

use cusodede\permissions\assets\PermissionsCollectionsAsset;
use cusodede\permissions\controllers\PermissionsCollectionsController;
use cusodede\permissions\controllers\PermissionsController;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\models\PermissionsCollectionsSearch;
use cusodede\permissions\PermissionsModule;
use kartik\grid\ActionColumn;
use kartik\grid\DataColumn;
use kartik\grid\GridView;
use pozitronik\grid_config\GridConfig;
use pozitronik\grid_helper_asset\GridHelperAsset;
use pozitronik\helpers\Utils;
use pozitronik\widgets\BadgeWidget;
use yii\bootstrap4\Html;
use yii\data\ActiveDataProvider;
use yii\web\JsExpression;
use yii\web\View;

PermissionsCollectionsAsset::register($this);
GridHelperAsset::register($this);

$id = 'permissions-collections-index-grid';

?>
<?= GridConfig::widget([
	'id' => $id,
	'grid' => GridView::begin([
		'id' => $id,
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'filterOnFocusOut' => false,
		'panel' => [
			'heading' => false,
		],
		'replaceTags' => [
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['коллекция', 'коллекции', 'коллекций']):"Нет коллекций",
			'{newRecord}' => Html::a('Новая коллекция', PermissionsModule::to('permissions-collections/create'), ['class' => 'btn btn-success']),
			'{filterBtn}' => Html::button("<i class='fa fa-filter'></i>", ['onclick' => new JsExpression('setFakeGridFilter("#'.$id.'")'), 'class' => 'btn btn-info']),
			'{collectionsLink}' => Html::a("Редактор разрешений", PermissionsModule::to('permissions/index'), ['class' => 'btn btn-info'])
		],
		'toolbar' => [
			'{filterBtn}'
		],
		'panelBeforeTemplate' => '{options}{newRecord}{collectionsLink}{toolbarContainer}{before}<div class="clearfix"></div>',
		'emptyText' => Html::a('Новая коллекция', PermissionsCollectionsController::to('create'), ['class' => 'btn btn-success']),
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => [
			[
				'class' => ActionColumn::class,
				'hAlign' => GridView::ALIGN_LEFT,
				'template' => '<div class="btn-group">{edit}</div>',
				'buttons' => [
					'edit' => static fn(string $url) => Html::a('<i class="fa fa-edit"></i>', $url, [
							'class' => 'btn btn-sm btn-outline-primary',
							'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Редактировать коллекцию']
						]
					),
				],
			],
			'id',
			[
				'class' => DataColumn::class,
				'attribute' => 'default',
				'format' => 'boolean'
			],
			'name',
			'priority',
			'comment',
			[
				'class' => DataColumn::class,
				'attribute' => 'permission',
				'label' => 'Включённые разрешения',
				'value' => static fn(PermissionsCollections $collections) => BadgeWidget::widget([//прямые
							'items' => $collections->relatedPermissions,
							'subItem' => 'name',
							'urlScheme' => [PermissionsController::to('edit'), 'id' => 'id']//вдобавок к модалке оставляем ссылку для прямого перехода
						]).BadgeWidget::widget([//через коллекции
							'items' => $collections->relatedPermissionsViaSlaveGroups,
							'subItem' => 'name',
							'urlScheme' => [PermissionsController::to('edit'), 'id' => 'id']
						]),
				'format' => 'raw'
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'relatedUsers',
				'format' => 'raw',
				'value' => static fn(PermissionsCollections $collections) => BadgeWidget::widget([
					'items' => $collections->relatedUsersRecursively,
					'subItem' => 'username',
				])
			]
		]
	])
]) ?>
