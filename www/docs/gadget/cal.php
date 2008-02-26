<?

# Display a calendar month in the Google gadget

include_once '../../includes/easyparliament/init.php';
$LIST = new DEBATELIST;
# Guess it should really use a View of none, but this way I get all
# the display stuff done for me...
ob_start();
$LIST->display('calendar', array('months' => 1));
$cal = ob_get_clean();
$cal = preg_replace('#<a href="/debates/\?d=(.*?)"#', '<a onclick="return loadDay(\'$1\');" target="_blank" href="http://www.theyworkforyou.com/debates/?d=$1"', $cal);
print $cal;

