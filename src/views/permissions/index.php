<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var PermissionsSearch $searchModel
 * @var ActiveDataProvider $dataProvider
 */

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsSearch;
use cusodede\permissions\PermissionsModule;
use kartik\editable\Editable;
use kartik\grid\ActionColumn;
use kartik\grid\DataColumn;
use kartik\grid\EditableColumn;
use kartik\grid\GridView;
use pozitronik\grid_config\GridConfig;
use pozitronik\grid_helper_asset\GridHelperAsset;
use pozitronik\helpers\Utils;
use pozitronik\widgets\BadgeWidget;
use yii\bootstrap4\Html;
use yii\data\ActiveDataProvider;
use yii\web\JsExpression;
use yii\web\View;
use kartik\select2\Select2;

GridHelperAsset::register($this);

$id = 'permissions-index-grid';
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
			'{totalCount}' => ($dataProvider->totalCount > 0)?Utils::pluralForm($dataProvider->totalCount, ['разрешение', 'разрешения', 'разрешений']):"Нет разрешений",
			'{newRecord}' => Html::a('Новая запись', PermissionsModule::to('permissions/create'), ['class' => 'btn btn-success']),
			'{filterBtn}' => Html::button("<i class='fa fa-filter'></i>", ['onclick' => new JsExpression('setFakeGridFilter("#'.$id.'")'), 'class' => 'btn btn-info']),
			'{collectionsLink}' => Html::a('Редактор групп', PermissionsModule::to('permissions-collections/index'), ['class' => 'btn btn-info'])
		],
		'toolbar' => [
			'{filterBtn}'
		],
		'panelBeforeTemplate' => '{options}{newRecord}{collectionsLink}{toolbarContainer}{before}<div class="clearfix"></div>',
		'summary' => null,
		'showOnEmpty' => true,
		'export' => false,
		'resizableColumns' => true,
		'responsive' => true,
		'columns' => [
			[
				'class' => ActionColumn::class,
				'hAlign' => GridView::ALIGN_LEFT,
				'template' => '<div class="btn-group">{edit}{delete}</div>',
				'buttons' => [
					'edit' => static fn(string $url) => Html::a('<i class="fa fa-edit"></i>', $url, [
							'class' => 'btn btn-sm btn-outline-primary',
							'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Редактирование']
						]
					),
					'delete' => static fn(string $url) => Html::a('<i class="fa fa-trash"></i>', $url, [
						'class' => ['btn btn-sm btn-outline-primary'],
						'data' => [
							'method' => "post",
							'confirm' => 'Вы уверены, что хотите удалить этот элемент?',
							'trigger' => 'hover',
							'toggle' => 'tooltip',
							'placement' => 'top',
							'original-title' => 'Удалить'
						]
					]),
				],
			],
			'id',
			[
				'class' => EditableColumn::class,
				'editableOptions' => static fn(Permissions $permission, int $key, int $index) => [
					'formOptions' => [
						'action' => PermissionsModule::to('permissions/editDefault')
					],
					'inputType' => Editable::INPUT_TEXT
				],
				'attribute' => 'name',
				'format' => 'text'
			],
			[
				'class' => EditableColumn::class,
				'editableOptions' => static fn(Permissions $permission, int $key, int $index) => [
					'formOptions' => [
						'action' => PermissionsModule::to('permissions/editDefault')
					],
					'inputType' => Editable::INPUT_SELECT2,
					'options' => [
						'data' => PermissionsModule::GetControllersList(PermissionsModule::param(Permissions::CONTROLLER_DIRS)),
						'pluginOptions' => [
							'multiple' => false,
							'allowClear' => true,
							'placeholder' => '',
							'tags' => true
						]
					]
				],
				'attribute' => 'controllerPath',
				'format' => 'text'
			],
			[
				'class' => EditableColumn::class,
				'editableOptions' => static fn(Permissions $permission, int $key, int $index) => [
					'valueIfNull' => '*',
					'formOptions' => [
						'action' => PermissionsModule::to('permissions/editDefault'),
					],
					'inputType' => Editable::INPUT_TEXT
				],
				'attribute' => 'action',
				'format' => 'text'
			],
			[
				'class' => EditableColumn::class,
				'editableOptions' => static fn(Permissions $permission, int $key, int $index) => [
					'valueIfNull' => '*',
					'formOptions' => [
						'action' => PermissionsModule::to('permissions/editDefault')
					],
					'inputType' => Editable::INPUT_SELECT2,
					'options' => [
						'data' => PermissionsModule::VERBS,
						'pluginOptions' => [
							'multiple' => false,
							'allowClear' => true,
							'placeholder' => '',
							'tags' => true
						]
					]
				],
				'filter' => Select2::widget([
					'model' => $searchModel,
					'attribute' => 'verb',
					'data' => PermissionsModule::VERBS,
					'pluginOptions' => [
						'allowClear' => true,
						'placeholder' => ''
					]
				]),
				'attribute' => 'verb',
				'format' => 'text'
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'usageFlags',
				'format' => 'raw',
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'module',
				'format' => 'raw',
				'value' => static fn(Permissions $permission):string => $permission->module??''
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'controller',
				'format' => 'text',
			],
			[
				'class' => EditableColumn::class,
				'editableOptions' => static fn(Permissions $permission, int $key, int $index) => [
					'formOptions' => [
						'action' => PermissionsModule::to('permissions/editDefault')
					],
					'inputType' => Editable::INPUT_TEXTAREA,
				],
				'attribute' => 'comment',
				'format' => 'text'
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'collection',
				'label' => 'Входит в группы',
				'value' => static fn(Permissions $permission) => BadgeWidget::widget([
					'items' => $permission->relatedPermissionsCollections,
					'subItem' => 'name',
					'urlScheme' => [PermissionsModule::to('permissions-collections/edit'), 'id' => 'id']
				]),
				'format' => 'raw'
			],
			[
				'class' => DataColumn::class,
				'attribute' => 'user',
				'label' => 'Назначено пользователям',
				'value' => static fn(Permissions $permission) => BadgeWidget::widget([
					'items' => $permission->relatedUsers,
					'subItem' => 'username',
				]),
				'format' => 'raw'
			]
		]
	])
]) ?>
