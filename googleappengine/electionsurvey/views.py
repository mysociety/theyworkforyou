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
from django.shortcuts import render_to_response, get_object_or_404#

import forms

from models import Seat, RefinedIssue

def index(request):
    return render_to_response('index.html', {})

def survey_candidacy(request):
    seat = Seat.get_by_key_name("613") 
    issues_for_seat = seat.refinedissue_set.fetch(1000)
    issue_forms = []
    valid = True
    for issue in issues_for_seat:
        form = forms.LocalIssueQuestionForm(request.POST or None, issue=issue)
        valid = valid and form.is_valid()
        issue_forms.append(form)

    if request.POST and valid:
        # XXX save data
        return HttpResponseRedirect('/thanks/') # Redirect after POST

    return render_to_response('survey_candidacy.html', {
       'issue_forms': issue_forms,
    })


