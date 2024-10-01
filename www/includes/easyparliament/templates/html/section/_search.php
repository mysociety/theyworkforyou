    <div class="business-section search-section">
        <div class="business-section__primary search-section__primary">
            <form action="<?= $urls['search'] ?>" method="GET">
            <label for="q" class="search-section__label"><?= $search_title ?></label>
                <div class="row collapse">
                    <div class="medium-9 columns">
                        <input name="q" id="q" class="search-section__input" type="text" placeholder="<?= gettext('Enter a keyword, phrase, or person') ?>">
                    </div>
                    <div class="medium-3 columns">
                        <input type="submit" value="<?= gettext('Search') ?>" class="button search-section__submit">
                    </div>
                </div>
                <?php if (isset($search_sections)) { ?>
                    <?php if (count($search_sections) == 1) { ?>
                    <input name="section" value="<?= $search_sections[0]['section'] ?>" type="hidden">
                    <?php } elseif (count($search_sections) > 1) { ?>
                    <div class="search-section__filters">
                        <?php foreach ($search_sections as $section) { ?>
                            <label><input name="section[]" value="<?= $section['section'] ?>" type="checkbox" checked="checked"><?= $section['title'] ?></label>
                    <?php } ?>
                    </div>
                    <?php }
                    } elseif (isset($section)) { ?>
                <input name="section" value="<?= $section ?>" type="hidden">
                <?php } ?>
            </form>
        </div>
        <div class="business-section__secondary search-section__secondary">
            <?php if (count($popular_searches)) { ?>
            <h3><?= gettext('Popular searches today') ?></h3>
            <ul class="search-section__suggestions">
                <?php foreach ($popular_searches as $i => $popular_search) { ?>
                <li><?= $popular_search['display']; ?></li>
                <?php } ?>
            </ul>
            <?php } ?>
        </div>
    </div>
