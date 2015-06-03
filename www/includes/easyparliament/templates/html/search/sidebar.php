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

                <h2>Search tips</h2>

                <p>
                TheyWorkForYou search is case-insensitive, and tries to match all the search terms within a document.
                </p>

                <p>
                <a href="#searchtips" class="search-options-toggle js-toggle-search-options">More tips</a>
                </p>

                <div id="searchtips">
                <p>
                To search for a person, just enter their name &ndash; we will search our database of people, as well as searching speeches made by that person, or that mention that person.
                </p>

                <p>To search for an exact phrase, use quotes (""). For example to find only documents contain the exact phrase "Hutton Report":<br>
                <span class="example-input">"hutton report"</span>
                Put a word in quotes if you don't want to perform stemming (where e.g. searching for <kbd>horse</kbd> will also return results with <kbd>horses</kbd>).
                </p>

                <p>To exclude a word from your search, put a minus ("-") sign in front,
                for example to find documents containing the word "representation" but not the word "taxation":<br>
                <span class="example-input">representation -taxation</span></p>

                <p>If you want to search for words only when they're used near each other in the text, use "NEAR". For example, to find documents containing the word "elephant" near the word "room":<br>
                <span class="example-input">elephant NEAR room</span></p>

                <p><strong>Advanced Users:</strong> You can perform boolean searches, with
                brackets, AND, OR, and XOR. The filters can be entered directly, as well as
                from the advanced search form, here's a selection:
                <ul>
                    <li>column:123</li>
                    <li>party:Lab</li>
                    <li>department:Defence</li>
                    <li>section:uk section:wms</li>
                    <li>date:20080716</li>
                    <li>20080101..20080131</li>
                </ul>
                </p>

                </div>

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
