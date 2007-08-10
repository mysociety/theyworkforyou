<?php

/*

Was: SKIN class v1.2 2003-07-15
phil@gyford.com

REQUIRES:
	utility.php v1.0
	CONSTANTS:
		WEBPATH			/directory/
		COOKIEDOMAIN	.haddock.org


DOCUMENTATION:

For setting different stylesheets on the fly, setting cookies
to maintain the new skin for the user.

Each skin can have a different number of stylesheets. 
global.css (and global_non_ns4.css) are called first.
screen.css and print.css are media-specific.
extra.css comes last and can modfiy the previous styles.
		
Each skin can use another skin's stylesheets.
If a stylesheet is left out of a skin's array, or is set
to "", that stylesheet is not used.


We have an array of possible skins like this:

$this->skins = array (
	"default" => array (
		"global" 	=> "default",
		"screen" 	=> "",
		"print" 	=> "default",
		"extra" 	=> ""
	),
	"test" => array (
		"global"	=> "test",
		"print" 	=> "test"
	)
);


To change skin, be on a page where $this_page="skin" and have "newskin=foo" in the URL.
(We could change this to allow the skin to be changed on any page...)

Call $SKIN->output_stylesheets() from wherever you need the stylesheets to appear.

Call $SKIN->get_skin() if you need to know what skin is currently being used.



VERSIONS
v1.2	2003-11-16
		Changed from using global variables to constants.
*/


class SKIN {

	var $skin = 'default';
	
	var $skins = array (
		'default' => array (
			'global' 	=> 'default',
			'screen' 	=> '',
			'print' 	=> 'default',
			'extra' 	=> ''
		),
		// Switch all stylesheets off.
		'none' => array (
			'global' 	=> '',
			'screen' 	=> '',
			'print' 	=> '',
			'extra' 	=> ''
		)
	);
	
	function SKIN () {
		global $this_page;

		if ($this_page == "skin" && get_http_var("newskin") != "") {
			// We only allow the reskinning on the "skin" page.
			$this->new_skin( get_http_var("newskin") );
		} else {
			$this->set_skin ( get_cookie_var("skin") );
		}	
	
	}
	
	
	function new_skin ($skin) {
		// If this is a valid skin, then set a cookie

		if (isset($skin) && isset($this->skins[$skin])) {
			setcookie("skin", "$skin",time() + (86400 * 365), WEBPATH, COOKIEDOMAIN, 0);
			$this->set_skin($skin); // So the new skin works on the current page.
		}
	}
	

	function get_skin () {
		return $this->skin;
	}
	
	
	function set_skin ($skin) {
		
		if (isset($skin) && isset($this->skins[$skin])) {
			$this->skin = $skin;
		} else {
			$this->skin = "default";
		}

		twfy_debug ("SKIN", "Skin set to '".$this->skin."'");
	}
	
	
	function output_stylesheets () {
		
		print "\t<!-- skin: ".$this->skin." -->\n";

		// The array of stylesheets to use for this skin.
		$skinstyles = $this->skins[$this->skin];

		if (isset($skinstyles["global"]) && $skinstyles["global"] != "") {
			?>
	<link rel="stylesheet" href="<?php echo WEBPATH; ?>style/<?php echo $skinstyles['global']; ?>/global.css" type="text/css">
<?php
			if (isset($_SERVER['HTTP_USER_AGENT']) && !(ereg("MSIE 4.0", $_SERVER['HTTP_USER_AGENT']))){
				// Hide this from IE4 and Mac AOL5.
				?>
	<style type="text/css">
		@import url(<?php echo WEBPATH; ?>style/<?php echo $skinstyles['global']; ?>/global_non_ns4.css);
	</style>
<?php
			}
		}
		if (isset($skinstyles["screen"]) && $skinstyles["screen"] != "") {
			?>
	<link rel="stylesheet" href="<?php echo WEBPATH; ?>style/<?php echo $skinstyles['screen']; ?>/screen.css" type="text/css" media="screen">
<?php
		}
		if (isset($skinstyles["extra"]) && $skinstyles["extra"] != "") {
			?>
	<link rel="stylesheet" href="<?php echo WEBPATH; ?>style/<?php echo $skinstyles['extra']; ?>/extra.css" type="text/css" media="screen">
<?php
		}
		if (isset($skinstyles["print"]) && 
			$skinstyles["print"] != "" && 
			( isset($_SERVER['HTTP_USER_AGENT']) && !(ereg("MSIE 4.0", $_SERVER['HTTP_USER_AGENT'])) )
		) {
			// Hide this from IE4 and Mac AOL5.
			?>
	<link rel="stylesheet" href="<?php echo WEBPATH; ?>style/<?php echo $skinstyles['print']; ?>/print.css" type="text/css" media="print">
<?php
		}

		if (get_http_var('c4') || get_http_var('c4x')) {
			$x = get_http_var('c4x') ? 'X' : ''; ?>
	<link rel="stylesheet" href="<?php echo WEBPATH; ?>style/channel4/global<?=$x ?>.css" type="text/css">
<?php
			if (isset($_SERVER['HTTP_USER_AGENT']) && !(ereg("MSIE 4.0", $_SERVER['HTTP_USER_AGENT']))){
				// Hide this from IE4 and Mac AOL5.
				?>
	<style type="text/css">
		@import url(<?php echo WEBPATH; ?>style/channel4/global<?=$x ?>_non_ns4.css);
	</style>
<?php
			}
		}
	}

}


?>
