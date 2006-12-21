<?php
// For displaying the main Hansard content listings (by date).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// $data will be like this:
// $data = array(
// 		'info' => array (
//			'major'	=> 1
//		),
// 		'years' => array (
//			'2004' => array (
//				'01' => array ('01', '02', '03' ... '31'),
//				'02' => etc...
//			)
//		)
// )
// It will just have entries for days for which we have relevant
// hansard data.
// But months that have no data will still have a month array (empty).

// $data['info'] may have 'onday', a 'yyyy-mm-dd' date which indicates
// which date will be highlighted (otherwise, today is).

include_once INCLUDESPATH."easyparliament/recess.php";
global $PAGE, $DATA, $this_page, $hansardmajors;

twfy_debug("TEMPLATE", "hansard_calendar.php");

if (isset($data['years'])) {
	foreach ($data['years'] as $year => $months) {
		foreach ($months as $month => $dates) {

			// $month and $year are integers.
			// $dates is an array of dates that should be links in this month.
			
			// From http://www.zend.com/zend/trick/tricks-Oct-2002.php
			// Adjusted for style, putting Monday first, and the URL of the page linked to.

						
			// Create array containing abbreviations of days of week.
			$daysOfWeek = array('Mon','Tue','Wed','Thu','Fri','Sat','Sun');
			
			// What is the first day of the month in question?
			$firstDayOfMonth = mktime(0,0,0,$month,1,$year);
			
			// How many days does this month contain?
			$numberDays = date('t',$firstDayOfMonth);
			
			// Retrieve some information about the first day of the
			// month in question.
			$dateComponents = getdate($firstDayOfMonth);
			
			// What is the name of the month in question?
			$monthName = $dateComponents['month'];

			// We can set a particular day to be highlighted.
			// If we don't set it, today will be highlighted.
			if (isset($data['info']['onday'])) {
				// 'onday' is like 'yyyy-mm-dd'.
				list($onY, $onM, $onD) = explode('-', $data['info']['onday']);
				if ($onY == $year && $onM == $month) {
					$toDay = $onD;
				} else {
					$toDay = '';
				}
			} else {
				// If this calendar is for this current, real world, month
				// we get the value of today, so we can highlight it.
				$nowDateComponents = getdate();

				if ($nowDateComponents['year'] == $year && $nowDateComponents['mon'] == $month) {
					$toDay = $nowDateComponents['mday'];
				} else {
					$toDay = '';
				}
			}

			// What is the index value (0-6) of the first day of the
			// month in question.
			
			// Adjusted to cope with the week starting on Monday.
			$dayOfWeek = $dateComponents['wday'] - 1;
			
			// Adjusted to cope with the week starting on Monday.
			if ($dayOfWeek < 0) {
				$dayOfWeek = 6;
			}
			
			// Create the table tag opener and day headers
				?>
				<div class="calendar">
				<table border="0">
				<caption><?php echo "$monthName $year"; ?></caption>
				<thead>
				<tr><?php
			
			// Create the calendar headers
			
			foreach($daysOfWeek as $day) {
				print "<th>$day</th>";
			} 
			
			// Create the rest of the calendar
			
			// Initiate the day counter, starting with the 1st.
			
			$currentDay = 1;
			
			?></tr>
				</thead>
				<tbody>
				<tr><?php
			
			// The variable $dayOfWeek is used to
			// ensure that the calendar
			// display consists of exactly 7 columns.
			
			if ($dayOfWeek > 0) { 
				print "<td colspan=\"$dayOfWeek\">&nbsp;</td>"; 
			}
			
			$DAYURL = new URL($data['info']['page']);
			
			while ($currentDay <= $numberDays) {
			
				// Seventh column (Sunday) reached. Start a new row.
				
				if ($dayOfWeek == 7) {
				
					$dayOfWeek = 0;
					?></tr>
				<tr><?php
				}

				$recess = recess_prettify($currentDay, $month, $year);
				if ($data['info']['major'] == 5)
					$recess = array('');

				// Is this day actually Today in the real world?
				// If so, higlight it.
				// Also highlight days where there are no
				// sittings - e.g. WH is only Tuesday-Thursday
				if ($currentDay == $toDay) {
					print '<td class="on"';
					if ($recess[0] && $recess[0]!=1) print ' title="'.$recess[0].'"';
					print '>';
				} elseif ($recess[0]) {
					print '<td class="no"';
					if ($recess[0]!=1) print ' title="'.$recess[0].'"';
					print '>';
				} else {
					print '<td>';
				}
	
				// Is the $currentDay a member of $dates? If so,
				// the day should be linked.
				if (in_array($currentDay,$dates)) {
				
					$date = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);
					
					if ($currentDay == $toDay) {
						print $currentDay . '</td>';
					} else {
						$DAYURL->insert(array('d'=>$date));
						print "<a href=\"" . $DAYURL->generate() . "\">$currentDay</a></td>";
					}
					
					// $currentDay is not a member of $dates.
				
				} else {
				
					print "$currentDay</td>";
				}
				
				// Increment counters
				
				$currentDay++;
				$dayOfWeek++;
			}
			
			// Complete the row of the last week in month, if necessary
			
			if ($dayOfWeek != 7) { 
			
				$remainingDays = 7 - $dayOfWeek;
				print "<td colspan=\"$remainingDays\">&nbsp;</td>"; 
			}
			
			
			?></tr>
				</tbody>
				</table>
				</div> <!-- end calendar -->
				
<?php

				if ($PAGE->within_stripe_sidebar()) {
					// Not ideal that this is here, but it works.
					// And it's easier here as we need the date.
					$years = array_keys($data['years']);
					$year = $years[0];
					$URL = new URL($hansardmajors[$data['info']['major']]['page_year']);
					$URL->insert(array('y'=>$year));
					?>
				<p><a href="<?php echo $URL->generate(); ?>">See all of <?php echo $year; ?></a></p>
<?php
				}

		}
	}
} else {
	?>
			<p>There is no data, sorry.</p>
<?php
}





