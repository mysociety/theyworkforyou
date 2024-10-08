<?php

namespace MySociety\TheyWorkForYou\Db;

// We'll add up the times of each query so we can output the page total at the end.

global $mysqltotalduration;
$mysqltotalduration = 0.0;
$global_connection = null;

/**
 * Database Connection
 *
 * Somewhere (probably in `includes/easyparliament/init.php`) there should be
 * something like:
 *
 * ```php
 * Class ParlDB extends \MySociety\TheyWorkForYou\Db\Connection {
 *     function ParlDB() {
 *         $this->init (OPTION_TWFY_DB_HOST, OPTION_TWFY_DB_USER, OPTION_TWFY_DB_PASS, OPTION_TWFY_DB_NAME);
 *     }
 * }
 * ```
 *
 * Then, when you need to do queries, you do:
 *
 * ```php
 * $db = new ParlDB;
 * $q = $db->query("SELECT haddock FROM fish");
 * ```
 *
 * `$q` is then an instance of `Db\Query`.
 *
 * If other databases are needed, we just need to create a class for each, each
 * one extending `Db\Connection`.
 *
 * Call `$db->display_total_duration()` at the end of a page to send total
 * query time to `debug()`.
 *
 * Depends on having the `debug()` and `getmicrotime()` functions available
 * elsewhere to output debugging info.
 *
 */

class Connection {
    /**
     * Initialise Connection
     *
     * If an existing MySQL connection exists, use that. Otherwise, create a
     * new connection.
     *
     * @param string $db_host The hostname of the database server
     * @param string $db_user The user to connect to the database as
     * @param string $db_pass The password for the database user
     * @param string $db_name The name of the database
     *
     * @return boolean If the connection has been created successfully.
     */

    public function init($db_host, $db_user, $db_pass, $db_name) {
        global $global_connection;
        // These vars come from config.php.

        if (!$global_connection) {
            $dsn = 'mysql:charset=utf8;dbname=' . $db_name;
            if ($db_host) {
                $dsn .= ';host=' . $db_host;
            }

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

    /**
     * Quote String
     *
     * @param string $string The string to quote.
     *
     * @return string The quoted string.
     */

    public function quote($string) {
        return $this->conn->quote($string);
    }

    /**
     * Execute Query
     *
     * Takes a query, executes it and turns it into a query object.
     *
     * @param string     $sql    The SQL query to execute
     * @param array|null $params Parameters to inject into the query
     *
     * @return Query An object containing the results of the query.
     */

    public function query($sql, $params = null) {

        $start = getmicrotime();
        $q = new \MySociety\TheyWorkForYou\Db\Query($this->conn);
        $q->query($sql, $params);

        $duration = getmicrotime() - $start;
        global $mysqltotalduration;
        $mysqltotalduration += $duration;
        twfy_debug("SQL", "Complete after $duration seconds.");
        // We could also output $q->mysql_info() here, but that's for
        // PHP >= 4.3.0.
        return $q;
    }

    /**
     * Display Total Duration
     *
     * Displays the total time taken to execute all queries made via this
     * connection.
     */

    public function display_total_duration() {
        global $mysqltotalduration;
        twfy_debug("TIME", "Total time for MySQL queries on this page: " . $mysqltotalduration . " seconds.");
    }

    /**
     * Fatal Error
     *
     * Display a fatal error and exit the script.
     *
     * @param string $error The error message to display.
     */

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

}
