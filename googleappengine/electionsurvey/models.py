#
# models.py:
# Models for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

import django.forms
from django.conf import settings

# XXX should this be using db.Model ?
# import appengine_django.models
# Causes error in appengine_django/models.py on this line:
#       self.app_label = model_module.__name__.split('.')[-2]
# Due to us not having app name in the model module name. Which we don't have
# because GAE didn't seem to like it.

import random
import re
import datetime

import hashtags

# Candidate from YourMP

# size is large/medium/small
def _image_id_to_url(image_id, size):
    padded_id = "%09i" % image_id
    return "http://yournextmp.s3.amazonaws.com/images/%s/%s/%s-%s.png" % (padded_id[-4:-2], padded_id[-2:],padded_id, size)

class Party(db.Model):
    ynmp_id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    image_id = db.IntegerProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    # Image URL
    def image_url(self):
        if self.image_id:
            return _image_id_to_url(self.image_id, "small")
        else:
            return "/static/independent.png" # uses logo from http://www.independentnetwork.org.uk/ for now


class Candidate(db.Model):
    ynmp_id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()
    status = db.StringProperty() # raw from YourNextMP dump, either 'standing' or 'standing_down'

    email = db.StringProperty()
    address = db.TextProperty()
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

    # Image URL
    def image_url(self):
        if self.image_id:
            return _image_id_to_url(self.image_id, "small")
        else:
            return None

    def yournextmp_url(self):
        return "http://www.yournextmp.com/candidates/%s" % self.code

 
class Seat(db.Model):
    ynmp_id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    frozen_local_issues = db.BooleanProperty(default = False)

    democracyclub_slug = db.StringProperty()

    def calc_slug(self): 
        slug = self.democracyclub_slug
        if not slug:
            slug = self.code
            slug = slug.replace("_", "-")

            if slug == 'newry-and-armagh':
                slug = 'newry-armagh'
        return slug

    def democracyclub_url(self): 
        slug = self.calc_slug()
        return "http://www.democracyclub.org.uk/constituencies/%s/" % slug

    def hashtag(self):
        slug = self.calc_slug()
        if slug in hashtags.constituency_hashtags:
            return " #" + hashtags.constituency_hashtags[slug]
        else:
            return ""

    def encoded_hashtag(self):
        slug = self.calc_slug()
        if slug in hashtags.constituency_hashtags:
            return "%20%23" + hashtags.constituency_hashtags[slug]
        else:
            return ""

    def get_absolute_url(self):
        return settings.EMAIL_URL_PREFIX + "/quiz/seats/" + self.code

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
    survey_invite_posted = db.BooleanProperty(default = False)
    survey_invite_sent_to_addresses = db.StringListProperty() # historic list of addresses we've emailed
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
        return self.surveyresponse_set.filter('national =', True)

    def local_responses(self):
        return self.surveyresponse_set.filter('national =', False)

    # Audit log of what has happened to candidate.
    # Note: The log function does a save too
    def log(self, message):
        self.audit_log.append(datetime.datetime.now().isoformat() + " " + message)
        self.save()

    def __unicode__(self):
        return "%s standing in %s for %s" % (self.candidate.name,
                                             self.seat.name,
                                             self.candidate.party.name)
           
# Local issue data from DemocracyClub. Also used to store national issues with
# a magic constituency called "National". See bin/national-issues.csv.
class RefinedIssue(db.Model):
    democlub_id = db.IntegerProperty()

    question = db.StringProperty()
    reference_url = db.StringProperty()
    short_name = db.StringProperty()
    national = db.BooleanProperty()

    seat = db.ReferenceProperty(Seat)

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    deleted = db.BooleanProperty(default = False)

# Lookups for describing agreement
agreement_verb = {
    0: "strongly disagrees",
    25: "disagrees",
    50: "is neutral",
    75: "agrees",
    100: "strongly agrees"
}
agreement_verb_you = {
    0: "strongly disagree",
    25: "disagree",
    50: "are neutral",
    75: "agree",
    100: "strongly agree"
}

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

    more_explanation = db.StringProperty(multiline=True) # maximum 500 characters, indexed

    def deleted(self):
        return self.refined_issue.deleted or self.candidacy.deleted

    def verb(self):
        return agreement_verb[self.agreement]
    def verb_you(self):
        return agreement_verb_you[self.agreement]

# Used to find average agreement to a question by all members of a particular party
class AverageResponseByParty(db.Model):
    party = db.ReferenceProperty(Party, required=True)
    refined_issue = db.ReferenceProperty(RefinedIssue, required=True)

    average_agreement = db.FloatProperty()

    processing_running_total = db.IntegerProperty()
    processing_running_count = db.IntegerProperty()
    processing_last_candidacy = db.ReferenceProperty()

# populated with data built up from hits (caching)
# confirmed == false until a human has confirmed that this is a true match
# later we might want to add *reject* to force this match to never be made?
class AristotleToYnmpCandidateMap(db.Model):
    aristotle_id = db.IntegerProperty()
    ynmp_id = db.IntegerProperty()
    confirmed = db.BooleanProperty(default = False)
       
# Sign up to post election email alerts and services
class PostElectionSignup(db.Model):
    name = db.StringProperty()
    email = db.StringProperty()
    postcode = db.StringProperty()

    theyworkforyou = db.BooleanProperty()
    hearfromyourmp = db.BooleanProperty()


