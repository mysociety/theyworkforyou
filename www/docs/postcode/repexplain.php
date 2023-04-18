<h2><?= gettext("More information") ?></h2>

<?php if ($multi == "scotland") { ?>

    <p><?= gettext("Your MSPs represents you in the Scottish Parliament. The Scottish Parliament is responsible for a wide range of devolved matters in which it sets policy independently of the London Parliament. Devolved matters include education, health, agriculture, justice and prisons. It also has some tax-raising powers.")?></p>

<?php } elseif ($multi == "northern-ireland") { ?>

    <p><?= gettext("Your MLAs represent you on the Northern Ireland Assembly. The Northern Ireland Assembly has full authority over \"transferred matters\", which include agriculture, education, employment, the environment and health.")?></p>

<?php } elseif ($multi == "wales") { ?>

    <p><?= gettext("Your MSs represents you in the Senedd. The Senedd has a wide range of powers over areas including economic development, transport, finance, local government, health, housing and the Welsh Language.")?></p>

<?php } ?>

<p><?= gettext("Your MP represents you in the House of Commons. The House of Commons is responsible for making laws in the UK and for overall scrutiny of all aspects of government.")?></p>

<p><?= gettext("You can write to any representative, or your local councillors through <a href=\"http://www.writetothem.com/who?pc=" . $pc . "\">WriteToThem.com</a>")?>.</p>
