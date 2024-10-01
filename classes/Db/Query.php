<?php

namespace MySociety\TheyWorkForYou\Db;

/**
 * Database Query
 *
 * Represents a single query to the database.
 *
 * ### After a `SELECT`
 *
 * $q can be used as an iterator with foreach to loop over the rows.
 * $q->first() returns the first row if present, else null.
 * $q->exists() returns a boolean as to whether there were any results.
 * $q->rows() returns the number of rows selected
 * $q->insert_id() returns NULL.
 * $q->affected_rows() returns NULL.
 *
 * ### After an `INSERT`
 *
 * $q->rows() returns NULL.
 * $q->insert_id() returns the last_insert_id (if there's AUTO_INCREMENT on a column).
 * $q->affected_rows() returns 1.
 *
 * ### After an `UPDATE`
 *
 * If rows have been changed:
 * $q->rows() returns NULL.
 * $q->insert_id() returns 0.
 * $q->affected_rows() returns the number of rows changed.
 *
 * ### After a `DELETE`
 *
 * $q->rows() returns NULL.
 * $q->insert_id() returns 0.
 * $q->affected_rows() returns the number of rows changed.
 */

class Query implements \IteratorAggregate, \ArrayAccess {
    private $success = true;
    private $rows = null;
    private $data = [];
    private $insert_id = null;
    private $affected_rows = null;

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

        if (!$this->success) {
            return;
        }

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

            twfy_debug("SQLRESULT", [$this, 'displayResult']);
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

    public function getIterator() {
        return new \ArrayIterator($this->data);
    }

    public function offsetGet($offset) {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value) {
        throw new \Exception();
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        throw new \Exception();
    }

    public function fetchAll() {
        return $this->data;
    }

    /**
     * @param integer $row_index
     * @param string $column_name
     */
    public function field($row_index, $column_name) {
        if ($this->rows > 0) {
            return $this->data[$row_index][$column_name];
        }
        return "";
    }

    public function rows() {
        return $this->rows;
    }

    // After SELECT.

    /**
     * @param integer $row_index
     */
    public function row($row_index) {
        if ($this->success && $this->rows > 0) {
            return $this->data[$row_index];
        }
        return [];
    }

    public function exists() {
        return $this->rows > 0;
    }

    public function first() {
        return $this->rows > 0 ? $this[0] : null;
    }

    # Used when debugging
    public function displayResult() {
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
