<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

// if this is set to a year for which we have WTT responsiveness stats then
// it'll display a banner with the MPs stats, assuming we have them for the
// year
$display_wtt_stats_banner = '2015';

?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php if (count($policyPositions->positions) > 0): ?>
            <div class="person-navigation">
                <ul>
                    <li class="active"><a href="<?= $member_url ?>">Overview</a></li>
                    <li><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                    <li><a href="<?= $member_url ?>/recent">Recent Votes</a></li>
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
                </ul>
                <div class="magellan-placeholder">&nbsp;</div>
            </div>
            <div class="primary-content__unit">

              <?php if ($profile_message): ?>
                <div id="profile-message" class="panel panel--profile-message">
                    <p><?= $profile_message ?></p>
                </div>
              <?php endif; ?>

              <?php if ($party == 'Sinn Féin' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
              <?php elseif (isset($is_new_mp) && $is_new_mp && count($recent_appearances['appearances']) == 0): ?>
                <div class="panel panel--secondary">
                    <h3><?= $full_name ?> is a recently elected MP &ndash; elected on <?= format_date($entry_date, LONGDATEFORMAT) ?></h3>

                    <p>When <?= $full_name ?> starts to speak in debates and vote on bills, that information will appear on this page.</p>

                  <?php if ($has_email_alerts) { ?>
                    <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#" onclick="trackLinkClick(this, 'Alert', 'Search', 'Person'); return false;">Sign up for email alerts to be the first to know when that happens.</a>
                  <?php } ?>
                </div>
              <?php endif; ?>

                <?php if ( !$current_member[HOUSE_TYPE_COMMONS] ) { ?>
                    <?php if (count($policyPositions->positions) > 0) { ?>
                    <div class="panel">
                        <a name="votes"></a>
                        <h2 data-magellan-destination="votes">A selection of <?= $full_name ?>'s votes</h2>

                        <p><a href="<?= $member_url ?>/votes">See full list of topics voted on</a></p>

                        <ul class="vote-descriptions">
                          <?php foreach ($policyPositions->positions as $key_vote) {

                            $description = ucfirst($key_vote['desc']);
                            $link = sprintf(
                                '%s/divisions?policy=%s',
                                $member_url,
                                $key_vote['policy_id']
                            );
                            $show_link = $key_vote['position'] != 'has never voted on';

                            include '_vote_description.php';

                          } ?>
                        </ul>

                        <p>We have <b>lots more</b> plain English analysis of <?= $full_name ?>&rsquo;s voting record  on issues like health, welfare, taxation and more. Visit <a href="<?= $member_url ?>/votes"><?= $full_name ?>&rsquo;s full vote analysis page</a> for more.</p>

                    </div>
                    <?php } ?>
                <?php } else if (count($policyPositions->positions) > 0 || count($sorted_diffs) > 0): ?>
                <div class="panel">
                    <a name="votes"></a>
                    <h2 data-magellan-destination="votes"><?= $full_name ?>&rsquo;s voting in Parliament</h2>


                    <?php if (count($sorted_diffs) > 0 && $party_member_count > 1): ?>

                        <p>
                        <?= $full_name ?> is a <?= $party ?> MP, and on the <b>vast majority</b> of issues votes the <b>same way</b> as other <?= $party ?> MPs.
                        </p>

                        <p>
                        However, <?= $full_name ?> sometimes <b>differs</b> from their party colleagues, such as:
                        </p>

                        <ul class="vote-descriptions">
                          <?php foreach ($sorted_diffs as $policy_id => $score) {

                            $key_vote = NULL;
                            $description = sprintf(
                                '%s <b>%s</b> %s, while most %s MPs <b>%s</b>.',
                                $full_name,
                                $positions[$policy_id]['position'],
                                strip_tags($policies[$policy_id]),
                                $party,
                                $party_positions[$policy_id]['position']
                            );
                            $link = $member_url . '/divisions?policy=' . $policy_id;
                            $show_link = true;

                            include '_vote_description.php';

                          } ?>
                        </ul>

                        <p>We have <b>lots more</b> plain English analysis of <?= $full_name ?>&rsquo;s voting record  on issues like health, welfare, taxation and more. Visit <a href="<?= $member_url ?>/votes"><?= $full_name ?>&rsquo;s full vote analysis page</a> for more.</p>

                    <?php elseif (count($policyPositions->positions) > 0 ): ?>
                        <?php if (count($party_positions) && $party_member_count > 1) { ?>
                        <p>
                        <?= $full_name ?> is a <?= $party ?> MP, and on the <b>vast majority</b> of issues votes the <b>same way</b> as other <?= $party ?> MPs.
                        </p>
                        <?php } ?>

                        <p>
                        This is a selection of <?= $full_name ?>&rsquo;s votes.
                        </p>

                        <ul class="vote-descriptions">
                          <?php foreach ($policyPositions->positions as $key_vote) {

                            $description = ucfirst($key_vote['desc']);
                            $link = sprintf(
                                '%s/divisions?policy=%s',
                                $member_url,
                                $key_vote['policy_id']
                            );
                            $show_link = $key_vote['position'] != 'has never voted on';

                            include '_vote_description.php';

                          } ?>
                        </ul>

                        <p>We have <b>lots more</b> plain English analysis of <?= $full_name ?>&rsquo;s voting record  on issues like health, welfare, taxation and more. Visit <a href="<?= $member_url ?>/votes"><?= $full_name ?>&rsquo;s full vote analysis page</a> for more.</p>
                    <?php elseif (count($policyPositions->positions) == 0 ): ?>

                        <p>No votes to display.</p>

                    <?php endif; ?>

                    <p><?= $full_name ?> <?= $rebellion_rate ?></p>
                    <small>Last updated: <?= format_date($policy_last_update['latest'], SHORTDATEFORMAT) ?></small>

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

                        <?php foreach ($useful_links as $link) {
                            // make an attempt at checking the link is valid
                            if (strpos($link['href'], 'http') === 0) {?>
                                <li><a href="<?= $link['href'] ?>"><?= $link['text'] ?></a></li>
                        <?php }
                        } ?>

                    </ul>

                    <?php endif; ?>

                    <?php if (count($social_links) > 0): ?>
                    <h3>Social Media</h3>
                    <ul>
                        <?php foreach ($social_links as $link): ?>
                        <li><a class="fi-social-<?= $link['type'] ?>" href="<?= $link['href'] ?>" onclick="trackLinkClick(this, 'Social-Link', '<?= $link['type'] ?>', '<?= $link['text'] ?>'); return false;"><?= $link['text'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>


                    <?php if ($has_expenses): ?>
                    <h3>Expenses</h3>

                    <ul>
                        <?php if ($pre_2010_expenses): ?>
                        <li><a href="<?= $expenses_url_2004 ?>">Expenses from 2004 to 2009</a></li>
                        <?php endif; ?>
                        <?php if ($post_2010_expenses): ?>
                        <li><a href="http://www.theipsa.org.uk/mp-costs/interactive-map/">Expenses from 2010 onwards</a></li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if (count($topics_of_interest) > 0 || $eu_stance): ?>

                    <h3>Topics of interest</h3>

                    <?php if ($eu_stance): ?>
                        <p>
                            <?php if ($eu_stance == 'Leave' || $eu_stance == 'Remain') { ?>
                                <strong><?= $full_name ?></strong> campaigned to <?= $eu_stance == 'Leave' ? 'leave' : 'remain in' ?> the European Union
                            <?php } else { ?>
                                We don't know whether <strong><?= $full_name ?></strong> campaigned to leave, or stay in the European Union
                            <?php } ?>
                            <small>Source: <a href="http://www.bbc.co.uk/news/uk-politics-eu-referendum-35616946">BBC</a></small>
                        </p>
                    <?php endif; ?>

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

                    <p>Please note that numbers do not measure quality. Also, representatives may do other things not currently covered by this site. <a href="<?= WEBPATH ?>help/#numbers">More about this</a></p>

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

                <div class="panel panel--secondary">
                    <p>Note for journalists and researchers: The data on this page may be used freely,
                       on condition that TheyWorkForYou.com is cited as the source.</p>

                    <p>This data was produced by TheyWorkForYou from a variety
                        of sources. Voting information from
                        <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/<?= $member_id ?>&amp;showall=yes">Public Whip</a>.</p>

                  <?php if($image && $image['exists']) {
                      if(isset($data['photo_attribution_text'])) { ?>
                    <p>
                      <?php if(isset($data['photo_attribution_link'])) { ?>
                        Profile photo:
                        <a href="<?= $data['photo_attribution_link'] ?>"><?= $data['photo_attribution_text'] ?></a>
                      <?php } else { ?>
                        Profile photo: <?= $data['photo_attribution_text'] ?>
                      <?php } ?>
                    </p>
                  <?php }
                  } else { ?>
                    <p>
                        We&rsquo;re missing a photo of <?= $full_name ?>.
                        If you have a photo <em>that you can release under
                        a Creative Commons Attribution-ShareAlike license</em>
                        or can locate a <em>copyright free</em> photo,
                        <a href="mailto:<?= str_replace('@', '&#64;', CONTACTEMAIL) ?>">please email it to us</a>.
                        Please do not email us about copyrighted photos
                        elsewhere on the internet; we can&rsquo;t use them.
                    </p>
                  <?php } ?>
                </div>

            </div>
        </div>
    </div>
</div>
