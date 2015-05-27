<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">

            <h2>Whoops...</h2>

            <p><?= $error ?></p>

        <?php if ( $rep_search_url ) { ?>
            <p>Why not&hellip;</p>

        </div>

        <div class="full-page__unit">
            <div class="mp-postcode-search">
            <h3>Search for your <?= $rep_name ?> by postcode</h3>

            <form action="<?= $rep_search_url ?>" method="get">

                    <div class="row collapse">
                        <div class="small-10 columns">
                            <input type="text" name="pc" value="" maxlength="10" size="10" placeholder="Your postcode">
                        </div>
                        <div class="small-2 columns">
                            <input type="submit" value="GO" class="button prefix">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="full-page__unit">
        <p>Or <a href="<?= $all_mps_url ?>">browse all <?= $rep_name ?>s</a>?</p>
        </div>
        <?php } else { ?>
        <p>Why not <a href="<?= $all_mps_url ?>">browse all <?= $rep_name ?>s</a>?</p>
        </div>
        <?php } ?>
    </div>
</div>
