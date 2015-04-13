<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

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
                    <?php if ( isset($policydivisions) && $policydivisions && count($policydivisions) == 1 ) { ?>
                    <p class="policy-votes-intro">
                        How <?= $full_name ?> voted on <?= $policydivisions[array_keys($policydivisions)[0]]['desc'] ?>.
                    </p>
                    <?php } ?>
                    <ul>
                        <li><a href="<?= $member_url ?>/votes">Back to all topics</a></li>
                    </ul>
                </div>

                <div class="primary-content__unit">

                    <?php if ($party == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                    <div class="panel">
                        <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                    </div>
                    <?php endif; ?>

                    <?php $displayed_votes = FALSE; ?>
                    <?php if ( isset($policydivisions) && $policydivisions ) { ?>
                        <?php if ( $answered_q ) { ?>
                            <p class="panel panel--feedback">
                                Thanks for the feedback.
                            </p>
                        <?php } else { ?>
                            <form method="post" action="<?= OPTION_SURVEY_URL ?>" class="panel panel--feedback">
                                <input type="hidden" name="sourceidentifier" value="divisions-suggestions">
                                <input type="hidden" name="datetime" value="<?=time() ?>">
                                <input type="hidden" name="subgroup" value="0">

                                <input type="hidden" name="user_code" value="<?=$user_code ?>">
                                <input type="hidden" name="auth_signature" value="<?=$auth_signature ?>">

                                <input type="hidden" name="came_from" value="<?=$page_url ?>">
                                <input type="hidden" name="return_url" value="<?=$page_url ?>">
                                <p>
                                    <strong>This page is new!</strong>
                                    Is there anything else you&rsquo;d like to see on it?
                                </p>
                                <p>
                                    <input type="text" name="policy-page-suggestion" placeholder="I want to see&hellip;">
                                    <input type="submit" class="button small" value="Make it happen!">
                                </p>
                            </form>
                        <?php } ?>

                        <?php if ($has_voting_record) { ?>

                            <?php foreach ($policydivisions as $policy) { ?>

                                <?php if ( isset($policy['header']) ) { ?>
                                    <div class="panel policy-votes-hero" style="background-image: url('<?php echo $policy['header']['image']; ?>');">
                                        <h2><?php echo $policy['header']['title']; ?></h2>
                                        <p><?php echo $policy['header']['description']; ?>.</p>
                                        <?php if ( $policy['header']['image_source'] ) { ?>
                                        <span class="policy-votes-hero__image-attribution">
                                            Photo:
                                            <a href="<?php echo $policy['header']['image_source']; ?>">
                                                <?php echo $policy['header']['image_attribution']; ?>
                                            </a>
                                            <a href="<?php echo $policy['header']['image_license_url']; ?>">
                                                <?php echo $policy['header']['image_license']; ?>
                                            </a>
                                        </span>
                                        <?php } ?>
                                    </div>
                                <?php } ?>


                                <?php if ( isset($policy['position']) ) { ?>
                                    <div class="panel">
                                        <h3 class="policy-vote-overall-stance">
                                            <?= $full_name ?> <?= $policy['position']['voted'] == 'never voted' ? $policy['position']['voted'] . ' on' : 'voted ' .$policy['position']['voted'] ?> <?= $policy['desc'] ?>
                                        </h3>

                                        <?php if ( DEVSITE ) { ?>
                                        <p class="policy-vote-agree-disagree">
                                            <button class="button">I agree with this MP</button>
                                            <button class="button button--negative">I disagree with this MP</button>
                                        </p>
                                        <?php } ?>

                                        <h3 class="policy-votes-list-header"><span id="policy-votes-type">All</span> votes about <?= $policy['desc'] ?>:</h3>

                                        <ul class="vote-descriptions policy-votes">
                                        <?php
                                            $show_all = FALSE;
                                            if ( $policy['weak_count'] == count($policy['divisions']) ) {
                                                $show_all = TRUE;
                                            }
                                        ?>
                                        <?php foreach ($policy['divisions'] as $division) { ?>
                                            <li id="<?= $division['division_id'] ?>" class="<?= $division['strong'] || $show_all ? 'policy-vote--major' : 'policy-vote--minor' ?>">
                                                <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($division['date'])) ?>:</span>
                                                <span class="policy-vote__text"><?= $full_name ?><?= $division['text'] ?></span>
                                                <?php if ( $division['url'] ) { ?>
                                                    <a class="vote-description__source" href="<?= $division['url'] ?>">Show full debate</a>
                                                <?php } ?>
                                            </li>

                                        <?php $displayed_votes = TRUE; ?>

                                        <?php } ?>
                                        </ul>

                                        <div class="policy-votes-list-footer">
                                            <p class="policy-votes__byline">Vote information from <a href="http://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&dmp=<?= $policy['policy_id'] ?>">PublicWhip</a></p>
                                            <?php if ( !$show_all && $policy['weak_count'] > 0 ) { ?>
                                            <p><button class="button secondary-button small js-show-all-votes">Show all votes, including <?= $policy['weak_count'] ?> less important <?= $policy['weak_count'] == 1 ? 'vote' : 'votes' ?></button></p>
                                            <?php } ?>
                                        </div>

                                        <script type="text/javascript">
                                        $(function(){
                                            <?php if ( !$show_all ) { ?>
                                            $('#policy-votes-type').text('Key');
                                            <?php } ?>
                                            $('.js-show-all-votes').on('click', function(){
                                                $(this).fadeOut();
                                                $('.policy-vote--minor').slideDown();
                                                $('#policy-votes-type').text('All');
                                            });
                                        })
                                        </script>

                                    </div>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>

                    <?php } ?>

                    <?php if (!$displayed_votes) { ?>

                        <div class="panel">
                            <p>This person has not voted on this policy.</p>
                        </div>

                    <?php } ?>



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
