<div class="full-page">
    <div class="full-page__row search-page people-list-page">

        <?php if ($type != 'peers' && count($mp_data)) { ?>

        <div class="search-page__section search-page__section--your-mp">
            <div class="search-page__section__primary">
                <div class="people-list__your-mp">
                    <div class="people-list__your-mp__header">
                      <p>
                          Based on postcode <strong><?= $mp_data['postcode'] ?></strong>
                          <a href="<?= $mp_data['change_url'] ?>">(Change postcode)</a>
                      </p>
                      <h3>Your <?= $rep_name ?> is</h3>
                    </div>
                    <a href="<?= $mp_data['mp_url'] ?>" class="people-list__person">
                    <img class="people-list__person__image" src="<?= $mp_data['image'] ?>">
                        <h2 class="people-list__person__name"><?= $mp_data['name'] ?></h2>
                        <p class="people-list__person__memberships">
                        <span class="people-list__person__constituency"><?= $mp_data['constituency'] ?></span>
                        <span class="people-list__person__party <?= strtolower( $mp_data['party'] ) ?>"><?= $mp_data['party'] ?></span>
                        </p>
                    </a>
                </div>
            </div>
        </div>

      <?php } else { ?>

        <form action="/search/">
            <div class="search-page__section search-page__section--search">
                <div class="search-page__section__primary">
                    <p class="search-page-main-inputs">
                    <?php if ( $type == 'peers' ) { ?>
                        <label for="find-mp-by-name-or-postcode">Find <?= $rep_plural ?> by name:</label>
                    <?php } else { ?>
                        <label for="find-mp-by-name-or-postcode">Find your <?= $rep_name ?> by name or postcode:</label>
                    <?php } ?>
                        <input type="text" class="form-control" name="q" id="find-mp-by-name-or-postcode">
                        <button type="submit" class="button">Find</button>
                    </p>
                </div>
            </div>
        </form>

      <?php } ?>

        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
            <h2>All <?= $rep_plural ?></h2>

                <?php if ( $type != 'peers' ) { ?>
                <ul class="search-result-display-options">
                    <?php if ( $order == 'given_name' ) { ?>
                    <li><strong>Sorted by</strong> First name</li>
                    <li>Sort by <a href="<?= $urls['by_last'] ?>">Last name</a> / <a href="<?= $urls['by_party'] ?>">Party</a></li>
                    <?php } else if ( $order == 'party' ) { ?>
                    <li><strong>Sorted by</strong> Party</li>
                    <li>Sort by <a href="<?= $urls['by_first'] ?>">First name</a> / <a href="<?= $urls['by_last'] ?>">Last name</a></li>
                    <?php } else { ?>
                    <li><strong>Sorted by</strong> Last name</li>
                    <li>Sort by <a href="<?= $urls['by_first'] ?>">First name</a> / <a href="<?= $urls['by_party'] ?>">Party</a></li>
                    <?php } ?>
                </ul>
                <?php } else { ?>
                <ul class="search-result-display-options">
                    <?php if ( $order == 'party' ) { ?>
                    <li><strong>Sorted by</strong> Party</li>
                    <li>Sort by <a href="<?= $urls['by_name'] ?>">Name</a></li>
                    <?php } else { ?>
                    <li><strong>Sorted by</strong> Name</li>
                    <li>Sort by <a href="<?= $urls['by_party'] ?>">Party</a></li>
                    <?php } ?>
                </ul>
                <?php } ?>

                <div class="people-list-alphabet">
                    <a href="#A">A</a>
                    <a href="#B">B</a>
                    <a href="#C">C</a>
                    <a href="#D">D</a>
                    <a href="#E">E</a>
                    <a href="#F">F</a>
                    <a href="#G">G</a>
                    <a href="#H">H</a>
                    <a href="#I">I</a>
                    <a href="#J">J</a>
                    <a href="#K">K</a>
                    <a href="#L">L</a>
                    <a href="#M">M</a>
                    <a href="#N">N</a>
                    <a href="#O">O</a>
                    <a href="#P">P</a>
                    <a href="#Q">Q</a>
                    <a href="#R">R</a>
                    <a href="#S">S</a>
                    <a href="#T">T</a>
                    <a href="#U">U</a>
                    <a href="#V">V</a>
                    <a href="#W">W</a>
                    <a href="#X">X</a>
                    <a href="#Y">Y</a>
                    <a href="#Z">Z</a>
                </div>

                <div class="people-list">
                <?php $current_initial = ''; ?>
                <?php foreach ( $data as $person ) {
                    $initial = substr( strtoupper($person['family_name']), 0, 1);
                    if ( $initial != $current_initial ) {
                        $current_initial = $initial;
                        $initial_link = "name=\"$initial\" ";
                    } else {
                        $initial_link = "";
                    }
                ?>
                <a <?= $initial_link ?>href="/mp/<?= $person['url'] ?>" class="people-list__person">
                <img class="people-list__person__image" src="<?= $person['image'] ?>">
                        <h2 class="people-list__person__name"><?= $person['name'] ?></h2>
                        <p class="people-list__person__memberships">
                        <span class="people-list__person__constituency"><?= $person['constituency'] ?></span>
                        <span class="people-list__person__party <?= strtolower($person['party']) ?>"><?= $person['party'] ?></span>
                        </p>
                    </a>
                <?php } ?>
                </div>

            </div>

            <div class="search-page__section__secondary search-page-sidebar">
                <h2>Download data</h2>
                <p class="sidebar-item-with-icon sidebar-item-with-icon--excel">
                <a href="<?= $urls['by_csv'] ?>">Download a CSV of current <?= $rep_plural ?></a>
                    suitable for Excel
                </p>
                <?php if ( $type == 'mps' ) { ?>
                <form method="get" action="<?= $urls['plain'] ?>" class="sidebar-item-with-icon sidebar-item-with-icon--date">
                    <p>
                        Or download a past list
                        <a class="pick-a-date" href="#past-list-dates">Pick a date</a>
                    </p>
                    <p class="past-list-dates" id="past-list-dates">
                        <a href="<?= $urls['plain'] ?>?date=2010-05-06">MPs at 2010 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=2005-05-05">MPs at 2005 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=2001-06-07">MPs at 2001 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1997-05-01">MPs at 1997 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1992-04-09">MPs at 1992 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1987-06-11">MPs at 1987 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1983-06-09">MPs at 1983 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1979-05-03">MPs at 1979 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1974-10-10">MPs at Oct 1974 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1974-02-28">MPs at Feb 1974 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1970-06-18">MPs at 1970 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1966-03-31">MPs at 1966 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1964-10-15">MPs at 1964 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1959-10-08">MPs at 1959 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1955-05-26">MPs at 1955 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1951-10-25">MPs at 1951 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1950-02-23">MPs at 1950 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1945-07-05">MPs at 1945 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1935-11-14">MPs at 1935 general election</a>
                        <label for="past-list-custom-date">Custom date&hellip;</label>
                        <span class="input-appended">
                            <input type="text" id="past-list-custom-date" name="date" class="form-control" placeholder="YYYY-MM-DD">
                            <input type="submit" value="Download" class="button">
                        </span>
                    </p>
                </form>

                <script type="text/javascript">
                $(function(){
                  $('#past-list-dates').hide();
                  $('a[href="#past-list-dates"]').on('click', function(e){
                    e.preventDefault();
                    $(this).trigger('blur');
                    $('#past-list-dates').slideToggle();
                  })
                });
                </script>
                <?php } else if ( $type == 'msps' ) { ?>
                    <p class="past-list-dates" id="past-list-dates">
                        <a href="<?= $urls['plain'] ?>?date=2007-05-03">MPs at 2007 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=2003-05-01">MPs at 2003 general election</a>
                        <a href="<?= $urls['plain'] ?>?date=1999-05-06">MPs at 1999 general election</a>
                        <a href="<?= $urls['plain'] ?>?all=1">Historical list of all MSPs</a>
                    </p>
                <?php } else { ?>
                    <p class="past-list-dates" id="past-list-dates">
                    <a href="<?= $urls['plain'] ?>?all=1">Historical list of all <?= $rep_plural ?></a>
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
        </div>

    </div>
</div>

<script type="text/javascript">
$(function(){
  $('#download-past-list').on('change', function(){
    if($(this).children('#custom-date').is(':selected')){
      $('#download-past-list-custom-date').show();
    } else {
      $('#download-past-list-custom-date').hide();
    }
  });
  $('#download-past-list-custom-date').hide();
});
</script>
