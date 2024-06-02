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
            <?php include '_person_navigation.php'; ?>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <h3 class="browse-content"><?= gettext('Browse content') ?></h3>
                    <ul>
                        <li><a href="#profile"><?= gettext('Profile') ?></a></li>
                        <?php if (count($recent_appearances['appearances'])): ?>
                          <li><a href="#appearances"><?= gettext('Appearances') ?></a></li>
                        <?php endif; ?>
                      </ul>
                      <?php include '_featured_content.php'; ?>
                      <?php include '_donation.php'; ?>
                </div>
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
                    <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>#" onclick="trackLinkClick(this, 'alert_click', 'Search', 'Person'); return false;">Sign up for email alerts to be the first to know when that happens.</a>
                  <?php } ?>
                </div>
              <?php endif; ?>

                <?php include "_chamber_info_panel.php"; ?>

                <div class="panel">
                    <a name="profile"></a>
                    <h2><?=gettext('Profile') ?></h2>

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
                    <h3><?=gettext('Social Media') ?></h3>
                    <ul>
                        <?php foreach ($social_links as $link): ?>
                        <li><a class="fi-social-<?= $link['type'] ?>" href="<?= $link['href'] ?>" onclick="trackLinkClick(this, 'social_link', '<?= $link['type'] ?>', '<?= $link['text'] ?>'); return false;"><?= $link['text'] ?></a></li>
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
                        <li><a href="https://www.theipsa.org.uk/mp-costs/interactive-map/">Expenses from 2010 onwards</a></li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if (count($topics_of_interest) > 0 || $eu_stance): ?>

                    <h3><?= gettext('Topics of interest') ?></h3>

                    <?php if ($eu_stance): ?>
                        <p>
                            <?php if ($eu_stance == 'Leave' || $eu_stance == 'Remain') { ?>
                                <strong><?= $full_name ?></strong> campaigned to <?= $eu_stance == 'Leave' ? 'leave' : 'remain in' ?> the European Union
                            <?php } else { ?>
                                We don't know whether <strong><?= $full_name ?></strong> campaigned to leave, or stay in the European Union
                            <?php } ?>
                            <small>Source: <a href="https://www.bbc.co.uk/news/uk-politics-eu-referendum-35616946">BBC</a></small>
                        </p>
                    <?php endif; ?>

                    <ul class="comma-list">

                        <?php foreach ($topics_of_interest as $topic): ?>
                        <li><?= $topic ?></li>
                        <?php endforeach; ?>

                    </ul>

                    <?php endif; ?>

                    <?php if (count($current_offices) > 0): ?>

                    <h3><?=gettext('Currently held offices') ?></h3>

                    <ul class='list-dates'>

                        <?php foreach ($current_offices as $office): ?>
                        <li><?= $office ?> <small>(<?= $office->pretty_dates() ?>)</small></li>
                        <?php endforeach; ?>

                    </ul>

                    <?php endif; ?>

                    <?php if (count($previous_offices) > 0): ?>

                    <h3><?=gettext('Other offices held in the past') ?></h3>

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


                <?php if (count($recent_appearances['appearances'])): ?>
                <div class="panel">
                    <a name="appearances"></a>
                    <h2><?=gettext('Recent appearances') ?></h2>

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

                        <p><?=gettext('No recent appearances to display.') ?></p>

                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <div class="panel panel--secondary">
                    <p><?= gettext('Note for journalists and researchers: The data on this page may be used freely, on condition that TheyWorkForYou.com is cited as the source.') ?></p>

                    <p><?php print gettext('This data was produced by TheyWorkForYou from a variety of sources.') . ' ';
                        printf(gettext('Voting information from <a href="%s">Public Whip</a>.'), "https://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/ $member_id&amp;showall=yes"); ?></p>

                  <?php if($image && $image['exists']) {
                      if(isset($data['photo_attribution_text'])) { ?>
                    <p>
                      <?php if(isset($data['photo_attribution_link'])) { ?>
                        <?= gettext('Profile photo:') ?>
                        <a href="<?= $data['photo_attribution_link'] ?>"><?= $data['photo_attribution_text'] ?></a>
                      <?php } else { ?>
                        <?= gettext('Profile photo:') ?> <?= $data['photo_attribution_text'] ?>
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
