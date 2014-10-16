<?php

/* MYSQL class

Depends on having the debug() and getmicrotime() functions available elsewhere to output debugging info.

Somewhere (probably in includes/easyparliament/init.php) there should be something like:

    Class ParlDB extends MySQL {
        function ParlDB() {
            $this->init (OPTION_TWFY_DB_HOST, OPTION_TWFY_DB_USER, OPTION_TWFY_DB_PASS, OPTION_TWFY_DB_NAME);
        }
    }

Then, when you need to do queries, you do:

    $db = new \MySociety\TheyWorkForYou\ParlDb;
    $q = $db->query("SELECT haddock FROM fish");

$q is then a \MySociety\TheyWorkForYou\MySql\Query object.

If other databases are needed, we just need to create a class for each, each one
extending MySQL.

Call $db->display_total_duration() at the end of a page to send total query time to debug().

(n is 0-based below...)

*/

// We'll add up the times of each query so we can output the page total at the end.
global $mysqltotalduration;
$mysqltotalduration = 0.0;

$global_connection = null;
Class MySQL {

    public function init($db_host, $db_user, $db_pass, $db_name) {
        global $global_connection;
        // These vars come from config.php.

        if (!$global_connection) {
            $dsn = 'mysql:dbname=' . $db_name . ';host=' . $db_host;

            try {
                $conn = new PDO($dsn, $db_user, $db_pass);
            } catch (PDOException $e) {
                $this->fatal_error('We were unable to connect to the TheyWorkForYou database for some reason. Please try again in a few minutes.');
            }

            $global_connection = $conn;
        }

        $this->conn = $global_connection;

        return true;
    }

    public function quote($string) {
        return $this->conn->quote($string);
    }

    public function query($sql, $params = NULL) {
        // Pass it an SQL query and if the query was successful
        // it returns a \MySociety\TheyWorkForYou\MySql\Query object which you can get results from.

        $start = getmicrotime();
        $q = new \MySociety\TheyWorkForYou\MySql\Query($this->conn);
        $q->query($sql, $params);

        $duration = getmicrotime() - $start;
        global $mysqltotalduration;
        $mysqltotalduration += $duration;
        twfy_debug ("SQL", "Complete after $duration seconds.");
        // We could also output $q->mysql_info() here, but that's for
        // PHP >= 4.3.0.
        return $q;
    }

    // Call at the end of a page.
    public function display_total_duration() {
        global $mysqltotalduration;
        twfy_debug ("TIME", "Total time for MySQL queries on this page: " . $mysqltotalduration . " seconds.");
    }

    public function fatal_error($error) {
        echo '
<html><head><title>TheyWorkForYou - Database Error</title></head>
<body>
<h1><a href="/"><img border="0" src="/images/theyworkforyoucom.gif" width="293" height="28" alt="TheyWorkForYou"></a></h1>
<h2>Database error</h2>
';
        echo "<p>$error</p>";
        echo '</body></html>';
        exit;
    }

// End MySQL class
}
