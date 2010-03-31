$(function(){
    // Enable/disable more explanation fields at start according to status of radio buttons
    set_whether_more_explanations_enabled()
    // Allow editing of more explanations when radio button has been pressed
    $('.enable_more_explanation').change(set_whether_more_explanations_enabled);
    // Autosave the form when any part of it changes
    $('.autosave_survey_form').change(autosave_survey_form);
});

// Go through every more explanation field, setting it
function set_whether_more_explanations_enabled() {
    $('.single_question').map(set_whether_more_explanation_enabled);
}

// Sets whether a particular more explanation field should be enabled or disabled
// sq - an element of class .single_question
function set_whether_more_explanation_enabled(sq) {
    var d = "disabled";
    var checked_count = $(this).find('.enable_more_explanation:checked').length;
    if (checked_count > 0) {
        d = false;
    }
    $(this).find('.more_explanation_textarea').attr("disabled", d);
}

// Store form data on server so can come back to it later
function autosave_survey_form() {
    var token = $('#token').val();
    // store all the form data
    var ser = $('form#electionsurvey').serialize();
    // submit it to the server
    $.ajax({ url: "/survey/autosave/" + token, context: document.body, type: 'POST', data: { 'ser': ser }, success: function(){
        $('#autosave').stop(true, true).show().fadeOut(2000);
    }});
}

