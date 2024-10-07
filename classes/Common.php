<?php

namespace MySociety\TheyWorkForYou;

class Common {
    public function getPopularSearches() {
        global $SEARCHLOG;
        $popular_searches = $SEARCHLOG->popular_recent(3, 2000, 5);

        return $popular_searches;
    }
}
