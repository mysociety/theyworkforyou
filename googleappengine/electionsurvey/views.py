#
# views.py:
# Views for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.api import urlfetch
from google.appengine.api.datastore_types import Key
from google.appengine.ext import db

from django.forms.formsets import formset_factory
from django.shortcuts import render_to_response, get_object_or_404
from django.http import HttpResponseRedirect

import forms

from models import Seat, RefinedIssue, Candidacy

def _save_form_array(issue_forms):
    for form in issue_forms:
        form.save()

# Front page of election site
def index(request):
    return render_to_response('index.html', {})

# Authenticate a candidate
def _check_auth(request):
    form = forms.AuthCandidacyForm(request.POST or None)

    if not request.POST:
        return render_to_response('survey_candidacy_auth.html', { 'form': form })

    token = request.POST['token']
    candidacy = Candidacy.find_by_token(token)
    
    if not candidacy:
        # XXX add error message
        return render_to_response('survey_candidacy_auth.html', { 'form': form, 'error': True })

    return candidacy

# Survey a candidate
def survey_candidacy(request):
    # Check they have the token
    response = _check_auth(request)
    if not isinstance(response, Candidacy):
        return response
    candidacy = response

    # Have they tried to post an answer?
    submitted = request.POST and 'questions_submitted' in request.POST

    # Construct array of forms containing all local issues
    issues_for_seat = candidacy.seat.refinedissue_set.fetch(1000)
    issue_forms = []
    valid = True
    for issue in issues_for_seat:
        form = forms.LocalIssueQuestionForm(submitted and request.POST or None, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        issue_forms.append(form)

    # Save the answers to all questions in a transaction 
    if submitted and valid:
        db.run_in_transaction(_save_form_array, issue_forms)
        return HttpResponseRedirect('/survey/thanks') # Redirect after POST

    return render_to_response('survey_candidacy_questions.html', {
        'issue_forms': issue_forms, 'token': candidacy.survey_token
    })

# Candidate has finished survey
def survey_candidacy_thanks(request):
    return render_to_response('survey_candidacy_thanks.html', {})



