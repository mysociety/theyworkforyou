import datetime
import sys

from google.appengine.ext import db
from google.appengine.tools import bulkloader

sys.path.insert(0, '.')
sys.path.insert(0, '..')
from models import Candidate, Party

def int_or_null(x):
    if (x == "null" or x == "None" or x == ""):
        return None
    return int(x)

class CandidateLoader(bulkloader.Loader):
    """Loads a CSV of parties into GAE."""
    def __init__(self):
        bulkloader.Loader.__init__(self, 'Candidate',
                                   [
                                    ('id', int),
                                    ('name', lambda x: x.decode('utf-8')),
                                    ('code', str),
                                    ('email', str),
                                    ('party_id', lambda x: Party.get_by_id(int(x))),
                                    ('image_id', int_or_null),
                                    ('created', lambda x: datetime.datetime.strptime(x, "%Y-%m-%dT%H:%M:%S")),
                                    ('updated', lambda x: datetime.datetime.strptime(x, "%Y-%m-%dT%H:%M:%S")),
                                   ])
    def generate_key(self, i, values):
        id = values[0]
        return id

loaders = [CandidateLoader]


