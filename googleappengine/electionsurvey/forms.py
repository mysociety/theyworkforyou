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
            (0, 'Strongly disagree'),
            (25, 'Disagree'),
            (75, 'Agree'),
            (100, 'Strongly agree'),
        ]
    )

