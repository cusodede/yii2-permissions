<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Permissions $model
 */

use cusodede\permissions\models\Permissions;
use pozitronik\widgets\BadgeWidget;
use yii\bootstrap4\Modal;
use yii\web\View;
use yii\bootstrap4\ActiveForm;

?>
<?php Modal::begin([
	'id' => "{$model->formName()}-modal-create-new",
	'size' => Modal::SIZE_LARGE,
	'title' => BadgeWidget::widget([
		'items' => $model,
		'subItem' => 'name'
	]),
	'footer' => $this->render('../subviews/editPanelFooter', [
		'model' => $model,
		'form' => "{$model->formName()}-modal-create"
	]),//post button outside the form
	'options' => [
		'tabindex' => false, // important for Select2 to work properly
		'class' => 'modal-dialog-large'
	]
]); ?>
<?php $form = ActiveForm::begin(
	[
		'id' => "{$model->formName()}-modal-create",
		'enableAjaxValidation' => true,

	]) ?>
<?= $this->render('../subviews/editPanelBody', compact('model', 'form')) ?>
<?php ActiveForm::end(); ?>
<?php Modal::end(); ?>