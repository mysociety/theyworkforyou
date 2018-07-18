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

  $('.js-toggle').each(function(){
    var $link = $(this);
    var $el = $( $link.attr('href') );
    var eventName = 'click.toggle-until-click-outside-' + $el.attr('id');

    var openDropdown = function openDropdown(){
      $el.addClass('toggled');
      $el.attr('aria-hidden', 'false');
      $link.addClass('toggled');
      $link.attr('aria-expanded', 'true');

      if($link.is('.js-toggle-until-click-outside')){
        // Timeout is a bit hacky, but avoids cancelling the click
        // event before the current callback has ended
        setTimeout(function(){
          $el.on(eventName, function(e){
            e.stopPropagation();
          });
          $link.on(eventName, function(e){
            e.stopPropagation();
          })
          $('body').on(eventName, closeDropdown);
        }, 250);
      }
    }

    var closeDropdown = function closeDropdown() {
      $el.removeClass('toggled');
      $el.attr('aria-hidden', 'true');
      $link.removeClass('toggled');
      $link.attr('aria-expanded', 'false');
      $('body').off(eventName);
      $el.off(eventName);
      $link.off(eventName);
    }

    $link.attr('aria-controls', $el.attr('id'));
    $link.attr('aria-haspopup', 'true');

    $link.on('click', function(e){
      e.preventDefault();

      if ( $el.is('.toggled') ) {
        closeDropdown();
      } else {
        openDropdown();
      }
    });
  });

  $('.moreinfo').hover(
      function() { $(this).children('.moreinfo-text').show(); },
      function() { $(this).children('.moreinfo-text').hide(); }
  );

  $('.js-facebook-share').on('click', function(e){
    e.preventDefault();
    FB.ui({
      method: 'share',
      href: $(this).attr('data-url'),
      quote: $(this).attr('data-text')
    }, function(response){
        if (response && window.ga && window.ga.create) {
            window.ga('send', 'social', 'facebook', 'share', window.location.href);
        }
    });
  });

  // https://dev.twitter.com/web/intents#tweet-intent
  $('.js-twitter-share').on('click', function(e){
    e.preventDefault();
    var windowOptions = 'scrollbars=yes,resizable=yes,toolbar=no,location=yes';
    var width = 550;
    var height = 420;
    var winHeight = screen.height;
    var winWidth = screen.width;
    var left = Math.round((winWidth / 2) - (width / 2));
    var top = 0;
    if (winHeight > height) {
      top = Math.round((winHeight / 2) - (height / 2));
    }
    window.open(
        $(this).attr('href'),
        'intent',
        windowOptions + ',width=' + width + ',height=' + height + ',left=' + left + ',top=' + top
    );
    if (window.ga && window.ga.create) {
        window.ga('send', 'social', 'twitter', 'tweet', window.location.href);
    }
  });

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
