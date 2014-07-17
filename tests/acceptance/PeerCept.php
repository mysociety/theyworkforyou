<?php

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that Peer pages work');
$I->amOnPage('peer/24696/lord_aberdare');
$I->see('Lord Aberdare');
$I->see('Crossbench Peer');
$I->see('Voting Record');
$I->see('Recent Appearances');
$I->see('Numerology');
