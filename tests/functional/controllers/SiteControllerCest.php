<?php
namespace controllers;

use FunctionalTester;
use yii\helpers\VarDumper;

class SiteControllerCest
{
    // tests
    public function actionIndex(FunctionalTester $I)
    {
        $I->amOnPage('/site/index');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'result' => 1
        ]);
    }
}
