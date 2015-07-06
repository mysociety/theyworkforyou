<div class="full-page">
    <div class="full-page__row search-page people-list-page">

      <?php if ( isset($GLOBALS['postcode']) ) { ?>

        <div class="search-page__section search-page__section--your-mp">
            <div class="search-page__section__primary">
                <div class="people-list__your-mp">
                    <div class="people-list__your-mp__header">
                      <p>
                          Based on postcode <strong><?php echo $GLOBALS['postcode']; ?></strong>
                          <a href="#">(Change postcode)</a>
                      </p>
                      <h3>Your MP is</h3>
                    </div>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/10186.jpg">
                        <h2 class="people-list__person__name">Louise Ellman</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Liverpool, Riverside</span>
                            <span class="people-list__person__party labour">Labour/Co-operative</span>
                        </p>
                    </a>
                </div>
            </div>
        </div>

      <?php } else { ?>

        <form action="./with-postcode">
            <div class="search-page__section search-page__section--search">
                <div class="search-page__section__primary">
                    <p class="search-page-main-inputs">
                        <label for="find-mp-by-name-or-postcode">Find your MP by name or postcode:</label>
                        <input type="text" class="form-control" id="find-mp-by-name-or-postcode">
                        <button type="submit" class="button">Find</button>
                    </p>
                </div>
            </div>
        </form>

      <?php } ?>

        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
                <h2>All MPs</h2>

                <ul class="search-result-display-options">
                    <li><strong>Sorted by</strong> Last name</li>
                    <li>Sort by <a href="#">First name</a> / <a href="#">Party</a></li>
                </ul>

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
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/10001.jpeg">
                        <h2 class="people-list__person__name">Diane Abbott</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Hackney North and Stoke Newington</span>
                            <span class="people-list__person__party labour">Labour</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/25034.jpeg">
                        <h2 class="people-list__person__name">Debbie Abrahams</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Oldham East and Saddleworth</span>
                            <span class="people-list__person__party labour">Labour</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/24878.jpeg">
                        <h2 class="people-list__person__name">Nigel Adams</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Selby and Ainsty</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/11929.jpg">
                        <h2 class="people-list__person__name">Adam Afriyie</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Windsor</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/24904.jpeg">
                        <h2 class="people-list__person__name">Peter Aldous</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Waveney</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/24953.jpeg">
                        <h2 class="people-list__person__name">Heidi Alexander</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Lewisham East</span>
                            <span class="people-list__person__party labour">Labour</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/24958.jpeg">
                        <h2 class="people-list__person__name">Rushanara Ali</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Bethnal Green and Bow</span>
                            <span class="people-list__person__party labour">Labour</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/unknownperson.png">
                        <h2 class="people-list__person__name">Lucy Allan</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Telford</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/24785.jpeg">
                        <h2 class="people-list__person__name">Harriet Baldwin</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">West Worcestershire</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                        <ul class="people-list__person__positions">
                            <li>The Economic Secretary to the Treasury</li>
                        </ul>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/10040.jpg">
                        <h2 class="people-list__person__name">John Bercow</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Birmingham</span>
                            <span class="people-list__person__party">No party</span>
                        </p>
                        <ul class="people-list__person__positions">
                            <li>Speaker of the House of Commons</li>
                        </ul>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/24766.jpeg">
                        <h2 class="people-list__person__name">Nicholas Boles</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Grantham and Stamford</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                        <ul class="people-list__person__positions">
                            <li>Minister of State (Department for Business, Innovation and Skills) (Jointly with the Department for Education)</li>
                            <li>The Minister for Universities and Science</li>
                        </ul>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/mps/10777.jpg">
                        <h2 class="people-list__person__name">David Cameron</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Witney</span>
                            <span class="people-list__person__party conservative">Conservative</span>
                        </p>
                        <ul class="people-list__person__positions">
                            <li>Leader of the Conservative Party</li>
                            <li>The Prime Minister</li>
                        </ul>
                    </a>
                    <a href="#" class="people-list__person">
                        <img class="people-list__person__image" src="http://www.theyworkforyou.com/images/unknownperson.png">
                        <h2 class="people-list__person__name">Douglas Chapman</h2>
                        <p class="people-list__person__memberships">
                            <span class="people-list__person__constituency">Dunfermline and West Fife</span>
                            <span class="people-list__person__party conservative snp">Scottish National Party</span>
                        </p>
                    </a>
                </div>

            </div>

            <div class="search-page__section__secondary search-page-sidebar">
                <h2>Download data</h2>
                <p class="sidebar-item-with-icon sidebar-item-with-icon--excel">
                    <a href="#">Download a CSV of current MPs</a>
                    suitable for Excel
                </p>
                <form method="get" action="/mps/" class="sidebar-item-with-icon sidebar-item-with-icon--date">
                    <p>
                        Or download a past list
                        <a class="pick-a-date" href="#past-list-dates">Pick a date</a>
                    </p>
                    <p class="past-list-dates" id="past-list-dates">
                        <a href="/mps/?date=2010-05-06">MPs at 2010 general election</a>
                        <a href="/mps/?date=2005-05-05">MPs at 2005 general election</a>
                        <a href="/mps/?date=2001-06-07">MPs at 2001 general election</a>
                        <a href="/mps/?date=1997-05-01">MPs at 1997 general election</a>
                        <a href="/mps/?date=1992-04-09">MPs at 1992 general election</a>
                        <a href="/mps/?date=1987-06-11">MPs at 1987 general election</a>
                        <a href="/mps/?date=1983-06-09">MPs at 1983 general election</a>
                        <a href="/mps/?date=1979-05-03">MPs at 1979 general election</a>
                        <a href="/mps/?date=1974-10-10">MPs at Oct 1974 general election</a>
                        <a href="/mps/?date=1974-02-28">MPs at Feb 1974 general election</a>
                        <a href="/mps/?date=1970-06-18">MPs at 1970 general election</a>
                        <a href="/mps/?date=1966-03-31">MPs at 1966 general election</a>
                        <a href="/mps/?date=1964-10-15">MPs at 1964 general election</a>
                        <a href="/mps/?date=1959-10-08">MPs at 1959 general election</a>
                        <a href="/mps/?date=1955-05-26">MPs at 1955 general election</a>
                        <a href="/mps/?date=1951-10-25">MPs at 1951 general election</a>
                        <a href="/mps/?date=1950-02-23">MPs at 1950 general election</a>
                        <a href="/mps/?date=1945-07-05">MPs at 1945 general election</a>
                        <a href="/mps/?date=1935-11-14">MPs at 1935 general election</a>
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
