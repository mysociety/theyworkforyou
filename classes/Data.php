<?php

namespace MySociety\TheyWorkForYou;

/**
 * Data Class
 *
 * Includes a metadata file that contains the actual data. It will have an array like:
 *
 * ```php
 * $this->page = array (
 *     "default" => array (
 *         "sitetitle"    => "Haddock Directory",
 *         "session_vars" => array()
 *     ),
 *     "previous" => array (
 *         "title"   => "Previous links",
 *         "url"     => "previouslinks/",
 *         "section" => "blah"
 *     )
 *     etc...
 * );
 * ```
 *
 * And a $this->section array, although this is as yet unspecified. Something like:
 *
 * ```php
 * $this->section = array (
 *     "blah" => array (
 *         "title" => "Blah",
 *         "menu"  => array (
 *             "text"  => "Blah",
 *             "title" => "Here's a link to Blah"
 *         )
 *     )
 * );
 * ```
 *
 * At some points we have a function where $type is passed in as, say, "page"
 * and then we do:
 *
 * ```php
 * $dataarray =& $this->$type;
 * return $dataarray[$item][$key];
 * ```
 *
 * Why? Because doing `$this->$type[$item][$key]` doesn't seem to work and
 * we need to use the reference to get it working.
 *
 * @author Phil Gyford <phil@gyford.com>
 */

class Data {
    public $page;
    public $section;

    public function __construct() {
        include_once METADATAPATH; // defined in config.php
        $this->page = $page;
        $this->section = $section;
    }

    /**
     * Set $this_section depending on this page's section.
     *
     * Special function for setting $this_section depending on the value of $this_page.
     */

    public function set_section() {
        // This should be called at the start of a page.
        global $this_section, $this_page;

        $this_section = $this->page_metadata($this_page, "section");
    }

    /**
     * Get page metadata
     *
     * @param string $page Page name
     * @param string $key  The element of metadata you want to retrieve
     */

    public function page_metadata($page, $key) {
        return $this->getMetadata(
            [
                'page' => $page,
                'key'  => $key,
            ],
            'page'
        );
    }

    /**
     * Get section metadata
     *
     * @param string $section Section name
     * @param string $key     The element of metadata you want to retrieve
     */

    public function section_metadata($section, $key) {
        return $this->getMetadata(
            [
                'section' => $section,
                'key'     => $key,
            ],
            'section'
        );
    }

    /**
     * Set page metadata
     *
     * @param $page  Page name
     * @param $key   The element of metadata you want to set
     * @param $value The value to set
     */

    public function set_page_metadata($page, $key, $value) {
        $this->setMetadata(["page" => $page, "key" => $key, "value" => $value]);
    }

    /**
     * Set section metadata
     *
     * @param $section Section name
     * @param $key     The element of metadata you want to set
     * @param $value   The value to set
     */

    public function set_section_metadata($section, $key, $value) {
        $this->setMetadata(["section" => $section, "key" => $key, "value" => $value]);
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

    /**
     * Test for the presence of something
     *
     * eg $exists = $DATA->test_for_metadata("page", "about", "title")
     */

    public function test_for_metadata($type, $item, $key) {
        $dataarray = & $this->$type;

        if (isset($dataarray[$item][$key])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $type
     */

    private function getMetadata($args, $type) {
        // $type is either 'page' or 'section'
        global $this_page, $this_section;

        if (is_array($args)) {
            $item = $args[$type];
            $key = $args['key'];
        } else {
            $var = "this_" . $type;
            $item = $$var; // $this_page or $this_section.
            $key = $args;
        }

        twfy_debug("DATA", "$type: $item, $key");
        $dataarray = & $this->$type;

        if ($this->test_for_metadata($type, $item, $key)) {
            $return = $dataarray[$item][$key];
            $debugtext = "Key: " . $type . "[" . $item . "][" . $key . "]";

        } elseif ($this->test_for_metadata($type, "default", $key)) {
            $return = $dataarray["default"][$key];
            $debugtext = "Key: " . $type . "['default'][" . $key . "]";

        } else {
            $return = false;
            $debugtext = "No metadata found for key '$key'";
        }

        twfy_debug("DATA", "$debugtext, returning '" . (is_scalar($return) ? $return : gettype($return)) . "'.");

        return $return;
    }

    private function setMetadata($args) {

        if (isset($args["section"])) {
            $type = "section";
            $item = $args["section"];
        } else {
            $type = "page";
            $item = $args["page"];
        }

        $key = $args["key"];
        $value = $args["value"];

        twfy_debug("DATA", "Setting: " . $type . "[" . $item . "][" . $key . "] = '" . print_r($value, 1) . "'");

        $dataarray = & $this->$type;
        $dataarray[$item][$key] = $value;
    }

}
