from google.appengine.tools import bulkloader
import sys
sys.path.insert(0, '.')
from keys import APIKey

class APIKeyLoader(bulkloader.Loader):
    """Loads a CSV of API keys into GAE. The key is the, umm, key."""
    def __init__(self):
        bulkloader.Loader.__init__(self, 'APIKey', [
            ('name', str),
        ])

    def generate_key(self, i, values):
        return values[0]

loaders = [ APIKeyLoader ]
