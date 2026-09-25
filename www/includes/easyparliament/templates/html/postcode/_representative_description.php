<?php

use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeSectionData;
use MySociety\TheyWorkForYou\HouseType;

/** @var RepresentativeSectionData $section */
?>
<p><?= match ($section->house) {
    HouseType::COMMONS => gettext('Your MP represents you in the House of Commons. The House of Commons is responsible for making laws in the UK and for overall scrutiny of all aspects of government.'),
    HouseType::SCOTLAND => gettext('Your MSPs represent you in the Scottish Parliament. The Scottish Parliament is responsible for a wide range of devolved matters in which it sets policy independently of the London Parliament. Devolved matters include education, health, agriculture, justice and prisons. It also has some tax-raising powers.'),
    HouseType::NI => gettext('Your MLAs represent you in the Northern Ireland Assembly. The Northern Ireland Assembly has full authority over "transferred matters", which include agriculture, education, employment, the environment and health.'),
    HouseType::WALES => gettext('Your MSs represent you in the Senedd. The Senedd has a wide range of powers over areas including economic development, transport, finance, local government, health, housing and the Welsh Language.'),
} ?></p>
