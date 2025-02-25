<div class="person-navigation">
    <ul>
        <li <?php if ($pagetype == "") { ?>class="active"<?php } ?>><a href="<?= $member_url ?>"><?= gettext('Overview') ?></a></li>
          <?php if ($this_page == "mp") { ?>
            <li <?php if ($pagetype == "votes") { ?>class="active"<?php } ?>><a href="<?= $member_url ?>/votes"><?= gettext('Voting Summary') ?></a></li>
          <?php } ?>
          <?php if (in_array($this_page, ["mp", "msp", "ms"])) { ?>
          <li <?php if ($pagetype == "recent") { ?>class="active"<?php } ?>><a href="<?= $member_url ?>/recent"><?= gettext('Recent Votes') ?></a></li>
            <?php if ($register_interests) { ?>
                <li <?php if ($pagetype == "register") { ?>class="active"<?php } ?>><a href="<?= $member_url ?>/register"><?= gettext('Register of Interests') ?></a></li>
            <?php } ?>
          <?php } ?>
          </ul>
</div>