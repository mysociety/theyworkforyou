<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that the home page works');
$I->amOnPage('/');
$I->see('TheyWorkForYou');
