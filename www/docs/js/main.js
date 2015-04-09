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

    if (document.getElementById(sId).style.display == 'block'){
        document.getElementById(sId).style.display = 'none';
    }else{
        document.getElementById(sId).style.display = 'block';
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

function swapCalendar(direction) {
  var current = $('.cal-wrapper.visible');
  var num = parseInt(current.attr('data-count'), 10);
  var new_num = num + direction;
  var next = $('#day-' + new_num);
  if ( next.length ) {
    var date = next.attr('data-date');
    current.addClass('hidden').removeClass('visible');
    next.addClass('visible').removeClass('hidden');
    $('.controls__current a').text(date);
  }
}

$(function(){
	$('body').addClass('js');
  // check if touch
  if ( Modernizr.touch ) {
      $('body').addClass('touch');
  }
  $('.moreinfo').hover(
      function() { $(this).children('.moreinfo-text').show(); },
      function() { $(this).children('.moreinfo-text').hide(); }
  );
  if( $('.about-this-page__one-of-two').length ) {
    // these are usually .panel--secondary elements
    var $elements = $('.about-this-page__one-of-two').children();
    var maxHeight = 0;
    $elements.each(function(){
      // find height with padding but not margin
      var thisHeight = $(this).outerHeight(false);
      maxHeight = Math.max(maxHeight, thisHeight);
    });
    $elements.each(function(){
      // assumes {box-sizing: border-box}
      $(this).css('height', maxHeight);
    });
  }
  $('.controls__prev').on('click', function prevDay() {
    swapCalendar(-1);
    return false;
  });
  $('.controls__next').on('click', function nextDay() {
    swapCalendar(1);
    return false;
  });
  $('.button--show-all').on('click', function fpShowAll() {
    $self = $(this);
    if ( $self.text() == 'Show more' ) {
      $('.recently__list-more').show();
      $self.text('Show less');
    } else {
      $('.recently__list-more').hide();
      $self.text('Show more');
    }
    return false;
  });
	window.setTimeout(function(){
		if ( $('#minisurvey').length ) {
			lastAnswered = $.cookie('survey');
			current = $('input[name="question_no"]').attr('value');
			// need to check the cookie here as browser caching means that
			// the survey block can be included in the page even after they've
			// answered the question
			if ( lastAnswered == null || lastAnswered < current ) {
				$('#minisurvey').show('slow');
			}
		}
	}, 2000);
});
