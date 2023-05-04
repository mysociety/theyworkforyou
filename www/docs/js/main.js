var trackEvent = function(eventName, params) {
  // We'll return a promise, and resolve it when either Gtag handles
  // our event, or a maximum fallback period elapses. Promises can
  // only be resolved once, so this also ensures whatever callbacks
  // are attached to the promise only execute once.
  var dfd = $.Deferred();

  var callback = function(){
      dfd.resolve();
  };

  // Returns either an array, or undefined.
  var measurementProtocolDetails = window.dataLayer.filter(function(row){
      return row[0] === 'measurement_protocol';
  })[0];

  if (measurementProtocolDetails) {
      var measurementParams = {
          measurement_id: measurementProtocolDetails[1],
          api_secret: measurementProtocolDetails[2]
      }
      if (measurementProtocolDetails[3]) {
          params['debug_mode'] = '1';
      }

      // Set a random client_id (2 32-bit integers seperated by a dot).
      // Note this random approach means the GA debugView won't work.
      // (To get that to work, you need to turn back on the cookies,
      // then use the same client_id as in in the _ga cookie.)
      var client_id = Math.floor(Math.random() * 1000000000) + '.' + Math.floor(Math.random() * 1000000000);

      // print response to console
      $.ajax({
          url: 'https://www.google-analytics.com/mp/collect?' + $.param(measurementParams),
          method: 'POST',
          data: JSON.stringify({
              client_id: client_id,
              events: [{
                  name: eventName,
                  params: params
              }]
          })
      }).always(callback);

      // Wait a maximum of 2 seconds for ajax to resolve.
      setTimeout(callback, 2000);

  } else {
      // Measurement Protocol not available. Resolve promise immediately.
      callback();
  }

  return dfd.promise();
};

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
    $('.js-vote-accordion').each(function(){

        var $votes = $(this);
        var $trigger = $('<button>');

        var fold = function() {
            $votes.children().eq(10).nextAll().addClass('visible-print-block');
        };

        var unfold = function() {
            $votes.children().removeClass('visible-print-block');
            $trigger.remove();
        };

        if ( $votes.children().length > 10 ) {
          fold();

          $trigger.addClass('hidden-print');
          $trigger.html('<span class="button button--secondary button--small">Show All</span>');
          $trigger.on('click', unfold);
          $trigger.appendTo($votes);
        }

    });

  var tocLink = function($el){
    var id = $el.attr('id');
    var label = $el.attr('data-toc-label') || $el.text();
    return $('<a>').attr('href', '#' + id).text(label);
  }

  var tocRelationship = function($a, $b) {
    var l = ['H1', 'H2', 'H3', 'H4'];
    var a = $a.prop('tagName');
    var b = $b.prop('tagName');
    if ( l.indexOf(b) === -1 || l.indexOf(a) === -1 ) {
      return 'sibling';
    } else if ( l.indexOf(b) > l.indexOf(a) ) {
      return 'child';
    } else if ( l.indexOf(b) < l.indexOf(a) ) {
      return 'parent';
    } else {
      return 'sibling';
    }
  }

  $('.js-toc').each(function(){

    if ( "IntersectionObserver" in window ) {
      var visibleTocSections = [];

      var wrapTocSection = function($el){
        var $sectionContents = $el.nextUntil('.js-toc-item');
        $el.add($sectionContents).wrapAll('<div class="js-toc-section"></div>');
        observer.observe( $el.parent('.js-toc-section')[0] );
      };

      var highlightCurrentTocSection = function(){
        $container.find('li.current').removeClass('current');
        // We could mark all of the visibleTocSections as .current,
        // but for now, let's keep it simple and just do the last one.
        var id = visibleTocSections[ visibleTocSections.length - 1 ];
        $container.find('a[href="#' + id + '"]').parents('li').addClass('current');
      };

      var tocItemIntersection = function(entries, observer){
        $.each(entries, function(i, entry){
          var id = $(entry.target).children('.js-toc-item, .js-toc-title').eq(0).attr('id');
          if ( entry.isIntersecting ) {
            // Element is visible.
            visibleTocSections.push(id);
          } else if ( visibleTocSections.indexOf(id) > -1 ) {
            // Previously visible element is no longer visible.
            visibleTocSections.splice( visibleTocSections.indexOf(id), 1 );
          }
          highlightCurrentTocSection();
        });
      };

      var observer = new IntersectionObserver(tocItemIntersection, {
        rootMargin: '-20% 0% -20% 0%',
        threshold: 0
      });
    }

    var $container = $(this);

    var $button = $('<button>').text('Jump to page section');
    $button.prepend('<span>');
    $button.on('click', function(){
      $container.toggleClass('mobile-expanded');
    });
    $container.append($button);

    $container.on('click', 'a', function(){
      $container.removeClass('mobile-expanded');
    });

    var $tocTitle = $('.js-toc-title');
    if ( $tocTitle.length === 1 ) {
      $container.append( tocLink($tocTitle) );
      if ( "IntersectionObserver" in window ) {
        wrapTocSection($tocTitle);
      }
    }

    var $tocItems = $('.js-toc-item');

    var $tocUl = $('<ul>');
    $container.append( $tocUl );
    var levels = [ $tocUl ];

    $tocItems.each(function(i){
      var $tocItem = $(this);
      var $li = $('<li>');
      $li.append( tocLink( $tocItem ) );
      levels[0].append($li);

      var $nextItem = $tocItems.eq(i+1);
      var relationship = tocRelationship( $tocItem, $nextItem );
      if ( relationship === 'child' ) {
        var $tocChildrenUl = $('<ul>');
        $li.append($tocChildrenUl);
        levels.unshift($tocChildrenUl);
      } else if ( relationship === 'parent' ) {
        levels.shift();
      }

      if ( "IntersectionObserver" in window ) {
        wrapTocSection($tocItem);
      }
    });

  });


  if ( ! ( 'open' in document.createElement('details') ) ) {
    $('summary').siblings().hide();
    $('summary').on('click', function(){
      $(this).siblings().toggle();
    });
  }

  $('.autocomplete').each(function() {
    accessibleAutocomplete.enhanceSelectElement({
      selectElement: this,
      displayMenu: 'overlay',
      defaultValue: '',
      required: true
    });
  });

  $(".menu-dropdown").click(function(e) {
    var t = $(e.target);
    if ( ! t.hasClass('button') ) {
        t = t.parent('.button');
    }
    t.toggleClass('open');
    t.parent().next(".nav-menu").toggleClass('closed');
  });

  $('.js-show-all-votes').on('click', function(){
    $(this).fadeOut();
    $('.policy-vote--minor').slideDown();
    $('#policy-votes-type').text('All');
  });

  $('a[href="#past-list-dates"]').on('click', function(e){
    e.preventDefault();
    $(this).trigger('blur');
    $('#past-list-dates').slideToggle();
  })

  // Move advanced search options to appear *before* results.
  // (This means non-javascript users don't have to scroll past it
  // to see their search results.)
  $('.js-search-form-without-options').replaceWith( $('.js-search-form-with-options') );

  // Show/hide advanced search options.
  $('.js-toggle-search-options').on('click', function(e){
    e.preventDefault();
    var id = $(this).attr('href'); // #options
    if($(id).is(':visible')){
      $('.js-toggle-search-options[href="' + id + '"]').removeClass('toggled');
      $(id).slideUp(250, function(){
        // Disable the inputs *after* they're hidden (less distracting).
        $(id).find(':input').attr('disabled', 'disabled');
      });
    } else {
      $('.js-toggle-search-options[href="' + id + '"]').addClass('toggled');
      $(id).find(':input:disabled').removeAttr('disabled');
      $(id).slideDown(250);
    }
  });
  if (!$('#options').data('advanced')) {
    $("#options").find(":input").attr("disabled", "disabled");
  }
});

// Backwards-compatible functions for the click/submit trackers on MP pages
function trackFormSubmit(form, category, name, value) {
  trackEvent("form_submit",
    { category: category,
      event_action: name,
      event_label: value
  }).always(function(){
    form.submit();
  });
}
function trackLinkClick(link, category, name, value) {
  trackEvent(category, {
    event_action: name,
    event_label: value
  }).always(function(){
    document.location.href = link.href;
  })
}
