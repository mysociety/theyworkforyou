#
# quiz_extras.py:
# For templates for Election quiz.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import datetime
import re

from django import template

register = template.Library()

agreement_verb = {
    0: "strongly disagrees",
    25: "disagrees",
    50: "is neutral",
    75: "agrees",
    100: "strongly agrees"
}

@register.simple_tag
def lookup_answer_agreement(answers, candidacy, issue):
    answers_for_candidacy = answers.responses[candidacy.key().name()]
    if issue.key().name() not in answers_for_candidacy:
        return ""
    refined_issue = answers_for_candidacy[issue.key().name()]
    assert refined_issue.agreement in [0, 25, 50, 75, 100]
    return agreement_verb[refined_issue.agreement]

@register.simple_tag
def lookup_answer_more_explanation(answers, candidacy, issue):
    answers_for_candidacy = answers.responses[candidacy.key().name()]
    if issue.key().name() not in answers_for_candidacy:
        return ""
    refined_issue = answers_for_candidacy[issue.key().name()]
    return re.sub("\s+", " ", str(refined_issue.more_explanation).strip())




