<?php if (isset($section_comments)) { ?>
<div class="full-page">
<?php
$first = true;
    foreach ($section_comments as $comment) {
        [$date, $time] = explode(' ', $comment['posted']);
        $date = format_date($date, SHORTDATEFORMAT);
        $time = format_time($time, TIMEFORMAT);
        $USERURL = new \MySociety\TheyWorkForYou\Url('userview');
        $USERURL->insert(['u' => $comment['user_id']]);
        ?>
    <div class="debate-intro">
        <div class="full-page__row">
            <div class="full-page__unit">
                <div class="debate-speech__speaker-and-content">
                    <div class="intro-header">
                        <?php if ($first) {
                            $first = false; ?>
                        <h2>About this debate</h2>
                        <?php } ?>
                        <div>
                            <div class="credit">
                                <a href="<?= $USERURL->generate() ?>" title="See information about this user"><strong><?php echo _htmlentities($comment['firstname']) . ' ' . _htmlentities($comment['lastname']); ?></strong></a>
                                <small>· Posted on <?= $date ?> <?= $time ?></small>
                            </div>
                        </div>
                    </div>

                    <p class="comment">
                    <?= prepare_comment_for_display($comment['body']) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
</div>
<?php } ?>
