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
	<?= $form->field($generator, 'includePermissions'); ?>
	<?= $form->field($generator, 'includePermissionsCollections'); ?>
	<?= $form->field($generator, 'includeUserAccounts'); ?>
</div>
