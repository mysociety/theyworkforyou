<?php
/*	Remember, we are currently within the COMMENTLIST class,
	in the render() function.
*/

if (isset($data['comments'])) {
	foreach ($data['comments'] as $key => $row) {
		unset($data['comments'][$key]['modflagged']);
		unset($data['comments'][$key]['visible']);
	}
}
api_output($data);

?>
