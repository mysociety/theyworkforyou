    <div class="regional-header regional-header--<?= $current_assembly ?>">
        <div class="regional-header__overlay"></div>
        <div class="person-header <?= $this_page ?> <?= (isset($data['photo_attribution_text'])?'has-data-attribution':'') ?>">
            <div class=" full-page__row">
            <div class="person-header__content page-content__row">
                <div class="person-name">
                  <?php if ( $image ) { ?>
                    <div class="mp-image">
                        <img src="<?= $image['url'] ?>" height="48">
                    </div>
                  <?php } ?>
                    <div class="mp-name-and-position">
                        <h1><?= $full_name ?></h1>
                      <?php if ($current_position) { ?>
                         <p><?= $current_position ?></p>
                      <?php } else if ($former_position) { ?>
                         <p><?= $former_position ?></p>
                      <?php } ?>
                    </div>
                </div>
                <?php if($image && $image['exists']): ?>
                    <?php if(isset($data['photo_attribution_text'])) { ?>
                        <div class="person-data-attribution">
                          <?php if(isset($data['photo_attribution_link'])) { ?>
                            Profile photo: <a href="<?= $data['photo_attribution_link'] ?>"><?= $data['photo_attribution_text'] ?></a>
                          <?php } else { ?>
                            Profile photo: <?= $data['photo_attribution_text'] ?>
                          <?php } ?>
                        </div>
                    <?php } ?>
                <?php else: ?>
                    <div class="person-data-attribution">
                        We&rsquo;re missing a photo of <?= $full_name ?>. If you have a
                        photo <em>that you can release under a Creative Commons Attribution-ShareAlike
                        license</em> or can locate a <em>copyright free</em> photo,
                        <a href="mailto:<?= str_replace('@', '&#64;', CONTACTEMAIL) ?>">please email it to us</a>.
                        Please do not email us about copyrighted photos elsewhere on the internet; we can&rsquo;t
                        use them.
                    </div>
                <?php endif; ?>
                <div class="person-constituency">
                  <?php if ( $constituency && $this_page != 'peer' && $this_page != 'royal' ) { ?>
                    <span class="constituency"><?= $constituency ?></span>
                  <?php } ?>
                  <?php if ( $party ) { ?>
                    <span class="party <?= $party_short ?>"><?= $party ?></span>
                  <?php } ?>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                  <?php if ($current_member_anywhere && $this_page != 'royal') { ?>
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
