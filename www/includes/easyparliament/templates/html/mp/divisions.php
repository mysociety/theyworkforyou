
    <div class="<?= $current_assembly ?>">
        <div class="person-header <?= $this_page ?>">
            <div class=" full-page__row">
            <div class="person-header__content page-content__row">
                <div class="person-name">
                  <?php if ( $image['exists'] ) { ?>
                    <div class="mp-image">
                        <img src="<?= $image['url'] ?>" height="48">
                    </div>
                  <?php } ?>
                    <div class="mp-name-and-position">
                        <h1><?= $full_name ?></h1>
                      <?php if ( $current_position ) { ?>
                         <p><?= $current_position ?></p>
                      <?php } else if ( $former_position ) { ?>
                         <p><?= $former_position ?></p>
                      <?php } ?>
                    </div>
                </div>
                <div class="person-constituency">
                   <?php if ( $constituency && $this_page != 'peer' && $this_page != 'royal' ): ?>
                     <span class="constituency"><?= $constituency ?></span>
                   <?php endif; ?>
                     <span class="party <?= $party_short ?>"><?= $party ?></span>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                  <?php if ($current_member_anywhere) { ?>
                    <a href="https://www.writetothem.com/<?php
                        if ($current_member[HOUSE_TYPE_LORDS]) {
                            echo "?person=uk.org.publicwhip/person/$person_id";
                        }
                        if ($the_users_mp) {
                            echo "?a=WMC&amp;pc=" . _htmlentities(urlencode($user_postcode));
                        }
                    ?>" class="button wtt" onclick="trackLinkClick(this, 'Links', 'WriteToThem', 'Person'); return false;"><img src="/style/img/envelope.png">Send a message</a>

                  <?php } ?>
                  <?php if ($has_email_alerts) { ?>
                    <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#" class="button alert" onclick="trackLinkClick(this, 'Alert', 'Search', 'Person'); return false;"><img src="/style/img/plus-circle.png">Get email updates</a>
                  <?php } ?>
                </div>
            </div>
            </div>
        </div>
    </div>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="person-navigation page-content__row">
                <ul>
                    <li><a href="<?= $member_url ?>">Overview</a></li>
                    <li class="active"><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
            <div class="person-panels page-content__row">
                <div class="sidebar__unit in-page-nav">
                    <ul data-magellan-expedition="fixed">
                        <?php if ($has_voting_record): ?>
                        <?php endif; ?>
                    </ul>
                    <div>&nbsp;</div>
                </div>
                <div class="primary-content__unit">

                    <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                    <div class="panel">
                        <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_voting_record): ?>

                        <?php $displayed_votes = FALSE; ?>

                        <?php foreach ($policydivisions as $policy): ?>
                                <div class="panel">
                                <h2 id="<?= $policy['policy_id'] ?>" data-magellan-destination="<?= $policy['policy_id'] ?>">
                                    How <?= $full_name ?> voted on <?= $policies[$policy['policy_id']] ?>
                                </h2>
                                <ul class="vote-descriptions">


                                <?php foreach ($policy['divisions'] as $division): ?>
                                    <li>
                                    <a href="<?= $division['url'] ?>"><?= $division['text'] ?></a>: <?= $division['vote'] ?> : <?= $division['gid'] ?>
                                    </li>


                                <?php $displayed_votes = TRUE; ?>

                                <?php endforeach; ?>
                                </ul>
                                </div>
                        <?php endforeach; ?>

                        <?php if (!$displayed_votes): ?>

                            <div class="panel">
                                <p>This person has not voted on any of the key issues which we keep track of.</p>
                            </div>

                        <?php endif; ?>

                    <?php endif; ?>


                    <div class="panel">
                        <p>Please feel free to use the data on this page, but if
                            you do you must cite TheyWorkForYou.com in the body
                            of your articles as the source of any analysis or
                            data you get off this site.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
