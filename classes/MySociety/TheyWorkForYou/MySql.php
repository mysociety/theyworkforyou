<?php
/**
 * MySql Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Provides access to MySQL connections and queries.
 *
 * Depends on having the `debug()` and `getmicrotime()` functions available
 * elsewhere to output debugging info.
 *
 * The {@see ParlDb} class extends this class to provide connectivity. If other
 * databases are needed, we just need to create a class for each, each one
 * extending this class.
 *
 * Then, when you need to do queries, you do:
 *
 *     $db = new \MySociety\TheyWorkForYou\ParlDb;
 *     $q = $db->query("SELECT haddock FROM fish");
 *
 * `$q` is then a {@see MySql\Query} object.
 *
 * Call `$db->display_total_duration()` at the end of a page to send total query
 * time to debug().
 */

class MySql {

    public function init($db_host, $db_user, $db_pass, $db_name) {
        global $global_connection;
        // These vars come from config.php.

        if (!$global_connection) {
            $dsn = 'mysql:dbname=' . $db_name . ';host=' . $db_host;

            try {
                $conn = new \PDO($dsn, $db_user, $db_pass);
            } catch (\PDOException $e) {
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
        $q = new MySql\Query($this->conn);
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
