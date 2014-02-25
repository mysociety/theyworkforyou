    <div class="westminster">
        <div class="person-header">
            <div class=" full-page__row">
            <div class="person-header__content page-content__row">
                <div class="person-name">
                    <h1>
                        <?= $full_name ?>
                    </h1>
                </div>
                <div class="person-search">
                    <form action="<?= $search_url ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Person'); return false;">
                        <input id="person_search_input" name="q" size="24" maxlength="200" placeholder="Search this person's speeches"><input type="submit" class="submit" value="GO">
                        <input type="hidden" name="pid" value="<?= $person_id ?>">
                    </form>
                </div>
                <div class="person-buttons">
                    <a href="#" class="button wtt">Send a message</a>
                    <a href="#" class="button alert">Get email updates</a>
                </div>
                <div class="person-constituency">
                     <span class="constituency"><?= $constituency ?></span> <span class="party"><?= $party ?></span>
                </div>
            </div>
            </div>
        </div>
    </div>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="person-navigation page-content__row">
                <ul>
                    <li>Overview</li>
                    <li>Voting Record</li>
                </ul>
            </div>
            <div class="person-content__header page-content__row">
                <h1>Overview</h1>
            </div>
            <div class="person-panels page-content__row">
                <div class="sidebar__unit in-page-nav">
                    <ul>
                        <li>Votes</li>
                        <li>Appearances</li>
                        <li>Profile</li>
                        <li>Numerology</li>
                        <li>Register of Interests</li>
                    </ul>
                </div>
                <div class="primary-content__unit">

                    <div class="panel">
                        <h2>Voting Summary</h2>

                        <p><?= $rebellion_rate ?></p>

                        <h3>How <?= $full_name ?> voted on key issues<?= $key_votes['since_string'] ?></h3>

                        <?php if (count($key_votes['key_votes']) > 0): ?>

                            <ul>

                            <?php foreach ($key_votes['key_votes'] as $key_vote): ?>

                                <li><?= $key_vote ?></li>

                            <?php endforeach; ?>

                            </ul>

                            <?php if (isset($key_votes['more_link'])): ?>

                            <?= $key_votes['more_link'] ?>

                            <?php endif; ?>

                        <?php else: ?>

                            <p>No votes to display.</p>

                        <?php endif; ?>

                    </div>

                    <div class="panel">
                        <h2>Recent appearances</h2>
                    </div>

                    <div class="panel">
                        <h2>Profile</h2>
                    </div>

                    <div class="panel">
                        <h2>Numerology</h2>
                    </div>

                    <div class="panel">
                        <h2>Register of Interests</h2>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
