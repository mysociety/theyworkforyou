<?php if (isset($section_comments)) { ?>
<div class="full-page">
<?php
foreach ($section_comments as $comment) {
    [$date, $time] = explode(' ', $comment['posted']);
    $date = format_date($date, SHORTDATEFORMAT);
    $time = format_time($time, TIMEFORMAT);
    $USERURL = new \MySociety\TheyWorkForYou\Url('userview');
    $USERURL->insert(['u' => $comment['user_id']]);
    ?>
    <div class="debate-speech">
        <div class="full-page__row">
            <div class="full-page__unit">
                <div class="debate-speech__speaker-and-content">
                    <p class="credit"><a href="<?= $USERURL->generate() ?>" title="See information about this user"><strong><?php echo _htmlentities($comment['firstname']) . ' ' . _htmlentities($comment['lastname']); ?></strong></a><br>
                    <small>Posted on <?= $date ?> <?= $time ?></small>
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
<div class="full-page">
    <div class="debate-intro">
        <div class="full-page__row">
            <div class="full-page__unit">
                <div class="debate-speech__speaker-and-content">
                    <div class="intro-header">
                        <h2>About this debate</h2>
                        <div>
                            <div class="credit">
                                <a href="" title="See information about this user"><strong>John Doe</strong></a>
                                <small>· Posted on 17 November 1:07pm</small>
                            </div>
                        </div>
                    </div>
                    <p class="comment">
                        At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>
