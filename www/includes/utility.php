<?php

/*
General utility functions v1.1 (well, it was).

*/

function twfy_debug ($header, $text="") {
	// Pass it a brief header word and some debug text and it'll be output.

	// We set ?DEBUGTAG=n in the URL.
	// (DEBUGTAG is set in config.php).
	// n is a number from (currently) 1 to 4.
	// This sets what amount of debug information is shown.
	// For level '1' we show anything that is passed to this function
	// with a $header in $levels[1].
	// For level '2', anything with a $header in $levels[1] AND $levels[2].
	// Level '4' shows everything.
	
	$debug_level = get_http_var(DEBUGTAG);
	#$debug_level = 1;
	
	if ($debug_level != '') {
	
		// Set which level shows which types of debug info.
		$levels = array (
			1 => array ('SKIN', 'THEUSER', 'TIME', 'SQLERROR', 'PAGE', 'TEMPLATE', 'SEARCH', 'ALERTS', 'MP'),
			2 => array ('SQL', 'EMAIL', 'WIKIPEDIA', 'hansardlist', 'debatelist', 'wranslist', 'whalllist'),
			3 => array ('SQLRESULT')
			// Higher than this: 'DATA', etc.
		);
	
		// Store which headers we are allowed to show.
		$allowed_headers = array();
		
		if ($debug_level > count($levels)) {
			$max_level_to_show = count($levels);
		} else {
			$max_level_to_show = $debug_level;
		}
		
		for ($n = 1; $n <= $max_level_to_show; $n++) {
			$allowed_headers = array_merge ($allowed_headers, $levels[$n] );
		}
		
		// If we can show this header, then, er, show it.
		if ( in_array($header, $allowed_headers) || $debug_level >= 4) {
			print "<p><span style=\"color:#039;\"><strong>$header</strong></span> $text</p>\n";	
		}
	}
}


function error_handler ($errno, $errmsg, $filename, $linenum, $vars) {
	// Custom error-handling function.
	// Sends an email to BUGSLIST.
	global $PAGE;

   // define an assoc array of error string
   // in reality the only entries we should
   // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
   // E_USER_WARNING and E_USER_NOTICE
   $errortype = array (
		E_ERROR				=> "Error",
		E_WARNING			=> "Warning",
		E_PARSE				=> "Parsing Error",
		E_NOTICE			=> "Notice",
		E_CORE_ERROR		=> "Core Error",
		E_CORE_WARNING		=> "Core Warning",
		E_COMPILE_ERROR		=> "Compile Error",
		E_COMPILE_WARNING	=> "Compile Warning",
		E_USER_ERROR		=> "User Error",
		E_USER_WARNING		=> "User Warning",
		E_USER_NOTICE		=> "User Notice",
		// PHP 5 only
		//E_STRICT			=> "Runtime Notice"
	);

	$err = '';
	if (isset($_SERVER['REQUEST_URI'])) {
		$err .= "URL:\t\thttp://" . DOMAIN . $_SERVER['REQUEST_URI'] . "\n";
	} else {
		$err .= "URL:\t\tNone - running from command line?\n";
	}
	if (isset($_SERVER['HTTP_REFERER'])) {
		$err .= "Referer:\t" . $_SERVER['HTTP_REFERER'] . "\n";
	} else {
		$err .= "Referer:\tNone\n";
	}
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$err .= "User-Agent:\t" . $_SERVER['HTTP_USER_AGENT'] . "\n";
	} else {
		$err .= "User-Agent:\tNone\n";
	}
	$err .= "Number:\t\t$errno\n";
	$err .= "Type:\t\t" . $errortype[$errno] . "\n";
	$err .= "Message:\t$errmsg\n";
	$err .= "File:\t\t$filename\n";
	$err .= "Line:\t\t$linenum\n";


// I'm not sure this bit is actually any use!

	// set of errors for which a var trace will be saved.
//	$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
//	if (in_array($errno, $user_errors)) {
//		$err .= "Variables:\t" . serialize($vars) . "\n";
//	}
	
	
	// Add the problematic line if possible.
	if (is_readable($filename)) {
		$source = file($filename);
		$err .= "\nSource:\n\n";
		// Show the line, plus prev and next, with line numbers.
		$err .= $linenum-2 . " " . $source[$linenum-3];
		$err .= $linenum-1 . " " . $source[$linenum-2];
		$err .= $linenum . " " . $source[$linenum-1];
		$err .= $linenum+1 . " " . $source[$linenum];
		$err .= $linenum+2 . " " . $source[$linenum+1];
	}
	
	
	// Will we need to exit after this error?
	$fatal_errors = array(E_ERROR, E_USER_ERROR);
	if (in_array($errno, $fatal_errors)) {
		$fatal = true;
	} else {
		$fatal = false;
	}


	// Finally, display errors and stuff...

	if (DEVSITE) {
		// On a devsite we just display the problem.
		$message = array(
			'title' => "Error",
			'text' => "$err\n"
		);
		if (is_object($PAGE)) {
			$PAGE->error_message($message, $fatal);
			vardump(adodb_backtrace());
		} else {
			vardump($message);
			vardump(adodb_backtrace());
		}
		
	} else {
		// On live sites we display a nice message and email the problem.
		
		$message = array(
			'title' => "Sorry, an error has occurred",
			'text' => "We've been notified by email and will try to fix the problem soon!"
		);

		if (is_object($PAGE)) {
			$PAGE->error_message($message, $fatal);
		} else {
			print "<p>Oops, sorry, an error has occurred!</p>\n";
		}
		mail(BUGSLIST, "[TWFYBUG]: $errmsg", $err,
			"From: Bug <beta@theyworkforyou.com>\n".
			"X-Mailer: PHP/" . phpversion()
		);
	}	
	

	// Do we need to exit?
	
	if ($fatal) {
		exit(1);
	}

}




// Replacement for var_dump()
function vardump($blah) {
	print "<pre>\n";
	var_dump($blah);
	print "</pre>\n";
}

// pretty prints the backtrace, copied from http://uk.php.net/manual/en/function.debug-backtrace.php
function adodb_backtrace($print=true)
{
  $s = '';
  if (PHPVERSION() >= 4.3) {
      
    $MAXSTRLEN = 64;
      
    $traceArr = debug_backtrace();
    array_shift($traceArr);
    $tabs = sizeof($traceArr)-1;
    foreach ($traceArr as $arr) {
      for ($i=0; $i < $tabs; $i++) $s .= ' &nbsp; ';
      $tabs -= 1;
      $s .= '<font face="Courier New,Courier">';
      if (isset($arr['class'])) $s .= $arr['class'].'.';
      $args = array();
      if (isset($arr['args'])) foreach($arr['args'] as $v) {
	if (is_null($v)) $args[] = 'null';
	else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
	else if (is_object($v)) $args[] = 'Object:'.get_class($v);
	else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
	else {
	  $v = (string) @$v;
	  $str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
	  if (strlen($v) > $MAXSTRLEN) $str .= '...';
	  $args[] = $str;
	}
      }
              
      $s .= $arr['function'].'('.implode(', ',$args).')';
      //      $s .= sprintf("</font><font color=#808080 size=-1> # line %4d,".
      //		    " file: <a href=\"file:/%s\">%s</a></font>",
      //	    $arr['line'],$arr['file'],$arr['file']);
      $s .= "\n";
    }   
    if ($print) print $s;
  }
  return $s;
}


// Far from foolproof, but better than nothing.
function validate_email ($string) {
	if (!ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.
		'@'.
		'[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
		'[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $string)) {
		return false;
	} else {
		return true;
	}
}


function validate_postcode ($postcode) {
	// See http://www.govtalk.gov.uk/gdsc/html/noframes/PostCode-2-1-Release.htm

	$postcode = trim($postcode);

	$in  = 'ABDEFGHJLNPQRSTUWXYZ';
	$fst = 'ABCDEFGHIJKLMNOPRSTUWYZ';
	$sec = 'ABCDEFGHJKLMNOPQRSTUVWXY';
	$thd = 'ABCDEFGHJKSTUW';
	$fth = 'ABEHMNPRVWXY';
	$num = '0123456789';
	$nom = '0123456789';
	$gap = '\s\.';	

	if (	preg_match("/^[$fst][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
			preg_match("/^[$fst][$num][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
			preg_match("/^[$fst][$sec][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
			preg_match("/^[$fst][$sec][$num][$num][$gap]*[$nom][$in][$in]$/i", $postcode) ||
			preg_match("/^[$fst][$num][$thd][$gap]*[$nom][$in][$in]$/i", $postcode) ||
			preg_match("/^[$fst][$sec][$num][$fth][$gap]*[$nom][$in][$in]$/i", $postcode)
		) {
		return true;
	} else {
		return false;
	}
}

// Returns the unixtime in microseconds.
function getmicrotime() {
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];

	return $mtime;
}

/* twfy_debug_timestamp
 * Output a timestamp since the page was started. */
$timestamp_last = $timestamp_start = getmicrotime();
function twfy_debug_timestamp($label = "") {
    global $timestamp_last, $timestamp_start;
    $t = getmicrotime();
    twfy_debug("TIME", sprintf("%f msecs since start; %f msecs since last; %s",
            ($t - $timestamp_start)*1000.0, ($t - $timestamp_last)*1000.0, $label));
    $timestamp_last = $t;
}

function format_timestamp ($timestamp, $format) {
	// Pass it a MYSQL TIMESTAMP (YYYYMMDDHHMMSS) and a
	// PHP date format string (eg, "Y-m-d H:i:s")
	// and it returns a nicely formatted string according to requirements.
	
	// Because strtotime can't handle TIMESTAMPS.
	
	if (preg_match("/^(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)$/", $timestamp, $matches)) {
		list($string, $year, $month, $day, $hour, $min, $sec) = $matches;
	
		return gmdate ($format, gmmktime($hour, $min, $sec, $month, $day, $year));
	} else {
		return "";
	}

}


function format_date ($date, $format) {
	// Pass it a date (YYYY-MM-DD) and a
	// PHP date format string (eg, "Y-m-d H:i:s")
	// and it returns a nicely formatted string according to requirements.

	if (preg_match("/^(\d\d\d\d)-(\d\d)-(\d\d)$/", $date, $matches)) {
		list($string, $year, $month, $day) = $matches;
	
		return gmdate ($format, gmmktime(0, 0, 0, $month, $day, $year));
	} else {
		return "";
	}

}


function format_time ($time, $format) {
	// Pass it a time (HH:MM:SS) and a
	// PHP date format string (eg, "H:i")
	// and it returns a nicely formatted string according to requirements.

	if (preg_match("/^(\d\d):(\d\d):(\d\d)$/", $time, $matches)) {
		list($string, $hour, $min, $sec) = $matches;

		return gmdate ($format, gmmktime($hour, $min, $sec));
	} else {
		return "";
	}
}



function relative_time ($datetime) {
	// Pass it a 'YYYY-MM-DD HH:MM:SS' and it will return something
	// like "Two hours ago", "Last week", etc.
	
	// http://maniacalrage.net/projects/relative/
	
	if (!preg_match("/\d\d\d\d-\d\d-\d\d \d\d\:\d\d\:\d\d/", $datetime)) {
		return '';
	}

	$in_seconds = strtotime($datetime);
	$now = mktime();

	$diff 	=  $now - $in_seconds;
	$months	=  floor($diff/2419200);
	$diff 	-= $months * 2419200;
	$weeks 	=  floor($diff/604800);
	$diff	-= $weeks*604800;
	$days 	=  floor($diff/86400);
	$diff 	-= $days * 86400;
	$hours 	=  floor($diff/3600);
	$diff 	-= $hours * 3600;
	$minutes = floor($diff/60);
	$diff 	-= $minutes * 60;
	$seconds = $diff;
    
	
	if ($months > 0) {
		// Over a month old, just show the actual date.
		$date = substr($datetime, 0, 10);
		return format_date($date, LONGDATEFORMAT);

	} else {
		$relative_date = '';
		if ($weeks > 0) {
			// Weeks and days
			$relative_date .= ($relative_date?', ':'').$weeks.' week'.($weeks>1?'s':'');
			$relative_date .= $days>0?($relative_date?', ':'').$days.' day'.($days>1?'s':''):'';
		} elseif ($days > 0) {
			// days and hours
			$relative_date .= ($relative_date?', ':'').$days.' day'.($days>1?'s':'');
			$relative_date .= $hours>0?($relative_date?', ':'').$hours.' hour'.($hours>1?'s':''):'';
		} elseif ($hours > 0) {
			// hours and minutes
			$relative_date .= ($relative_date?', ':'').$hours.' hour'.($hours>1?'s':'');
			$relative_date .= $minutes>0?($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':''):'';
		} elseif ($minutes > 0) {
			// minutes only
			$relative_date .= ($relative_date?', ':'').$minutes.' minute'.($minutes>1?'s':'');
		} else {
			// seconds only
			$relative_date .= ($relative_date?', ':'').$seconds.' second'.($seconds>1?'s':'');
		}
	}
	
	// Return relative date and add proper verbiage
	return $relative_date.' ago';
	
}

function parse_date($date) {
	$now = time();
	$date = preg_replace('#\b([a-z]|on|an|of|in|the|year of our lord)\b#i','',$date);
	$date = preg_replace('#[\x80-\xff]#','',$date);
	if (!$date)
		return null;

	$epoch = 0;
	$day = null;
	$year = null;
	$month = null;
	if (preg_match('#(\d+)/(\d+)/(\d+)#',$date,$m)) {
		$day = $m[1]; $month = $m[2]; $year = $m[3];
		if ($year<100) $year += 2000;
	} elseif (preg_match('#(\d+)/(\d+)#',$date,$m)) {
		$day = $m[1]; $month = $m[2]; $year = date('Y');
	} elseif (preg_match('#^([0123][0-9])([01][0-9])([0-9][0-9])$#',$date,$m)) {
		$day = $m[1]; $month = $m[2]; $year = $m[3];
	} else {
		$dayofweek = date('w'); # 0 Sunday, 6 Saturday
		if (preg_match('#next\s+(sun|sunday|mon|monday|tue|tues|tuesday|wed|wednes|wednesday|thu|thur|thurs|thursday|fri|friday|sat|saturday)\b#i',$date,$m)) {
			$date = preg_replace('#next#i','this',$date);
			if ($dayofweek == 5) {
				$now = strtotime('3 days', $now);
			} elseif ($dayofweek == 4) {
				$now = strtotime('4 days', $now);
			} else {
				$now = strtotime('5 days', $now);
			}
		}
		$t = strtotime($date,$now);
		if ($t != -1) {
			$day = date('d',$t); $month = date('m',$t); $year = date('Y',$t); $epoch = $t;
			if ("$day$month$year"==date('dmY')) {
				$epoch = 0; $day = 0; $month = 0; $year = 0;
			}
		}
	}
	if (!$epoch && $day && $month && $year) {
		$t = mktime(0,0,0,$month,$day,$year);
		$day = date('d',$t); $month = date('m',$t); $year = date('Y',$t); $epoch = $t;
	}

	if ($epoch == 0)
		return null;
	return array('iso'=>"$year-$month-$day", 'epoch'=>$epoch, 'day'=>$day, 'month'=>$month, 'year'=>$year);
}

/* strip_tags_tospaces TEXT
 * Return a copy of TEXT in which certain block-level HTML tags have been
 * replaced by single spaces, and other HTML tags have been removed. */
function strip_tags_tospaces($text) {
    $text = preg_replace("#\<(p|br|div|td|tr|th|table)[^>]*\>#i", " ", $text);
    return strip_tags(trim($text)); 
}

function trim_characters ($text, $start, $length) {
	// Pass it a string, a numeric start position and a numeric length.
	// If the start position is > 0, the string will be trimmed to start at the
	// nearest word boundary after (or at) that position.
	// If the string is then longer than $length, it will be trimmed to the nearest
	// word boundary below (or at) that length.
	// If either end is trimmed, ellipses will be added.
	// The modified string is then returned - its *maximum* length is $length.
	// HTML is always stripped (must be for trimming to prevent broken tags).

	$text = strip_tags_tospaces($text);
	
	// Split long strings up so they don't go too long.
	// Mainly for URLs which are displayed, but aren't links when trimmed.
	$text = preg_replace("/(\S{60})/", "\$1 ", $text);

	// Otherwise the word boundary matching goes odd...
	$text = preg_replace("/[\n\r]/", " ", $text);
	
	// Trim start.
	if ($start > 0) {
		$text = substr($text, $start);
		
		// Word boundary.         
		if (preg_match ("/.+?\b(.*)/", $text, $matches)) {
			$text = $matches[1];
			// Strip spare space at the start.
			$text = preg_replace ("/^\s/", '', $text);
		}
		$text = '...' . $text;
	}
	
	// Trim end.
	if (strlen($text) > $length) {

		// Allow space for ellipsis.
		$text = substr($text, 0, $length - 3); 

		// Word boundary.         
		if (preg_match ("/(.*)\b.+/", $text, $matches)) {
			$text = $matches[1];
			// Strip spare space at the end.
			$text = preg_replace ("/\s$/", '', $text);
		}
		// We don't want to use the HTML entity for an ellipsis (&#8230;), because then 
		// it screws up when we subsequently use htmlentities() to print the returned
		// string!
		$text .= '...'; 
	}

	return $text;
}


function filter_user_input ($text, $filter_type) {
	// We use this to filter any major user input, especially comments.
	// Gets rid of bad HTML, basically.
	// Uses iamcal.com's lib_filter class.
	
	// $filter_type is the level of filtering we want:
	// 	'comment' allows <b> and <i> tags.
	//	'strict' strips all tags.
	
	global $filter;
	
	$text = trim($text);
	
	// Replace 3 or more newlines with just two newlines.
	//$text = preg_replace("/(\n){3,}/", "\n\n", $text);

	if ($filter_type == 'strict') {
		// No tags allowed at all!
		$filter->allowed = array ();
		
	} else {
		// Comment.
		// Only allowing <b> and <i> tags.
		$filter->allowed = array (
			'strong' => array(),
			'em' => array(),
		);
	}
	
	$text = $filter->go($text);


	return $text;

}



function prepare_comment_for_display ($text) {
	// Makes any URLs into HTML links.
	// Turns \n's into <br />

	// Encode HTML entities.
	// Can't do htmlentities() because it'll turn the few tags we allow into &lt;
	// Must go before the URL stuff.
	$text = htmlentities_notags($text);
	$link_length = 60;
	$text = preg_replace(
		"/((http(s?):\/\/)|(www\.))([a-zA-Z\d\_\.\+\,\;\?\%\~\-\/\#\='\*\$\!\(\)\&]+)([a-zA-Z\d\_\?\%\~\-\/\#\='\*\$\!\&])/e",
		'(strlen(\'$0\')>$link_length) ? \'<a href="$0">\'.substr(\'$0\',0,$link_length)."...</a>" : \'<a href="$0">$0</a>\'',
		$text);
	$text = preg_replace("/([\w\.]+)(@)([\w\.\-]+)/i", "<a href=\"mailto:$0\">$0</a>", $text); 
	$text = nl2br($text);
	return $text;	
}


function htmlentities_notags ($text) {
	// If you want to do htmlentities() on some text that has HTML tags
	// in it, then you need this function.
	
	$tbl = get_html_translation_table(HTML_ENTITIES);

	// You could encode extra stuff...
	//$tbl["“"] = "&quot;";
	//$tbl["”"] = "&quot;";
	//$tbl["…"] = "...";
	//$tbl["—"] = "-";
	//$tbl["»"] = "&raquo;";
	//$tbl["«"] = "&laquo;";
		 
	// Don't want to encode these things
	unset ($tbl["<"]);
	unset ($tbl[">"]);
	unset ($tbl["'"]);
	unset ($tbl['"']);
	
	# strtr "will *NOT* try to replace stuff that it has already worked on."
	$text = strtr($text, $tbl);

	# Remove all illegal HTML characters (damn you, Windows-1252)
	$text = preg_replace('/[\x80-\x9f]/', '', $text);

	return $text;

}



function fix_gid_from_db ($gid, $keepmajor = false) {
	// The gids in the database are longer than we use in the site.
	// Feed this a gid from the db and it will be returned truncated.
	
	// $gid will be like 'uk.org.publicwhip/debate/2003-02-28.475.3'.
	
	// You will almost always want $keepmajor to be false.
	// This returns '2003-02-28.475.3' which is used for URLs.
	
	// However, trackbacks want a bit more info, so we can tell what
	// kind of thing they link to. So they need $keepmajor to be true.
	// This returns 'debate_2003-02-28.475.3'.
	
	if ($keepmajor) {
		$newgid = substr($gid, strpos($gid, '/')+1 );
		$newgid = str_replace('/', '_', $newgid);
	} else {
		$newgid = substr($gid, strrpos($gid, '/')+1 );
	}

	return $newgid;
	
}

function gid_to_anchor ($gid) {
	// For trimming gids to be used as #anchors in pages.
	// Extracted here so we keep it consistent.
	// The gid should already be truncated using fix_gid_from_db(), so it
	// will be like 2003-11-20.966.0
	// This function returns 966.0
	
	return substr( $gid, (strpos($gid, '.') + 1) );
}


function send_template_email ($data, $merge) {
	// We should have some email templates in INCLUDESPATH/easyparliament/templates/emails/.
	
	// $data is like:
	// array (
	//	'template' 	=> 'send_confirmation',
	//	'to'		=> 'phil@gyford.com',
	//	'subject'	=> 'Your confirmation email'
	// );
	
	// $merge is like:
	// array (
	//	'FIRSTNAME' => 'Phil',
	//	'LATNAME'	=> 'Gyford'
	// 	etc...
	// );
	
	// In $data, 'template' and 'to' are mandatory. 'template' is the 
	// name of the file (when it has '.txt' added to it).
	
	// We'll get the text of the template and replace all the $merge
	// keys with their tokens. eg, if '{FIRSTNAME}' in the template will 
	// be replaced with 'Phil'.
	
	// Additionally, the first line of a template may start with 
	// 'Subject:'. Any text immediately following that, on the same line
	// will be the subject of the email (it will also have its tokens merged).
	// But this subject can be overridden by sending including a 'subject'
	// pair in $data.
	
	global $PAGE;
	
	if (!isset($data['to']) || $data['to'] == '') {
		$PAGE->error_message ("We need an email address to send to.");
		return false;
	}

	$filename = INCLUDESPATH . "easyparliament/templates/emails/" . $data['template'] . ".txt";

	if (!file_exists($filename)) {
		$PAGE->error_message("Sorry, we could not find the email template '" . htmlentities($data['template']) . "'.");
		return false;
	}
	
	// Get the text from the template.
	$handle = fopen($filename, "r");
	$emailtext = fread($handle, filesize($filename));
	fclose($handle);

	// See if there's a default subject in the template.
	$firstline = substr($emailtext, 0, strpos($emailtext, "\n"));
	
	// Work out what the subject line is.
	if (preg_match("/Subject:/", $firstline)) {
		if (isset($data['subject'])) {
			$subject = trim($data['subject']);
		} else {
			$subject = trim( substr($firstline, 8) );
		}
		
		// Either way, remove this subject line from the template.
		$emailtext = substr($emailtext, strpos($emailtext, "\n"));
		
	} elseif (isset($data['subject'])) {
		$subject = $data['subject'];
	} else {
		$PAGE->error_message ("We don't have a subject line for the email, so it wasn't sent.");
		return false;
	}
	

	// Now merge all the tokens from $merge into $emailtext...
	$search = array();
	$replace = array();
	
	foreach ($merge as $key => $val) {
		$search[] = '/{'.$key.'}/';
		$replace[] = $val;
	}
	
	$emailtext = preg_replace($search, $replace, $emailtext);
	
	// Send it!
	$success = send_email ($data['to'], $subject, $emailtext);

	return $success;

}



function send_email ($to, $subject, $message) {
	// Use this rather than PHP's mail() direct, so we can make alterations
	// easily to all the emails we send out from the site.
	
	// eg, we might want to add a .sig to everything here...
	
	// Everything is not BCC'd to REPORTLIST (unless it's already going to the list!).
	
	$headers = 
	 "From: TheyWorkForYou.com <" . CONTACTEMAIL . ">\r\n" .
     "Reply-To: TheyWorkForYou.com <" . CONTACTEMAIL . ">\r\n" .
     "Content-Type: text/plain; charset=iso-8859-1\r\n" .
     "Content-Transfer-Encoding: 8bit\r\n" . 
     "X-Mailer: PHP/" . phpversion();
    /* 
	if ($to != REPORTLIST) {
  		$headers .= "\r\nBcc: " . BCCADDRESS;
	}
     */
	twfy_debug('EMAIL', "Sending email to $to with subject of '$subject'");

	$success = mail ($to, $subject, $message, $headers);

	return $success;
}



///////////////////////////////
// Cal's functions from
// http://www.iamcal.com/publish/article.php?id=13

// Call this with a key name to get a GET or POST variable.
function get_http_var ($name, $default=''){
	global $HTTP_GET_VARS, $HTTP_POST_VARS;
	if (arrayKeyExists($name, $HTTP_GET_VARS)) {
		return clean_var($HTTP_GET_VARS[$name]);
	}
	if (arrayKeyExists($name, $HTTP_POST_VARS)) {
		return clean_var($HTTP_POST_VARS[$name]);
	}
	return $default;
}

function clean_var ($a){
	return (ini_get("magic_quotes_gpc") == 1) ? recursive_strip($a) : $a;
}

function recursive_strip ($a){
	if (is_array($a)) {
		while (list($key, $val) = each($a)) {
			$a[$key] = recursive_strip($val);
		}
	} else {
		$a = StripSlashes($a);
	}
	return $a;
}


// Call this with a key name to get a COOKIE variable.
function get_cookie_var($name, $default=''){
	global $HTTP_COOKIE_VARS;
	if (arrayKeyExists($name, $HTTP_COOKIE_VARS)) {
		return clean_var($HTTP_COOKIE_VARS[$name]);
	}
	return $default;
}
///////////////////////////////



// Because array_key_exists() doesn't exist prior to PHP v4.1.0
function arrayKeyExists($key, $search) {
   if (in_array($key, array_keys($search))) {
       return true;
   } else {
       return false;
   }
}


// Pass it an array of key names that should not be generated as
// hidden form variables. It then outputs hidden form variables 
// based on the session_vars for this page.
function hidden_form_vars ($omit = array()) {
	global $DATA, $this_page;
	
	$session_vars = $DATA->page_metadata($this_page, "session_vars");

	foreach ($session_vars as $n => $key) {
		if (!in_array($key, $omit)) {
			print "<input type=\"hidden\" name=\"$key\" value=\"" . htmlentities(get_http_var($key)) . "\" />\n";
		}
	}
}



// Deprecated. Use hidden_form_vars, above, instead.
function hidden_vars ($omit = array()) {
	global $DATA;
	
	foreach ($args as $key => $val) {
		if (!in_array($key, $omit)) {
			print "<input type=\"hidden\" name=\"$key\" value=\"" . htmlspecialchars($val) . "\" />\n";
		}	
	}
}

function make_ranking($rank)
{
    # 11th, 12th, 13th use "th" not "st", "nd", "rd"
    if (floor(($rank % 100) / 10) == 1)
        return $rank . "th"; 
    # 1st
    if ($rank % 10 == 1)
        return $rank . "st";
    # 2nd
    if ($rank % 10 == 2)
        return $rank . "nd"; 
    # 3rd
    if ($rank % 10 == 3)
        return $rank . "rd"; 
    # Everything else use th
    return $rank . "th";
}

function make_plural($word, $number)
{
    if ($number == 1)
        return $word;
    return $word . "s";
}

function make_member_url($name, $const = '', $house = 1) {
	$s = array(' ', '&amp;', '&ocirc;', '&ouml;', '&acirc;');
	$r = array('_', 'and',     'o',       'o', 'a' );
	$name = preg_replace('#^the #', '', strtolower($name));
	$out = urlencode(str_replace($s, $r, $name));
	if ($const && $house==1)
		$out .= '/' . urlencode(str_replace($s, $r, strtolower($const)));
	return $out;
}

function member_full_name($house, $title, $first_name, $last_name, $constituency) {
	$s = 'ERROR';
	if ($house == 1) {
		$s = $first_name . ' ' . $last_name;
		if ($title) {
			$s = $title . ' ' . $s;
		}
	} elseif ($house == 2) {
		$s = '';
		if (!$last_name) $s = 'the ';
		$s .= $title;
		if ($last_name) $s .= ' ' . $last_name;
		if ($constituency) $s .= ' of ' . $constituency;
	}
	return $s;
}

function prettify_office($pos, $dept) {
	$lookup = array(
		'Prime Minister, HM Treasury' => 'Prime Minister',
		'Secretary of State, Foreign & Commonwealth Office' => 'Foreign Secretary',
		'Secretary of State, Home Office' => 'Home Secretary',
		'Minister of State (Energy), Department of Trade and Industry' => 'Minister for energy, Department of Trade and Industry',
		'Minister of State (Pensions), Department for Work and Pensions' => 'Minister for pensions, Department for Work and Pensions',
	);
	if ($pos) { # Government post, or Chairman of Select Committee
		$pretty = "$pos, $dept";
		if (array_key_exists($pretty, $lookup))
			$pretty = $lookup[$pretty];
	} else { # Member of Select Committee
		$pretty = "Member, $dept";
	}
	return $pretty;
}

function major_summary($data) {
	global $hansardmajors;
	$db = new ParlDB;
	$one_date = false;
	if (isset($data['date'])) $one_date = true;

	$daytext = array();
	if (!$one_date) {
		$todaystime = gmmktime(0, 0, 0, date('m'), date('d'), date('Y'));
		foreach ($data as $major => $array) {
			if ($todaystime - $array['timestamp'] == 86400) $daytext[$major] = "Yesterday's";
			elseif ($todaystime - $array['timestamp'] <= (6 * 86400)) $daytext[$major] = gmdate('l', $array['timestamp']) . "'s";
			else $daytext[$major] = "The most recent ";
		}
	}
	$printed_majors = array(1, 2, 3, 101);
	print '<ul id="hansard-day">';
	while (count($printed_majors)) {
		if (!array_key_exists($printed_majors[0], $data)) {
			unset($printed_majors[0]);
			sort($printed_majors);
			continue;
		}
		
		if ($one_date)
			$date = $data['date'];
		else 
			$date = $data[$printed_majors[0]]['hdate'];
		$q = $db->query('SELECT major, body, gid
				FROM hansard,epobject
				WHERE hansard.epobject_id = epobject.epobject_id AND section_id=0
				AND hdate="'.$date.'"
				AND major IN (' . join(',',$printed_majors) . ')
				ORDER BY major, hpos');
		$current_major = 0;
		for ($i = 0; $i < $q->rows(); $i++) {
			$gid = fix_gid_from_db($q->field($i, 'gid'));
			$major = $q->field($i, 'major');
			$body = $q->field($i, 'body');
			//if (strstr($body, 'Chair]')) continue;
			if ($major != $current_major) {
				if ($current_major) print '</ul>';
				$LISTURL = new URL($hansardmajors[$major]['page_all']);
				_major_summary_title($major, $data, $LISTURL, $daytext);
				$current_major = $major;
				# XXX: Surely a better way of doing this? Oh well
				unset($printed_majors[array_search($major, $printed_majors)]);
				sort($printed_majors);
			}
			$LISTURL->insert( array( 'id' => $gid ) );
			print '<li><a href="'.$LISTURL->generate().'">';
			print $body . '</a>';
		}
		print '</ul>';
		if ($one_date) $printed_majors = array();
	}
	if (array_key_exists(4, $data)) {
		if ($one_date)
			$date = $data['date'];
		else 
			$date = $data[4]['hdate'];
		$q = $db->query('SELECT section_id, body, gid FROM hansard,epobject
				WHERE hansard.epobject_id = epobject.epobject_id AND major=4 AND hdate="'.$date.'" AND subsection_id=0
				ORDER BY major, hpos');
		if ($q->rows()) {
			$LISTURL = new URL($hansardmajors[4]['page_all']);
			_major_summary_title(4, $data, $LISTURL, $daytext);
			$current_sid = 0;
			for ($i = 0; $i < $q->rows(); $i++) {
				$gid = fix_gid_from_db($q->field($i, 'gid'));
				$body = $q->field($i, 'body');
				$section_id = $q->field($i, 'section_id');
				if (!$section_id) {
					if ($current_sid++) print '</ul>';
					print '<li>' . $body . '<ul>';
		
				} else {
					$LISTURL->insert( array( 'id' => $gid ) );
					print '<li><a href="'.$LISTURL->generate().'">';
					print $body . '</a>';
				}
			}
			print '</ul>';
		}
	}
	print '</ul>';
}
function _major_summary_title($major, $data, $LISTURL, $daytext) {
	global $hansardmajors;
	print '<li><strong>';
	if (isset($daytext[$major])) print $daytext[$major] . ' ';
	print '<a href="';
	if (isset($data[$major]['listurl']))
		print $data[$major]['listurl'];
	else
		print $LISTURL->generate();
	print '">' . $hansardmajors[$major]['title'] . '</a>';
	if (isset($daytext[$major])) print ':';
	print '</strong> <ul>';
}
?>
