$(function(){
    // Autosave the form when any radio button changes
    $('.watchmechange').change(survey_form_changed);
});

function survey_form_changed() {
    var token = $('#token').val();
    // store all the form data
    var ser = $('form').serialize();
    // submit it to the server
    $.ajax({ url: "/survey/autosave/" + token, context: document.body, type: 'POST', data: { 'ser': ser }, success: function(){
        $('#autosave').stop(true, true).show().fadeOut(2000);
    }});
}

