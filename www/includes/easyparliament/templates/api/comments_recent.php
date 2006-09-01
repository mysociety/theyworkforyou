<?php
/*	For listing, say, the most recent comments on the site.
	Remember, we are currently within the COMMENTLIST class,
	in the render() function.
*/

if (isset($data['comments']) && count($data['comments']) > 0) {
	$USERURL = new URL('userview');
	foreach ($data['comments'] as $key => $value) {
		unset($data['comments'][$key]['modflagged']);
		unset($data['comments'][$key]['visible']);
		$USERURL->insert(array('u'=>$value['user_id']));
		$data['comments'][$key]['userurl'] = $USERURL->generate();
	}
}
api_output($data);
?>
