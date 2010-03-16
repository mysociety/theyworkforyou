import cgi, re
from django.utils import simplejson
from google.appengine.ext import webapp
from google.appengine.ext.webapp.util import run_wsgi_app
from models import Postcode
from keys import APIKey

def check_params(self):
    """Checks we have all the required stuff in the query, and does the postcode lookup."""

    key = self.request.get('key')
    if not key:
        api_error(self, 'API key must be provided.')
        return None

    format = self.request.get('format')
    if format not in ('js', 'xml'):
        api_error(self, 'Format parameter must be js or xml.')
        return None

    postcode = self.request.get('pc').replace(' ', '').upper()
    if not postcode:
        api_error(self, 'Postcode must be provided.')
        return None

    key = APIKey.get_by_key_name(key)
    if not key:
        api_error(self, "That doesn't appear to be a valid API key.")
        return None
    key.increment()

    data = Postcode.get_by_key_name(postcode)
    if not data:
        api_error(self, "I'm afraid we couldn't find that postcode.")
        return None

    return data

def api_output_xml(self, data):
    """Output an API response in XML."""
    self.response.headers['Content-Type'] = 'text/xml'
    self.response.out.write('<?xml version="1.0" encoding="utf-8"?>\n')
    self.response.out.write('<twfy>\n')
    for k, v in data.items():
        if v is None: v = ''
        self.response.out.write('<%s>%s</%s>\n' % (k, cgi.escape(v), k))
    self.response.out.write('</twfy>')

def api_output(self, data):
    """Output an API response, depending upon format. JSON done here as it's easy."""
    if self.request.get('format') == 'js':
        self.response.headers['Content-Type'] = 'text/javascript'
        json = simplejson.dumps(data)
        if re.match('[A-Za-z0-9._[\]]+$', self.request.get('callback')):
            json = '%s(%s)' % (self.request.get('callback'), json)
        self.response.out.write(json)
    else:
        api_output_xml(self, data)

def api_error(self, error):
    """Output an API error."""
    api_output(self, {
        'error': error
    })

class MainPage(webapp.RequestHandler):
    """Front page, does nothing."""
    def get(self):
        self.response.out.write('<html><body>')
        self.response.out.write('TheyWorkForYou API on AppEngine')
        self.response.out.write('</body></html>')

class Lookup(webapp.RequestHandler):
    """The main GAE lookup that returns constituencies and MP details."""
    def get(self):
        postcode = check_params(self)
        if not postcode:
            return
        mp = postcode.get_mp()
        api_output(self, {
            'current_constituency': postcode.current_constituency,
            'future_constituency': postcode.future_constituency,
            'current_mp_person_id': mp and mp.key().name() or None,
            'current_mp_name': mp and mp.name or None,
            'current_mp_party': mp and mp.party or None,
        })

class getConstituency(webapp.RequestHandler):
    """An equivalent to the TWFY API getConstituency call, with extra constituency variables."""
    def get(self):
        postcode = check_params(self)
        if not postcode:
            return
        future = self.request.get('future')
        if future == '0': future = ''
        api_output(self, {
            'current_constituency': postcode.current_constituency,
            'future_constituency': postcode.future_constituency,
            'name': future and postcode.future_constituency or postcode.current_constituency
        })

class getMP(webapp.RequestHandler):
    """An equivalent to the TWFY API getMP call, but with fewer details."""
    def get(self):
        postcode = check_params(self)
        if not postcode:
            return
        mp = postcode.get_mp()
        if mp:
            api_output(self, {
                'person_id': mp.key().name(),
                'full_name': mp.name,
                'party': mp.party,
                'constituency': mp.constituency,
            })
        else:
            api_error(self, 'There is no current MP for that postcode.')

application = webapp.WSGIApplication([
    ('/', MainPage),
    ('/lookup', Lookup),
    ('/getConstituency', getConstituency),
    ('/getMP', getMP),
], debug=True)

def main():
    run_wsgi_app(application)

if __name__ == "__main__":
    main()

