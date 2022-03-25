<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

/**
 * Class PermissionsHelper
 */
class PermissionsHelper {

	public const VERBS = [
		'GET' => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT' => 'PUT',
		'PATCH' => 'PATCH',
		'DELETE' => 'DELETE'
	];
}