    <div class="<?= $current_assembly ?>">
        <div class="person-header <?= $this_page ?>">
            <div class=" full-page__row">
            <div class="person-header__content page-content__row">
                <div class="person-name">
                    <h1>
                      <?php if ( $image ): ?>
                        <span class="mp-image">
                        <img src="<?= $image ?>" height="48">
                        </span>
                      <?php endif; ?>
                        <?= $full_name ?>
                    </h1>
                </div>
                <div class="person-constituency">
                    <?php if ( $constituency && $this_page != 'peer' && $this_page != 'royal' ): ?>
                      <span class="constituency"><?= $constituency ?></span>
                    <?php endif; ?>
                    <?php if ( $party ): ?>
                      <span class="party <?= $party_short ?>"><?= $party ?></span>
                    <?php endif; ?>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                  <?php if ($current_member_anywhere && $this_page != 'royal'): ?>
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
          <?php if ($has_voting_record): ?>
            <div class="person-navigation page-content__row">
                <ul>
                    <li class="active"><a href="<?= $member_url ?>">Overview</a></li>
                    <li><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
          <?php endif; ?>
            <div class="person-panels page-content__row">
                <div class="sidebar__unit in-page-nav">
                    <ul data-magellan-expedition="fixed">
                      <?php if ($has_voting_record): ?>
                        <li data-magellan-arrival="votes"><a href="#votes">Votes</a></li>
                      <?php endif; ?>
                      <?php if ($has_recent_appearances): ?>
                        <li data-magellan-arrival="appearances"><a href="#appearances">Appearances</a></li>
                      <?php endif; ?>
                        <li data-magellan-arrival="profile"><a href="#profile">Profile</a></li>
                        <li data-magellan-arrival="numerology"><a href="#numerology">Numerology</a></li>
                      <?php if ($register_interests): ?>
                        <li data-magellan-arrival="register"><a href="#register">Register of Interests</a></li>
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
                    <div class="panel vote-summary">
                        <a name="votes"></a>
                        <h2 data-magellan-destination="votes">Voting Summary <a class="vote-summary__more-link" href="<?= $member_url ?>/votes">Show full voting record</a></h2>

                      <?php if (count($policyPositions->positions) > 0 || count($hotTopicPositions->positions) > 0): ?>
                        <p class="vote-summary__intro">Here&rsquo;s a flavour of how <?= $full_name ?> voted on parliamentary bills<?= isset($policyPositions->sinceString) ? $policyPositions->sinceString : '' ?>. Vote strength is determined by the number of votes placed on a topic, and the number of votes missed or abstained.</p>
                      <?php endif; ?>

                        <div class="vote-summary__summaries">

                          <?php if (count($hotTopicPositions->positions) > 0): ?>
                            <div class="vote-summary__summaries__summary">
                                <h3>Hot Topics</h3>
                                <ul class="policies">
                                  <?php foreach ($hotTopicPositions->positions as $key_vote): ?>
                                    <li><?= $key_vote['desc'] ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $key_vote['policy_id'] ?>"></a></li>
                                  <?php endforeach; ?>
                                    <li class="vote-summary__next-step"><a href="<?= $member_url ?>/votes">See how <?= $full_name ?> voted</a> on other issues like health, welfare, foreign policy, social issues, and taxation.</li>
                                </ul>
                            </div>
                          <?php endif; ?>

                          <?php if (count($policyPositions->positions) > 0): ?>
                            <div class="vote-summary__summaries__summary">
                                <h3>Recently Added</h3>
                                <ul class="policies">
                                  <?php foreach ($policyPositions->positions as $key_vote): ?>
                                    <li><?= $key_vote['desc'] ?><a class="dream_details" href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $key_vote['policy_id'] ?>"></a></li>
                                  <?php endforeach; ?>
                                    <li class="vote-summary__next-step">
                                      <?php if ($has_email_alerts): ?>
                                        <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#">Get email notifications</a> when <?= $full_name ?> votes in parliament.
                                      <?php else: ?>
                                        <a href="<?= $member_url ?>/votes">See more votes</a> <?= $full_name ?> has made recently in parliament.</a>
                                      <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                          <?php endif; ?>

                        </div>

                        <p class="vote-summary__rebellion"><?= $full_name ?> <?= $rebellion_rate ?></p>

                    </div>
                  <?php endif; ?>

                  <?php if ($has_recent_appearances): ?>
                    <div class="panel">
                        <a name="appearances"></a>
                        <h2 data-magellan-destination="appearances">Recent appearances</h2>

                          <?php if (count($recent_appearances) > 0): ?>

                            <ul class="appearances">

                              <?php foreach ($recent_appearances['appearances'] as $recent_appearance): ?>

                                <li>
                                    <h4><a href="<?= $recent_appearance['listurl'] ?>"><?= $recent_appearance['parent']['body'] ?></a> <span class="date"><?= date('j M Y', strtotime($recent_appearance['hdate'])) ?></span></h4>
                                    <blockquote><?= $recent_appearance['extract'] ?></blockquote>
                                </li>

                              <?php endforeach; ?>

                            </ul>

                            <p><a href="<?= $recent_appearances['more_href'] ?>"><?= $recent_appearances['more_text'] ?></a></p>

                              <?php if (isset($recent_appearances['additional_links'])): ?>
                                <?= $recent_appearances['additional_links'] ?>
                              <?php endif; ?>

                          <?php else: ?>

                            <p>No recent appearances to display.</p>

                          <?php endif; ?>

                    </div>
                  <?php endif; ?>

                    <div class="panel">
                        <a name="profile"></a>
                        <h2 data-magellan-destination="profile">Profile</h2>

                        <p><?= $member_summary ?></p>

                      <?php if (count($useful_links) > 0): ?>

                        <ul class="comma-list">
                          <?php foreach ($useful_links as $link): ?>
                            <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                          <?php endforeach; ?>
                        </ul>

                      <?php endif; ?>

                      <?php if ($has_expenses): ?>
                        <h3>Expenses</h3>

                        <p>Expenses data for MPs is available from 2004 onwards
split over several locations. At the moment we don't have the time to convert
it to a format we can display on the site so we just have to point you to where
you can find it.</p>

                        <ul>
                            <li><a href="<?= $expenses_url_2004 ?>">Expenses from 2004 to 2009</a></li>
                            <li><a href="http://www.parliamentary-standards.org.uk/AnnualisedData.aspx">Expenses from 2010 onwards</a></li>
                        </ul>
                      <?php endif; ?>

                      <?php if (count($topics_of_interest) > 0): ?>

                        <h3>Topics of interest</h3>

                        <ul class="comma-list">
                          <?php foreach ($topics_of_interest as $topic): ?>
                            <li><?= $topic ?></li>
                          <?php endforeach; ?>
                        </ul>

                      <?php endif; ?>

                      <?php if (count($previous_offices) > 0): ?>

                        <h3>Other offices held in the past</h3>

                        <ul>
                          <?php foreach ($previous_offices as $office): ?>
                            <li><?= $office ?></li>
                          <?php endforeach; ?>
                        </ul>

                      <?php endif; ?>

                      <?php if (count($constituency_previous_mps) > 0): ?>

                        <h3>Previous MPs in this constituency</h3>

                        <ul class="comma-list">
                          <?php foreach ($constituency_previous_mps as $constituency_previous_mp): ?>
                            <li><a href="<?= $constituency_previous_mp['href'] ?>"><?= $constituency_previous_mp['text'] ?></a></li>
                          <?php endforeach; ?>
                        </ul>

                      <?php endif; ?>

                      <?php if (count($constituency_future_mps) > 0): ?>

                        <h3>Future MPs in this constituency</h3>

                        <ul class="comma-list">
                          <?php foreach ($constituency_future_mps as $constituency_future_mp): ?>
                            <li><a href="<?= $constituency_future_mp['href'] ?>"><?= $constituency_future_mp['text'] ?></a></li>
                          <?php endforeach; ?>
                        </ul>

                      <?php endif; ?>

                      <?php if (count($public_bill_committees['data']) > 0): ?>

                        <h3>Public bill committees <small>(Sittings attended)</small></h3>

                      <?php if ($public_bill_committees['info']): ?>
                        <p><em><?= $public_bill_committees['info'] ?></em></p>
                      <?php endif; ?>

                        <ul>
                          <?php foreach ($public_bill_committees['data'] as $committee): ?>
                            <li><a href="<?= $committee['href'] ?>"><?= $committee['text'] ?></a> (<?= $committee['attending'] ?>)</li>
                          <?php endforeach; ?>
                        </ul>

                        <?php endif; ?>

                    </div>

                    <div class="panel">
                        <a name="numerology"></a>
                        <h2 data-magellan-destination="numerology">Numerology</h2>

                      <?php if (count($numerology) > 0): ?>

                        <p>Please note that numbers do not measure quality. Also, representatives may do other things not currently covered by this site.<br><small><a href="<?= WEBPATH ?>help/#numbers">More about this</a></small></p>

                        <ul class="numerology">

                          <?php foreach ($numerology as $numerology_item): ?>
                            <?php if ($numerology_item): ?>
                            <li><?= $numerology_item ?></li>
                            <?php endif; ?>
                          <?php endforeach; ?>

                        </ul>

                      <?php else: ?>

                        <p>No information to display yet.</p>

                      <?php endif; ?>

                    </div>

                  <?php if ($register_interests): ?>
                    <div class="panel register">
                        <a name="register"></a>
                        <h2 data-magellan-destination="register">Register of Members&rsquo; Interests</h2>

                      <?php if ($register_interests['date']): ?>
                        <p>Last updated: <?= $register_interests['date'] ?>.</p>
                      <?php endif; ?>

                        <?= $register_interests['data'] ?>

                        <p>
                            <a href="<?= WEBPATH ?>regmem/?p=<?= $person_id ?>">View the history of this MP&rsquo;s entries in the Register</a>
                        </p>

                        <p>
                             <a class="moreinfo-link" href="http://www.publications.parliament.uk/pa/cm/cmregmem/100927/introduction.htm">More about the register</a>
                        </p>
                    </div>
                  <?php endif; ?>

                    <div class="about-this-page">
                        <div class="about-this-page__one-of-two">
                            <form class="panel--secondary mini-survey">
                                <h3>Did you find what you were looking for?</h3>
                                <p><label><input type="radio"> Yes, thanks!</label></p>
                                <p><label><input type="radio"> No, I didn&rsquo;t find it.</label></p>
                                <p><input type="submit" class="button wtt" value="Answer"></p>
                            </form>
                        </div>
                        <div class="about-this-page__one-of-two">
                            <div class="panel--secondary">
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
    </div>
</div>
