<?php
declare(strict_types = 1);

namespace cusodede\permissions\controllers;

use cusodede\permissions\filters\PermissionFilter;
use cusodede\permissions\helpers\CommonHelper;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsSearch;
use cusodede\permissions\PermissionsModule;
use cusodede\permissions\traits\ControllerPermissionsTrait;
use cusodede\web\default_controller\models\DefaultController;
use kartik\depdrop\DepDropAction;
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
	public function behaviors():array {
		return [
			'access' => [
				'class' => PermissionFilter::class
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return PermissionsModule::param('viewPath.permissions', '@vendor/cusodede/yii2-permissions/src/views/permissions');
	}

	/**
	 * @return string
	 */
	public static function Title():string {
		return 'Доступы';
	}

	/**
	 * @inheritDoc
	 */
	public function actions():array {
		/*@see https://webtips.krajee.com/setup-editable-column-grid-view-manipulate-records/*/
		return ArrayHelper::merge(parent::actions(), [
			/**
			 * Можно назначить один экшен на все поля, которым не требуется специализированный обработчик,
			 * данные всё равно грузятся так, будто постится полная форма.
			 * @see EditableColumnAction::validateEditable
			 */
			'editDefault' => [
				'class' => EditableColumnAction::class,
				'modelClass' => Permissions::class,
				'showModelErrors' => true,
				'outputValue' => function(Permissions $model, string $attribute, int $key, int $index) {
					if (in_array($attribute, Permissions::ALLOWED_EMPTY_PARAMS) && empty($model->$attribute)) {
						return "*";
					}
					return '';
				},
			]
		], [/* tries to return selected controller actions*/
			'get-controller-actions' => [
				'class' => DepDropAction::class,
				'outputCallback' => function(string $selectedId, array $params):array {
					$controllerClass = (false === $moduleId = strstr($selectedId, '/', true))
						?CommonHelper::GetControllerClassFileByControllerId($selectedId)
						:CommonHelper::GetControllerClassFileByControllerId(strstr($selectedId, '/'), $moduleId);
					$actions = CommonHelper::GetControllerClassActions($controllerClass);
					return ArrayHelper::mapEx($actions, ['id' => 'value', 'name' => 'value']);
				}
			]
		]);
	}

}