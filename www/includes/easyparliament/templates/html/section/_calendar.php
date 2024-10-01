<div class="calendar">
  <?php if(isset($month)) {
      // What is the first day of the month in question?
      $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);

      // How many days does this month contain?
      $numberDays = date('t', $firstDayOfMonth);

      // What is the name of the month in question?
      $monthName = strftime('%B', $firstDayOfMonth);

      if (isset($info['onday'])) {
          // 'onday' is like 'yyyy-mm-dd'.
          $datebits = explode('-', $info['onday']);
          if (count($datebits) > 2 && $datebits[0] == $year && $datebits[1] == $month) {
              $toDay = $datebits[2];
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

      $dayOfWeek = strftime('%w', $firstDayOfMonth) - 1;

      // Adjusted to cope with the week starting on Monday.
      if ($dayOfWeek < 0) {
          $dayOfWeek = 6;
      }
      ?>
  <?php }
  if (!isset($years)) { ?>
    <div class="calendar__controls">
        <?php if (isset($prev)) { ?>
        <a href="<?= $prev['url'] ?>" class="calendar__controls__previous">&larr;</a>
        <?php } else { ?>
        <span class="calendar__controls__previous">&nbsp;</span>
        <?php } ?>
        <span class="calendar__controls__current">
            <?= $monthName ?> <?= $year ?>
        </span>
        <?php if (isset($next)) { ?>
        <a href="<?= $next['url'] ?>" class="calendar__controls__next">&rarr;</a>
        <?php } else { ?>
        <span class="calendar__controls__next">&nbsp;</span>
        <?php } ?>
    </div>
  <?php } else { ?>
    <div class="calendar__header">
        <?= $monthName ?>
    </div>
  <?php } ?>

    <table>
        <thead>
            <tr>
                <th><?= gettext('Mon') ?></th>
                <th><?= gettext('Tue') ?></th>
                <th><?= gettext('Wed') ?></th>
                <th><?= gettext('Thu') ?></th>
                <th><?= gettext('Fri') ?></th>
                <th><?= gettext('Sat') ?></th>
                <th><?= gettext('Sun') ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>

<?php
          if ($dayOfWeek > 0) {
              print "<td colspan=\"$dayOfWeek\">&nbsp;</td>";
          }

  $currentDay = 1;

  while ($currentDay <= $numberDays) {

      // Seventh column (Sunday) reached. Start a new row.

      if ($dayOfWeek == 7) {

          $dayOfWeek = 0;
          ?></tr>
                <tr><?php
      }

      $recess = recess_prettify($currentDay, $month, $year, $recess_major);

      // Is this day actually Today in the real world?
      // If so, higlight it.
      // Also highlight days where there are no
      // sittings - e.g. WH is only Tuesday-Thursday
      if ($currentDay == $toDay) {
          print '<td class="on"';
          if ($recess[0] && $recess[0] != 1) {
              print ' title="' . $recess[0] . '"';
          }
          print '>';
      } elseif ($recess[0]) {
          print '<td class="no"';
          if ($recess[0] != 1) {
              print ' title="' . $recess[0] . '"';
          }
          print '>';
      } else {
          print '<td>';
      }

      // Is the $currentDay a member of $dates? If so,
      // the day should be linked.
      if (in_array($currentDay, $dates)) {

          $date = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);

          if ($currentDay == $toDay) {
              print '<span>' . $currentDay . '</span></td>';
          } else {
              $day_section = $section;
              if ($section == 'sp') {
                  $day_section = 'spdebates';
              }
              $urls[$day_section . 'day']->insert(['d' => $date]);
              print "<a href=\"" . $urls[$day_section . 'day']->generate() . "\">$currentDay</a></td>";
          }

          // $currentDay is not a member of $dates.

      } else {

          print '<span>' . $currentDay . '</span></td>';
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
  ?>
            </tr>
        </tbody>
    </table>
  <?php if(!isset($years)) { ?>
    <div class="calendar__footer">
    <?php
          $y = $urls['day'];
      $y->reset();
      $y->insert([ 'y' => $year ]);
      $url = $y->generate();
      ?>
    <a href="<?= $url ?>"><?= sprintf(gettext('See all of %s'), $year) ?></a>
    </div>
  <?php } ?>
</div>
