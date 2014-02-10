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

    $db = new ParlDB;
    $q = $db->query("SELECT haddock FROM fish");

$q is then a MySQLQuery object.

If other databases are needed, we just need to create a class for each, each one
extending MySQL.

Call $db->display_total_duration() at the end of a page to send total query time to debug().

(n is 0-based below...)

After a SELECT
==============
If successful:
    $q->success() returns true.
    $q->rows() returns the number of rows selected
    $q->row(n) returns an array of the nth row, with the keys being column names.
    $q->field(n,col) returns the contents of the "col" column in the nth row.
    $q->insert_id() returns NULL.
    $q->affected_rows() returns NULL.
If 0 rows selected:
    $q->success() returns true.
    $q->rows() returns 0.
    $q->row(n) returns an empty array.
    $q->field(n,col) returns "".
    $q->insert_id() returns NULL.
    $q->affected_rows() returns NULL.

After an INSERT
===============
If successful:
    $q->success() returns true.
    $q->rows() returns NULL.
    $q->row(n) returns an empty array.
    $q->field(n,col) returns "".
    $q->insert_id() returns the last_insert_id (if there's AUTO_INCREMENT on a column).
    $q->affected_rows() returns 1.

After an UPDATE
===============
If rows have been changed:
    $q->success() returns true.
    $q->rows() returns NULL.
    $q->row(n) returns an empty array.
    $q->field(n,col) returns "".
    $q->insert_id() returns 0.
    $q->affected_rows() returns the number of rows changed.

After a DELETE
==============
If rows have been deleted:
    $q->success() returns true.
    $q->rows() returns NULL.
    $q->row(n) returns an empty array.
    $q->field(n,col) returns "".
    $q->insert_id() returns 0.
    $q->affected_rows() returns the number of rows changed.
If no rows are deleted:
    $q->success() returns true.
    $q->rows() returns NULL.
    $q->row(n) returns an empty array.
    $q->field(n,col) returns "".
    $q->insert_id() returns 0.
    $q->affected_rows() returns 0.


If there's an error for any of the above actions:
    $q->success() returns false.
    $q->rows() returns NULL.
    $q->row(n) returns an empty array.
    $q->field(n,col) returns "".
    $q->insert_id() returns NULL.
    $q->affected_rows() returns NULL.

*/

// We'll add up the times of each query so we can output the page total at the end.
global $mysqltotalduration;
$mysqltotalduration = 0.0;


Class MySQLQuery {

    public $success = true;
    public $rows = NULL;
    public $data = array();
    public $insert_id = NULL;
    public $affected_rows = NULL;

    public function MySQLQuery($conn) {
        $this->conn = $conn;
    }

    public function query($sql="") {

        if (empty($sql)) {
            $this->success = false;

            return;
        }

        if (empty($this->conn)) {
            $this->success = false;

            return;
        }

        twfy_debug ("SQL", $sql);
        $q = mysql_query($sql,$this->conn) or $this->error(mysql_errno().": ".mysql_error());
        if (!$this->success) return;

        if ( (!$q) or (empty($q)) ) {
            // A failed query.
            $this->success = false;
        } elseif (is_bool($q)) {
            // A successful query of a type *other* than
            // SELECT, SHOW, EXPLAIN or DESCRIBE

            // For INSERTs that have generated an id from an AUTO_INCREMENT column.
            $this->insert_id = mysql_insert_id();
            $this->affected_rows = mysql_affected_rows();
            $this->success = true;

        } else {

            // A successful SELECT, SHOW, EXPLAIN or DESCRIBE query.
            $this->success = true;

            $result = array();
            while ($row = mysql_fetch_assoc($q)) {
                $result[] = $row;
            }

            $this->rows = count($result);
            $this->data = $result;
            twfy_debug ("SQLRESULT", array($this, '_display_result'));
            mysql_free_result($q);
        }
    }

    public function success() {
        return $this->success;
    }

    // After INSERTS.
    public function insert_id() {
        return $this->insert_id;
    }

    // After INSERT, UPDATE, DELETE.
    public function affected_rows() {
        return $this->affected_rows;
    }

    // After SELECT.
    public function field($row_index, $column_name) {
        if ($this->rows > 0)
            return $this->data[$row_index][$column_name];
        return "";
    }

    // After SELECT.
    public function rows() {
        return $this->rows;
    }

    // After SELECT.
    public function row($row_index) {
        if ($this->success && $this->rows > 0)
            return $this->data[$row_index];
        return array();
    }

    # Used when debugging
    public function _display_result() {
        $html = "";

        if ($this->rows > 0) {

            $html .= "<table border=\"1\">\n<tr>\n";

            foreach (array_keys($this->data[0]) as $fieldname) {
                $html .= "<th>".htmlentities($fieldname)."</th>";
            }
            $html .= "</tr>\n";

            foreach ($this->data as $index => $row) {
                $html .= "<tr>";
                foreach ($row as $n => $field) {
                    if ($n == "email" || $n == "password" || $n == "postcode") {
                        // Don't want to risk this data being displayed on any page.
                        $html .= "<td>**MASKED**</td>";
                    } else {
                        $html .= "<td>".htmlentities($field)."</td>";
                    }
                }
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";
        }

        return $html;
    }

    public function error($errormsg) {
        // When a query goes wrong...
        $this->success = false;
        trigger_error($errormsg, E_USER_ERROR);
    }

// End MySQLQuery class
}

$global_connection = null;
Class MySQL {

    public function init($db_host, $db_user, $db_pass, $db_name) {
        global $global_connection;
        // These vars come from config.php.

        if (!$global_connection) {
            $conn = mysql_connect($db_host, $db_user, $db_pass);
            if (!$conn)
                $this->fatal_error('Unfortunately, we could not connect to the database for some reason. Please try again in a few minutes.');
            if (!mysql_select_db($db_name, $conn))
                $this->fatal_error('Strangely, the TheyWorkForYou database is missing! Hopefully someone will spot this soon, please try again in a few minutes.');
            $global_connection = $conn;
        }
        $this->conn = $global_connection;

        // Select default character set
        $q = new MySQLQuery($this->conn);

        return true;
    }

    public function fatal_error($error) {
        echo '
<html><head><title>TheyWorkForYou - Database error</title></head>
<body>
<h1><a href="/"><img border="0" src="/images/theyworkforyoucom.gif" width="293" height="28" alt="TheyWorkForYou"></a></h1>
<h2>Database error</h2>
';
        echo "<p>$error</p>";
        echo '</body></html>';
        exit;
    }

    public function query($sql) {
        // Pass it an SQL query and if the query was successful
        // it returns a MySQLQuery object which you can get results from.

        $start = getmicrotime();
        $q = new MySQLQuery($this->conn);
        $q->query($sql);

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

// End MySQL class
}
