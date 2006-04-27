<?php

/*
URL class v1.3 2003-11-25
phil@gyford.com


REQUIRES:
	data.php v1.0
	utiltity.php v1.0
	GLOBALS:
		WEBPATH	/directory/



DOCUMENTATION:

The URL class is used for generating URLs and other related things.
Relies on there being a get_http_var() function.

This is probably how you'll use it most:

	$URL = new URL("yourpagename");
	print $URL->generate();

In the metadata you should set a session_vars variable, an array.
The default page session_vars may be just array("debug").
These can then be overridden on a per-page basis.
Session vars are GET/POST vars that will be passed by default to that page.
ie, if "foo=bar" is in the current URL and you generate a URL to a page that has "foo"
as a session_var, "foo=bar" will be automatically added to the generated URL.
You can modify the session vars that will be included in the URL generated using the functions below.


PUBLICALLY ACCESSIBLE FUNCTIONS:

restore() 	- Sets $URL->session_vars back to how they were when the object was instantiated.
reset() 	- Sets $URL->session_vars to be an empty array.
insert() 	- Add/overwrite session key and value pair(s).
remove() 	- Remove session key/value pair(s).
update() 	- Update the values of some/all session_vars.
generate() 	- Generate a URL to the page specified with session vars.
header_redirect_url()	- Generate a URL with
hidden_form_vars() - Prints hidden form variables.


VERSION HISTORY
v1.0	2003-07-16
v1.1	2003-09-10 
			Changed format for $encode in generate().
			Insert() now overwrites existing variables, rather than maintaining them.
v1.2	2003-10-03
			Added support for "pg" variables in the metadata. You can create virtual
				pages from the same physical file.
v1.3	2003-11-25
			Changed from PAGEURL to URL. Now use defined constants instead of globals.
*/

class URL {

	function URL ($pagename) {
		// Initialise.
		global $DATA;

		// The page we're going to be generating URL(s) for.
		$this->destinationpage = $pagename;
		
		// These stores an associative array of key/value pairs that
		// we'll want passed on to other pages.
		$this->session_vars = array ();
		
		// Set the contents of $this->session_vars.
		// session_vars are variables we generally want to pass between pages, if any.
		// Will only be added as vars if they have values.
		
		$keys = $DATA->page_metadata($this->destinationpage, "session_vars");
		foreach ($keys as $key) {
			if (get_http_var($key) != "") {
				$this->session_vars[$key] = get_http_var($key);
			}
		}

		// Some pages have the same URL, modified by a "pg" variable.
		// See if this page is one such, and add the variable if so.
		if ($pg = $DATA->page_metadata($this->destinationpage, "pg")) {
			$this->session_vars["pg"] = $pg;
		}
		
		// So we can restore the originals.
		$this->original_session_vars = $this->session_vars;

	}
	
	
	function restore() {
		// Call this to reset the session vars to how they were when
		// the object was instantiated.
		$this->session_vars = $this->original_session_vars;

	}
	
	
	function reset() {
		// Call this to remove all the session_vars.
		$this->session_vars = array ();
	}
	
	
	function insert($arr) {
		// $arr is an associative array of key/value pairs.
		// These will be used as session_vars in addition to any that
		// already exist.
		foreach ($arr as $key => $val) {
			$this->session_vars[$key] = $val;
		}
	}
	
	
	function remove($arr) {
		// $arr is a list array of key names. Any key/value pairs
		// in session_vars with keys found in $arr will be removed.
		foreach ($arr as $key) {
			if (isset($this->session_vars[$key])) {
				unset($this->session_vars[$key]);
			}
		}
	}
	
	function update($arr) {
		// $arr is an associative array of key/value pairs.
		// Any keys in session_vars that are also in $arr
		// will have their values overwritten by those in $arr.
		// Other session_var key/vals are not affected.
		foreach ($arr as $key => $val) {
			if (isset($this->session_vars[$key])) {
				$this->session_vars[$key] = $arr[$key];
			}
		}
	}


	
	function generate($encode = "html", $overrideVars=array()) {
		// Returns a URL with the appropriate session_vars.
		// If $encode is "html", the URL will be suitable to be put in HTML.
		// If $encode is "none", the URL will be as is.
		// If $encode is "url", the URL will...
		//
		// $overrideVars is a key=>value mapping which allows some
		// specific variable/value pairs to be overridden/inserted
		// into the query. Use this when you want to keep the standard
		// 'session vars' in a url, but override just one or two of
		// them.
		global $DATA;
		
		$url_args = array ();
		
		foreach (array_merge($this->session_vars, $overrideVars) as $key => $var) {
			if ($var != null)
				$url_args[] = "$key=" . urlencode(stripslashes($var));
		}
		
		$page_url = WEBPATH . $DATA->page_metadata($this->destinationpage, "url");
		
		if (sizeof($url_args) == 0) {
			return $page_url;
		} else {
			if ($encode == "html") {
				return $page_url . "?" . implode("&amp;", $url_args);
			} elseif ($encode == "none" || $encode == "url") {
				return $page_url . "?" . implode("&", $url_args);
			}
		}
	}


/* 	DEPRECATED. Use hidden_form_vars() in utility.php instead. */

	// Use this when you have a form and want to retain some/all of the 
	// variables in the URL get string.
	// If you have a form that changes, say, $s, then you'll need to
	// pass "s" in in the $remove_vars array, so it isn't created as a
	// hidden variable.
	function hidden_form_varsOLD ($remove_vars=array(), $insert_vars=array()) {
		global $HTTP_GET_VARS, $HTTP_POST_VARS;
		
		// This should really be tidied up lots. That $dont_keep array for a start is NASTY!
		// You can also pass in an array of variables to remove() and insert().
		
		foreach ($HTTP_GET_VARS as $key => $val) {
			$vars[$key] = get_http_var($key);
		}
		foreach ($HTTP_POST_VARS as $key => $val) {
			$vars[$key] = get_http_var($key);
		}

		// We'll want to reset things to this when we're done.
		$old_session_vars = $this->session_vars;
		$this->reset();
		$this->insert($vars);
		$this->remove($remove_vars);
		$this->insert($insert_vars);

		$html = "";

		// Put keys of any variables you never want to hang on to in here:
		$dont_keep = array();	// VERY BAD!
		$this->remove($dont_keep);

		foreach ($this->session_vars as $key => $val) {
			if (!in_array($key, $dont_keep)) {
				$html .= '<input type="hidden" name="' . $key . '" value="' . $val . "\" />\n";

			}
		}
		
		// Reset $session_vars to how it was before. 
		// Otherwise if you call functions after you've generated hidden vars
		// everything will be changed around from how it was before.
		$this->session_vars = $old_session_vars;

		if ($html != "") {
			return $html . "\n";
		} else {
			return $html;
		}

	}

}

?>
