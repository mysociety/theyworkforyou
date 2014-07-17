<?php

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that the MPs list page works');
$I->amOnPage('/mps');
$I->see('All MPs');
$I->seeLink('Diane Abbott','/mp/10001/diane_abbott/hackney_north_and_stoke_newington');
