<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that MP pages work');
$I->amOnPage('/mp/10001/diane_abbott/hackney_north_and_stoke_newington');
$I->see('Diane Abbott');
$I->see('Labour MP for Hackney North and Stoke Newington');
$I->see('Voting Record');
$I->see('Most Recent Appearances');
$I->see('Numerology');
