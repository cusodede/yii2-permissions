<?php
declare(strict_types = 1);

use yii\db\Connection;

return [
	'class' => Connection::class,
	'dsn' => getenv('PERM_DB_DSN'),
	'username' => getenv('PERM_DB_USER'),
	'password' => getenv('PERM_DB_PASS'),
	'enableSchemaCache' => false,
];