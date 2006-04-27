<?php

class GLOSSARYLIST {

	function render ($data, $format='html', $template='glossary') {
		// Once we have the data that's to be rendered,
		// include the template.
		
		if ($format != 'html') {
			$format = 'html';
		}
		
		include (FILEPATH."/../includes/easyparliament/templates/$format/$template.php");
	
	}

}

?>