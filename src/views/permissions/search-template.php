<?php
declare(strict_types = 1);

/**
 * @var View $this
 */

use cusodede\permissions\controllers\PermissionsController;
use yii\base\View;

?>
<div class="suggestion-item">
	<div class="suggestion-name">{{name}}</div>
	<div class="clearfix"></div>
	<div class="suggestion-secondary">{{controller}}</div>
	<div class="suggestion-links">
		<a href="<?= PermissionsController::to('edit') ?>?id={{id}}"
		   class="dashboard-button btn btn-xs btn-info float-left">Редактировать<a/>
	</div>
	<div class="clearfix"></div>
</div>