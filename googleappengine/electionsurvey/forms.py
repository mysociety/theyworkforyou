#
# forms.py:
# Forms for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

from django import forms

from models import SurveyResponse, Seat

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
            (100, 'Agree (strongly)'),
            (75, 'Agree'),
            (25, 'Disagree'),
            (0, 'Disagree (strongly)'),
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

# Admin form for sending survey by email to candidates
class EmailSurveyToCandidacies(forms.Form):
    seats = list(db.Query(Seat))
    seats.sort(cmp=lambda x, y: cmp(x.name, y.name))
    constituency_choices = [("all", "Any constituencies")] + [ (s.id, s.name) for s in seats]
    constituency = forms.ChoiceField(
            required=True, choices=constituency_choices, label="Constituency:", help_text="(Only send to candidates standing in this constituency)"
    )

    already_emailed_choices = [
            ("false", "Only candidates not yet emailed"),
            ("true", "Only candidates who have already been emailed"),
            ("either", "Whether or not they've already been emailed")
    ]
    already_emailed = forms.ChoiceField(
            required=True, choices=already_emailed_choices, label="Already emailed:", help_text=""
    )


