<?php

declare(strict_types = 1);

namespace app\controllers_issue_45;

use cusodede\permissions\filters\PermissionFilter;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\models\PermissionsCollectionsSearch;
use cusodede\web\default_controller\models\DefaultController;
use Throwable;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Основной контроллер "Встреч"
 */
class MeetingsController extends DefaultController {
	protected const DEFAULT_TITLE = 'Встречи';
	public ?string $modelClass = PermissionsCollections::class;
	public ?string $modelSearchClass = PermissionsCollectionsSearch::class;
	public string $defaultPanelPath = '/default_panel';

	protected static ?string $_primaryKeyName = 'id';

	protected array $disabledActions = [
		'actionView'
	];

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
		return '';
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionPartnerSearch():string {
		return '';
	}

	/**
	 * @return array
	 * @throws BadRequestHttpException
	 */
	public function actionAjaxValidation():array {
		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;

			return [
				'some key' => 'some value'
			];
		}

		throw new BadRequestHttpException();
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionCard():string|Response {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function actionCreate():Response|string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function actionEdit():Response|string {
		return '';
	}
}
