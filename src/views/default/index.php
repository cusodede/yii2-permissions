<?php
declare(strict_types = 1);

/**
 * @var View $this
 */
use cusodede\permissions\PermissionsModule;
use yii\bootstrap4\ButtonGroup;
use yii\web\View;

?>

<?= ButtonGroup::widget([
	'buttons' => [
		PermissionsModule::a('Добавляет разрешения, описанные в файле конфигурации', 'default/init-config-permissions', ['class' => 'btn btn-default']),
		PermissionsModule::a('Генерация доступов по контроллерам', 'default/init-controllers-permissions', ['class' => 'btn btn-default']),
		PermissionsModule::a('Удаление неиспользуемых контроллеров', 'default/drop-controllers-permissions', ['class' => 'btn btn-warning']),
	]
]) ?>