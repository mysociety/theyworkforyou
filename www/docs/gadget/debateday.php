<?

include_once '../../includes/easyparliament/init.php';
$LIST = new DEBATELIST;
# Guess it should really use a View of none, but this way I get all
# the display stuff done for me...
ob_start();
$LIST->display('date', array('date' => get_http_var('d')));
$cal = ob_get_clean();
$cal = preg_replace('#^.*?(<ul id="hansard-day">)#s', '$1', $cal);
$cal = preg_replace('#<!-- end hansard-day -->.*$#s', '', $cal);
$cal = str_replace('<a href="', '<a target="_blank" href="http://www.theyworkforyou.com', $cal);
print $cal;

