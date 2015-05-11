var researchUser;
var startTime = new Date();
var recordEndpoint = 'http://logbook.mysociety.org/quant2';
var siteTag = 'twfy';

$(document).ready(function() {

  // Retrieve the bucket from storage, or allocate the user if not.
  if ($.localStorage.isEmpty('research.qual2')) {
    researchUser = {
      'bucket': Math.floor(Math.random() * 3) + 1,
    };
    $.localStorage.set('research.qual2', researchUser);
  } else {
    researchUser = $.localStorage.get('research.qual2');
  }

  // Record this page view and bucket
  $.ajax({
    url: recordEndpoint,
    data: {
      'site': siteTag,
      'method': 'view',
      'page': document.URL,
      'bucket': researchUser.bucket
    },
    dataType: 'jsonp'
  });

  // Choose which action to take
  if (researchUser.bucket == 1) {

    // Bind the "where next" click event
    $('#research-quant2-bucket1-wherenext').click(function(e){

      e.preventDefault();

      var link = $(this);

      var endTime = new Date();
      var timer = endTime - startTime;
      timer /= 1000;

      $.ajax({
        url: recordEndpoint,
        data: {
          'site': siteTag,
          'method': 'click_popup_link',
          'page': document.URL,
          'bucket': researchUser.bucket,
          'data': 'where-next',
          'timer': timer
        },
        dataType: 'jsonp',
        timeout: 300
      }).always(function() {
        window.location.assign(link.attr('href'));
      });

    });

    // Bind the "where next" click event
    $('[data-research-quant2-bucket1-linkname]').click(function(e){

      e.preventDefault();

      var link = $(this);

      var endTime = new Date();
      var timer = endTime - startTime;
      timer /= 1000;

      $.ajax({
        url: recordEndpoint,
        data: {
          'site': siteTag,
          'method': 'click_popup_link',
          'page': document.URL,
          'bucket': researchUser.bucket,
          'data': link.data('research-quant2-bucket1-linkname'),
          'timer': timer
        },
        dataType: 'jsonp'
      });

      $('#whereNextModal').foundation('reveal', 'close');

    });

    // On the page for 10 seconds, do the popup
    setTimeout(function() {

      // Are popups suppressed (ie have we already shown one?)
      if ($.localStorage.isEmpty('research.qual2.suppress_popup')) {

        // Set the suppress popup flag.
        $.localStorage.set('research.qual2.suppress_popup', true);

        $('#whereNextModal').foundation('reveal', 'open');
        $.ajax({
          url: recordEndpoint,
          data: {
            'site': siteTag,
            'method': 'show_popup',
            'page': document.URL,
            'bucket': researchUser.bucket,
            'data': 'timed'
          },
          dataType: 'jsonp'
        });

      } else {

        $.ajax({
          url: recordEndpoint,
          data: {
            'site': siteTag,
            'method': 'suppressed_popup',
            'page': document.URL,
            'bucket': researchUser.bucket,
            'data': 'timed'
          },
          dataType: 'jsonp'
        });

      }

    }, 10000);

  } else if (researchUser.bucket == 2) {

    $('#research-quant2-bucket2').show();

    // Bind the click event
    $('#research-quant2-bucket2 a').click(function(e){

      e.preventDefault();

      var link = $(this);

      var endTime = new Date();
      var timer = endTime - startTime;
      timer /= 1000;

      $.ajax({
        url: recordEndpoint,
        data: {
          'site': siteTag,
          'method': 'click_nav_link',
          'page': document.URL,
          'bucket': researchUser.bucket,
          'timer': timer
        },
        dataType: 'jsonp',
        timeout: 300
      }).always(function() {
        window.location.assign(link.attr('href'));
      });

    });

  }

  // Bucket 3 doesn't do anything except record a view, which we've already done.

});
