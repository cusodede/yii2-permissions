<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Permissions $model
 * @var ActiveForm $form
 */

use cusodede\permissions\models\Permissions;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\ControllerHelper;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use yii\web\View;

?>

<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'name')->textInput() ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'priority')->widget(TouchSpin::class) ?>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'controllerPath')->widget(Select2::class, [
			'data' => ControllerHelper::GetControllersList(Permissions::ConfigurationParameter(Permissions::CONTROLLER_DIRS)),
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
		<?= $form->field($model, 'action')->textInput() ?>
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

