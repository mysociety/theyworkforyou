#
# models.py:
# Models for TheyWorkForYou election quiz Django project
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

from google.appengine.ext import db

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
    party_id = db.ReferenceProperty(Party)
    image_id = db.IntegerProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


class Seat(db.Model):
    id = db.IntegerProperty()
    name = db.StringProperty()
    code = db.StringProperty()

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()


class Candidacy(db.Model):
    id = db.IntegerProperty()

    seat_id = db.ReferenceProperty(Seat)
    candidate_id = db.ReferenceProperty(Candidate)

    created = db.DateTimeProperty()
    updated = db.DateTimeProperty()
    

