<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

# fetch covid_policy_list
$policies_obj = new MySociety\TheyWorkForYou\Policies();
$covid_policy_list = $policies_obj->getCovidAffected();
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
                        <?php if ($has_voting_record): ?>
                        <?php foreach ($key_votes_segments as $segment): ?>
                        <?php if (count($segment['votes']->positions) > 0): ?>
                        <li><a href="#<?= $segment['key'] ?>"><?= $segment['title'] ?></a></li>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>
            <div class="primary-content__unit">

                <?php if ($profile_message): ?>
                <div class="panel panel--profile-message">
                    <p><?= $profile_message ?></p>
                </div>
                <?php endif; ?>


                <div class="panel">
                    <h2>Voting summaries</h2>
                    <p>
                        MPs have many roles, but one of the most important is that they make decisions. These decisions shape the laws that govern us, and can affect every aspect of how we live our lives. 
                        One of the ways MPs make decisions is by voting.
                    </p>
                    <p>
                        On TheyWorkForYou, we create voting summaries that group a set of decisions together, show how an MP has generally voted on a set of related votes, and if they differ from their party.
                    </p>
                    <p>
                        You can see these groups, randomly ordered, below.
                    </p>
                    <p>
                        You can read more about <a href="/voting-information/#voting-summaries">how this works</a>, <a href="/voting-information/#what-kind-of-votes-are-included-in-theyworkforyou-s-policies">the kinds of votes we include</a>, <a href="/voting-information/#comparison-to-parties">how we compare MPs to parties</a>, and <a href="/voting-information/#votes-are-not-opinions-but-they-matter">why we think this is important</a>.</a>
                    </p>
                    <hr />
                    <p>
                        These summaries are created by the team at TheyWorkForYou. We are independent of Parliament and receive no public funding for this work.
                    </p>
                    <p>
                        If you want to support and help us improve these summaries, please consider <a href="https://www.mysociety.org/donate?utm_source=theyworkforyou.com&utm_content=vote-desc&utm_medium=link&utm_campaign=voting_page">donating</a>.
                    </p>
                    <div class="inline-donation-box">
                        <a href="/support-us/?how-often=annually&how-much=10" class="button">Donate £10 a year</a>
                        <a href="/support-us/?how-often=one-off&how-much=25" class="button" >Donate £25 once</a>
                        <a href="/support-us/" class="button">Donate another amount</a>
                    </div>
                    <p>Learn more about <a href="/support-us/#why-does-mysociety-need-donations-for-these-sites">how we'll use your donation</a> and <a href="/support-us/#i-want-to-be-a-mysociety-supporter">other ways to help</a>.</p>
                </div>

                <?php if ($party_switcher == true): ?>
                    <?php include('_cross_party_mp_panel.php'); ?>
                <?php endif; ?>

                <?php if ($party == 'Sinn Féin' && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php elseif (isset($is_new_mp) && $is_new_mp && !$has_voting_record): ?>
                <div class="panel panel--secondary">
                    <h3><?= $full_name ?> is a recently elected MP - elected on <?= format_date($entry_date, LONGDATEFORMAT) ?></h3>

                    <p>When <?= $full_name ?> starts to vote on bills, that information will appear on this page.</p>
                </div>
                <?php endif; ?>

                <?php if ($party_member_count > 1) { ?>
                <div class="panel">
                    <a name="votes"></a>
                    <h2><?= $full_name ?>&rsquo;s voting in Parliament</h2>

                    <?php if ($party_switcher == true or $current_member[HOUSE_TYPE_COMMONS] == false) { ?>
                        <p> 
                        <?= $full_name ?> was previously a <?= $unslugified_comparison_party ?> MP, and on the <b>vast majority</b> of issues <a href="/voting-information/#party-and-individual-responsibility-for-decisions">would have followed instructions from their party</a> and voted the <b>same way</b> as <?= $unslugified_comparison_party ?> MPs.
                        </p>
                    <?php } else { ?>
                        <p> 
                        <?= $full_name ?> is a <?= $unslugified_comparison_party ?> MP, and on the <b>vast majority</b> of issues <a href="/voting-information/#party-and-individual-responsibility-for-decisions">follow instructions from their party</a> and vote the <b>same way</b> as other <?= $unslugified_comparison_party ?> MPs.
                        </p>
                    <?php } ?>

                    
                    <?php if (count($sorted_diffs_only) > 0) { ?>
                    <?php if ($party_switcher == true) { ?>
                        <p>
                        However, <?= $full_name ?> sometimes <b>differs</b> from <?= $unslugified_comparison_party ?> MPs, such as:
                        </p>
                    <?php } else { ?>
                        <p>
                        However, <?= $full_name ?> sometimes <b>differs</b> from their party colleagues, such as:
                        </p>
                    <?php } ?>

                    <ul class="vote-descriptions">
                      <?php foreach ($sorted_diffs_only as $policy_id => $diff) {

                          $key_vote = $diff;
                          $covid_affected = in_array($policy_id, $covid_policy_list);
                          $policy_desc = strip_tags($key_vote['policy_text']);
                          $policy_direction = $key_vote["person_position"];
                          $policy_group = "highlighted";
                          $party_score_difference = $key_vote["score_difference"];
                          $party_position = $key_vote['party_position'] ;
                          $comparison_party = $data["comparison_party"];
                          $current_party_comparison = $data["current_party_comparison"];
                          $unslugified_comparison_party = ucwords(str_replace('-', ' ', $comparison_party));

                          if (strlen($unslugified_comparison_party) == 3) {
                              $unslugified_comparison_party = strtoupper($comparison_party);
                          }
                          $description = sprintf(
                              '%s <b>%s</b> %s; comparable %s MPs <b>%s</b>.',
                              $full_name,
                              $diff['person_position'],
                              strip_tags($diff['policy_text']),
                              $unslugified_comparison_party,
                              $diff['party_position']
                          );
                          $link = $member_url . '/divisions?policy=' . $policy_id;
                          $link_text = 'Show votes';

                          include '_vote_description.php';

                      } ?>
                    </ul>

                    <?php } ?>

                <?php if ($rebellion_rate) { ?>
                    <p><?= $full_name ?> <?= $rebellion_rate ?></p>
                <?php } ?>

                </div>
                <?php } ?>

                <?php if ($has_voting_record): ?>
                    
                    <?php $policies_obj = new MySociety\TheyWorkForYou\Policies(); ?>
                    <?php $covid_policy_list = $policies_obj->getCovidAffected(); ?>

                    <?php $displayed_votes = false; ?>

                    <?php foreach ($key_votes_segments as $segment): ?>

                        <?php if (count($segment['votes']->positions) > 0): ?>
                        <?php $most_recent = ''; ?>

                        <div class="panel">

                            <h2 id="<?= $segment['key'] ?>">
                                How <?= $full_name ?> voted on <?= $segment['title'] ?>
                                <small><a class="nav-anchor" href="<?= $member_url ?>/votes#<?= $segment['key'] ?>">#</a></small>
                            </h2>

                            <p>For votes held while they were in office:</p>

                            <ul class="vote-descriptions">
                              <?php foreach ($segment['votes']->positions as $key_vote) {
                                  $policy_id = $key_vote['policy_id'];
                                  $covid_affected = in_array($policy_id, $covid_policy_list);
                                  $policy_desc = strip_tags($key_vote['policy']);
                                  $policy_direction = $key_vote["position"];
                                  $policy_group = $segment['key'];

                                  if (isset($policy_last_update[$policy_id]) && $policy_last_update[$policy_id] > $most_recent) {
                                      $most_recent = $policy_last_update[$policy_id];
                                  }

                                  if ($key_vote['has_strong'] || $key_vote['position'] == 'has never voted on') {
                                      $description = ucfirst($key_vote['desc']);
                                  } else {
                                      $description = sprintf(
                                          'We don&rsquo;t have enough information to calculate %s&rsquo;s position on %s.',
                                          $full_name,
                                          $key_vote['policy']
                                      );
                                  }
                                  $link = sprintf(
                                      '%s/divisions?policy=%s',
                                      $member_url,
                                      $policy_id
                                  );
                                  $link_text = $key_vote['position'] != 'has never voted on' ? 'Show votes' : 'Details';
                                  $comparison_party = $data["comparison_party"];

                                  # Unslugify for display
                                  $unslugified_comparison_party = ucwords(str_replace('-', ' ', $comparison_party));

                                  if (strlen($unslugified_comparison_party) == 3) {
                                      $unslugified_comparison_party = strtoupper($comparison_party);
                                  }
                                  $min_diff_score = 0; // setting this to 0 means that all comparisons are displayed.
                                  if (isset($sorted_diffs[$policy_id])) {
                                      $diff = $sorted_diffs[$policy_id];
                                      $party_position = $diff['party_position'];
                                      $party_score_difference = $diff["score_difference"];
                                      if ($sorted_diffs[$policy_id]['score_difference'] > $min_diff_score && $party_member_count > 1) {
                                          $party_voting_line = sprintf('Comparable %s MPs %s.', $unslugified_comparison_party, $diff['party_position']);
                                      }
                                  } else {
                                      $party_voting_line = null;
                                      $party_position = null;
                                      $party_score_difference = null;
                                  }

                                  include '_vote_description.php';

                              } ?>
                            </ul>

                            <div class="share-vote-descriptions">
                                <p>Share a <a href="<?= $abs_member_url ?>/policy_set_png?policy_set=<?= $segment['key'] ?>">screenshot</a> of these votes:</p>

                                <a href="#" class="facebook-share-button js-facebook-share" data-text="<?= $single_policy_page ? '' : $segment['title'] . ' ' ?><?= $page_title ?>" data-url="<?= $abs_member_url ?>/votes?policy=<?= $segment['key'] ?>">Share</a>

                                <a class="twitter-share-button" href="https://twitter.com/share" data-size="small" data-url="<?= $abs_member_url ?>/votes?policy=<?= $segment['key'] ?>">Tweet</a>
                            </div>

                            <p class="voting-information-provenance">
                                Last updated: <?= format_date($most_recent, LONGDATEFORMAT) ?>.
                                <a href="/voting-information">Learn more about our voting records and what they mean.</a>
                            </p>

                        </div>

                            <?php $displayed_votes = true; ?>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if ($displayed_votes): ?>

                        <?php if ($segment['votes']->moreLinksString): ?>

                            <div class="panel">
                                <p><?= $segment['votes']->moreLinksString ?></p>
                            </div>

                        <?php endif; ?>

                    <?php else: ?>

                        <div class="panel">
                            <p>This person has not voted on any of the key issues which we keep track of.</p>
                        </div>

                    <?php endif; ?>

                <?php endif; ?>
                <?php include('_covid19_panel.php'); ?>

                <?php include('_profile_footer.php'); ?>
            </div>
        </div>
    </div>
</div>
