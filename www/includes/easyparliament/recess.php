<?php

/*
recess.php 2004-06-05
francis@flourish.org
*/

$GLOBALS['recessdates'] = array(
	2001 => array(
		1=>array('less'=>8),
		2=>array('between'=>array(15,26)),
		4=>array('between'=>array(10,23)),
		5=>array('more'=>13),
		6=>array('less'=>13),
		7=>array('more'=>20),
		8=>array('all'=>1),
		9=>array('less'=>14, 'more'=>14),
		10=>array('less'=>4, 'between'=>array(4,8, 8,15)),
		12=>array('more'=>19) ),
	2002 => array(
		1=>array('less'=>8),
		2=>array('between'=>array(14,25)),
		3=>array('more'=>26),
		4=>array('less'=>3, 'between'=>array(3,10)),
		5=>array('more'=>24),
		6=>array('less'=>10),
		7=>array('more'=>24),
		8=>array('all'=>1),
		9=>array('less'=>19, 'more'=>24),
		10=>array('less'=>14),
		12=>array('more'=>19) ),
	2003 => array(
		1=>array('less'=>7),
		2=>array('between'=>array(13,24)),
		4=>array('between'=>array(14,28)),
		5=>array('more'=>22),
		6=>array('less'=>3),
		7=>array('more'=>17),
		8=>array('all'=>1),
		9=>array('less'=>8, 'more'=>18),
		10=>array('less'=>14),
		12=>array('more'=>18)),
	2004 => array(
		1=>array('less'=>5),
		2=>array('between'=>array(12,23)),
		4=>array('between'=>array(1,19)),
		5=>array('more'=>27),
		6=>array('less'=>7),
		7=>array('more'=>22),
		8=>array('all'=>1),
		9=>array('less'=>7, 'more'=>16),
		10=>array('less'=>11),
		12=>array('more'=>21) ),
	2005 => array(
		1=>array('less'=>10),
		2=>array('between'=>array(10,21)),
		3=>array('more'=>24),
		4=>array('less'=>4, 'more'=>10),
		5=>array('less'=>11, 'more'=>26),
		6=>array('less'=>6),
		7=>array('more'=>21),
		8=>array('all'=>1),
		9=>array('all'=>1),
		10=>array('less'=>10),
		12=>array('more'=>20) ),
	2006 => array(
		1=>array('less'=>9),
		2=>array('between'=>array(16,27)),
		3=>array('more'=>30),
		4=>array('less'=>18),
		5=>array('more'=>25),
		6=>array('less'=>5),
		7=>array('more'=>25),
		8=>array('all'=>1),
		9=>array('all'=>1),
		10=>array('less'=>9),
		12=>array('more'=>19),
	),
	2007 => array(
		1=>array('less'=>8),
		2=>array('between'=>array(8,19)),
		3=>array('more'=>29),
		4=>array('less'=>16),
		5=>array('more'=>24),
		6=>array('less'=>4),
		7=>array('more'=>26),
		8=>array('all'=>1),
		9=>array('all'=>1),
		10=>array('less'=>8),
	),
);

function currently_in_recess() {
    // Main file which recesswatcher.py overwrites each day
    $h = fopen(RECESSFILE, "r");
    $today = date("Y-m-d");
    while ($line = fgets($h)){
        list($name, $from, $to) = split(",", $line);
        if ($from <= $today and $today <= $to) {
            return array($name, trim($from), trim($to));
        }
    }
    // Second manual override file
    $h = fopen(RECESSFILE.".extra", "r");
    while ($line = fgets($h)){
        list($name, $from, $to) = split(",", $line);
        if ($from <= $today and $today <= $to) {
            return array($name, trim($from), trim($to));
        }
    }
    return false;
}

function recess_prettify($currentDay, $month, $year) {
	global $recessdates;
	$recess = 0;
	if (isset($recessdates[$year][$month]['all'])) {
		$recess = 'Summer Recess';
	}
	if ( (isset($recessdates[$year][$month]['less']) && $currentDay < $recessdates[$year][$month]['less'])
	|| (isset($recessdates[$year][$month]['more']) && $currentDay > $recessdates[$year][$month]['more'])
	|| (isset($recessdates[$year][$month]['between']) && $currentDay > $recessdates[$year][$month]['between'][0] && $currentDay < $recessdates[$year][$month]['between'][1])
	|| (isset($recessdates[$year][$month]['between'][2]) && $currentDay > $recessdates[$year][$month]['between'][2] && $currentDay < $recessdates[$year][$month]['between'][3]) ) {
		switch ($month) {
			case 1: case 12: $recess = 'Christmas Recess'; break;
			case 2: $recess = 'Half Term Week'; break;
			case 3: $recess = 'Easter Recess'; break;
			case 4: if (isset($recessdates[$year][$month]['more']) && $currentDay > $recessdates[$year][$month]['more']) { $recess = 'Election Recess'; } else { $recess = 'Easter Recess'; } break;
			case 5: if ($year==2001 || (isset($recessdates[$year][$month]['less']) && $currentDay < $recessdates[$year][$month]['less'])) { $recess = 'Election Recess'; } else { $recess = 'Whit Recess'; } break;
			case 6: if ($year==2001) { $recess = 'Election Recess'; } else { $recess = 'Whit Recess'; } break;
			case 7: case 8: $recess = 'Summer Recess'; break;
			case 9: if (isset($recessdates[$year][$month]['less']) && $currentDay < $recessdates[$year][$month]['less']) { $recess = 'Summer Recess'; } else { $recess = 'Conference Recess'; } break;
			case 10: $recess = 'Conference Recess'; break;
			default: $recess = 1;
		}
	}
	return $recess;
}
?>
