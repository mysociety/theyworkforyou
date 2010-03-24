#
# models.py:
# Models for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

import random

# Candidate from YourMP

class Party(db.Model):
    id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    image_id = db.IntegerProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


class Candidate(db.Model):
    id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    email = db.StringProperty()
    party = db.ReferenceProperty(Party)
    image_id = db.IntegerProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


class Seat(db.Model):
    id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


digits = "0123456789abcdefghjkmnpqrstvwxyz"
class Candidacy(db.Model):
    id = db.IntegerProperty()

    seat = db.ReferenceProperty(Seat)
    candidate = db.ReferenceProperty(Candidate)

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()

    # used in URL of survey
    survey_token = db.StringProperty()

    def generate_survey_token(self):
        i = random.getrandbits(40) # 8 characters of 5 bits each
        enc = ''
        while i>=32:
            i, mod = divmod(i,32)
            enc = digits[mod] + enc
        enc = digits[i] + enc
        self.survey_token = enc
        self.save()


# Local issue data from DemocracyClub    

class RefinedIssue(db.Model):
    id = db.IntegerProperty()

    question = db.StringProperty()
    reference_url = db.StringProperty()

    seat = db.ReferenceProperty(Seat)

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


# Candidate survey response model
class SurveyResponse(db.Model):
    def __init__(self, *args, **kwargs):
        kwargs['key_name'] = "%s-%s" % (kwargs['candidacy'].name(), kwargs['refined_issue'].name())
        super(SurveyResponse, self).__init__(*args, **kwargs)

    candidacy = db.ReferenceProperty(Candidacy, required=True)
    refined_issue = db.ReferenceProperty(RefinedIssue, required=True)

    # 0 = strongly disagree, 100 = strongly agree
    agreement = db.RatingProperty(required=True)





