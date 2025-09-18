<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php include '_person_navigation.php'; ?>
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
                        <a name="speeches"></a>
                        <h2>🗣️ <?= gettext('Speeches and Debates') ?></h3>
                    <?php if (count($recent_appearances['speeches']) > 0) { ?>

                        <ul class="appearances speeches">
                            <?php foreach ($recent_appearances['speeches'] as $speech): ?>
                                <li>
                                    <h4><a href="<?= $speech['listurl'] ?>"><?= $speech['parent']['body'] ?></a> <span class="date"><?= date('j M Y', strtotime($speech['hdate'])) ?></span></h4>
                                    <blockquote><?= $speech['extract'] ?></blockquote>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p><a href="<?= $recent_appearances['more_speeches_href'] ?>"><?= $recent_appearances['more_speeches_text'] ?></a></p>
                    
                    <?php } else { ?>
                        <p><?= gettext('No recent speeches to display.') ?></p>
                    <?php } ?>
                    </div>

                    <div class="panel">
                        <a name="written-questions"></a>
                        <h2>✍️ <?= gettext('Written Questions and Answers') ?></h2>
                    <?php if (count($recent_appearances['written_questions']) > 0) { ?>

                        <ul class="appearances written-questions">
                            <?php foreach ($recent_appearances['written_questions'] as $written): ?>
                                <li>
                                    <h4><a href="<?= $written['listurl'] ?>"><?= $written['parent']['body'] ?></a> <span class="date"><?= date('j M Y', strtotime($written['hdate'])) ?></span></h4>
                                    <blockquote><?= $written['extract'] ?></blockquote>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p><a href="<?= $recent_appearances['more_questions_href'] ?>"><?= $recent_appearances['more_questions_text'] ?></a></p>
                    <?php } else { ?>
                        <p><?= gettext('No recent written questions to display.') ?></p>
                    <?php } ?>

                </div>

            </div>
        </div>
    </div>
</div>