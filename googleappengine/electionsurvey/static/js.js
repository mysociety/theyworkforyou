$(function(){
    //$('#container').hide(750) testing
    
    if ($('form#electionsurvey').length) {
        var more_explanation_label_unfolded = $('ul.questions > li div.more_explanation label').html();
        var more_explanation_label_folded = "After you answer, optional space for a short explanation is available.";

        // Enable/disable more explanation fields at start according to status of radio buttons
        $('ul.questions textarea').attr('disabled', 'disabled').addClass('disabled').hide();
        $('ul.questions div.more_explanation label').html(more_explanation_label_folded);
        $('ul.questions > li').has('input:radio:checked').find('textarea').removeAttr('disabled').removeClass('disabled').show();
        $('ul.questions > li').has('input:radio:checked').find('div.more_explanation label').html(more_explanation_label_unfolded);
        // Allow editing of more explanations when radio button has been pressed
        $('input:radio').change(function(){
            $(this).closest('ul.questions > li').find('textarea').removeAttr('disabled').removeClass('disabled').show(600);
            $(this).closest('ul.questions > li').find('div.more_explanation label').html(more_explanation_label_unfolded);
        });

        // Autosave the form when any part of it changes
        $('input:radio').add('ul.questions textarea').change(autosave_survey_form);
        // Autosave if they close the window (in case in middle of typing in textarea)
        window.onbeforeunload = autosave_survey_form;
        // Autosave when the submit button is clicked (although this will do an onbeforeunload also?)
        $('form#electionsurvey').submit(autosave_survey_form);

        // Prevent too much text in the more explanation fields
        $('ul.questions').find('textarea').textLimiter(250, { limitColor: '#FF0000' });
    }
    if ($('ul.answers').length) {
        $('ul.answers > li div.statement').click(function(){
          
            var findout = $(this).find('span.findout');
            var p = $(this).parent();
            var full_answers = p.find('div.full_answers');
            if (full_answers.is(":hidden")) {
               findout.html("Hide");
            } else {
               findout.html("Responses &dArr;");
            }
            full_answers.toggle(600);
            p.toggleClass('opened');
            return false; // don't follow the fake link
        }).hover(
            function(){ $(this).addClass('hovering'); },
            function(){ $(this).removeClass('hovering'); }
        );

        $('ul.answers a.inner_hide').click(function(){
            $(this).closest('li.answer').find('div.statement').click();
            return false;
        });
    }
    if ($('form#postcode_form').length) {
        $('form#postcode_form #id_postcode').focus()
    }
});

// Store form data on server so can come back to it later
function autosave_survey_form() {
    var token = $('input#token').val();
    // see if the form is modified at all, i.e. we have
    // some fields in it other than token / questions_submitted
    var fields = $('form#electionsurvey').serializeArray();
    var filled_in = false;
    jQuery.each(fields, function(i, field){
        if (field.name != 'token' && field.name != 'questions_submitted') {
            filled_in = true;
        }
    });
    if (filled_in) {
        // store all the form data
        var ser = $('form#electionsurvey').serialize();
        // submit it to the server
        $.ajax({ url: "/survey/autosave/" + token, context: document.body, type: 'POST', data: { 'ser': ser }, success: function(){
            $('div#autosave').stop(true, true).show().fadeOut(2000);
        }});
    }
}

