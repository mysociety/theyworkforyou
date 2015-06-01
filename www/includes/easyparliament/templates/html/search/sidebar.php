            <div class="search-page__section__secondary search-page-sidebar">
                <?php if ( isset( $search_sidebar['email'] ) ) { ?>
                <h2>Create an alert</h2>
                <p class="search-alert-type search-alert-type--email">
                    <a href="<?= $search_sidebar['email'] ?>">Subscribe to an email alert</a>
                    for <em class="current-search-term"><?= $search_sidebar['email_desc'] ?></em>
                    <?php if (isset( $search_sidebar['email_section'] ) ) { ?>
                    <br><small>(or just <a href="<?= $search_sidebar['email_section'] ?>"><?= $search_sidebar['email_desc_section'] ?></a>)</small>
                    <?php } ?>
                </p>
                <p class="search-alert-type search-alert-type--rss">
                    Or <a href="<?= $search_sidebar['rss'] ?>">get an RSS feed</a>
                    of new matches as they happen
                </p>
                <?php } ?>

                <h2>Did you find what you were looking for?</h2>
                <form method="post" action="http://survey.mysociety.org">
                    <input type="hidden" name="sourceidentifier" value="twfy-mini-2">
                    <input type="hidden" name="datetime" value="1431962861">
                    <input type="hidden" name="subgroup" value="0">
                    <input type="hidden" name="user_code" value="123">
                    <input type="hidden" name="auth_signature" value="123">
                    <input type="hidden" name="came_from" value="http://www.theyworkforyou.com/search/?answered_survey=2">
                    <input type="hidden" name="return_url" value="http://www.theyworkforyou.com/search/?answered_survey=2">
                    <input type="hidden" name="question_no" value="2">
                    <p>
                        <label><input type="radio" name="find_on_page" value="1"> Yes</label>
                        <label><input type="radio" name="find_on_page" value="0"> No</label>
                    </p>
                    <p>
                        <input type="submit" class="button small" value="Submit answer">
                    </p>
                </form>
            </div>
