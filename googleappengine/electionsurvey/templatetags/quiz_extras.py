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

@register.simple_tag
def lookup_answer_agreement(answers, candidacy, issue):
    answers_for_candidacy = answers.responses[candidacy.key().name()]
    if issue.key().name() not in answers_for_candidacy:
        return ""
    refined_issue = answers_for_candidacy[issue.key().name()]
    return str(refined_issue.agreement)

@register.simple_tag
def lookup_answer_more_explanation(answers, candidacy, issue):
    answers_for_candidacy = answers.responses[candidacy.key().name()]
    if issue.key().name() not in answers_for_candidacy:
        return ""
    refined_issue = answers_for_candidacy[issue.key().name()]
    return re.sub("\s+", " ", str(refined_issue.more_explanation).strip())




