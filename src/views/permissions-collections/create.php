<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var PermissionsCollections $model
 */

use cusodede\permissions\models\PermissionsCollections;
use yii\web\View;
use yii\bootstrap4\ActiveForm;
use cusodede\permissions\assets\PermissionsCollectionsCreateAsset;

PermissionsCollectionsCreateAsset::register($this);
?>

<?php $form = ActiveForm::begin(); ?>
<div class="panel">
	<div class="panel-hdr">
	</div>
	<div class="panel-container show">
		<div class="panel-content">
			<?= $this->render('subviews/editPanelBody', compact('model', 'form')) ?>
		</div>
		<div class="panel-content">
			<?= $form->errorSummary($model) ?>
			<?= $this->render('subviews/editPanelFooter', compact('model', 'form')) ?>
			<div class="clearfix"></div>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>
