<?php if ($newsletter_item) { ?>
<form method="post" class="sidebar__unit__featured_side sidebar__newsletter-form" action="//mysociety.us9.list-manage.com/subscribe/post?u=53d0d2026dea615ed488a8834&amp;id=287dc28511" onsubmit="trackFormSubmit(this, 'FooterNewsletterSignup', 'submit', null); return false;">
  <?php if (isset($newsletter_item->thumbnail_image_url)) { ?>
  <img class="featured_side__image" src="<?= $newsletter_item->thumbnail_image_url ?>" alt="<?= $newsletter_item->thumbnail_image_alt_text ?? '' ?>">
  <?php } ?>
  <div class="featured_side__content">
    <h3 class="content__title"><?= $newsletter_item->title ?? gettext('Sign up to mySociety’s newsletter') ?></h3>
    <?php if (isset($newsletter_item->content)) { ?>
    <p class="content__description"><?= $newsletter_item->content ?></p>
    <?php } ?>
    <input type="email" placeholder="<?= gettext('Your email address') ?>" name="EMAIL"/>
    <div>
      <label style="position: absolute; left: -5000px;">
        Leave this box empty: <input type="text" name="b_53d0d2026dea615ed488a8834_287dc28511" tabindex="-1" value="" />
      </label>
      <!-- Adds the tag: "twfy" -->
      <input type="hidden" name="tags" value="11866267">
      <!-- Adds the interest groups: "Democracy and Parliaments" -->
      <input type="hidden" name="group[11745][4]" value="1">
      <input type="submit" value="<?= $newsletter_item->button_text ?? gettext('Subscribe') ?>" name="subscribe" class="button prefix <?= $newsletter_item->button_class ?? '' ?>">
    </div>

    <?php if (!empty($newsletter_item->footer_html)) { ?>
        <div class="sidebar__newsletter-form__footer">
            <?= $newsletter_item->footer_html ?>
        </div>
    <?php } ?>
  </div>
</form>
<?php }; ?>
