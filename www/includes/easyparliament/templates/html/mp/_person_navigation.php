<div class="page-mobile-navigation-controller">
    <button class="js-table-content-button" aria-label="open/close navigation subpages">
        <i class="fi-list"></i><?= sprintf(gettext('%s Menu'), strtoupper($this_page)) ?>
    </button>   
</div>

<div class="person-navigation js-table-of-content">
    <h3 class="browse-content"><?= ucfirst($full_name) ?></h3>
    <ul>
        <li <?php if ($pagetype == ""): ?>class="active"<?php endif; ?>>
            <a href="<?= $member_url ?>" class="person-navigation--subpage-heading">
                <h2>📌 <?= gettext('Overview') ?></h2>
            </a>
            <ul class="subpage-content-list">
                <li><a href="#profile"><?= gettext('Profile') ?></a></li>
            </ul>
        </li>


        <?php if (!empty($memberships)): ?>
            <li <?php if ($pagetype == "memberships"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/memberships" class="person-navigation--subpage-heading">
                    <h2>👥 <?= gettext('Committees / APPGs') ?></h2>
                </a>

                <?php if (!empty($memberships)): ?>
                    <nav class="subpage-content-list js-accordion" aria-label="Memberships list">
                        <ul class="subpage-content-list">
                            <?php if (array_key_exists('posts', $memberships)): ?>
                            <li><a href="#posts"><?= gettext('Memberships') ?></a></li>
                            <?php endif; ?>
                            <?php if (array_key_exists('previous_posts', $memberships)): ?>
                            <li><a href="#previous_posts"><?= gettext('Previous Memberships') ?></a></li>
                            <?php endif; ?>
                            <?php if (array_key_exists('appg_membership', $memberships)): ?>
                                <?php if ($memberships['appg_membership']->is_an_officer()): ?>
                                <li><a href="#appg_is_officer_of"><?= gettext('APPG Offices held') ?></a></li>
                                <?php endif; ?>
                                <?php if ($memberships['appg_membership']->is_a_member()): ?>
                                <li><a href="#appg_is_ordinary_member_of"><?= gettext('APPG memberships') ?></a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </li>
        <?php endif; ?>

        <?php if (array_key_exists('letters_signed', $memberships) || array_key_exists('edms_signed', $memberships) || array_key_exists('topics_of_interest', $memberships) || array_key_exists('eu_stance', $memberships)): ?>
            <li <?php if ($pagetype == "signatures"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/signatures" class="person-navigation--subpage-heading">
                    <h2>✍️ <?= gettext('Signatures') ?></h2>
                </a>

                <nav class="subpage-content-list js-accordion" aria-label="Signatures list">
                    <ul class="subpage-content-list">
                            <?php if (array_key_exists('letters_signed', $memberships)): ?>
                            <li><a href="#letters_signed"><?= gettext('Recent open letters') ?></a></li>
                            <?php endif; ?>
                            <?php if (array_key_exists('edms_signed', $memberships)): ?>
                            <li><a href="#edms_signed"><?= gettext('Recent EDMs') ?></a></li>
                            <?php endif; ?>
                            <?php if (array_key_exists('topics_of_interest', $memberships) || array_key_exists('eu_stance', $memberships)): ?>
                            <li><a href="#topics"><?= gettext('Topics of interest') ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
            </li>
        <?php endif; ?>

        <?php if ($this_page == "mp"): ?>
            <li <?php if ($pagetype == "votes"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/votes" class="person-navigation--subpage-heading">
                    <h2>🗳️ <?= gettext('Voting Summary') ?></h2>
                </a>

                <?php if (!empty($has_voting_record) && !empty($key_votes_segments)): ?>
                    <nav class="subpage-content-list js-accordion" aria-label="Policy groups">
                        <h3 class="js-accordion-button">Policy areas</h3>
                        <ul class="js-accordion-content">
                            <?php if ($has_voting_record): ?>
                                <?php foreach ($key_votes_segments as $segment): ?>
                                    <?php if (count($segment->policy_pairs) > 0): ?>
                                    <li><a href="#<?= $segment->group_slug ?>"><?= $segment->group_name ?></a></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </li>
        <?php endif; ?>

        <?php if (in_array($this_page, ["mp", "msp", "ms"])): ?>
            <li <?php if ($pagetype == "recent"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/recent" class="person-navigation--subpage-heading">
                    <h2>📜 <?= gettext('Recent Votes') ?></h2>
                </a>
            </li>
        <?php endif; ?>

        <?php if (count($recent_appearances['appearances'])): ?>
            <li <?php if ($pagetype == "speeches"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/speeches" class="person-navigation--subpage-heading">
                    <h2>💬 <?= gettext('Speeches and Questions') ?></h2>
                </a>
                
                <?php if ($pagetype == "speeches"): ?>
                    <nav class="subpage-content-list js-accordion" aria-label="Appearances list">
                        <ul class="subpage-content-list">
                            <?php if (count($recent_appearances['speeches']) > 0): ?>
                            <li><a href="#speeches"><?= gettext('Speeches & Debates') ?></a></li>
                            <?php endif; ?>
                            <?php if (count($recent_appearances['written_questions']) > 0): ?>
                            <li><a href="#written-questions"><?= gettext('Written Questions') ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </li>
        <?php endif; ?>

        <?php if ($register_interests): ?>
            <li <?php if ($pagetype == "register"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/register" class="person-navigation--subpage-heading">
                    <h2>📖 <?= gettext('Register of Interests') ?></h2>
                </a>

                <?php if (!empty($register_interests)): ?>
                    <?php foreach ($register_interests['chamber_registers'] as $register) { ?>
                        <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>

                        <nav class="subpage-content-list js-accordion" aria-label="<?= $register->displayChamber() ?> list">
                            <h3 class="js-accordion-button"><?= $register->displayChamber() ?></h3>
                            <ul class="js-accordion-content">
                                <?php foreach ($register->categories as $category): ?>
                                    <?php if ($category->only_null_entries()): ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <li><a href="#category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </nav>
                    <?php } ?>
                <?php endif; ?>
            </li>
        <?php endif; ?>

        <?php if ($register_2024_enriched): ?>
            <li <?php if ($pagetype == "election_register"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/election_register" class="person-navigation--subpage-heading">
                    <h2>🏛️ <?= gettext('2024 Election Donations') ?></h2>
                </a>

                <ul class="subpage-content-list">
                    <li><a href="https://www.whofundsthem.com">About WhoFundsThem</a></li> 
                <?php $election_registers = [$register_2024_enriched]; ?>
                <?php if (!empty($election_registers)): ?>
                     <?php foreach ($election_registers as $register) { ?>
                                <?php foreach ($register->categories as $category) { ?>
                                    <?php if ($category->only_null_entries()) { ?>
                                        <?php continue; ?>
                                    <?php }; ?>
                                    <li><a href="#category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></a></li>
                                <?php }; ?>
                            <?php }; ?>
                <?php endif; ?>
                </ul>
            </li>
        <?php endif; ?>

        <?php if (in_array($this_page, ["mp"])): ?>
            <li <?php if ($pagetype == "constituency"): ?>class="active"<?php endif; ?>>
                <a href="<?= $member_url ?>/constituency" class="person-navigation--subpage-heading">
                    <h2>🌍 <?= gettext('Constituency information') ?>🆕</h2>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>
