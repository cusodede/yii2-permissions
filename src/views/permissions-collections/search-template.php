<?php
declare(strict_types = 1);

/**
 * @var View $this
 */

use cusodede\permissions\controllers\PermissionsCollectionsController;
use yii\base\View;

?>
<div class="suggestion-item">
	<div class="suggestion-name">{{name}}</div>
	<div class="suggestion-links">
		<a href="<?= PermissionsCollectionsController::to('edit') ?>?id={{id}}"
		   class="dashboard-button btn btn-xs btn-info float-left">Редактировать<a/>
	</div>
	<div class="clearfix"></div>
</div>