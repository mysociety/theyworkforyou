            <div class="search-page__section__secondary search-page-sidebar">
                <?php if (isset($search_sidebar['email'])) { ?>
                <h2><?= gettext('Create an alert') ?></h2>
                <p class="sidebar-item-with-icon">
                    <?= sprintf(gettext('<a href="%s">Subscribe to an email alert</a> for <em class="current-search-term">%s</em>'), $search_sidebar['email'], $search_sidebar['email_desc']) ?>
                    <?php if (isset($search_sidebar['email_section'])) { ?>
                    <br><small><?= sprintf(gettext('(or just <a href="%s">%s</a>)'), $search_sidebar['email_section'], $search_sidebar['email_desc_section']) ?></small>
                    <?php } ?>
                </p>
                <p class="sidebar-item-with-icon sidebar-item-with-icon--rss">
                    <?= sprintf(gettext('Or <a href="%s">get an RSS feed</a> of new matches as they happen.'), $search_sidebar['rss']) ?><br>
                    <small><a href="/help/#rss"><?= gettext('What is RSS?') ?></a></small>
                </p>
                <?php } ?>

            </div>
