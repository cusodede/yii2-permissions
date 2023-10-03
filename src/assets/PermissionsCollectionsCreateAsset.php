<?php
declare(strict_types = 1);

namespace cusodede\permissions\assets;

use yii\web\AssetBundle;

/**
 * Class PermissionsCollectionsCreateAsset
 * @package app\assets
 */
class PermissionsCollectionsCreateAsset extends AssetBundle {

	/**
	 * {@inheritDoc}
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/permissionsCollectionsCreate/';
		$this->js = [
			'js/create.js'
		];
		$this->depends = [
			'yii\web\JqueryAsset'
		];

		parent::init();
	}
}
