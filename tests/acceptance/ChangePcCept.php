<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that changing postcodes works');
$I->amOnPage('/user/changepc');
$I->see('Enter your postcode', 'h1');
$I->fillField('pc', 'SW1A 1AA');
$I->click('Go');

$I->see('Mark Field', 'h1');
