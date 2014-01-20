<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that debate list pages work');
$I->amOnPage('/debates');
$I->see('UK Parliament Hansard Debates', 'h1');
$I->see('Recent House of Commons debates', 'h2');
$I->see('Recent Westminster Hall debates', 'h2');
$I->see('Recent House of Lords debates', 'h2');
