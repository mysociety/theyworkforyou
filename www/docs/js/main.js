function showVideo() {
	document.getElementById('video_show').style.display='block';
	document.getElementById('video_wrap').style.display='none';
	return false;
}

function hideVideo() {
	document.getElementById('video_wrap').style.display='block';
	document.getElementById('video_show').style.display='none';
	return false;
}

function moveVideo() {
	return true; // XXX
}
