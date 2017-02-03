<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="person-navigation">
                <ul>
                    <li><a href="<?= $member_url ?>">Overview</a></li>
                    <li><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                    <li class="active"><a href="<?= $member_url ?>/recent">Recent Votes</a></li>
                </ul>
            </div>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <p class="policy-votes-intro">
                    Recent votes by <?= $full_name ?>.
                </p>
            </div>

            <div class="primary-content__unit">

                <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php endif; ?>

                <?php $displayed_votes = FALSE; $show_all = TRUE; $current_date = ''; ?>

                <?php if ( isset($divisions) && $divisions ) {
                    if ($has_voting_record) {
                        foreach ($divisions as $division) {
                          $displayed_votes = TRUE;

                          if ($current_date != $division['date']) {
                            if ($current_date != '' ) {
                              print('</ul></div>');
                            }
                            $current_date = $division['date']; ?>
                          <div class="panel">
                            <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($division['date'])) ?>:</span>
                             <ul class="vote-descriptions policy-votes"> 
                          <?php } ?>
                          <li id="<?= $division['division_id'] ?>" class="<?= $division['strong'] || $show_all ? 'policy-vote--major' : 'policy-vote--minor' ?>">
                              <span class="policy-vote__text"><?= $full_name ?><?= $division['text'] ?></span>
                              <?php if ( $division['url'] ) { ?>
                                  <a class="vote-description__source" href="<?= $division['url'] ?>">Show full debate</a>
                              <?php } ?>
                          </li>
                        <?php }
                        echo('</div>');
                    }
                } ?>


                <?php if (!$displayed_votes) { ?>

                    <div class="panel">
                        <p>This person has not voted recently.</p>
                    </div>

                <?php } ?>



                <div class="panel">
                    <p>Note for journalists and researchers: The data on this page may be used freely,
                       on condition that TheyWorkForYou.com is cited as the source.</p>

                    <p>For an explanation of the vote descriptions please see the FAQ entries on
                    <a href="/help/#vote-descriptions">vote descriptions</a> and
                    <a href="/help/#votingrecord">how the voting record is decided</a></p>
                </div>

            </div>
        </div>
    </div>
</div>
