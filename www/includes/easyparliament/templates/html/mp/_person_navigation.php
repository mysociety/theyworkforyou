<div class="person-navigation">

    <ul>
        <li <?php if ($pagetype == ""): ?>class="active"<?php endif; ?>><a href="<?= $member_url ?>"><?= gettext('Overview') ?></a>
          <ul>
              <li>
                  <a href="#profile">Profile</a>
              </li>
              <li>
                  <a href="#appearances">Appearances</a>
              </li>
          </ul>
        </li>

        <?php if ($this_page == "mp"): ?>
          <li <?php if ($pagetype == "votes"): ?>class="active"<?php endif; ?>><a href="<?= $member_url ?>/votes"><?= gettext('Voting Summary') ?></a>

            <ul>
                <h3>Comparison periods</h3>
                <li>
                    <a href="?comparison_period=all_time" class="active-comparison-period">All time</a>
                </li>
                <li>
                    <a href="?comparison_period=labour_1997" class="">Labour 1997-2010</a>
                </li>
                <li>
                    <a href="?comparison_period=coalition_2010" class="">Coalition 2010-2015</a>
                </li>
                <li>
                    <a href="?comparison_period=conservative_2015" class="">Conservative 2015-2024</a>
                </li>
                <li>
                    <a href="?comparison_period=labour_2024" class="">Labour 2024-</a>
                </li>
            </ul>

            <ul>
                <h3>Policy groups</h3>
                <li>
                    <a href="#welfare">Welfare, Benefits and Pensions</a>
                </li>
                <li>
                    <a href="#business">Business and the Economy</a>
                </li>
                <li>
                    <a href="#taxation">Taxation and Employment</a>
                </li>
                <li>
                    <a href="#housing">Housing</a>
                </li>
                <li>
                    <a href="#health">Health</a>
                </li>
                <li>
                    <a href="#reform">Constitutional Reform</a>
                </li>
                <li>
                    <a href="#home">Home Affairs</a>
                </li>
                <li>
                    <a href="#environment">Environmental Issues</a>
                </li>
                <li>
                    <a href="#foreignpolicy">Foreign Policy and Defence</a>
                </li>
                <li>
                    <a href="#education">Education</a>
                </li>
                <li>
                    <a href="#social">Social Issues</a>
                </li>
                <li>
                    <a href="#transport">Transport</a>
                </li>
                <li>
                    <a href="#misc">Miscellaneous Topics</a>
                </li>
            </ul>


          </li>
        <?php endif; ?>

          <?php if (in_array($this_page, ["mp", "msp", "ms"])): ?>
            <li <?php if ($pagetype == "recent"): ?>class="active"<?php endif; ?>><a href="<?= $member_url ?>/recent"><?= gettext('Recent Votes') ?></a>


            </li>
          <?php endif; ?>

          <?php if ($register_interests): ?>
            <li <?php if ($pagetype == "register"): ?>class="active"<?php endif; ?>><a href="<?= $member_url ?>/register"><?= gettext('Register of Interests') ?></a></li>
          <?php endif; ?>
          <?php if ($register_2024_enriched): ?>
                <li <?php if ($pagetype == "election_register"): ?>class="active"<?php endif; ?>><a href="<?= $member_url ?>/election_register"><?= gettext('2024 Election Donations') ?></a></li>
          <?php endif; ?>
          <?php if ($memberships): ?>
            <li <?php if ($pagetype == "member_interests"): ?>class="active"<?php endif; ?>><a href="<?= $member_url ?>/memberships"><?= gettext('Committees / APPGs / Signatures') ?></a></li>
          <?php endif; ?>
    </ul>
</div>

<style>
    .person-panels .in-page-nav>* {
        max-height: 70dvh;
        overflow-y: auto;
    }
</style>