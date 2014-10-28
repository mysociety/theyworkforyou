<?php
/**
 * Data Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Data Class
 *
 * @author phil@gyford.com
 *
 * Includes a metadata file that contains the actual data. It will have an array like:
 *
 *     $this->page = array (
 *         "default" => array (
 *             "sitetitle"     => "Haddock Directory",
 *             "session_vars" => array()
 *         ),
 *         "previous" => array (
 *             "title"         => "Previous links",
 *             "url"           => "previouslinks/",
 *             "section"       => "blah"
 *         )
 *         etc...
 *     );
 *
 * And a $this->section array, although this is as yet unspecified. Something like:
 *
 *     $this->section = array (
 *         "blah" => array (
 *             "title"     => "Blah",
 *             "menu"      => array (
 *                 "text"      => "Blah",
 *                 "title"     => "Here's a link to Blah"
 *             )
 *         )
 *     );
 *
 * At some points we have a function where $type is passed in as, say, "page"
 * and then we do:
 *     $dataarray =& $this->$type;
 *     return $dataarray[$item][$key];
 *
 * Why? Because doing $this->$type[$item][$key] doesn't seem to work and
 * we need to use the reference to get it working.
 */

class Data {

    public function __construct() {

        include_once METADATAPATH;  // defined in config.php

    }

    /**
     * Sets $this_section depending on this page's section.
     *
     * Special function for setting $this_section depending on the value of $this_page.
     */

    public function set_section() {
        // This should be called at the start of a page.
        global $this_section, $this_page;

        $this_section = $this->page_metadata($this_page, "section");
    }

    /**
     * Returns an item of metadata for a page.
     *
     * @param string $page The page name.
     * @param string $key  The element of metadata to retrieve.
     */

    public function page_metadata($page, $key) {
        return $this->_get_metadata(array("page"=>$page, "key"=>$key), "page");
    }

    /**
     * Returns an item of metadata for a section.
     *
     * @param string $section The section name.
     * @param string $key     The element of metadata to retrieve.
     */

    public function section_metadata($section, $key) {
        return $this->_get_metadata(array("section"=>$section, "key"=>$key), "section");
    }

    /**
     * Sets an item of metadata for a page.
     *
     * @param string $page  The page name.
     * @param string $key   The element of metadata to set.
     * @param string $value The value to set the metadata to.
     */

    public function set_page_metadata($page, $key, $value) {
        $this->_set_metadata(array("page"=>$page,"key"=>$key,"value"=>$value));
    }

    /**
     * Sets an item of metadata for a section.
     *
     * @param string $section  The section name.
     * @param string $key      The element of metadata to set.
     * @param string $value    The value to set the metadata to.
     */

    public function set_section_metadata($section, $key, $value) {
        $this->_set_metadata(array("section"=>$section,"key"=>$key,"value"=>$value));
    }


    /**
     * Directly access an item.
     *
     * @deprecated
     */

    public function metadata($type, $item, $key) {
        if ($this->test_for_metadata($type, $item, $key)) {
            return $this->$type[$item][$key];
        } else {
            return "INVALID METADATA: $type[$item][$key]";
        }
    }



    // Test for the presence of something.
    // eg $exists = $DATA->test_for_metadata("page", "about", "title")
    public function test_for_metadata($type, $item, $key) {
        $dataarray =& $this->$type;

        if (isset($dataarray[$item][$key])) {
            return true;
        } else {
            return false;
        }
    }

    // Only accessed through page_metadata() or section_metadata()
    private function _get_metadata($args="", $type) {
        // $type is either 'page' or 'section'
        global $this_page, $this_section;

        if (is_array($args)) {
            $item = $args[$type];
            $key = $args['key'];
        } else {
            $var = "this_".$type;
            $item = $$var; // $this_page or $this_section.
            $key = $args;
        }

        twfy_debug("DATA", "$type: $item, $key");
        $dataarray =& $this->$type;

        if ($this->test_for_metadata($type, $item, $key)) {
            $return = $dataarray[$item][$key];
            $debugtext = "Key: ".$type."[".$item."][".$key."]";

        } elseif ($this->test_for_metadata($type, "default", $key)) {
            $return = $dataarray["default"][$key];
            $debugtext = "Key: ".$type."['default'][".$key."]";

        } else {
            $return = false;
            $debugtext = "No metadata found for key '$key'";
        }

        twfy_debug("DATA", "$debugtext, returning '" . (is_scalar($return) ? $return : gettype($return)) . "'.");

        return $return;
    }

    private function _set_metadata($args) {

        if (isset($args["section"])) {
            $type = "section";
            $item = $args["section"];
        } else {
            $type = "page";
            $item = $args["page"];
        }

        $key = $args["key"];
        $value = $args["value"];

        twfy_debug("DATA", "Setting: ".$type."[".$item."][".$key."] = '" . print_r($value, 1) . "'");

        $dataarray =& $this->$type;
        $dataarray[$item][$key] = $value;
    }

}
