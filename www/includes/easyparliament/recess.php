<?php

/*
recess.php 2004-06-05
francis@flourish.org
*/

/* UK Parliament */
$GLOBALS['recessdates'][1] = array(
	2000 => array(
		12=>array('more'=>21)
	),
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
		12=>array('more'=>18),
	),
	2008 => array(
		1=>array('less'=>7),
		2=>array('between'=>array(7,18)),
		4=>array('between'=>array(3,21)),
		5=>array('more'=>22),
		6=>array('less'=>2),
		7=>array('more'=>22),
		8=>array('all'=>1),
		9=>array('all'=>1),
		10=>array('less'=>6),
	),
	2009 => array(
		1=>array('less'=>12),
		2=>array('between'=>array(12,23)),
		4=>array('between'=>array(2,20)),
		5=>array('more'=>21),
		6=>array('less'=>1),
		7=>array('more'=>21),
		8=>array('all'=>1),
		9=>array('all'=>1),
		10=>array('less'=>12),
	),
);

/* Scottish Parliament */
$GLOBALS['recessdates'][4] = array(
	1999 => array(
		7 => array('more' => 2),
		8 => array('less' => 31),
		10 => array('between' => array(8,25)),
		12 => array('more' => 17)
	),
	2000 => array(
		1 => array('less' => 10),
		4 => array('between' => array(7,25)),
		7 => array('more' => 7),
		8 => array('all' => 1),
		9 => array('less' => 4),
		10 => array('between' => array(6,23)),
		12 => array('more' => 20)
	),
	2001 => array(
		1=>array('less'=>8),
		2=>array('between'=>array(16,26)),
		4=>array('between'=>array(6,23)),
		6=>array('more'=>29),
		7=>array('all'=>1),
		8=>array('all'=>1),
		9=>array('less'=>3),
		10=>array('between'=>array(5,22)),
		12=>array('more'=>21) ),
	2002 => array(
		1=>array('less'=>7),
		2=>array('between'=>array(15,25)),
		3=>array('more'=>28),
		4=>array('less'=>15),
		7=>array('more'=>10),
		8=>array('all'=>1),
		9=>array('less'=>2),
		10=>array('between'=>array(11,28)),
		12=>array('more'=>20) ),
	2003 => array(
		1=>array('less'=>6),
		4=>array('between'=>array(0,31)),
		5=>array('less'=>2),
		6=>array('more'=>27),
		7=>array('all'=>1),
		8=>array('all'=>1),
		10=>array('between'=>array(10,27)),
		12=>array('more'=>19)),
	2004 => array(
		1=>array('less'=>5),
		2=>array('between'=>array(13,23)),
		4=>array('between'=>array(2,19)),
		6=>array('more'=>25),
		7=>array('all'=>1),
		8=>array('less'=>30),
		10=>array('between'=>array(10,23)),
		12=>array('more'=>26) ),
	2005 => array(
		1=>array('less'=>8),
		2=>array('between'=>array(11,21)),
		3=>array('more'=>24),
		4=>array('less'=>11),
		7=>array('more'=>1),
		8=>array('all'=>1),
		9=>array('less'=>5),
		10=>array('between'=>array(7,24)),
		12=>array('more'=>23) ),
	2006 => array(
		1=>array('less'=>9),
		2=>array('between'=>array(10,20)),
		4=>array('less'=>18),
		7=>array('more'=>0),
		8=>array('all'=>1),
		9=>array('less'=>4),
		10=>array('between'=>array(6,23)),
		12=>array('more'=>22),
	),
	2007 => array(
		1=>array('less'=>8),
		4=>array('more'=>2),
		6=>array('more'=>29),
		7=>array('all'=>1),
		8=>array('all'=>1),
		9=>array('less'=>3),
		10=>array('between'=>array(5,22)),
		12=>array('more'=>21),
	),
	2008 => array(
		1=>array('less'=>5),
		2=>array('between'=>array(8,18)),
		3=>array('more'=>28),
		4=>array('less'=>14),
		6=>array('more'=>27),
		7=>array('all'=>1),
		8=>array('all'=>1),
		10=>array('between'=>array(10,27)),
	),
);

/*
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
*/

function recess_prettify($day, $month, $year, $body) {
	global $recessdates;
	$dates = $recessdates[$body];
	$recess = 0; $from = ''; $to = '';
	if (isset($dates[$year][$month]['all'])) {
		$recess = 'Summer Recess';
		if (isset($dates[$year][7]['all'])) {
			$from = "$year-6-" . $dates[$year][6]['more'];
		} else {
			$from = "$year-7-" . $dates[$year][7]['more'];
		}
		if (!isset($dates[$year][9]))
			$to = "$year-08-31";
		elseif (isset($dates[$year][9]['all']))
			$to = "$year-10-" . $dates[$year][10]['less'];
		else
			$to = "$year-9-" . $dates[$year][9]['less'];
	}
	if ( (isset($dates[$year][$month]['less']) && $day < $dates[$year][$month]['less'])
	|| (isset($dates[$year][$month]['more']) && $day > $dates[$year][$month]['more'])
	|| (isset($dates[$year][$month]['between']) && $day > $dates[$year][$month]['between'][0] && $day < $dates[$year][$month]['between'][1])
	|| (isset($dates[$year][$month]['between'][2]) && $day > $dates[$year][$month]['between'][2] && $day < $dates[$year][$month]['between'][3]) ) {
		switch ($month) {
			case 1: case 12: $recess = 'Christmas Recess'; break;
			case 2: if ($body==1) $recess = 'Half Term Week';
				elseif ($body==4) $recess = 'February Recess';
				break;
			case 3: if ($body==1) $recess = 'Easter Recess';
				elseif ($body==4) $recess = 'Spring Recess';
				break;
			case 4: if (isset($dates[$year][$month]['more']) && $day > $dates[$year][$month]['more']) {
					$recess = 'Election Recess';
				} elseif ($body==4 && $year==2003) {
					$recess = 'Election Recess';
				} elseif ($body==1) {
					$recess = 'Easter Recess';
				} elseif ($body==4) {
					$recess = 'Spring Recess';
				}
				break;
			case 5: if ($year==2001 || (isset($dates[$year][$month]['less']) && $day < $dates[$year][$month]['less'])) {
					$recess = 'Election Recess';
				} else {
					$recess = 'Whit Recess';
				}
				break;
			case 6: if ($year==2001) {
					$recess = 'Election Recess';
				} elseif ($body==1) {
					$recess = 'Whit Recess';
				} elseif ($body==4) {
					$recess = 'Summer Recess';
				} else {
					trigger_error("Argh6");
				}
				break;
			case 7: case 8: $recess = 'Summer Recess';
				break;
			case 9: if (isset($dates[$year][$month]['less']) && $day < $dates[$year][$month]['less']) {
					$recess = 'Summer Recess';
				} elseif ($body==1) {
					$recess = 'Conference Recess';
				} else {
					trigger_error("Argh9");
				}
				break;
			case 10: if ($body==1) $recess = 'Conference Recess';
				elseif ($body==4) $recess = 'Autumn Recess';
				break;
			default: $recess = 1;
		}
		if (isset($dates[$year][$month]['less']) && $day < $dates[$year][$month]['less']) {
			$to = "$year-$month-" . $dates[$year][$month]['less'];
			if ($month==1)
				$from = ($year-1)."-12-" . $dates[$year-1][12]['more'];
			else {
				for ($newmonth = $month-1; $newmonth>=1; $newmonth--) {
					if (isset($dates[$year][$newmonth]['more'])) {
						$from = "$year-".($newmonth)."-" . $dates[$year][$newmonth]['more'];
						break;
					}
				}
			}
		}
		if (isset($dates[$year][$month]['more']) && $day > $dates[$year][$month]['more']) {
			$from = "$year-$month-" . $dates[$year][$month]['more'];
			if ($month==12)
				$to = ($year+1)."-01-" . $dates[$year+1][1]['less'];
			else {
				for ($newmonth = $month+1; $newmonth<=12; $newmonth++) {
					if (isset($dates[$year][$newmonth]['less'])) {
						$to = "$year-".($newmonth)."-" . $dates[$year][$newmonth]['less'];
						break;
					}
				}
			}
		}
		if (isset($dates[$year][$month]['between']) && $day > $dates[$year][$month]['between'][0] && $day < $dates[$year][$month]['between'][1]) {
			$from = "$year-$month-" . $dates[$year][$month]['between'][0];
			$to = "$year-$month-" . $dates[$year][$month]['between'][1];
		}
		if (isset($dates[$year][$month]['between'][2]) && $day > $dates[$year][$month]['between'][2] && $day < $dates[$year][$month]['between'][3]) {
			$from = "$year-$month-" . $dates[$year][$month]['between'][2];
			$to = "$year-$month-" . $dates[$year][$month]['between'][3];
		}
	}
	return array($recess, $from, $to);
}
?>
