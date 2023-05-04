<div class="person-header <?= $current_member_anywhere ? '' : 'person-header--historical'; ?>">
    <div class="full-page__row">
        <div class="full-page__unit">
          <?php if ( $image ) { ?>
            <div class="person-header__image <?= $image['size'] == 'S' ? 'person-header__image--small' : '' ?>">
              <?php if ( $image['size'] == 'S' ) { ?>
                <span style="background-image: url('<?= $image['url'] ?>');"></span>
              <?php } ?>
                <img src="<?= $image['url'] ?>">
            </div>
          <?php } ?>
            <div class="person-header__about">
                <h1 class="person-header__about__name"><?= ucfirst($full_name) ?></h1>
              <?php if ( $known_for ) { ?>
                <p class="person-header__about__known-for">
                    <?= $known_for ?>
                </p>
              <?php } ?>
              <?php if ( $latest_membership && $latest_membership['house'] != HOUSE_TYPE_ROYAL ) { ?>
                <p class="person-header__about__position">
                    <span class="person-header__about__position__role">
                        <?= $latest_membership['current'] ? '' : 'Former' ?>
                        <?= $latest_membership['party'] == 'Bishop' ? '' : $latest_membership['party'] ?>
                        <?= $latest_membership['rep_name'] ?>
                    </span>
                  <?php if ( $latest_membership['constituency'] ) { ?>
                    for
                    <span class="person-header__about__position__constituency">
                        <?= $latest_membership['constituency'] ?>
                    </span>
                  <?php } ?>
                </p>
              <?php } ?>
              <?php if (count($social_links) > 0) { ?>
                <p class="person-header__about__media">
                  <?php foreach ($social_links as $link){ ?>
                      <a href="<?= $link['href'] ?>" onclick="trackLinkClick(this, 'social_link', '<?= $link['type'] ?>', '<?= $link['text'] ?>'); return false;"><?= $link['text'] ?></a>
                  <?php } ?>
                </p>
              <?php } ?>
            </div>
            <form class="person-header__search" action="<?= $search_url ?>" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                <div class="row collapse">
                    <div class="small-9 columns">
                        <input name="q" placeholder="Search this person’s speeches" type="search">
                    </div>
                    <div class="small-3 columns">
                        <button type="submit" class="prefix">Search</button>
                    </div>
                </div>
                <input type="hidden" name="pid" value="<?= $person_id ?>">
            </form>
          <?php if ($current_member_anywhere) { ?>
            <div class="person-header__actions">
              <?php if ($this_page != 'royal') {
                $wtt_url = 'https://www.writetothem.com/';
                if ($current_member[HOUSE_TYPE_LORDS]) {
                    $wtt_url = $wtt_url . "?person=uk.org.publicwhip/person/$person_id";
                } else if ($the_users_mp) {
                    $wtt_url = $wtt_url . "?a=WMC&amp;pc=" . _htmlentities(urlencode($user_postcode));
                }
              ?>
                <a href="<?= $wtt_url ?>" class="button" onclick="trackLinkClick(this, 'link_click', 'WriteToThem', 'Person'); return false;">Send a message</a>
              <?php } ?>
              <?php if ($has_email_alerts) { ?>
                <a href="<?= WEBPATH ?>alert/?pid=<?= $person_id ?>" class="button tertiary" onclick="trackLinkClick(this, 'alert_click', 'Search', 'Person'); return false;">Get email updates</a>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
    </div>
</div>
