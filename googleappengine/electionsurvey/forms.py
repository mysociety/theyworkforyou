#
# forms.py:
# Forms for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from django import forms

class LocalIssueQuestionForm(forms.Form):
    def __init__(self, *args, **kwargs):
        issue = kwargs.pop('issue')
        kwargs['prefix'] = 'issue-%d' % issue.id
        super(LocalIssueQuestionForm, self).__init__(*args, **kwargs)
        self.fields['agreement'].label = issue.question

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

