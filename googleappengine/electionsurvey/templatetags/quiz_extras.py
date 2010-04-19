#
# quiz_extras.py:
# For templates for Election quiz.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import datetime

from django import template

register = template.Library()

# Example use
# <p>{% current_time "%Y-%M-%d %I:%M %p" %}</p>
@register.simple_tag
def current_time(format_string):
    return "moo" + datetime.datetime.now().strftime(format_string)

@register.tag
def lookup_answer_agreement(parser, token):
    try:
        tag_name, answers, candidacy, issue = token.split_contents()
    except ValueError:
        raise template.TemplateSyntaxError, "%r tag requires exactly three arguments" % token.contents.split()[0]
    return FormatLookupAnswer(answers, candidacy, issue, "agreement")

@register.tag
def lookup_answer_more_explanation(parser, token):
    try:
        tag_name, answers, candidacy, issue = token.split_contents()
    except ValueError:
        raise template.TemplateSyntaxError, "%r tag requires exactly three arguments" % token.contents.split()[0]
    return FormatLookupAnswer(answers, candidacy, issue, "more_explanation")


class FormatLookupAnswer(template.Node):
    def __init__(self, answers, candidacy, issue, field):
        self.answers = template.Variable(answers)
        self.candidacy = template.Variable(candidacy)
        self.issue = template.Variable(issue)
        self.field = field

    def render(self, context):
        try:
            answers = self.answers.resolve(context)
            candidacy = self.candidacy.resolve(context)
            issue = self.issue.resolve(context)

            answers_for_candidacy = answers.responses[candidacy.key().name()]
            if issue.key().name() in answers_for_candidacy:
                refined_issue = answers_for_candidacy[issue.key().name()]
                if self.field == "agreement":
                    return str(refined_issue.agreement)
                elif self.field == "more_explanation":
                    return str(refined_issue.more_explanation)
                else:
                    raise Exception("Unknown field in FormatLookupAnswer")
            else: 
                return ''
        except template.VariableDoesNotExist:
            return ''

# This just doesn't work, and errors in it don't report where they
# were thrown from. I give up.
#@register.simple_tag
#def lookup_answer_agreement(answers, candidacy, issue):
#    answers_for_candidacy = answers.responses[candidacy.key().name()]
#    #return str(issue.key().name()) + " bunny " + str(answers_for_candidacy)
#    refined_issue = answers_for_candidacy[issue.key().name()]
#    return str(refined_issue.agreement)
#    raise Exception("moo" + str(refined_issue.agreement))
#    return "hello"

#@register.simple_tag
#def lookup_answer_more_explanation(answers, candidacy, issue):
#    answer = answers.responses[candidacy][issue]
#    return answer.more_explanation



