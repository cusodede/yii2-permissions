<?php
declare(strict_types = 1);

namespace cusodede\permissions\controllers;

use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\models\PermissionsCollectionsSearch;
use cusodede\web\default_controller\models\DefaultController;

/**
 * Class PermissionsCollectionsController
 */
class PermissionsCollectionsController extends DefaultController {

	public ?string $modelClass = PermissionsCollections::class;
	public ?string $modelSearchClass = PermissionsCollectionsSearch::class;
	public bool $enablePrototypeMenu = false;

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return '@app/views/permissions-collections';
	}

}