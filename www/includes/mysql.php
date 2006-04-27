<?php

/* MYSQL class 

Depends on having the debug() and getmicrotime() functions available elsewhere to output debugging info.


Somewhere (probably in includes/easyparliament/init.php) there should be something like:

	Class ParlDB extends MySQL {
		function ParlDB () {
			$this->init (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
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


Versions
========
v1.2	2003-11-25
		Changed to using named constants, rather than global variables.
*/

// We'll add up the times of each query so we can output the page total at the end.
global $mysqltotalduration;
$mysqltotalduration = 0.0;


Class MySQLQuery {
	
	var $fieldnames_byid = array();
	var $fieldnames_byname = array();
	var $success = true;
	var $rows = NULL;
	var $fields = 0;
	var $data = array();
	var $insert_id = NULL;
	var $affected_rows = NULL;
	
	function MySQLQuery ($conn) {
		$this->conn = $conn;
	}

	function query ($sql="") {

		if (empty($sql)) {
			$this->success = false;
			return;
		}
		
		if (empty($this->conn)) {
			$this->success = false;
			return;
		}
		
				
		debug ("SQL", $sql);
		
		$q = mysql_query($sql,$this->conn) or $this->error(mysql_errno().": ".mysql_error());
		
		if ($this->success) {
			if ( (!$q) or (empty($q)) ) {
				// A failed query.
	
				$this->success = false;
				
				return;
				
			} elseif (is_bool($q)) {
				// A successful query of a type *other* than
				// SELECT, SHOW, EXPLAIN or DESCRIBE
				
				// For INSERTs that have generated an id from an AUTO_INCREMENT column.
				$this->insert_id = mysql_insert_id();
				
				$this->affected_rows = mysql_affected_rows();
				
				$this->success = true;
				
				return;
			
			} else {
	
				// A successful SELECT, SHOW, EXPLAIN or DESCRIBE query.		
				$this->success = true;
	
				$result = array();
				for ($i = 0; $i < mysql_num_fields($q); $i++) {
					$fieldnames_byid[$i] = mysql_field_name($q, $i);
					$fieldnames_byname[mysql_field_name($q, $i)] = $i;
				}
	
				for ($row = 0; $row < mysql_num_rows($q); $row++) {
					$result[$row] = mysql_fetch_row($q);
				}
	
				if (sizeof($result) > 0) {
					$this->rows	= sizeof($result);
				} else {
					$this->rows	= 0;
				}
	
				$this->fieldnames_byid 	= $fieldnames_byid;
				$this->fieldnames_byname = $fieldnames_byname;
				$this->fields		= sizeof($fieldnames_byid);
				$this->data			= $result;
	
				debug ("SQLRESULT", $this->_display_result());
	
				mysql_free_result($q);
	
				return;
			}
		} else {
			// There was an SQL error.
			return;
		}
		
	}
	
	
	function success() {
		return $this->success;
	}

	// After INSERTS.
	function insert_id() {
		return $this->insert_id;
	}
	
	// After INSERT, UPDATE, DELETE.
	function affected_rows() {
		return $this->affected_rows;
	}

	// After SELECT.
	function field($row_index, $column_name) {
		if ($this->rows > 0) {
            # Old slow version
			# $result = $this->_row_array($row_index);
			# return $result[$column_name];
            
            # New faster version
            $result = $this->data[$row_index][$this->fieldnames_byname[$column_name]];
            return $result;
		} else {
			return "";
		}
	}

	// After SELECT.
	function rows() {
		return $this->rows;
	}

	// After SELECT.
	function row($row_index) {
		if ($this->success) {
			$result = $this->_row_array($row_index);
			return $result;
		} else {
			return array();
		}
	}
	
	
	function _row_array($row_index) {
		$result = array();
		if ($this->rows > 0) {
			$fields = $this->data[$row_index];

			foreach ($fields as $index => $data) {
				$fieldname = $this->fieldnames_byid[$index];
				$result[$fieldname] = $data;
			}
		}
		
		return $result;
	}

	function _display_result() {
		
		$html = "";

		if (count($this->fieldnames_byid) > 0) {
		
			$html .= "<table border=\"1\">\n<tr>\n";

			foreach ($this->fieldnames_byid as $index => $fieldname) {
				$html .= "<th>".htmlentities($fieldname)."</th>";
			}
			$html .= "</tr>\n";

			foreach ($this->data as $index => $row) {
				$html .= "<tr>";
				foreach ($row as $n => $field) {
					if ($this->fieldnames_byid[$n] == "email" || $this->fieldnames_byid[$n] == "password" || $this->fieldnames_byid[$n] == "postcode") {
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
	
	
	function error($errormsg) {
		// When a query goes wrong...
		$this->success = false;

		trigger_error($errormsg, E_USER_ERROR);

		return;
	}
	
	
// End MySQLQuery class
}

$global_connection = null;
Class MySQL {
	
	function init ($db_host, $db_user, $db_pass, $db_name) {
		global $global_connection;
		// These vars come from config.php.

		if (!$global_connection) {
			$conn = mysql_connect($db_host, $db_user, $db_pass);
			if(!$conn) {
				print ("<p>DB connection attempt failed.</p>");
				exit;
			}
			if(!mysql_select_db($db_name, $conn)) {
				print ("<p>DB select failed</p>");
				exit;
			}
			$global_connection = $conn;
		}
		$this->conn = $global_connection;

		// Select default character set
		$q = new MySQLQuery($this->conn);

		return true;
	}
	
	
	function query ($sql) {
		// Pass it an SQL query and if the query was successful
		// it returns a MySQLQuery object which you can get results from.
		
		$start = getmicrotime();
		$q = new MySQLQuery($this->conn);
		$q->query($sql);
		
		$duration = getmicrotime() - $start;
		global $mysqltotalduration;
		$mysqltotalduration += $duration;
		debug ("SQL", "Complete after $duration seconds.");
		// We could also output $q->mysql_info() here, but that's for
		// PHP >= 4.3.0.

		return $q;

	}


	// Call at the end of a page.
	function display_total_duration () {
		global $mysqltotalduration;
		debug ("TIME", "Total time for MySQL queries on this page: " . $mysqltotalduration . " seconds.");
	}


// End MySQL class
}


?>
