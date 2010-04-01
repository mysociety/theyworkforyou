$(function(){
    // Enable/disable more explanation fields at start according to status of radio buttons
    $('ul.questions textarea').attr('disabled', 'disabled').addClass('disabled');
    $('ul.questions > li').has('input:radio:checked').find('textarea').removeAttr('disabled').removeClass('disabled');

    // Allow editing of more explanations when radio button has been pressed
    $('input:radio').change(function(){
        $(this).closest('ul.questions > li').find('textarea').removeAttr('disabled').removeClass('disabled');
    });

    // Autosave the form when any part of it changes
    $('input:radio').add('ul.questions textarea').change(autosave_survey_form);
    // Autosave if they close the window (in case in middle of typing in textarea)
    window.onbeforeunload = autosave_survey_form;
    // Autosave when the submit button is clicked (although this will do an onbeforeunload also?)
    $('form#electionsurvey').submit(autosave_survey_form);

    // Prevent too much text in the more explanation fields
    $('ul.questions').find('textarea').textLimiter(250, { limitColor: '#FF0000' });
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

