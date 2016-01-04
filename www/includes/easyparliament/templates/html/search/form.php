        <form>
            <div class="search-page__section search-page__section--search">
                <div class="search-page__section__primary">
                    <p class="search-page-main-inputs">
                        <input type="text" name="q" value="<?= _htmlentities($search_keyword) ?>" class="form-control">
                        <input type="submit" class="button" value="Search">
                    </p>
                    <?php if (isset($warnings) ) { ?>
                    <p class="error">
                        <?= $warnings ?>
                    </p>
                    <?php } ?>
                    <p>
                        <ul class="search-result-display-options">
                        <li><a href="?show_advanced_options=true" class="search-options-toggle js-toggle-search-options">Advanced search</a></li>
                        <?php if ( $is_adv ) { ?>
                            <?= $search_phrase ? '<li>Exactly: ' . _htmlentities($search_phrase) . '</li>' : '' ?>
                            <?= $search_exclude ? '<li>Excluding: ' . _htmlentities($search_exclude) . '</li>' : '' ?>
                            <?= $search_from ? '<li>From: ' . _htmlentities($search_from) . '</li>' : '' ?>
                            <?= $search_to ? '<li>To: ' . _htmlentities($search_to) . '</li>' : '' ?>
                            <?= $search_person ? '<li>Person: ' . _htmlentities($search_person) . '</li>' : '' ?>
                            <?= $search_section ? '<li>Section: ' . _htmlentities($search_section_pretty) . '</li>' : '' ?>
                            <?= $search_column ? '<li>Column: ' . _htmlentities($search_column) . '</li>' : '' ?>
                        <?php } ?>
                        </ul>
                    </p>
                </div>
            </div>

            <div class="search-page__section search-page__section--options" id="options" style="display: <?= $show_advanced_options ? 'block' : 'none' ?>">
                <div class="search-page__section__primary">
                    <h2>Advanced search</h2>

                    <h4>Find this exact word or phrase</h4>
                    <div class="search-option">
                        <div class="search-option__control">
                            <input name="phrase" type="text" value="<?= _htmlentities($search_phrase) ?>" class="form-control">
                        </div>
                        <div class="search-option__hint">
                            <p>You can also do this from the main search box by
putting exact words in quotes: like <code>"cycling"</code> or <code>"hutton
report"</code></p>
                            <p>By default, we show words related to your search
term, like &ldquo;cycle&rdquo; and &ldquo;cycles&rdquo; in a search for
<code>cycling</code>. Putting the word in quotes, like <code>"cycling"</code>,
will stop this.</p>

                        </div>
                    </div>

                    <h4>Excluding these words</h4>
                    <div class="search-option">
                        <div class="search-option__control">
                            <input name="exclude" type="text" value="<?= _htmlentities($search_exclude) ?>" class="form-control">
                        </div>
                        <div class="search-option__hint">
                            <p>You can also do this from the main search box by
putting a minus sign before words you don&rsquo;t want: like <code>hunting
-fox</code></p>
                            <p>We also support <a href="/help/#searching">a
bunch of boolean search modifiers</a>, like <code>AND</code> and
<code>NEAR</code>, for precise searching.</p>
                        </div>
                    </div>

                    <h4>Date range</h4>
                    <div class="search-option">
                        <div class="search-option__control search-option__control--date-range">
                            <input name="from" type="date" value="<?= _htmlentities($search_from) ?>" class="form-control">
                            <span>to</span>
                            <input name="to" type="date" value="<?= _htmlentities($search_to) ?>" class="form-control">
                        </div>
                        <div class="search-option__hint">
                            <p>You can give a <strong>start date, an end date,
or both</strong> to restrict results to a particular date range. A missing end
date implies the current date, and a missing start date implies the oldest date
we have in the system. Dates can be entered in any format you wish, e.g.
<code>3rd March 2007</code> or <code>17/10/1989</code></p>
                        </div>
                    </div>

                    <h4>Person</h4>
                    <div class="search-option">
                        <div class="search-option__control">
                            <input name="person" type="text" value="<?= _htmlentities($search_person) ?>" class="form-control">
                        </div>
                        <div class="search-option__hint">
                            <p>Enter a name here to restrict results to contributions only by that person.</p>
                        </div>
                    </div>

                    <h4>Section</h4>
                    <div class="search-option">
                        <div class="search-option__control">
                            <select name="section">
                                <option></option>
                                <optgroup label="UK Parliament">
                                    <option value="uk"<?= $search_section == 'uk' ? ' selected' : '' ?>>All UK</option>
                                    <option value="debates"<?= $search_section == 'debates' ? ' selected' : '' ?>>House of Commons debates</option>
                                    <option value="whall"<?= $search_section == 'whall' ? ' selected' : '' ?>>Westminster Hall debates</option>
                                    <option value="lords"<?= $search_section == 'lords' ? ' selected' : '' ?>>House of Lords debates</option>
                                    <option value="wrans"<?= $search_section == 'wrans' ? ' selected' : '' ?>>Written answers</option>
                                    <option value="wms"<?= $search_section == 'wms' ? ' selected' : '' ?>>Written ministerial statements</option>
                                    <option value="standing"<?= $search_section == 'standing' ? ' selected' : '' ?>>Bill Committees</option>
                                    <option value="future"<?= $search_section == 'future' ? ' selected' : '' ?>>Future Business</option>
                                </optgroup>
                                <optgroup label="Northern Ireland Assembly">
                                    <option value="ni"<?= $search_section == 'ni' ? ' selected' : '' ?>>Debates</option>
                                </optgroup>
                                <optgroup label="Scottish Parliament">
                                    <option value="scotland"<?= $search_section == 'scotland' ? ' selected' : '' ?>>All Scotland</option>
                                    <option value="sp"<?= $search_section == 'sp' ? ' selected' : '' ?>>Debates</option>
                                    <option value="spwrans"<?= $search_section == 'spwrans' ? ' selected' : '' ?>>Written answers</option>
                                </optgroup>
                             </select>
                        </div>
                        <div class="search-option__hint">
                            <p>Restrict results to a particular parliament or assembly that we cover (e.g. the Scottish Parliament), or a particular type of data within an institution, such as Commons Written Answers.</p>
                        </div>
                    </div>

                    <h4>Column</h4>
                    <div class="search-option">
                        <div class="search-option__control">
                            <input name="column" type="text" value="<?= _htmlentities($search_column) ?>" class="form-control">
                        </div>
                        <div class="search-option__hint">
                            <p>If you know the actual Hansard column number of
the information you are interested in (perhaps you&rsquo;re looking up a paper
reference), you can restrict results to that; you can also use
<code>column:123</code> in the main search box.</p>
                        </div>
                    </div>

                    <p><input type="submit" class="button" value="Search"></p>
                </div>
            </div>
        </form>

        <script type="text/javascript">
        $(function(){
          $('.js-toggle-search-options').on('click', function(e){
            e.preventDefault();
            var id = '#options';
            if($(id).is(':visible')){
              $('.js-toggle-search-options[href="' + id + '"]').removeClass('toggled');
              $(id).slideUp(250, function(){
                  // disable inputs after they're hidden
                  $(id).find(':input').attr('disabled', 'disabled');
              });
            } else {
              $('.js-toggle-search-options[href="' + id + '"]').addClass('toggled');
              $(id).find(':input:disabled').removeAttr('disabled');
              $(id).slideDown(250);
            }
          });
<? if( !($is_adv || $show_advanced_options) ) { ?>
          $("#options").find(":input").attr("disabled", "disabled");
<?php } ?>
        });
        </script>
