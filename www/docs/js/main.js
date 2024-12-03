var trackEvent = function(eventName, params) {
  // We'll return a promise, and resolve it when either Gtag handles
  // our event, or a maximum fallback period elapses. Promises can
  // only be resolved once, so this also ensures whatever callbacks
  // are attached to the promise only execute once.
  var dfd = $.Deferred();

  var callback = function(){
    dfd.resolve();
  };

  if (typeof gtag !== 'undefined') {
    // Tell Gtag to resolve our promise when it's done.
    var params = $.extend(params, {
      event_callback: callback
    });

    gtag('event', eventName, params);

    // Wait a maximum of 2 seconds for Gtag to resolve promise.
    setTimeout(callback, 2000);
  } else {
    // If gtag is not defined, e.g. in dev mode, resolve the promise immediately.
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
          var show_all = $votes.data('show-all');
          $trigger.html('<span class="button button--secondary button--small">' + show_all + '</span>');
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

  $('#add-all').on('click', function(e) {
    var $add_all = e.currentTarget;
    var $selected_related = document.querySelectorAll('input[name="selected_related_terms[]"]');
    if ($add_all.checked) {
      $selected_related.forEach(function(input) {
        if (input.checked) {
          input.setAttribute('data:was_checked', true);
        }
        input.checked = true;
        input.setAttribute('disabled', true);
      });
    } else {
      $selected_related.forEach(function(input) {
        if (!input.getAttribute('data:was_checked')) {
          input.checked = false;
        }
        input.removeAttribute('data:was_checked');
        input.removeAttribute('disabled');
      });
    }
  });
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

/* Donate page */

function fullname_show(focus){
  $('.donate-fullname').slideDown(100, function(){
    if (focus) {
      $('.donate-fullname input').focus();
    }
  });
}

function fullname_hide(){
  $('.donate-fullname').slideUp(100);
}

function fullname_toggle(test, focus){
  test ? fullname_show(focus) : fullname_hide();
}

function othervalue_show(focus){
  $('.how-much-other-value').slideDown(100, function(){
    $('.how-much-other-value input').prop("disabled", false);
    if (focus) {
      $('.how-much-other-value input').focus();
    }
  });
}

function othervalue_hide(){
  $('.how-much-other-value').slideUp(100, function(){
    $('.how-much-other-value input').prop("disabled", true);
  });
}

function othervalue_toggle(test, focus){
  test ? othervalue_show(focus) : othervalue_hide();
}

function amounts_annually(){
  $('.donate-annually-amount').show();
  $('.donate-monthly-amount').hide();
  $('.donate-one-off-amount').hide();
}

function amounts_monthly(){
  $('.donate-annually-amount').hide();
  $('.donate-monthly-amount').show();
  $('.donate-one-off-amount').hide();
}

function amounts_oneoff(){
  $('.donate-annually-amount').hide();
  $('.donate-monthly-amount').hide();
  $('.donate-one-off-amount').show();
}

function wrap_error($message){
  return '<div class="donate-form__error-wrapper"><p class="donate-form__error">' + $message + '</p></div>';
}

function createAccordion(triggerSelector, contentSelector) {
  var triggers = document.querySelectorAll(triggerSelector);
  
  triggers.forEach(function(trigger) {
    var content = document.querySelector(trigger.getAttribute('href'));

    var openAccordion = function() {
      content.style.maxHeight = content.scrollHeight + "px"; // Dynamically calculate height
      content.setAttribute('aria-hidden', 'false');
      trigger.setAttribute('aria-expanded', 'true');
    };

    var closeAccordion = function() {
      content.style.maxHeight = null; // Collapse
      content.setAttribute('aria-hidden', 'true');
      trigger.setAttribute('aria-expanded', 'false');
    };

    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      
      if (content.style.maxHeight) {
        closeAccordion();
      } else {
        openAccordion();
      }
    });
    
    // Accessibility
    trigger.setAttribute('aria-controls', content.getAttribute('id'));
    trigger.setAttribute('aria-expanded', 'false');
    content.setAttribute('aria-hidden', 'true');
    content.style.maxHeight = null;
  });
}

// Initialize accordion when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  createAccordion('.accordion-button', '.accordion-content');
});


$(function() {

  $('#how-often-annually').click(function() {
    var defaultValue = $(this).data('default-amount');
    $('#how-much-annually-' + defaultValue).prop('checked', true);
    amounts_annually();
    othervalue_hide();
  });
  $('#how-often-monthly').click(function() {
    var defaultValue = $(this).data('default-amount');
    $('#how-much-monthly-' + defaultValue).prop('checked', true);
    amounts_monthly();
    othervalue_hide();
  });
  $('#how-often-once').click(function() {
    var defaultValue = $(this).data('default-amount');
    $('#how-much-one-off-' + defaultValue).prop('checked', true);
    amounts_oneoff();
    othervalue_hide();
  });

  $('#gift-aid-yes').click(function(){
    fullname_toggle($('#gift-aid-yes').is(':checked'), true);
  });
  fullname_toggle($('#gift-aid-yes').is(':checked'));
  
  $('[id^=how-much-]').click(function(){
    othervalue_toggle($('#how-much-other').is(':checked'), true);
  });
  othervalue_toggle($('#how-much-other').is(':checked'));
  
  $('#donate_button').click(function(e) {
    e.preventDefault();
    var giftaid = $('input[name=gift-aid]:checked').val();
    var howoften = $('input[name=how-often]:checked').val();
    var amount = $('input[name=how-much]:checked').val();
    var contact_permission = $('input[name=contact_permission]:checked').val();
    var full_name = $('input[name=full_name]').val();
  
    if (amount == 'other') {
      amount = $('input[name=how-much-other]').val();
    }
    $('.donate-form__error').remove();
    if (!amount || !howoften) {
      $(this).parent().before(wrap_error('Please select an amount to donate.'));
      return;
    }
    if (!contact_permission) {
      $(this).parent().before(wrap_error('Please tell us if we can contact you about our work (or not!).'));
      return;
    }
    if (giftaid == 'Yes' && !full_name) {
      $(this).parent().before(wrap_error('Please enter your full name for gift aid.'));
      return;
    }

    var submitPaymentForm = function(){
        grecaptcha.execute();
    };

    trackEvent(
      "donate_form_submit", {"frequency": howoften, "value": amount }
    ).always(submitPaymentForm);
  });

  });
  
  function onDonateError(message) {
    var displayError = document.getElementsByClassName('donate-form__error-wrapper')[0];
    document.getElementById('spinner').style.display = 'none';
    displayError.innerHTML = '<p class="donate-form__error">' + message + '</p>';
  }
  
  function onDonatePass(token) {
    var data = $(document.donation_form).serialize();
    document.getElementById('spinner').style.display = 'inline-block';
    $.post('/support-us/?stripe=1', data, 'json').then(function(result) {
      if (result.error) {
        return onDonateError(result.error);
      }
      stripe.redirectToCheckout({
        sessionId: result.id
      }).then(function(result) {
        if (result.error) {
          onDonateError(result.error.message);
        }
      });
    });
  }
  