    <div class="westminster">
        <div class="person-header">
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
                     <span class="constituency"><?= $constituency ?></span> <span class="party <?= $party_short ?>"><?= $party ?></span>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                    <a href="#" class="button wtt"><img src="/style/img/envelope.png">Send a message</a>
                    <a href="#" class="button alert"><img src="/style/img/plus-circle.png">Get email updates</a>
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
                    <li>Overview</li>
                    <li>Voting Record</li>
                </ul>
            </div>
            <div class="person-content__header page-content__row">
                <h1>Overview</h1>
            </div>
            <div class="person-panels page-content__row">
                <div class="sidebar__unit in-page-nav">
                    <ul data-magellan-expedition="fixed">
                        <li data-magellan-arrival="votes"><a href="#votes">Votes</a></li>
                        <li data-magellan-arrival="appearances"><a href="#appearances">Appearances</a></li>
                        <li data-magellan-arrival="profile"><a href="#profile">Profile</a></li>
                        <li data-magellan-arrival="numerology"><a href="#numerology">Numerology</a></li>
                        <li data-magellan-arrival="register"><a href="#register">Register of Interests</a></li>
                    </ul>
                    <div>&nbsp;</div>
                </div>
                <div class="primary-content__unit">

                    <div class="panel">
                        <a name="votes"></a>
                        <h2 data-magellan-destination="votes">Voting Summary</h2>

                        <p><?= $rebellion_rate ?></p>

                        <h3>How <?= $full_name ?> voted on key issues<?= isset($key_votes['since_string']) ? $key_votes['since_string'] : '' ?></h3>

                        <?php if (count($key_votes['key_votes']) > 0): ?>

                            <ul>

                            <?php foreach ($key_votes['key_votes'] as $key_vote): ?>

                                <li><?= $key_vote ?></li>

                            <?php endforeach; ?>

                            </ul>

                            <?php if (isset($key_votes['more_link'])): ?>

                            <?= $key_votes['more_link'] ?>

                            <?php endif; ?>

                        <?php else: ?>

                            <p>No votes to display.</p>

                        <?php endif; ?>

                    </div>

                    <div class="panel">
                        <a name="appearances"></a>
                        <h2 data-magellan-destination="appearances">Recent appearances</h2>
                    </div>

                    <div class="panel">
                        <a name="profile"></a>
                        <h2 data-magellan-destination="profile">Profile</h2>

                        <p><?= $member_summary ?></p>

                        <?php if (count($useful_links) > 0): ?>

                        <ul>

                            <?php foreach ($useful_links as $link): ?>
                            <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <h3>Expenses</h3>

                        <p>Expenses data for MPs is availble from 2004 onwards split over several locations. At the moment we don't have the time to convert it to a format we can display on the site so we just have to point you to where you can find it.</p>

                        <ul>
                            <li><a href="<?= $expenses_url_2004 ?>">Expenses from 2004 to to 2009</a></li>
                            <li><a href="http://www.parliamentary-standards.org.uk/AnnualisedData.aspx">Expenses from 2010 onwards</a></li>
                        </ul>

                        <?php if (count($topics_of_interest) > 0): ?>

                        <h3>Topics of interest</h3>

                        <ul>

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

                        <ul>

                            <?php foreach ($constituency_previous_mps as $constituency_previous_mp): ?>
                            <li><a href="<?= $constituency_previous_mp['href'] ?>"><?= $constituency_previous_mp['text'] ?></a></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if (count($constituency_future_mps) > 0): ?>

                        <h3>Future MPs in this constituency</h3>

                        <ul>

                            <?php foreach ($constituency_future_mps as $constituency_future_mp): ?>
                            <li><a href="<?= $constituency_future_mp['href'] ?>"><?= $constituency_future_mp['text'] ?></a></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php endif; ?>

                        <?php if (count($public_bill_committees) > 0): ?>

                        <h3>Public bill committees <small>(Sittings attended)</small></h3>

                        <ul>

                            <?php foreach ($public_bill_committees as $committee): ?>
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

                        <ul>

                            <?php foreach ($numerology as $numerology_item): ?>
                            <li><?= $numerology_item ?></li>
                            <?php endforeach; ?>

                        </ul>

                        <?php else: ?>

                        <p>No information to display yet.</p>

                        <?php endif; ?>

                    </div>

                    <?php if ($register_interests): ?>
                    <div class="panel">
                        <a name="register"></a>
                        <h2 data-magellan-destination="register">Register of Interests</h2>

                        <div class="moreinfo">
                            <span class="moreinfo-text">
                            <? if ($register_interests['date']): ?>
                                <nobr>Register last updated: <?= $register_interests['date'] ?>.</nobr>
                            <? endif; ?>
                             More about the Register</span><a class="moreinfo-link" href="http://www.publications.parliament.uk/pa/cm/cmregmem/100927/introduction.htm"><img src="/images/questionmark.png" alt="" title=""></a>
                        </div>
                        <h2>Register of Members&rsquo; Interests</h2>
                        <?= $register_interests['data'] ?>
                        <p class="morelink"><strong>
                            <a href="<?= WEBPATH ?>regmem/?p=<?= $person_id ?>">View the history of this MP&rsquo;s entries in the Register</a>
                        </strong></p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
