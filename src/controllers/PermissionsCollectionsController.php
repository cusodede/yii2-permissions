<?php
declare(strict_types = 1);

namespace cusodede\permissions\controllers;

use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\models\PermissionsCollectionsSearch;
use cusodede\permissions\traits\ControllerPermissionsTrait;
use cusodede\web\default_controller\models\DefaultController;

/**
 * Class PermissionsCollectionsController
 */
class PermissionsCollectionsController extends DefaultController {
	use ControllerPermissionsTrait;

	public ?string $modelClass = PermissionsCollections::class;
	public ?string $modelSearchClass = PermissionsCollectionsSearch::class;
	public bool $enablePrototypeMenu = false;

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return '@vendor/cusodede/yii2-permissions/src/views/permissions-collections';
	}

}