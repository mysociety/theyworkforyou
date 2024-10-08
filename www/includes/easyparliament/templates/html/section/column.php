<?php
global $hansardmajors;

$previous_speech_time = null;

?>

<div class="debate-header regional-header regional-header--<?= $current_assembly ?>">
    <div class="regional-header__overlay"></div>
    <div class="full-page__row">
        <div class="debate-header__content full-page__unit">
            <h1>Column <?= _htmlentities($column) ?></h1>
            <p class="lead">
                <?= $location ?>
                on <a href="<?= $debate_day_link ?>"><?= $debate_day_human ?></a>.
            </p>
        </div>
    </div>
</div>
<div class="full-page">

  <?php if (count($data['rows']) == 0) { ?>
    <div class="debate-speech">
        <div class="full-page__row">
            <div class="full-page__unit">
                <?php if ($current_assembly != 'uk-commons' && $current_assembly != 'uk-lords') { ?>
                    We only have information about columns for debates in the House of Commons and the House of Lords.
                <?php } else { ?>
                    We can't find anything in column <?= $column ?> on <?= $debate_day_human ?>.
                <?php } ?>
            </div>
        </div>
    </div>
  <?php } else {
      $section = false;
      include '_section_content.php';
  } ?>

</div>

<?php include '_section_footer.php'; ?>
