import datetime
import sys

from google.appengine.ext import db
from google.appengine.tools import bulkloader

sys.path.insert(0, '.')
sys.path.insert(0, '..')
from models import Seat

def int_or_null(x):
    if (x == "null" or x == "None" or x == ""):
        return None
    return int(x)

class SeatLoader(bulkloader.Loader):
    """Loads a CSV of parties into GAE."""
    def __init__(self):
        bulkloader.Loader.__init__(self, 'Seat',
                                   [
                                    ('id', int),
                                    ('name', lambda x: x.decode('utf-8')),
                                    ('code', str),
                                    ('created', lambda x: datetime.datetime.strptime(x, "%Y-%m-%dT%H:%M:%S")),
                                    ('updated', lambda x: datetime.datetime.strptime(x, "%Y-%m-%dT%H:%M:%S")),
                                   ])
    def generate_key(self, i, values):
        id = values[0]
        return id

loaders = [SeatLoader]


