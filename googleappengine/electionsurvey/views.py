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

def index(request):
    return render_to_response('index.html', {})

def survey_candidacy(request):
    seat = Seat.get_by_key_name("613") 
    candidacy = Candidacy.get_by_key_name("1005")

    issues_for_seat = seat.refinedissue_set.fetch(1000)
    issue_forms = []
    valid = True
    for issue in issues_for_seat:
        form = forms.LocalIssueQuestionForm(request.POST or None, refined_issue=issue, candidacy=candidacy)
        valid = valid and form.is_valid()
        issue_forms.append(form)

    if request.POST and valid:
        # XXX save data
        for form in issue_forms:
            form.save()
        return HttpResponseRedirect('/survey_candidacy_thanks') # Redirect after POST

    return render_to_response('survey_candidacy.html', {
       'issue_forms': issue_forms,
    })

def survey_candidacy_thanks(request):
    return render_to_response('survey_candidacy_thanks.html', {})



