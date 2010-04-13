#
# forms.py:
# Forms for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

from django import forms
import django.contrib.localflavor.uk.forms

from models import SurveyResponse, Seat

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

# Enter postcode at start of survey
class QuizPostcodeForm(forms.Form):
    postcode = django.contrib.localflavor.uk.forms.UKPostcodeField(required=True,
            label = 'To begin, enter your postcode:')


