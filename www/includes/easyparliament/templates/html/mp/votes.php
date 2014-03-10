    <div class="<?= $current_assembly ?>">
        <div class="person-header <?= $this_page ?>">
            <div class=" full-page__row">
            <div class="person-header__content page-content__row">
                <div class="person-name">
                    <h1>
                        <?php if ( $image ) { ?>
                        <span class="mp-image">
                        <img src="<?= $image ?>" height="48">
                        </span>
                        <?php } ?>
                        <?= $full_name ?>
                    </h1>
                </div>
                <div class="person-constituency">
                     <?php if ( $constituency && $this_page != 'peer' && $this_page != 'royal' ) { ?><span class="constituency"><?= $constituency ?></span> <?php } ?><span class="party <?= $party_short ?>"><?= $party ?></span>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                    <?php if ($current_member_anywhere): ?>
                    <a href="https://www.writetothem.com/<?php
                        if ($current_member[HOUSE_TYPE_LORDS]) {
                            echo "?person=uk.org.publicwhip/person/$person_id";
                        }
                        if ($the_users_mp) {
                            echo "?a=WMC&amp;pc=" . htmlentities(urlencode($user_postcode));
                        }
                    ?>" class="button wtt" onclick="trackLinkClick(this, 'Links', 'WriteToThem', 'Person'); return false;"><img src="/style/img/envelope.png">Send a message</a>

                    <?php endif; ?>
                    <?php if ($has_email_alerts): ?>
                    <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#" class="button alert" onclick="trackLinkClick(this, 'Alert', 'Search', 'Person'); return false;"><img src="/style/img/plus-circle.png">Get email updates</a>
                    <?php endif; ?>
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
                        <li data-magellan-arrival="key"><a href="#key">Key Issues</a></li>
                    </ul>
                    <div>&nbsp;</div>
                </div>
                <div class="primary-content__unit">

                    <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                    <div class="panel">
                        <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                    </div>
                    <?php endif; ?>

                    <div class="panel">

                        <a name="key"></a>
                        <h2 data-magellan-destination="key">Key Issues</h2>

                        <?php if ($has_voting_record): ?>

                            <p><?= $rebellion_rate ?></p>

                            <h3>How <?= $full_name ?> voted on key issues<?= isset($key_votes['since_string']) ? $key_votes['since_string'] : '' ?></h3>

                            <?php if (count($key_votes['key_votes']) > 0): ?>

                                <ul class="policies">

                                <?php foreach ($key_votes['key_votes'] as $key_vote): ?>

                                <li><?= $key_vote['desc'] ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $key_vote['policy_id'] ?>">Details</a></li>

                                <?php endforeach; ?>

                                </ul>

                                <?php if (isset($key_votes['more_link'])): ?>

                                <p><?= $key_votes['more_link'] ?></p>

                                <?php endif; ?>

                            <?php else: ?>

                                <p>No votes to display.</p>

                            <?php endif; ?>

                        <?php else: ?>

                        <p>This person does not have a voting record.</p>

                        <?php endif; ?>

                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
