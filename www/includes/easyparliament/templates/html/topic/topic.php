<?php if ($topic->image_url()) { ?>
<div class="topic-header-wrapper" style="background-image: url(<?= $topic->image_url() ?>)">
<?php } ?>

<div class="topic-header">
    <div class="full-page">
        <div class="full-page__row">

            <div class="topic-name">
                <h1><?= _htmlspecialchars($topic->title()) ?></h1>
                <p class="lead"><?= _htmlspecialchars($topic->description()) ?></p>
            </div>

        </div>
    </div>
</div>

<?php if ($topic->image_url()) { ?>
</div>
<?php } ?>

<div class="full-page">
    <div class="full-page__row">

        <div class="small-12 large-3 large-push-9 columns">
          <?php if ($display_postcode_form): ?>
            <div class="topic-sidebar topic-postcode-search">
                <h3>How did your MP vote?</h3>
                <form action="#yourrep" method="get">
                    <label for="pc">Your postcode</label>
                    <input type="text" name="pc" id="pc" value="" maxlength="10" size="10" placeholder="eg. TF1 9QP">
                    <input type="submit" value="GO" class="button prefix">
                </form>
            </div>
          <?php endif; ?>

          <?php if ($topic->search_string()) { ?>
            <div class="topic-sidebar">
              <h3>
                  Get notifications about
                  <strong><?= _htmlspecialchars($topic->sctitle()) ?></strong>
              </h3>
              <a href="/alert/?alertsearch=<?= _htmlspecialchars($topic->search_string()) ?>" class="button expand">Set up an alert</a>
            </div>
          <?php } ?>
        </div>

        <div class="topic-content small-12 large-9 large-pull-3 columns">

          <?php if (isset($positions)): ?>
            <div class="topic-block policies">
                <h2 id="yourrep">How <?= $member_name ?> voted on <?= $topic->sctitle() ?></h2>

              <?php if ($total_votes == 0): ?>
                <p>
                    <a href="<?= $member_url ?>"><?= $member_name ?></a> hasn't
                    voted on any of the key issues on <?= _htmlspecialchars($topic->sctitle()) ?>. You may want
                    to <a href="<?= $member_url ?>/votes">see all their votes</a>.
                </p>

              <?php else: ?>
                <ul class="vote-descriptions">
                  <?php
                  $policy_ids = [];

                  foreach ($positions as $position) {
                      if (!in_array($position['policy_id'], $policy_ids)) {
                          $description = ucfirst($position['desc']);
                          $link = sprintf(
                              '%s/divisions?policy=%s',
                              $member_url,
                              $position['policy_id']
                          );
                          $link_text = $position['position'] != 'has never voted on' ? 'Show votes' : 'Details';
                          $key_vote = $position;
                          include(dirname(__DIR__) . '/mp/_vote_description.php');

                          $policy_ids[] = $position['policy_id'];
                      }
                  } ?>
                </ul>

              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if (isset($recent_divisions)): ?>
            <div class="topic-block policies">
                <h2>Recent votes on <?= _htmlspecialchars($topic->sctitle()) ?></h2>
                <ul class="vote-descriptions">
                  <?php foreach ($recent_divisions as $division) { ?>
                    <li id="<?= $division['division_id'] ?>">
                        The majority of MPs <?= $division['text'] ?>
                        <?php if (isset($division['debate_url'])) { ?>
                          <a class="vote-description__source" href="<?= $division['debate_url'] ?>">
                            Show debate
                          </a>
                          <a class="vote-description__evidence" href="<?= $division['debate_url'] ?>">
                            <?= format_date($division['date'], SHORTDATEFORMAT) ?>
                          </a>
                        <?php } else { ?>
                          <span class="vote-description__evidence">
                            <?= format_date($division['date'], SHORTDATEFORMAT) ?>
                          </span>
                        <?php } ?>
                    </li>
                  <?php } ?>
                </ul>
            </div>
          <?php endif; ?>

          <?php if ($business = $topic->getFullContent()): ?>
            <div class="topic-block policies">
                <h2>Parliamentary business about <?= _htmlspecialchars($topic->sctitle()) ?></h2>
                <ul class="business-list">
                  <?php foreach ($business as $item): ?>
                    <li>
                        <?php include(dirname(__DIR__) . '/section/_business_list_item.php'); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
            </div>
          <?php endif; ?>

        </div>
    </div>
</div>
