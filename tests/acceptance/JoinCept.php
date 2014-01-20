<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that the join page works');
$I->amOnPage('/user/?pg=join');
$I->see('Join TheyWorkForYou', 'h1');
