<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

// if this is set to a year for which we have WTT responsiveness stats then
// it'll display a banner with the MPs stats, assuming we have them for the
// year
$display_wtt_stats_banner = '2015';
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php include '_person_navigation.php'; ?>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php include '_donation_banner.php'; ?>

                <?php if (array_key_exists('letters_signed', $memberships) || array_key_exists('edms_signed', $memberships) || array_key_exists('annul_motions_signed', $memberships)): ?>
                    <div class="panel">
                    <h2 id="signatures"><?= gettext('Signatures') ?></h2>
                <?php endif; ?>
                <?php if (array_key_exists('annul_motions_signed', $memberships)): ?>
                    <h3 id="annul_motions_signed"><?=gettext('Recent motions to annul signed') ?></h3>
                    <p>Some kinds of government regulations automatically become law if Parliament does not object (negative statutory instrument). A motion to annul/prayer is a request to hold a vote to object. </p>
                    <p><em>Showing motions to annul signed in the last year.</em></p>
                    <ul class='list-dates'>
                        <?php foreach ($memberships['annul_motions_signed'] as $signature): ?>
                        <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures - 1 ?> others)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (array_key_exists('letters_signed', $memberships)): ?>
                    <h3 id="letters_signed"><?=gettext('Recent open letters signed') ?></h3>
                    <p>We're starting to collect and display when representatives sign open letters - if there are examples we're missing, <a href="https://survey.alchemer.com/s3/8440376/TheyWorkForYou-Open-Letter">please let us know</a>.</p>
                    <p><em>Showing open letters signed in the last year.</em></p>
                    <ul class='list-dates'>
                        <?php foreach ($memberships['letters_signed'] as $signature): ?>
                        <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures - 1 ?> others)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (array_key_exists('edms_signed', $memberships)): ?>
                    <h3 id="edms_signed"><?=gettext('Recent Early Day Motions signed') ?></h3>
                    <p>Early Day Motions are when MPs can propose and co-sign statements for discussion. In practice, these work as internal petitions where MPs can signal their support for a particular issue or cause.</p>
                    <p><em>Showing Early Day Motions signed in the last 3 months.</em></p>
                    <ul class='list-dates'>
                        <?php foreach ($memberships['edms_signed'] as $signature): ?>
                        <li><?= $signature->date ?>: <a href="<?= $signature->statement->link() ?>"><?= $signature->statement->title ?></a> (+<?= $signature->statement->total_signatures - 1  ?> others)</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (array_key_exists('edms_signed', $memberships) || array_key_exists('letters_signed', $memberships) || array_key_exists('annul_motions_signed', $memberships)): ?>
                    <p>
                    <a href="https://votes.theyworkforyou.com/person/<?= $person_id ?>/statements"><?= gettext('All open letters and EDMs signed') ?></a>.
                    </p>
                    <?php endif; ?>

                <?php if (array_key_exists('letters_signed', $memberships) || array_key_exists('edms_signed', $memberships) || array_key_exists('annul_motions_signed', $memberships)): ?>
                    </div>
                <?php endif; ?>

                <?php if (array_key_exists('topics_of_interest', $memberships) || array_key_exists('eu_stance', $memberships)): ?>
                    <div class="panel">
                        <h2 id="topics"><?= gettext('Topics of interest') ?></h2>

                        <?php if (array_key_exists('eu_stance', $memberships)): ?>
                            <p>
                                <?php if ($memberships['eu_stance'] == 'Leave' || $memberships['eu_stance'] == 'Remain') { ?>
                                    <strong><?= $full_name ?></strong> campaigned to <?= $memberships['eu_stance'] == 'Leave' ? 'leave' : 'remain in' ?> the European Union
                                <?php } else { ?>
                                    We don't know whether <strong><?= $full_name ?></strong> campaigned to leave, or stay in the European Union
                                <?php } ?>
                                <small>Source: <a href="https://www.bbc.co.uk/news/uk-politics-eu-referendum-35616946">BBC</a></small>
                            </p>
                        <?php endif; ?>

                        <?php if (array_key_exists('topics_of_interest', $memberships)): ?>
                            <ul class="comma-list">
                                <?php foreach ($memberships['topics_of_interest'] as $topic): ?>
                                <li><?= $topic ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
