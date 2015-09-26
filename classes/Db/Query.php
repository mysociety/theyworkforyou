<?php

namespace MySociety\TheyWorkForYou\Db;

/**
 * Database Query
 *
 * Represents a single query to the database.
 *
 * ### After a `SELECT`
 *
 * If successful:
 * - `$this->success()` returns `true`.
 * - `$this->rows()` returns the number of rows selected
 * - `$this->row(n)` returns an array of the nth row, with the keys being column names.
 * - `$this->field(n,col)` returns the contents of the "col" column in the nth row.
 * - `$this->insert_id()` returns `null`.
 * - `$this->affected_rows()` returns `null`.
 *
 * If 0 rows selected:
 * - `$this->success()` returns `true`.
 * - `$this->rows()` returns `0`.
 * - `$this->row(n)` returns an empty array.
 * - `$this->field(n,col)` returns an empty string.
 * - `$this->insert_id()` returns `null`.
 * - `$this->affected_rows()` returns `null`.
 *
 * ### After an `INSERT`
 *
 * If successful:
 * - `$this->success()` returns `true`.
 * - `$this->rows()` returns `null`.
 * - `$this->row(n)` returns an empty array.
 * - `$this->field(n,col)` returns an empty string.
 * - `$this->insert_id()` returns the last_insert_id (if there's AUTO_INCREMENT on a column)`.
 * - `$this->affected_rows()` returns `1`.
 *
 * ### After an `UPDATE`
 *
 * If rows have been changed:
 * - `$this->success()` returns `true`.
 * - `$this->rows()` returns `null`.
 * - `$this->row(n)` returns an empty array.
 * - `$this->field(n,col)` returns an empty string.
 * - `$this->insert_id()` returns `0`.
 * - `$this->affected_rows()` returns the number of rows changed.
 *
 * ### After a `DELETE`
 *
 * If rows have been deleted:
 * - `$this->success()` returns `true`.
 * - `$this->rows()` returns `null`.
 * - `$this->row(n)` returns an empty array.
 * - `$this->field(n,col)` returns an empty string.
 * - `$this->insert_id()` returns `0`.
 * - `$this->affected_rows()` returns the number of rows changed.
 *
 * If no rows are deleted:
 * - `$this->success()` returns `true`.
 * - `$this->rows()` returns `null`.
 * - `$this->row(n)` returns an empty array.
 * - `$this->field(n,col)` returns an empty string.
 * - `$this->insert_id()` returns `0`.
 * - `$this->affected_rows()` returns `0`.
 *
 * ### Errors
 *
 * If there's an error for any of the above actions:
 * - `$this->success()` returns `false`.
 * - `$this->rows()` returns `null`.
 * - `$this->row(n)` returns an empty array.
 * - `$this->field(n,col)` returns an empty string.
 * - `$this->insert_id()` returns `null`.
 * - `$this->affected_rows()` returns `null`.
 */

class Query {

    public $success = true;
    public $rows = null;
    public $data = array();
    public $insert_id = null;
    public $affected_rows = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function query($sql = "", $params = null) {

        if (empty($sql)) {
            $this->success = false;

            return;
        }

        if (empty($this->conn)) {
            $this->success = false;

            return;
        }

        twfy_debug("SQL", $sql);
        twfy_debug("SQL", print_r($params, 1));

        if ($params !== null) {
            // Prepare and execute a statement
            $pdoStatement = $this->conn->prepare($sql);

            foreach ($params as $paramKey => $paramValue) {

                if (is_int($paramValue)) {
                    $paramType = \PDO::PARAM_INT;
                } else {
                    $paramType = \PDO::PARAM_STR;
                }

                $pdoStatement->bindValue($paramKey, $paramValue, $paramType);
            }

            $pdoStatement->execute();

        } else {
            // Execute the raw query
            $pdoStatement = $this->conn->query($sql);
        }

        // Test the query actually worked
        if (!$pdoStatement) {
            $this->error($this->conn->errorCode() . ': ' . $this->conn->errorInfo()[2]);
        }

        if (!$this->success) return;

        if ((!$pdoStatement) or (empty($pdoStatement))) {
            // A failed query.
            $this->success = false;

        } else {

            // A successful SELECT, SHOW, EXPLAIN or DESCRIBE query.
            $this->success = true;

            $result = $pdoStatement->fetchAll(\PDO::FETCH_ASSOC);

            $this->rows = count($result);
            $this->data = $result;

            // Sanity check that lastInsertId() is actually a number, otherwise panic
            if (is_numeric($this->conn->lastInsertId())) {
                $this->insert_id = (int) $this->conn->lastInsertId();
            } else {
                throw new Exception('Last connection ID was not numeric!');
            }
            $this->affected_rows = $pdoStatement->rowCount();

            twfy_debug("SQLRESULT", array($this, 'displayResult'));
            // mysql_free_result($q);
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

    /**
     * @param integer $row_index
     * @param string $column_name
     */
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

    /**
     * @param integer $row_index
     */
    public function row($row_index) {
        if ($this->success && $this->rows > 0)
            return $this->data[$row_index];
        return array();
    }

    # Used when debugging
    private function displayResult() {
        $html = "";

        if ($this->rows > 0) {

            $html .= "<table border=\"1\">\n<tr>\n";

            foreach (array_keys($this->data[0]) as $fieldname) {
                $html .= "<th>" . _htmlentities($fieldname) . "</th>";
            }
            $html .= "</tr>\n";

            foreach ($this->data as $index => $row) {
                $html .= "<tr>";
                foreach ($row as $n => $field) {
                    if ($n == "email" || $n == "password" || $n == "postcode") {
                        // Don't want to risk this data being displayed on any page.
                        $html .= "<td>**MASKED**</td>";
                    } else {
                        $html .= "<td>" . _htmlentities($field) . "</td>";
                    }
                }
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";
        }

        return $html;
    }

    /**
     * @param string $errormsg
     */
    public function error($errormsg) {
        // When a query goes wrong...
        $this->success = false;
        trigger_error($errormsg, E_USER_ERROR);
    }

}
