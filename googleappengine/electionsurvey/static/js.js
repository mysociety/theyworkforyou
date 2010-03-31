$(function(){
    // Don't allow editing of more explanations by default
//    $('.more_explanation_textarea').attr("disabled", "disabled");
    // Allow editing of more explanations when radio button has been pressed
    $('.enable_more_explanation').change(set_whether_more_explanations_enabled);
    set_whether_more_explanations_enabled()
    // Autosave the form when any part of it changes
    $('.autosave_survey_form').change(autosave_survey_form);
});

//function enable_more_explanation() {
//    $(this).closest('.single_question').find('.more_explanation_textarea').attr("disabled", false)
//}

function set_whether_more_explanations_enabled() {
    $('.single_question').map(set_whether_more_explanation_enabled);
}

// Takes an element of class .single_question
function set_whether_more_explanation_enabled(sq) {
    var d = "disabled";
    var checked_count = $(this).find('.enable_more_explanation:checked').length;
    if (checked_count > 0) {
        d = false;
    }
    $(this).find('.more_explanation_textarea').attr("disabled", d);
}

function autosave_survey_form() {
    var token = $('#token').val();
    // store all the form data
    var ser = $('form').serialize();
    // submit it to the server
    $.ajax({ url: "/survey/autosave/" + token, context: document.body, type: 'POST', data: { 'ser': ser }, success: function(){
        $('#autosave').stop(true, true).show().fadeOut(2000);
    }});
}

