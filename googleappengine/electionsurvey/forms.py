#
# forms.py:
# Forms for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

from django import forms

from models import SurveyResponse

# Authenticate candidate
class AuthCandidacyForm(forms.Form):
    token = forms.CharField() 

# One question in the candidate survey
class LocalIssueQuestionForm(forms.Form):
    # Constructor needs to know which candidacy and issue this is for
    def __init__(self, *args, **kwargs):
        self.refined_issue = kwargs.pop('refined_issue')
        self.candidacy = kwargs.pop('candidacy')

        kwargs['prefix'] = 'issue-%d' % self.refined_issue.id
        super(LocalIssueQuestionForm, self).__init__(*args, **kwargs)
        self.fields['agreement'].label = self.refined_issue.question
    
    # Store the candidate's response in the database
    def save(self):
        survey_response = SurveyResponse(
            parent = self.candidacy,
            agreement = int(self.cleaned_data['agreement']),
            refined_issue = self.refined_issue.key(),
            candidacy = self.candidacy.key()
        )
        survey_response.put()

    # 0 = strongly disagree, 100 = strongly agree
    agreement = forms.ChoiceField(
        widget=forms.widgets.RadioSelect(),
        required=True,
        choices=[
            (100, 'Strongly agree'),
            (75, 'Agree'),
            (25, 'Disagree'),
            (0, 'Strongly disagree'),
        ]
    )

def _form_array_save(issue_forms):
    for form in issue_forms:
        form.save()

def _form_array_amount_done(issue_forms):
    c = 0
    for form in issue_forms:
        if not form.errors:
            c += 1
    return c



