from google.appengine.ext import db
from google.appengine.api import memcache

from django.contrib.syndication.feeds import Feed

from models import Candidacy


class LatestAnswers(Feed):
    title = "Candidates who've answers our survey"
    link = "/quiz/"
    description = "Candidates who've answers our survey"

    def items(self):
        key = "rss"
        data = memcache.get(key)
        if data is None:
            data = db.Query(Candidacy)\
                   .filter('survey_filled_in =', True)\
                   .order('-survey_filled_in_when').fetch(200)
            memcache.add(key, data, 60 * 10) # 10 minute cache
        return data
        
    def item_link(self, item):
        return item.seat.get_absolute_url() + "#%s" % item.candidate.ynmp_id

    def item_pubdate(self, item):
        return item.survey_filled_in_when
