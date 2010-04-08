from google.appengine.tools import bulkloader
import sys
sys.path.insert(0, '.')
from models import Postcode

class PostcodeLoader(bulkloader.Loader):
    """Loads a CSV of postcodes and current/future constituencies into GAE. The key is the postcode."""
    def __init__(self):
        bulkloader.Loader.__init__(self, 'Postcode', [
            ('postcode', str),
            ('current_constituency', lambda x: x.decode('iso-8859-1')),
            ('future_constituency', lambda x: x.decode('iso-8859-1')),
        ])

    def generate_key(self, i, values):
        postcode = values[0]
        return postcode.replace(' ', '').upper()

    #def handle_entity(self, entity):
    #    return entity

loaders = [ PostcodeLoader ]
