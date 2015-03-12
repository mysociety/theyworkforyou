<?php

// I'm temporarily overriding the database, to display a set of votes about
// fox hunting, for demonstration purposes.

// It's worth bearing in mind that I'm only expecting this page to show votes
// on one policy at a time. But I'm retaining the convention of $policydivisions
// being an array of policies, because that's how the View is set up.

// Just remember that $policydivisions should have only one item in it.

$policydivisions = array(
    1050 => array(
        'policy_id' => '1050',
        'divisions' => array(
            array(
                'title' => 'Hunting with Dogs: Ban',
                'text' => 'voted to ban hunting with dogs instead of allowing it to continue under either a self-supervision or a licensing scheme.',
                'date' => '2002-03-18',
                'vote' => 'absent',
                'gid' => '2002-03-18.136.2',
                'url' => '/debates/?gid=2002-03-18.136.2',
                'strong' => True
            ),
            array(
                'title' => 'Hunting Bill &#8212; New Clause 6 &#8212; Use of Dogs Below Ground (No. 2)',
                'text' => 'voted to amend the Hunting Bill to allow the use of terriers for hunting underground.',
                'date' => '2003-06-30',
                'vote' => 'aye',
                'gid' => '2003-06-30.131.0',
                'url' => '/debates/?gid=2003-06-30.131.0',
                'strong' => False
            ),
            array(
                'title' => 'Hunting Bill &#8212; New Clause 11 &#8212; Registration in Respect of Hunting of Foxes',
                'text' => 'voted against an absolute ban on the hunting of foxes with dogs, whether registered or not.',
                'date' => '2003-06-30',
                'vote' => 'no',
                'gid' => '2003-06-30.135.2',
                'url' => '/debates/?gid=2003-06-30.135.2',
                'strong' => True
            ),
            array(
                'title' => 'Hunting Bill &#8212; New Clause 14 &#8212; Registration in Respect of Hunting of Mink',
                'text' => 'voted against an amendment to the Hunting Bill that would absolutely ban the hunting of mink with dogs.',
                'date' => '2003-06-30',
                'vote' => 'no',
                'gid' => '2003-06-30.139.0',
                'url' => '/debates/?gid=2003-06-30.139.0',
                'strong' => False
            ),
            array(
                'title' => 'Hunting Bill',
                'text' => 'voted against enacting the Hunting Bill, which would ban the hunting of almost all wild mammals.',
                'date' => '2004-09-15',
                'vote' => 'no',
                'gid' => '2004-09-15.1351.0',
                'url' => '/debates/?gid=2004-09-15.1351.0',
                'strong' => True
            )
        )
    )
);

$policyinformation = array(
    1050 => array(
        'title' => 'Hunting Ban',
        'description' => 'A vote for a hunting ban is a vote against people being allowed to hunt wild animals (primarily foxes) with hounds.',
        'image' => '/style/img/topics/hunting-ban.jpg',
        'image_attribution' => 'Not enough megapixels',
        'image_license' => 'CC BY-NC-ND 2.0',
        'image_license_url' => 'https://creativecommons.org/licenses/by-nc-nd/2.0/',
        'image_source' => 'https://www.flickr.com/photos/bamberry/6590260745'
    )
);

?>


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
                    <p class="policy-votes-intro">
                        How <?= $full_name ?> voted on <?= $policies[$policydivisions[array_keys($policydivisions)[0]]['policy_id']] ?>.
                    </p>
                    <ul>
                        <li><a href="/votes">Back to all topics</a></li>
                    </ul>
                </div>
                <div class="primary-content__unit">

                    <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                    <div class="panel">
                        <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                    </div>
                    <?php endif; ?>

                    <form action="some-action" class="panel panel--feedback">
                        <p>
                            <strong>This page is new!</strong>
                            Is there anything else you&rsquo;d like to see on it?
                        </p>
                        <p>
                            <input type="text" name="policy-page-suggestion" placeholder="I want to see&hellip;">
                            <input type="submit" class="button small" value="Make it happen!">
                        </p>
                    </form>

                    <?php if ($has_voting_record): ?>

                        <?php $displayed_votes = FALSE; ?>

                        <?php foreach ($policydivisions as $policy): ?>
                        <?php /* remember, $policydivisions should only contain one $policy */ ?>

                            <div class="panel policy-votes-hero" style="background-image: url('<?php echo $policyinformation[$policy['policy_id']]['image']; ?>');">
                                <h2><?php echo $policyinformation[$policy['policy_id']]['title']; ?></h2>
                                <p><?php echo $policyinformation[$policy['policy_id']]['description']; ?></p>
                                <span class="policy-votes-hero__image-attribution">
                                    Photo:
                                    <a href="<?php echo $policyinformation[$policy['policy_id']]['image_source']; ?>">
                                        <?php echo $policyinformation[$policy['policy_id']]['image_attribution']; ?>
                                    </a>
                                    <a href="<?php echo $policyinformation[$policy['policy_id']]['image_license_url']; ?>">
                                        <?php echo $policyinformation[$policy['policy_id']]['image_license']; ?>
                                    </a>
                                </span>
                            </div>

                            <div class="panel">
                                <h3 class="policy-vote-overall-stance">
                                    <?= $full_name ?> voted strongly against <?= $policies[$policy['policy_id']] ?>
                                </h3>

                                <ul class="vote-descriptions policy-votes">
                                <?php foreach ($policy['divisions'] as $division): ?>
                                    <li class="<?= $division['strong'] ? 'policy-vote--major' : 'policy-vote--minor' ?>">
                                        <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($division['date'])) ?>:</span>
                                        <span class="policy-vote__text"><?= $full_name ?> <?= $division['text'] ?></span>
                                        <?php if ( $division['gid'] ) { ?>
                                            <a class="vote-description__source" href="<?= $division['url'] ?>">Show full debate</a>
                                        <?php } ?>
                                    </li>

                                <?php $displayed_votes = TRUE; ?>

                                <?php endforeach; ?>
                                </ul>
                                <p class="policy-votes__byline">Vote information from <a href="http://www.publicwhip.org.uk">PublicWhip</a></p>
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
