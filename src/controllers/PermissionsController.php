<?php
declare(strict_types = 1);

namespace cusodede\permissions\controllers;

use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsSearch;
use cusodede\permissions\traits\ControllerPermissionsTrait;
use cusodede\web\default_controller\models\DefaultController;
use kartik\grid\EditableColumnAction;
use pozitronik\helpers\ArrayHelper;

/**
 * Class PermissionsController
 */
class PermissionsController extends DefaultController {
	use ControllerPermissionsTrait;

	public ?string $modelClass = Permissions::class;
	public ?string $modelSearchClass = PermissionsSearch::class;
	public bool $enablePrototypeMenu = false;

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return '@vendor/cusodede/yii2-permissions/src/views/permissions';
	}

	/**
	 * @inheritDoc
	 */
	public function actions():array {
		$defaultEditableActionConfig = [
			'class' => EditableColumnAction::class,
			'modelClass' => Permissions::class,
			'showModelErrors' => true,
		];
		/*@see https://webtips.krajee.com/setup-editable-column-grid-view-manipulate-records/*/
		return ArrayHelper::merge(parent::actions(), [
			/**
			 * Можно назначить один экшен на все поля, которым не требуется специализированный обработчик,
			 * данные всё равно грузятся так, будто постится полная форма.
			 * @see \kartik\grid\EditableColumnAction::validateEditable()
			 */
			'editDefault' => $defaultEditableActionConfig,
			'editAction' => [
				'class' => EditableColumnAction::class,
				'modelClass' => Permissions::class,
				'showModelErrors' => true,
				'outputValue' => function(Permissions $model, string $attribute, int $key, int $index) {
					if (in_array($attribute, Permissions::ALLOWED_EMPTY_PARAMS) && empty($model->$attribute)) {
						return "Любой";
					}
					return '';
				},
			]
		]);
	}

}