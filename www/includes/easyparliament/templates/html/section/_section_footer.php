<nav class="debate-navigation debate-navigation--footer" role="navigation">
        <div class="full-page__row">
            <div class="debate-navigation__pagination">
                <?php if (isset($section_annotation_url)) { ?>
                <div class="js-annotation-toggle annotations-toggle-switch">
                    <label for="annotation-toggle">
                        <span><?= gettext('Show annotations') ?></span>
                        <input type="checkbox" id="annotation-toggle" checked>
                    </label>
                </div>
                <div id="annotation-status" class="visuallyhidden" aria-live="polite" aria-atomic="true"></div>
                <div class="debate-navigation__all-debates">
                    <a href="<?= $section_annotation_url ?>">Annotate!</a>
                </div>
                    <?php } ?>
                <?php if (isset($nextprev['prev'])) { ?>
                <div class="debate-navigation__previous-debate">
                    <a href="<?= $nextprev['prev']['url'] ?>" rel="prev">&laquo; <?= $nextprev['prev']['body'] ?></a>
                </div>
                <?php } ?>

                <?php if (isset($nextprev['up'])) { ?>
                <div class="debate-navigation__all-debates">
                    <a href="<?= $nextprev['up']['url'] ?>" rel="up"><?= $nextprev['up']['body'] ?></a>
                </div>
                <?php } ?>

                <?php if (isset($nextprev['next'])) { ?>
                <div class="debate-navigation__next-debate">
                    <a href="<?= $nextprev['next']['url'] ?>" rel="next"><?= $nextprev['next']['body'] ?> &raquo;</a>
                </div>
                <?php } ?>
            </div>
        </div>
    </nav>
