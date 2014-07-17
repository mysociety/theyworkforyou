<?php

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that the home page works');
$I->amOnPage('/');
$I->see('TheyWorkForYou');
