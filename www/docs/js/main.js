/* Glue for talking to the Flash, displaying of the video */

var lastgid = '';

function showVideo() {
	if (lastgid)
		clearGID(lastgid);
	document.getElementById('video_show').style.display='block';
	document.getElementById('video_wrap').style.display='none';
	return false;
}

function hideVideo() {
	document.getElementById('video_wrap').style.display='block';
	document.getElementById('video_show').style.display='none';
	return false;
}

function moveVideo(gid) {
	var success = document['video'].moveVideo(gid);
	if (success) {
		document.getElementById('video_wrap').style.display='block';
		document.getElementById('video_show').style.display='none';
		return false;
	}
	return true;
}

function updateSpeech(gid) {
	if (lastgid)
		clearGID(lastgid);
	gid = gid.split('.');
	gid = 'g' + gid[gid.length-2] + '.' + gid[gid.length-1];
	var d = document.getElementById(gid);
	if (d) d.className += ' vidOn';
	lastgid = gid;
}

function clearGID(gid) {
	gid = document.getElementById(gid);
	if (gid) {
		gid.className = gid.className.substring(0, gid.className.length-6);
	}
	lastgid = '';
}


function toggleVisible (sId) {
    if (document.getElementById(sId).style.display == 'none'){
        document.getElementById(sId).style.display = 'block';
    }else{
        document.getElementById(sId).style.display = 'none';
    }
}

function showPersonLinks(sId){
    //change class of image
    $('#speakerimage_' + sId).addClass("hover");
    
    //show links
    $('#personinfo_' + sId).show();
    $('#personinfo_' + sId).mouseleave(function (){
        $('#personinfo_' + sId).hide();
        $('#speakerimage_' + sId).removeClass("hover");
    });
    
}
