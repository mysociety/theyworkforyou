#
# forms.py:
# Forms for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from django import forms

from models import SurveyResponse

class LocalIssueQuestionForm(forms.Form):
    def __init__(self, *args, **kwargs):
        self.refined_issue = kwargs.pop('refined_issue')
        self.candidacy = kwargs.pop('candidacy')
        kwargs['prefix'] = 'issue-%d' % self.refined_issue.id
        super(LocalIssueQuestionForm, self).__init__(*args, **kwargs)
        self.fields['agreement'].label = self.refined_issue.question
    
    def save(self):
        survey_response = SurveyResponse(
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

