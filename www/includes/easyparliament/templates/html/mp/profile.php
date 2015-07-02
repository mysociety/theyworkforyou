<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php if (count($policyPositions->positions) > 0): ?>
            <div class="person-navigation">
                <ul>
                    <li class="active"><a href="<?= $member_url ?>">Overview</a></li>
                    <li><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <ul data-magellan-expedition="fixed">
                  <?php if (count($policyPositions->positions) > 0): ?>
                    <li data-magellan-arrival="votes"><a href="#votes">Votes</a></li>
                  <?php endif; ?>
                  <?php if (count($recent_appearances['appearances'])): ?>
                    <li data-magellan-arrival="appearances"><a href="#appearances">Appearances</a></li>
                  <?php endif; ?>
                    <li data-magellan-arrival="profile"><a href="#profile">Profile</a></li>
                  <?php if (count($numerology) > 0): ?>
                    <li data-magellan-arrival="numerology"><a href="#numerology">Numerology</a></li>
                  <?php endif; ?>
                  <?php if ($register_interests): ?>
                    <li data-magellan-arrival="register"><a href="#register">Register of Interests</a></li>
                  <?php endif; ?>
                    <li hidden id="research-quant2-bucket2"><a href="/action/where-next">What to do with the information on this page?</a></li>
                </ul>
                <div class="magellan-placeholder">&nbsp;</div>
            </div>
            <div class="primary-content__unit">

                <?php if (($party == 'Sinn Fein' || $party == utf8_decode('Sinn Féin')) && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php elseif (isset($is_new_mp) && $is_new_mp && count($recent_appearances['appearances']) == 0): ?>
                <div class="panel--secondary">
                    <h3><?= $full_name ?> is a recently elected MP &ndash; elected on <?= format_date($entry_date, LONGDATEFORMAT) ?></h3>

                    <p>When <?= $full_name ?> starts to speak in debates and vote on bills, that information will appear on this page.</p>

                  <?php if ($has_email_alerts) { ?>
                    <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#" onclick="trackLinkClick(this, 'Alert', 'Search', 'Person'); return false;">Sign up for email alerts to be the first to know when that happens.</a>
                  <?php } ?>
                </div>
                <?php endif; ?>

                <?php if (count($policyPositions->positions) > 0): ?>
                <div class="panel">
                    <a name="votes"></a>
                    <h2 data-magellan-destination="votes">A selection of <?= $full_name ?>'s votes</h2>

                    <p><a href="<?= $member_url ?>/votes">See full list of topics voted on</a></p>

                    <?php if (count($policyPositions->positions) > 0): ?>

                        <ul class="vote-descriptions">
                          <?php foreach ($policyPositions->positions as $key_vote): ?>
                            <li>
                                <?= ucfirst($key_vote['desc']) ?>
                                <a class="vote-description__source" href="<?= $member_url?>/divisions?policy=<?= $key_vote['policy_id'] ?>">Show votes</a>
                            </li>
                          <?php endforeach; ?>
                        </ul>

                        <p>New: more <a href="<?= $member_url ?>/votes">analysis of votes</a> on <a href="<?= $member_url ?>/votes#health">health</a>, <a href="<?= $member_url ?>/votes#welfare">welfare</a>, <a href="<?= $member_url ?>/votes#foreign">foreign policy</a>, <a href="<?= $member_url ?>/votes#social">social issues</a>, <a href="<?= $member_url ?>/votes#taxation">taxation</a> and more.</p>

                    <?php else: ?>

                        <p>No votes to display.</p>

                    <?php endif; ?>

                    <p><?= $full_name ?> <?= $rebellion_rate ?></p>

                </div>
                <?php endif; ?>

                <?php if (count($recent_appearances['appearances'])): ?>
                <div class="panel">
                    <a name="appearances"></a>
                    <h2 data-magellan-destination="appearances">Recent appearances</h2>

                    <?php if (count($recent_appearances['appearances']) > 0): ?>

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

                    <?php if (count($enter_leave) > 0): ?>
                        <?php foreach ($enter_leave as $string): ?>
                            <p><?= $string ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($other_parties): ?>
                    <p><?= $other_parties ?></p>
                    <?php endif; ?>

                    <?php if ($other_constituencies): ?>
                    <p><?= $other_constituencies ?></p>
                    <?php endif; ?>

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

                    <?php if (count($current_offices) > 0): ?>

                    <h3>Currently held offices</h3>

                    <ul class='list-dates'>

                        <?php foreach ($current_offices as $office): ?>
                        <li><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></li>
                        <?php endforeach; ?>

                    </ul>

                    <?php endif; ?>

                    <?php if (count($previous_offices) > 0): ?>

                    <h3>Other offices held in the past</h3>

                    <ul class='list-dates'>

                        <?php foreach ($previous_offices as $office): ?>
                        <li><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></li>
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

                <?php if (count($numerology) > 0): ?>

                <div class="panel">
                    <a name="numerology"></a>
                    <h2 data-magellan-destination="numerology">Numerology</h2>

                    <p>Please note that numbers do not measure quality. Also, representatives may do other things not currently covered by this site.<br><small><a href="<?= WEBPATH ?>help/#numbers">More about this</a></small></p>

                    <ul class="numerology">

                        <?php foreach ($numerology as $numerology_item): ?>
                        <?php if ($numerology_item): ?>
                        <li><?= $numerology_item ?></li>
                        <?php endif; ?>
                        <?php endforeach; ?>

                    </ul>

                </div>

                <?php endif; ?>

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
                    <div class="about-this-page__one-of-one">
                        <div class="panel--secondary">
                            <p>Please feel free to use the data on this page, but if
                                you do you must cite TheyWorkForYou.com in the body
                                of your articles as the source of any analysis or
                                data you get off this site.</p>

                            <p>This data was produced by TheyWorkForYou from a variety
                                of sources. Voting information from
                                <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/<?= $member_id ?>&amp;showall=yes">Public Whip</a>.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
