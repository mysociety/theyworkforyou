<?php

/* 
DATA class v1.1 2003-11-25
phil@gyford.com


REQUIRED:
	utiltity.php	v1.0
	GLOBALS:
		METADATAPATH	/Library/Webserver/haddock/includes/directory/metadata.php
	
	
DOCUMENTATION:

Instantiates itself as $DATA.

Includes a metadata file that contains the actual data. It will have an array like:

		$this->page = array (
			"default" => array (
				"sitetitle"		=> "Haddock Directory",
				"session_vars" => array()
			),
			"previous" => array (
				"title"			=> "Previous links",
				"url"			=> "previouslinks/",
				"section"		=> "blah"
			)
			etc...
		);

And a $this->section array, although this is as yet unspecified. Something like:
		
		$this->section = array (
			"blah" => array (
				"title" 	=> "Blah",
				"menu" 		=> array (
					"text"		=> "Blah",
					"title"		=> "Here's a link to Blah"
				)
			)
		);
		

PUBLICALLY ACCESSIBLE FUNCTIONS:

set_section()			- Sets $this_section depending on this page's section.

page_metadata(),
section_metadata()		- Returns an item of metadata for this page/section.

set_page_metadata(),
set_section_metadata()	- Sets an item of metadata for this page/section.


NOTE:

At some points we have a function where $type is passed in as, say, "page"
and then we do:
	$dataarray =& $this->$type;
	return $dataarray[$item][$key];
	
Why? Because doing $this->$type[$item][$key] doesn't seem to work and
we need to use the reference to get it working.



Versions
========
v1.1	2003-11-25
		Changed to using named constants, rather than global variables.
*/

class DATA {
	
	
	
	function Data () {
		
		include_once METADATAPATH;	// defined in config.php
	
	}


//////////////////////////////////////
// PUBLIC METADATA ACCESS FUNCTIONS //
//////////////////////////////////////


	// Special function for setting $this_section depending on the value of $this_page.	
	function set_section () {
		// This should be called at the start of a page.
		global $this_section, $this_page;

		$this_section = $this->page_metadata($this_page, "section");
	}
	


	// Getting page and section metadata
	// $page/$section is a page name.
	// $key is the element of metadata you want to retrieve.
	function page_metadata ($page, $key) {
		return $this->_get_metadata(array("page"=>$page, "key"=>$key), "page");
	}

	function section_metadata ($section, $key) {
		return $this->_get_metadata(array("section"=>$section, "key"=>$key), "section");
	}
	
	

	// Setting page and section.
	// $page/$section, $key and $value should make sense...
	function set_page_metadata ($page, $key, $value) {
		$this->_set_metadata(array("page"=>$page,"key"=>$key,"value"=>$value));
	}

	function set_section_metadata ($section, $key, $value) {
		$this->_set_metadata(array("section"=>$section,"key"=>$key,"value"=>$value));
	}
	

	// DEPRECATED.
	// Directly access an item.
	function metadata ($type, $item, $key) {
		if ($this->test_for_metadata($type, $item, $key)) {
			return $this->$type[$item][$key];
		} else {
			return "INVALID METADATA: $type[$item][$key]";
		}
	}
	
	
	
	// Test for the presence of something.
	// eg $exists = $DATA->test_for_metadata("page", "about", "title")
	function test_for_metadata ($type, $item, $key) {
		$dataarray =& $this->$type;

		if (isset($dataarray[$item][$key])) {
			return true;
		} else {
			return false;
		}
	}



///////////////////////////////////////
// PRIVATE METADATA ACCESS FUNCTIONS //
///////////////////////////////////////

	// Only accessed through page_metadata() or section_metadata()
	function _get_metadata ($args="", $type) {
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

		debug ("DATA", "$type: $item, $key");
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
		
		debug("DATA", "$debugtext, returning '$return'.");

		return $return;
	}



	function _set_metadata ($args) {

		if (isset($args["section"])) {
			$type = "section";
			$item = $args["section"];
		} else {
			$type = "page";
			$item = $args["page"];
		}
		
		$key = $args["key"];
		$value = $args["value"];
				
		debug("DATA", "Setting: ".$type."[".$item."][".$key."] = '$value'");
		
		$dataarray =& $this->$type;
		$dataarray[$item][$key] = $value;
	}

}

$DATA = new DATA;

?>
