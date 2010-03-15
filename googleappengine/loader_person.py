from google.appengine.tools import bulkloader
import sys
sys.path.insert(0, '.')
from models import Person

class PersonLoader(bulkloader.Loader):
    """Loads a CSV of current MPs into GAE. The key is the person ID."""
    def __init__(self):
        bulkloader.Loader.__init__(self, 'Person', [
            ('id', int),
            ('name', lambda x: x.decode('iso-8859-1')),
            ('party', str),
            ('constituency', lambda x: x.decode('iso-8859-1')),
        ])

    def generate_key(self, i, values):
        id = values[0]
        return id

loaders = [ PersonLoader ]
