<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="person-navigation">
                <ul>
                    <li><a href="<?= $member_url ?>">Overview</a></li>
                    <li class="active"><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                    <li><a href="<?= $member_url ?>/recent">Recent Votes</a></li>
                </ul>
            </div>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php if ( isset($policydivisions) && $policydivisions && count($policydivisions) == 1 ) { ?>
                        <p class="policy-votes-intro">
                            How <?= $full_name ?> voted on <?= $policydivisions[array_keys($policydivisions)[0]]['desc'] ?>.
                        </p>
                    <?php } ?>
                <h3 class="browse-content"><?= gettext('Browse content') ?></h3>          
                <?php
                    # because the loop below goes through all votes, we need to run through it first to
                    # see if there are any votes to display for the menu before the loop starts
                    if (isset($policydivisions) && $policydivisions && $has_voting_record) {

                        $has_strong_votes = false;
                        $has_weak_votes = false;
                        $has_strong_agreements = false;
                        $has_weak_agreements = false;
                        foreach ($policydivisions as $policy) { 
                                $current_policy_agreements = $policyagreements[$policy['policy_id']] ?? [];

                                foreach ($policy['divisions'] as $division) {
                                    if ($division['strong']) {
                                        $has_strong_votes = true;
                                    } else {
                                        $has_weak_votes = true;
                                    }
                                }

                                foreach ($current_policy_agreements as $agreement) {
                                    if ($agreement['strength'] == 'strong') {
                                        $has_strong_agreements = true;
                                    } else {
                                        $has_weak_agreements = true;
                                    }
                                }
                        }
                    }?>

                    
                    <ul>
                        <?php if ($has_strong_votes) { ?>
                            <li><a href="#scoring">Major votes</a></li>
                        <?php } ?>
                        <?php if ($has_strong_agreements) { ?>
                            <li><a href="#scoring-agreements">Scoring agreements</a></li>
                        <?php } ?>
                        <?php if ($has_weak_votes) { ?>
                            <li><a href="#informative">Minor votes</a></li>
                        <?php } ?>
                        <?php if ($has_weak_agreements) { ?>
                            <li><a href="#informative-agreements">Informative agreements</a></li>
                        <?php } ?>
                        <li><a href="<?= $member_url ?>/votes">Back to all topics</a></li>
                    </ul>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php if ($party == 'Sinn Féin' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php endif; ?>

                <?php $displayed_votes = false; ?>

                <?php
                    # $policydivisions contains all the relevant divisions for this MP
                    # $has_voting_record is true if the MP has voted on any policy
                    if (isset($policydivisions) && $policydivisions && $has_voting_record) {
                        # for some historical reason, the divisions page actually
                        # does all divisions, but generally this is an array of one
                        # (the current policy)
                        foreach ($policydivisions as $policy) { ?>
                        
                            <?php
                                $current_policy_agreements = $policyagreements[$policy['policy_id']] ?? [];
                                $divisions_scoring = [];
                                $divisions_informative = [];
                                $agreements_scoring = [];
                                $agreements_informative = [];
                                foreach ($policy['divisions'] as $division) {
                                    if ($division['strong']) {
                                        $divisions_scoring[] = $division;
                                    } else {
                                        $divisions_informative[] = $division;
                                    }
                                }

                                foreach ($current_policy_agreements as $agreement) {
                                    if ($agreement['strength'] == 'strong') {
                                        $agreements_scoring[] = $agreement;
                                    } else {
                                        $agreements_informative[] = $agreement;
                                    }
                                }

                            ?>

                            <?php
                                # a header dict is used to give human information about the specific policy
                                if ( isset($policy['header']) ) { ?>
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

                            <?php
                                # display the calculated position for the policy on this page
                                if ( isset($policy['position']) ) { ?>
                                <div class="panel">
                                    <?php if ( $policy['position']['has_strong'] ) { ?>
                                        <h3 class="policy-vote-overall-stance">
                                            <?= $full_name . ' ' . $policy['position']['desc'] ?>
                                        </h3>

                                        <?php $pw_url = 'https://www.publicwhip.org.uk/mp.php?mpid=' . $member_id . '&amp;dmp=' . $policy['policy_id']; ?>

                                        <p>
                                            To suggest amendments or new votes for inclusion, please <a href="https://survey.alchemer.com/s3/7724738/TheyWorkForYou-policy-update?policy_id=<?= $policy['policy_id'] ?>">use this feedback form</a>. 
                                        </p>

                                        <p>
                                            TheyWorkForYou has calculated this MP&rsquo;s stance based on votes we have grouped on this topic. <a href="/voting-information/#voting-summaries">Learn more</a>.
                                        </p>
                                        <p><a href="<?= $pw_url ?>">You can browse the source
                                            data on PublicWhip.org.uk</a>. We are in the process of moving away from using the PublicWhip and there may be some discrepancies between the two sites.  
                                        </p>

                                    <?php } else { ?>
                                        <h3 class="policy-vote-overall-stance">
                                            We don&rsquo;t have enough information to calculate <?= $full_name ?>&rsquo;s position on this issue
                                        </h3>

                                        <p>
                                        However, <?= $full_name ?> has taken part in the following votes on the topic:
                                        </p>
                                    <?php } ?>

                                <?php if ($divisions_scoring) { ?>
                                    <a name="scoring"></a>
                                    <h3 class="policy-votes-list-header">Major votes</h3>
                                    <ul class="vote-descriptions policy-votes">
                                    <?php foreach ($divisions_scoring as $division) {
                                        include('_division_description.php');
                                        $displayed_votes = true;
                                    } ?>
                                    </ul>
                                <?php } ?>
                                

                                    <?php if ($agreements_scoring) { ?>

                                        <a name="scoring-agreements"></a>
                                        <h3 class="policy-votes-list-header">Scoring Agreements</h3>
                                        <p>Agreements are when Parliament <a href="/voting-information/#agreements-and-divisions">takes a decision without holding a vote.</a></p>                                        <p>This does not necessarily mean universal approval, but does mean there were no (or few) objections made to the decision being made.</p>
                                        

                                    <p>The following agreements were made while this member was elected:</p>
                                    <ul class="vote-descriptions policy-votes">
                                        <?php foreach ($agreements_scoring as $division) {
                                            include('_agreement_description.php');
                                            $displayed_votes = true;
                                        } ?>
                                    </ul>
                                <?php } ?>
                                <?php if ($divisions_informative) { ?>
                                    <a name="informative"></a>
                                    <h3 class="policy-votes-list-header">Minor votes</h3>

                                    <ul class="vote-descriptions policy-votes">
                                        <?php foreach ($divisions_informative as $division) {
                                            include('_division_description.php');
                                            $displayed_votes = true;
                                        } ?>
                                    </ul>
                                <?php } ?>


                                    <?php if ($agreements_informative) { ?>

                                        <a name="informative-agreements"></a>
                                        <h3 class="policy-votes-list-header">Informative Agreements</h3>
                                        <p>Agreements are when Parliament <a href="/voting-information/#agreements-and-divisions">takes a decision without holding a vote.</a></p>
                                        <p>This does not necessarily mean universal approval, but does mean there were no (or few) objections made to the decision being made.</p>
                                        
                                    <ul class="vote-descriptions policy-votes">
                                        <?php foreach ($agreements_informative as $division) {
                                            include('_agreement_description.php');
                                            $displayed_votes = true;
                                        } ?>
                                    </ul>
                                <?php } ?>
                                    <div class="policy-votes-list-footer">
                                        <p class="voting-information-provenance">
                                            Vote information from <a href="https://www.publicwhip.org.uk/mp.php?mpid=<?= $member_id ?>&amp;dmp=<?= $policy['policy_id'] ?>">PublicWhip</a>.
                                            Last updated: <?= $policy_last_update[$policy['policy_id']] ?>.
                                            <a href="/voting-information">Learn more about our voting records and what they mean.</a>
                                        </p>
                                    </div>

                                </div>
                            <?php } ?>
                    <?php } ?>

                <?php } ?>

              <?php if ($profile_message): ?>
                <div class="panel panel--profile-message">
                    <p><?= $profile_message ?></p>
                </div>
              <?php endif; ?>

                <?php if (!$displayed_votes) { ?>

                    <div class="panel">
                        <p>This person has not voted on this policy.</p>
                    </div>

                <?php }
                include('_vote_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
