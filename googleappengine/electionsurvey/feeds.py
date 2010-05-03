from google.appengine.ext import db

from django.contrib.syndication.feeds import Feed

from models import Candidacy

class LatestAnswers(Feed):
    title = "Candidates who've answers our survey"
    link = "/quiz/"
    description = "Candidates who've answers our survey"

    def items(self):
        f = 4
        return db.Query(Candidacy).filter('survey_filled_in =', True).order('-survey_filled_in_when').fetch(200)
        
    def item_link(self, item):
        return item.seat.get_absolute_url()

    def item_pubdate(self, item):
        return item.survey_filled_in_when
