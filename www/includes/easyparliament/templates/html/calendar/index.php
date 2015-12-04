<div class="full-page__row">

    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
              <?php if ($data['scope'] == 'calendar_today') { ?>
                Today&rsquo;s business
              <?php } else if ($data['scope'] == 'calendar_past') { ?>
                Previous business
              <?php } else if ($data['scope'] == 'calendar_future') { ?>
                Upcoming business
              <?php } ?>
            </h1>
            <p class="business-section__header__date">
                <?= format_date( $data['date'], LONGERDATEFORMAT ) ?>
            </p>
        </div>
        <div class="business-section__primary"><?php

            if(isset($data['dates'])){
                $order = array(
                    'Commons: Main Chamber', 'Lords: Main Chamber',
                    'Commons: Westminster Hall',
                    'Commons: General Committee',
                    'Commons: Select Committee', 'Lords: Select Committee',
                    'Lords: Grand Committee',
                );
                $plural = array(0, 0, 0, 1, 1, 1, 0);
                $list   = array(1, 1, 1, 1, 0, 0, 1);
                $major  = array(1, 101, 2, 0, 0, 0, 0);

                # Content goes here
                foreach ($data['dates'] as $date => $day_events) {
                    foreach ($order as $i => $chamber) {
                        if (!array_key_exists($chamber, $day_events))
                            continue;
                        $events = $day_events[$chamber];
                        if ($plural[$i]) $chamber .= 's';
                        print "<h2>$chamber";
                        if (in_array($major[$i], $data['majors'])) {
                            $URL = new URL($hansardmajors[$major[$i]]['page_all']);
                            $URL->insert( array( 'd' => $date ) );
                            print ' &nbsp; <a href="' . $URL->generate() . '">See this day &rarr;</a>';
                        }
                        print "</h2>\n";
                        print $list[$i] ? "<ul>\n" : "<dl>\n";
                        foreach ($events as $event) {
                            \MySociety\TheyWorkForYou\Utility\Calendar::displayEntry($event);
                        }
                        print $list[$i] ? "</ul>\n" : "</dl>\n";
                    }
                }
            } else {
                if ($data['scope'] == 'calendar_summary') {
                    echo 'Hmm, we don’t have any calendar information right now. Maybe try searching our archive?';
                } else {
                    echo 'Hmm, we have no information for that date. Maybe try searching our archive?';
                }
            }
        ?></div>
        <div class="business-section__secondary">
            <div class="business-section__what-is-this">
                <h3>What is this?</h3>
                <p>Upcoming business takes information from the calendar published by Parliament, links it with our MP/Lord data, and provides email alerts for upcoming things you might be interested in.</p>
            </div>

            <form action="/search/" method="get" class="business-section__search">
                <input type="hidden" name="section" value="future">
                <h3>Search upcoming business</h3>
                <div class="row collapse">
                    <div class="small-8 columns">
                        <input type="text" name="s" id="search" value="" size="40" placeholder="Search term">
                    </div>
                    <div class="small-4 columns">
                        <input type="submit" value="Search" class="button prefix">
                    </div>
                </div>
                <p>You can set an email alert, on the results page, to be notified as soon as your search term appears in future business listings.</p>
            </form>

            <!-- calendars for the next two months should go here -->
        </div>
    </div>

</div>
