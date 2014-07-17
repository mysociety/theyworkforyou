<?php

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that PBC list pages work');
$I->amOnPage('/pbc');
$I->see('Public Bill Committees', 'h1');
$I->see('Most recent Public Bill committee debates', 'h2');
