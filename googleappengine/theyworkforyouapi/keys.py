from google.appengine.api import memcache
from google.appengine.ext import db
from google.appengine.runtime.apiproxy_errors import CapabilityDisabledError
import random

class APIKey(db.Model):
    """API keys for use of this service."""
    name = db.StringProperty(required=True)
    num_shards = db.IntegerProperty(required=True, default=20)

    def increment(self):
        """Increment the count for this key."""
        def txn():
            index = random.randint(0, self.num_shards - 1)
            shard_name = self.name + '-' + str(index)
            counter = CounterShard.get_by_key_name(shard_name)
            if counter is None:
                counter = CounterShard(key_name=shard_name, name=self.name)
            counter.count += 1
            counter.put()
        try:
            db.run_in_transaction(txn)
        except CapabilityDisabledError:
            pass
        memcache.incr(self.name)

    def get_count(self):
        """Retrieve the count for this key."""
        total = memcache.get(self.name)
        if total is None:
            total = 0
            for counter in CounterShard.all().filter('name = ', self.name):
                total += counter.count
            memcache.add(self.name, str(total), 60)
        return total

    def increase_shards(self, num):
        """Increase the number of shards for this key.
        Will never decrease the number of shards.

        Parameters:
          num - How many shards to use
        """
        def txn():
            if self.num_shards < num:
                self.num_shards = num
                self.put()
        db.run_in_transaction(txn)

class CounterShard(db.Model):
    """Shards for each named counter"""
    name = db.StringProperty(required=True)
    count = db.IntegerProperty(required=True, default=0)

