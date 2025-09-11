<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="primary-content__unit">


                <?php if ($party == 'Sinn Féin' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php endif; ?>

                <?php $vote_count = isset($divisions) ? count($divisions) : 0;?>

                <div class="panel">
                    <div class="policy-votes-intro">
                        <h2><?= gettext('Recent Votes') ?></h2>
                        <?php if (in_array(HOUSE_TYPE_COMMONS, $houses)) { ?>
                            <?php if ($vote_count > 0) { ?>
                                <p>
                                    This page shows <?= $full_name ?>'s most recent <strong><?= $vote_count ?></strong> votes.
                                </p>
                                <p>
                                    For each vote you can see the vote in the context of the debate.
                                    If they spoke in the same section as the vote, links to the speeches will be listed under the vote.
                                </p>
                                <p>
                                    You can also see more analysis of individual votes through <a href="https://votes.theyworkforyou.com">TheyWorkForYou Votes</a>.
                                </p>
                                <p>
                                    For a longer-term view of <?= $full_name ?>'s voting across different policy areas, 
                                    see their <a href="<?= $member_url ?>/votes">voting summary</a>.
                                </p>
                            <?php } else { ?>
                                <p>
                                    This page shows <?= $full_name ?>'s most recent votes. 
                                    For a longer-term view of their voting patterns, see 
                                    <a href="<?= $member_url ?>/votes">voting summary</a>.
                                </p>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>

                <?php

$displayed_votes = false;
$current_date = '';
$sidebar_links = [];

if (isset($divisions) && $divisions) {
    foreach ($divisions as $division) {
        $displayed_votes = true;

        if ($current_date != $division['date']) {
            if ($current_date != '') {
                print('</ul></div>');
            }
            $current_date = $division['date'];
            $sidebar_links[] = $division['date'];
            ?>
                          <div class="panel" id="<?= strftime('%Y-%m-%d', strtotime($division['date'])) ?>">
                            <h2><?= strftime('%e %b %Y', strtotime($division['date'])) ?></h2>
                             <ul class="vote-descriptions policy-votes">
                          <?php }
        include('_division_description.php');
    }
    echo('</div>');
} ?>

                <?php if (!$displayed_votes) { ?>
                    <div class="panel">
                        <p><?= gettext('This person has not voted recently.') ?></p>
                    </div>
                <?php }
                include('_covid19_panel.php');
include('_profile_footer.php'); ?>
            </div>

            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php include '_person_navigation.php'; ?>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>
        </div>
    </div>
</div>
