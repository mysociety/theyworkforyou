<?php

namespace MySociety\TheyWorkForYou;

class Common {
    public function getPopularSearches() {
        global $SEARCHLOG;
        $popular_searches = $SEARCHLOG->popular_recent(5, 2000, 5);

        return $popular_searches;
    }
}
