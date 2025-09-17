<?php
global $hansardmajors;

$previous_speech_time = null;

?>

<div class="debate-header regional-header regional-header--<?= $current_assembly ?>">
    <div class="regional-header__overlay"></div>
    <div class="full-page__row">
        <div class="debate-header__content full-page__unit">
            <h1><?= $heading ?></h1>
            <p class="lead">
                <?= $intro ?> <?= $location ?>
                <?php if (isset($debate_time_human)) {
                    printf(gettext("at %s"), $debate_time_human);
                }
print ' ';
printf(gettext('on <a href="%s">%s</a>'), $debate_day_link, $debate_day_human);
?>.
            </p>
            <p class="cta">
                <a class="button alert" href="/alerts/?alertsearch=<?= urlencode($email_alert_text) ?>"><?= gettext('Alert me about debates like this') ?></a>
            </p>
        </div>
    </div>
    <?php $section = true;
include '_section_nav.php'; ?>
</div>

<?php
if (isset($show_comment_form)) { ?>
<div class="full-page">
    <div class="full-page__row">
<?php
    global $PAGE;

    $comments['object']->display('ep', $comments['args']);
    $PAGE->comment_form($comments['commentdata']);
    ?>
    </div>
</div>
<?php } else {
    include '_section_comments.php'; ?>

<div class="full-page">

<?php
    include '_section_toc.php';
    $section = true;
    include '_section_content.php';
    ?>

</div>
<?php } ?>

<?php include '_section_footer.php'; ?>
