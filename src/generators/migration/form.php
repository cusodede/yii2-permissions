<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var yii\widgets\ActiveForm $form
 * @var Generator $generator
 */
use cusodede\permissions\generators\migration\Generator;
use yii\web\View;

?>
<div class="module-form">
	<?= $form->field($generator, 'includePermissions')->checkbox() ?>
	<?= $form->field($generator, 'includePermissionsCollections')->checkbox() ?>
	<?= $form->field($generator, 'includeRelationsToUserAccounts')->checkbox() ?>
	<?= $form->field($generator, 'savePath')->textInput() ?>
</div>
