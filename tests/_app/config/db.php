<?php
declare(strict_types = 1);

use yii\db\Connection;

return [
	'class' => Connection::class,
	'dsn' => $_ENV['PERM_DB_DSN'],
	'username' => $_ENV['PERM_DB_USER'],
	'password' => $_ENV['PERM_DB_PASS'],
	'enableSchemaCache' => false,
];