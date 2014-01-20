<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that the Lords list page works');
$I->amOnPage('/peers/');
$I->see('All Members of the House of Lords', 'h1');
$I->seeLink('Lord Aberdare','/peer/24696/lord_aberdare');
