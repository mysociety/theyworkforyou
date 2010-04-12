#
# models.py:
# Models for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

import django.forms

# XXX should this be using db.Model ?
# import appengine_django.models
# Causes error in appengine_django/models.py on this line:
#       self.app_label = model_module.__name__.split('.')[-2]
# Due to us not having app name in the model module name. Which we don't have
# because GAE didn't seem to like it.

import random
import re
import datetime

# Candidate from YourMP

class Party(db.Model):
    ynmp_id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    image_id = db.IntegerProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


class Candidate(db.Model):
    ynmp_id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    email = db.StringProperty()
    party = db.ReferenceProperty(Party)
    image_id = db.IntegerProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    # Validators
    def validated_email(self):
        email = self.email
        if email == None:
            return None
        email = email.strip()
        try:
            django.forms.EmailField().clean(email)
            return email
        except django.forms.ValidationError:
            return None
 
class Seat(db.Model):
    ynmp_id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    frozen_local_issues = db.BooleanProperty(default = False)


digits = "0123456789abcdefghjkmnpqrstvwxyz"
class Candidacy(db.Model):
    ynmp_id = db.IntegerProperty()

    seat = db.ReferenceProperty(Seat)
    candidate = db.ReferenceProperty(Candidate)

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    deleted = db.BooleanProperty(default = False)

    survey_token = db.StringProperty()
    survey_token_use_count = db.IntegerProperty()
    survey_invite_emailed = db.BooleanProperty(default = False)
    survey_invite_sent_to_emails = db.StringListProperty() # historic list of addresses we've emailed
    survey_filled_in = db.BooleanProperty(default = False)
    survey_filled_in_when = db.DateTimeProperty()
    survey_autosave = db.TextProperty()
    survey_autosave_when = db.DateTimeProperty()

    audit_log = db.StringListProperty()

    # Tokens are used in URL of survey / for user to enter from paper letter
    def generate_survey_token(self):
        i = random.getrandbits(40) # 8 characters of 5 bits each
        enc = ''
        while i>=32:
            i, mod = divmod(i,32)
            enc = digits[mod] + enc
        enc = digits[i] + enc
        self.survey_token = enc
        self.log('Generated survey token %s' % self.survey_token)
        self.save()

    @staticmethod
    def find_by_token(token): 
        # don't be too picky about what other characters they type in
        token = token.lower()
        token = re.sub("[^a-z0-9]", "", token)

        # find the candidacy
        founds = db.Query(Candidacy).filter('survey_token =', token).fetch(1000)
        if len(founds) == 0:
            return False
        assert len(founds) == 1
        return founds[0]

    def national_responses(self):
        return filter(lambda x: x.refined_issue.seat.name == 'National', self.surveyresponse_set)

    def local_responses(self):
        return filter(lambda x: x.refined_issue.seat.name != 'National', self.surveyresponse_set)
    # Audit log of what has happened to candidate.
    # Note: The log function does a save too
    def log(self, message):
        self.audit_log.append(datetime.datetime.now().isoformat() + " " + message)
        self.save()

           

# Local issue data from DemocracyClub    

class RefinedIssue(db.Model):
    democlub_id = db.IntegerProperty()

    question = db.StringProperty()
    reference_url = db.StringProperty()

    seat = db.ReferenceProperty(Seat)

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    deleted = db.BooleanProperty(default = False)

# Candidate survey, response to one question
class SurveyResponse(db.Model):
    def __init__(self, *args, **kwargs):
        kwargs['key_name'] = "%s-%s" % (kwargs['candidacy'].name(), kwargs['refined_issue'].name())
        super(SurveyResponse, self).__init__(*args, **kwargs)

    candidacy = db.ReferenceProperty(Candidacy, required=True)
    refined_issue = db.ReferenceProperty(RefinedIssue, required=True)
    national = db.BooleanProperty()

    # 100 = strongly agree, 0 = strongly disagree
    agreement = db.RatingProperty(required=True)

    more_explanation = db.StringProperty() # maximum 500 characters, indexed

    def deleted(self):
        return self.refined_issue.deleted





