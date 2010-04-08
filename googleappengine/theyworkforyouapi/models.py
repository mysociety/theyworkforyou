from django.utils import simplejson
from google.appengine.ext import db
from google.appengine.api import memcache

class Person(db.Model):
    """A current MP. The key of this model is the TWFY person ID."""
    name = db.StringProperty(required=True)
    party = db.StringProperty(required=True)
    constituency = db.StringProperty(required=True)

class Postcode(db.Model):
    """A postcode and its current/future constituencies. The postcode, uppercase
    without spaces, is used as the key on this model."""
    current_constituency = db.StringProperty(required=True, indexed=False)
    future_constituency = db.StringProperty(required=True, indexed=False)

    def get_mp(self):
        key = "mp-%s" % self.current_constituency.replace(' ', '_')
        mp = None
        # Memcache throws spurious errors sometimes. Might as well treat that as no result.
        try:
            mp = memcache.get(key)
        except:
            pass
        if mp is None:
            mp = Person.all().filter('constituency', self.current_constituency).get()
            if mp is None: mp = {} # If no result, store that as empty dictionary.
            memcache.add(key, mp)
        return mp

