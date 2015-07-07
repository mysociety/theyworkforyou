            <div class="search-page__section__secondary search-page-sidebar">
                <?php if ( isset( $search_sidebar['email'] ) ) { ?>
                <h2>Create an alert</h2>
                <p class="sidebar-item-with-icon sidebar-item-with-icon--email">
                    <a href="<?= $search_sidebar['email'] ?>">Subscribe to an email alert</a>
                    for <em class="current-search-term"><?= $search_sidebar['email_desc'] ?></em>
                    <?php if (isset( $search_sidebar['email_section'] ) ) { ?>
                    <br><small>(or just <a href="<?= $search_sidebar['email_section'] ?>"><?= $search_sidebar['email_desc_section'] ?></a>)</small>
                    <?php } ?>
                </p>
                <p class="sidebar-item-with-icon sidebar-item-with-icon--rss">
                    Or <a href="<?= $search_sidebar['rss'] ?>">get an RSS feed</a>
                    of new matches as they happen
                </p>
                <?php } ?>


                <?php include( dirname(__FILE__) . '/../sidebar/looking_for.php' ) ?>
            </div>
