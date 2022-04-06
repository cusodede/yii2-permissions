<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var PermissionsCollections $model
 */

use cusodede\permissions\models\PermissionsCollections;
use yii\web\View;
use yii\widgets\DetailView;

?>

<?= DetailView::widget([
	'model' => $model,
	'attributes' => [
		'name',
		'comment',
		'default:boolean'
	]
]) ?>
