<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">

            <h2><?= gettext('Whoops...') ?></h2>

            <p><?= $error ?></p>

        <?php if ($rep_search_url) { ?>
        </div>

        <div class="full-page__unit">
            <div class="mp-postcode-search">
            <h3><?= sprintf(gettext('Enter your postcode to see who your %s is, and what they’ve been doing'), $rep_name) ?>:</h3>

            <form action="<?= $rep_search_url ?>" method="get">

                    <div class="row collapse">
                        <div class="small-10 columns">
                            <input type="text" name="pc" value="" maxlength="10" size="10" placeholder="<?= gettext('Your postcode') ?>">
                        </div>
                        <div class="small-2 columns">
                            <input type="submit" value="<?= gettext('Go') ?>" class="button prefix">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="full-page__unit">
        <p><?= sprintf(gettext('Or <a href="%s">browse all %s</a>?'), $all_mps_url, $rep_name_plural) ?></p>
        </div>
        <?php } else { ?>
        <p><?= sprintf(gettext('Why not <a href="%s">browse all %s</a>?'), $all_mps_url, $rep_name_plural) ?></p>
        </div>
        <?php } ?>
    </div>
</div>
