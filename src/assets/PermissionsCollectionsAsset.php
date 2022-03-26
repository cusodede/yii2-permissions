<?php
declare(strict_types = 1);

namespace cusodede\permissions\assets;

use yii\web\AssetBundle;

/**
 * Class PermissionsCollectionsAsset
 * @package app\assets
 */
class PermissionsCollectionsAsset extends AssetBundle {

	/**
	 * {@inheritDoc}
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/permissionsCollections/';
		$this->js = [
			'js/permissionsCollections.js'
		];

		parent::init();
	}
}
