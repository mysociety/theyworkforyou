<?php
/**
 * Url Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Url
 *
 * The URL class is used for generating URLs and other related things.
 * Relies on there being a get_http_var() function.
 *
 * This is probably how you'll use it most:
 *     $URL = new \MySociety\TheyWorkForYou\Url("yourpagename");
 *     print $URL->generate();
 *
 * In the metadata you should set a session_vars variable, an array.
 * The default page session_vars may be just array("debug").
 * These can then be overridden on a per-page basis.
 * Session vars are GET/POST vars that will be passed by default to that page.
 * ie, if "foo=bar" is in the current URL and you generate a URL to a page that has "foo"
 * as a session_var, "foo=bar" will be automatically added to the generated URL.
 * You can modify the session vars that will be included in the URL generated using the class functions.
 */

class Url {

    public function __construct($pagename) {
        // Initialise.
        global $DATA;

        // The page we're going to be generating URL(s) for.
        $this->destinationpage = $pagename;

        // These stores an associative array of key/value pairs that
        // we'll want passed on to other pages.
        $this->session_vars = array ();

        // Prevent things using $DATA running if it hasn't been set, ie in testing
        if (isset($DATA)) {

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

        }

        // So we can restore the originals.
        $this->original_session_vars = $this->session_vars;

    }

    /**
     * Sets $URL->session_vars back to how they were when the object was instantiated.
     */

    public function restore() {
        // Call this to reset the session vars to how they were when
        // the object was instantiated.
        $this->session_vars = $this->original_session_vars;

    }

    /**
     * Sets $URL->session_vars to be an empty array.
     */

    public function reset() {
        // Call this to remove all the session_vars.
        $this->session_vars = array ();
    }

    /**
     * Add/overwrite session key and value pair(s).
     */
    public function insert($arr) {
        // $arr is an associative array of key/value pairs.
        // These will be used as session_vars in addition to any that
        // already exist.
        foreach ($arr as $key => $val) {
            $this->session_vars[$key] = $val;
        }
    }

    /**
     * Remove session key/value pair(s).
     */
    public function remove($arr) {
        // $arr is a list array of key names. Any key/value pairs
        // in session_vars with keys found in $arr will be removed.
        foreach ($arr as $key) {
            if (isset($this->session_vars[$key])) {
                unset($this->session_vars[$key]);
            }
        }
    }

    /**
     * Update the values of some/all session_vars.
     */
    public function update($arr) {
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

    /**
     * Generate a URL to the page specified with session vars.
     */
    public function generate($encode = "html", $overrideVars=array()) {
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
            if (is_array($var)) {
                foreach ($var as $v) {
                    $url_args[] = "$key=" . urlencode(stripslashes($v));
                }
            } elseif ($var != null)
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

}
