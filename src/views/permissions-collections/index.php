<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var PermissionsCollectionsSearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */

use cusodede\permissions\models\PermissionsCollectionsSearch;
use kartik\grid\ActionColumn;
use kartik\grid\DataColumn;
use kartik\grid\GridView;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\Utils;
use yii\data\ActiveDataProvider;
use yii\web\JsExpression;
use yii\web\View;

PermissionsCollectionsAsset::register($this);
ModalHelperAsset::register($this);

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
			'{optionsBtn}' => ToolbarFilterWidget::widget(['content' => '{options}']),
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['разрешение', 'разрешения', 'разрешений']):"Нет разрешений",
			'{newRecord}' => ToolbarFilterWidget::widget([
				'label' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['разрешение', 'разрешения', 'разрешений']):"Нет разрешений",
				'content' => Html::link('Новая запись', PermissionsController::to('create'), ['class' => 'btn btn-success'])
			]),
			'{filterBtn}' => ToolbarFilterWidget::widget(['content' => Html::button("<i class='fa fa-filter'></i>", ['onclick' => new JsExpression('setFakeGridFilter("#'.$id.'")'), 'class' => 'btn btn-info'])]),
			'{collectionsLink}' => ToolbarFilterWidget::widget(['content' => Html::link("Редактор разрешений", PermissionsController::to('index'), ['class' => 'btn btn-info'])])
		],
		'toolbar' => [
			'{filterBtn}'
		],
		'panelBeforeTemplate' => '{optionsBtn}{newRecord}{collectionsLink}{toolbarContainer}{before}<div class="clearfix"></div>',
		'emptyText' => Html::link('Новая группа', PermissionsCollectionsController::to('create'), ['class' => 'btn btn-success']),
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => [
			[
				'class' => ActionColumn::class,
				'hAlign' => GridView::ALIGN_LEFT,
				'template' => '<div class="btn-group">{edit}</div>',
				'buttons' => [
					'edit' => static fn(string $url) => Html::link('<i class="fa fa-edit"></i>', $url, [
							'class' => 'btn btn-sm btn-outline-primary',
							'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Редактирование']
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
			'comment',
			[
				'class' => DataColumn::class,
				'attribute' => 'permission',
				'label' => 'Включённые доступы',
				'value' => //прямые
				//вдобавок к модалке оставляем ссылку для прямого перехода
				//через группы
					static fn(PermissionsCollections $collections) => BadgeWidget::widget([//прямые
							'items' => $collections->relatedPermissions,
							'subItem' => 'name',
							'urlScheme' => [PermissionsController::to('edit'), 'id' => 'id']//вдобавок к модалке оставляем ссылку для прямого перехода
						]).BadgeWidget::widget([//через группы
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
					'urlScheme' => [UsersController::to('view'), 'id' => 'id']
				])
			]
		]
	])
]) ?>
