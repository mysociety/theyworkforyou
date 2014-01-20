<?php

$I = new WebGuy($scenario);
$I->wantTo('ensure that written answers list pages work');
$I->amOnPage('/written-answers-and-statements');
$I->see('Hansard Written Answers', 'h1');
$I->see('Some recent written answers', 'h2');
$I->see('Some recent written ministerial statements', 'h2');
