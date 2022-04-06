<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Permissions $model
 */

use cusodede\permissions\models\Permissions;
use yii\web\View;
use yii\widgets\DetailView;

?>

<?= DetailView::widget([
	'model' => $model,
	'attributes' => [
		'name',
		'comment',
		'module',
		'controller',
		'action',
		'verb',
		'priority'
	]
]) ?>
