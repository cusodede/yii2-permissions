<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * class SiteController
 */
class SiteController extends Controller
{
    /**
     * @return int[]
     */
    public function actionIndex():array {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['result' => 1];
    }
}