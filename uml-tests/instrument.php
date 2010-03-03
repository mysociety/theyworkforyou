<?php
  /* To instrument a file for code coverage, include this file with
     something like:

         include_once "../../includes/instrument.php";

     ... at the top of each .php file.  Also make sure you change
     $coverage_directory to somewhere writable.
  */

function save_code_coverage () {
    $coverage_directory = "/home/alice/twfy-coverage/";
    if (!file_exists($coverage_directory)) {
        mkdir($coverage_directory,0777,TRUE);
    }
    global $coverage_identifier;
    $output_filename = $coverage_directory . $coverage_identifier;
    $coverage_data = xdebug_get_code_coverage();
    $fp = fopen($output_filename,"w");
    fwrite($fp,$output_filename."\n");
    foreach ($coverage_data as $filename => $line_map) {
        fwrite($fp,$filename."\n");
        foreach ($line_map as $line_number => $number_of_uses) {
            fwrite($fp,"  ".$line_number.": ".$number_of_uses."\n");
        }
    }
    fclose($fp);
    xdebug_stop_code_coverage();
}

// Turn on the code coverage if it hasn't already been started:
if (!$coverage_identifier) {
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED|XDEBUG_CC_DEAD_CODE);
    // By default, make the filename the ISO 8601 time:
    $coverage_identifier = strftime("%Y-%m-%dT%H:%M:%S%z");
    // ... but if we can get the test identifier, append that:
    $test_id = $_GET["test_id"];
    if ($test_id) {
        $coverage_identifier .= "_" . $test_id;
    }
    register_shutdown_function('save_code_coverage');
}

?>
