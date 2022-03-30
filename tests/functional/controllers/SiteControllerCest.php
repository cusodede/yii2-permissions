<?php
declare(strict_types = 1);

namespace controllers;

use FunctionalTester;

/**
 * Class SiteControllerCest
 */
class SiteControllerCest {
	// tests
	public function actionIndex(FunctionalTester $I) {
		$I->amOnPage('/site/index');
		$I->seeResponseCodeIs(200);
		$I->seeResponseContainsJson([
			'result' => 1
		]);
	}
}
