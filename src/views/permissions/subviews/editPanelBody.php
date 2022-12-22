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
			'options' => ['id'=>'controller-path'],
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
		<?= $form->field($model, 'action')->widget(DepDrop::class, [
			'pluginOptions'=>[
				'depends'=>['controller-path'],
				'placeholder' => 'Select...',
				'url' => PermissionsModule::to('permissions/get-controller-actions')
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

