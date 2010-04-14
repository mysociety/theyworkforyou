#
# forms.py:
# Forms for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import urllib2
import re

from google.appengine.ext import db

from django import forms
from django.conf import settings
import django.contrib.localflavor.uk.forms

import django.utils.simplejson as json

from models import SurveyResponse, Seat, RefinedIssue

# Authenticate candidate
class AuthCandidacyForm(forms.Form):
    token = forms.CharField() 

# One question in the candidate survey
class IssueQuestionForm(forms.Form):
    # Constructor needs to know which candidacy and issue this is for
    def __init__(self, *args, **kwargs):
        self.refined_issue = kwargs.pop('refined_issue')
        self.national = (self.refined_issue.seat.name == 'National')
        self.candidacy = kwargs.pop('candidacy')

        kwargs['prefix'] = 'issue-%s' % self.refined_issue.key().name()
        super(IssueQuestionForm, self).__init__(*args, **kwargs)
        self.fields['agreement'].label = self.refined_issue.question
    
    # Store the candidate's response in the database
    def save(self):
        survey_response = SurveyResponse(
            parent = self.candidacy,
            agreement = int(self.cleaned_data['agreement']),
            refined_issue = self.refined_issue.key(),
            more_explanation = self.cleaned_data['more_explanation'],
            candidacy = self.candidacy.key(),
            national = self.national
        )
        survey_response.put()

    # 0 = strongly disagree, 100 = strongly agree
    agreement = forms.ChoiceField(
        widget=forms.widgets.RadioSelect(),
        required=True,
        choices=[
            (100, 'Agree (strongly)'),
            (75, 'Agree'),
            (50, 'Neutral'),
            (25, 'Disagree'),
            (0, 'Disagree (strongly)'),
        ]
    )

    more_explanation = forms.CharField(required=False,
            widget=forms.Textarea(attrs={'cols':60, 'rows':2}),
                label="Optional space for short explanation (not required, 250 characters max):"
    )

# One local question in the candidate survey
class LocalIssueQuestionForm(IssueQuestionForm):
    pass

# One national question in the candidate survey
class NationalIssueQuestionForm(IssueQuestionForm):
    pass

# Helpers for handling multiple questions 
def _form_array_save(issue_forms):
    for form in issue_forms:
        form.save()

def _form_array_amount_done(issue_forms):
    c = 0
    for form in issue_forms:
        if not form.errors:
            c += 1
    return c

# For display, includes space
def _canonicalise_postcode(postcode):
    postcode = re.sub('[^A-Z0-9]', '', postcode.upper())
    postcode = re.sub('(\d[A-Z]{2})$', r' \1', postcode)
    return postcode

# For use in URLs, excludes space
def _urlise_postcode(postcode):
    postcode = _canonicalise_postcode(postcode)
    postcode = postcode.replace(' ', '')
    return postcode

# Look up postcode, returning (post-election) constituency
def _postcode_to_constituency(postcode):
    postcode = _urlise_postcode(postcode)
    if postcode == 'WW99WW':
        seat_name = 'Felpersham Outer'
    else:
        url = "http://theyworkforyouapi.appspot.com/lookup?key=%s&format=js&pc=%s" % (settings.THEYWORKFORYOU_API_KEY, postcode)
        result = urllib2.urlopen(url).read()
        json_result = json.loads(result)

        if 'error' in json_result:
            raise django.forms.util.ValidationError(json_result['error'])
        seat_name = json_result['future_constituency']

    return db.Query(Seat).filter('name =', seat_name).get()

class MyUKPostcodeField(django.contrib.localflavor.uk.forms.UKPostcodeField):
    default_error_messages = { 'invalid': 'Please enter a valid postcode.' }

# Enter postcode at start of survey
class QuizPostcodeForm(forms.Form):
    # Look up constituency for postcode
    def clean_postcode(self):
        postcode = self.cleaned_data['postcode']
        seat = _postcode_to_constituency(postcode)
        self.cleaned_data['seat'] = seat
        return postcode

    postcode = MyUKPostcodeField(required=True, label = 'To begin, enter your postcode:')

# Choosing which national issues you want to cover:
class QuizNationalIssueSelectForm(forms.Form):
    def __init__(self, *args, **kwargs):
        self.national_issues = db.Query(RefinedIssue).filter('national =', True).fetch(1000)

        super(QuizNationalIssueSelectForm, self).__init__(*args, **kwargs)

        for national_issue in self.national_issues:
            national_issue_id = 'national_issue_' + str(national_issue.democlub_id)
            self.fields[national_issue_id] = forms.BooleanField(required=False, 
                label=national_issue.short_name
            )




