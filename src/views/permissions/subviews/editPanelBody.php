<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Permissions $model
 * @var ActiveForm $form
 */

use cusodede\permissions\models\Permissions;
use cusodede\permissions\PermissionsModule;
use kartik\depdrop\DepDrop;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use yii\web\View;

?>

<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'name')->textInput() ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'controllerPath')->widget(Select2::class, [
			'options' => ['id' => 'controller-path'],
			'data' => PermissionsModule::GetControllersList(PermissionsModule::param(Permissions::CONTROLLER_DIRS)),
			'pluginOptions' => [
				'multiple' => false,
				'allowClear' => true,
				'placeholder' => '',
				'tags' => true
			]
		]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= PermissionsModule::param('basicActionInput', false)
			?$form->field($model, 'action')->textInput()
			:$form->field($model, 'action')->widget(DepDrop::class, [
				'type' => DepDrop::TYPE_SELECT2,
				'value' => $model->action,
				'options' => ['placeholder' => $model->getAttributeLabel('action')],
				'select2Options' => [
					'pluginOptions' => [
						'allowClear' => true,
						'tags' => true
					]
				],
				'pluginOptions' => [
					'depends' => ['controller-path'],
					'url' => PermissionsModule::to(['permissions/get-controller-actions']),
					'initialize' => true
				]
			]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'verb')->widget(Select2::class, [
			'data' => PermissionsModule::VERBS,
			'pluginOptions' => [
				'multiple' => false,
				'allowClear' => true,
				'placeholder' => '',
				'tags' => true
			]
		]) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'comment')->textarea() ?>
	</div>
</div>

