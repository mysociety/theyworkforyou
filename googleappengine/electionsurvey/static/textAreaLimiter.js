// http://plugins.jquery.com/project/textAreaLimiter
 (function($) {
     $.fn.textLimiter = function(limit, settings) {
         var config = { truncate: true, limitColor: '#FFCC11' };
         if (settings) $.extend(config, settings);

         function limiter(inputE) {
             if ((inputE.val().length > limit) && config.truncate == true) {
                 inputE.val(inputE.val().substr(0, limit));
             }
             if ((inputE.val().length >= limit)) {
                 inputE.css('border', '1px solid ' + config.limitColor + '');
             }
             else {
                 inputE.css('border', inputE.data('originalBorder'));
             }
             inputE.next('div').html(+inputE.val().length + '/' + limit);
         }

         this.each(function() {
             var textArea = $(this);
             textArea.data('originalBorder', textArea.css('border'));
             textArea.after('<div class="textLimiter">' + textArea.val().length + '/' + limit + '.</div>');
             //On keyup check
             textArea.keyup(function() {
                 limiter(textArea);
             });
             //On paste check
             textArea.bind('paste', function(e) {
                 setTimeout(function() { limiter(textArea) }, 75);
             });
         });
         return this;
     };
 })(jQuery);



