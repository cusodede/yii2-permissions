<?php
declare(strict_types = 1);

namespace cusodede\permissions\helpers;

use cusodede\permissions\models\PermissionsCollections;

/**
 * Class PermissionsCollectionsHelper
 */
class PermissionsCollectionsHelper {

	/**
	 * @return array
	 */
	public static function getOptionsDataAttributes():array {
		$optionsArray = [];
		$permissionsCollections = PermissionsCollections::find()->all();
		foreach ($permissionsCollections as $collection) {
			$optionsArray[$collection->id] = [
				'data-permissionIds' => array_column($collection->relatedPermissions, 'id'),
				'data-collectionIds' => array_column($collection->relatedSlavePermissionsCollections, 'id'),
			];
		}

		return $optionsArray;
	}
}
