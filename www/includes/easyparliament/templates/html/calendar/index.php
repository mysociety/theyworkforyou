<div class="full-page__row">
    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
                <?= $parent_title ?>
            </h1>
            <p class="business-section__header__date">
                <?= $title ?>
            </p>
        </div>
        <div class="business-section__primary">
            <?php
                foreach ($dates as $date => $day_events) {
                    foreach ($order as $chamber) {
                        if (!array_key_exists($chamber['name'], $day_events)) {
                            continue;
                        }
                        $events = $day_events[$chamber['name']];
                        print "<h2>$chamber[name]";
                        if ($chamber['url'] ?? '') {
                            print ' &nbsp; <a href="' . $chamber['url'] . '">See this day &rarr;</a>';
                        }
                        print "</h2>\n";
                        print $chamber['list'] ? "<ul class='future'>\n" : "<dl class='future'>\n";
                        foreach ($events as $event) {
                            \MySociety\TheyWorkForYou\Utility\Calendar::displayEntry($event);
                        }
                        print $chamber['list'] ? "</ul>\n" : "</dl>\n";
                    }
                }
                ?>
        </div>
        <div class="business-section__secondary">
            <div class="business-section__secondary__item">
                <h3>What is this?</h3>
                <p><?= $parent_title ?> takes information from the calendar published by Parliament, and links it with our MP/Lord data, to give you an overview of what will be happening in Parliament on a given day.</p>
            </div>
            <div class="business-section__secondary__item">
                <h3>Search upcoming business</h3>
                <form action="/search/">
                    <label for="calendar-sidebar-search">Search term</label>
                    <div class="row collapse">
                        <div class="small-9 columns">
                            <input type="search" id="calendar-sidebar-search" name="q">
                        </div>
                        <div class="small-3 columns">
                            <button type="submit" class="prefix">Search</button>
                        </div>
                    </div>
                    <input type="hidden" name="section" value="future">
                </form>
            </div>
            <div class="business-section__secondary__item">
                <h3>Future business calendar</h3>
                <?php
                    foreach ($data['years'] as $year => $months) {
                        foreach ($months as $month => $dates) {
                            include dirname(__FILE__) . '/../section/_calendar.php';
                        }
                    }
                ?>
            </div>
        </div>
    </div>
</div>
