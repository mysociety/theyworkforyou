<?php

$divisions_to_link = [];
foreach($data['rows'] as $speech) {

    # Only care about divisions...
    if ($speech['htype'] != 14) {
        continue;
    }
    # ...in Commons/Lords
    if ($data['info']['major'] != 1 && $data['info']['major'] != 101) {
        continue;
    }

    $divisions_to_link[] = $speech;
}

if (count($divisions_to_link)) {
    ?>

<div class="debate-speech">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="debate-speech__division">
                <h2 class="debate-speech__division__header">
                    <img src="/images/bell.png" alt="">
                    <strong class="debate-speech__division__title"><?= gettext('Votes in this debate') ?></strong>
                </h2>

                <ul class="debate-speech__division__details">
                    <?php foreach ($divisions_to_link as $speech) {
                        $division = $speech['division'];
                        ?>
                    <li><a href="#g<?= gid_to_anchor($speech['gid']) ?>"><?= sprintf(gettext('Division number %s'), $division['number']) ?></a>
                        <?php if ($division['has_description']) { ?>
                            <br><span class="policy-vote__text">
                                <?php include(dirname(__FILE__) . '/../divisions/_vote_description.php'); ?>
                            </span>
                        <?php } ?>

                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php

}
