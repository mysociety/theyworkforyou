import urllib2
import cookielib

# We might as well use a plausible User-Agent:

agent = "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.1.1) Gecko/20061205 Firefox/2.0.0.1"

# Look as much like firefox as we can (apart from not accepting
# compressed output and not using HTTP keep-alive):

policy = cookielib.DefaultCookiePolicy(
    rfc2965=True,
    hide_cookie2=True,
    strict_ns_domain=cookielib.DefaultCookiePolicy.DomainStrict)
cj = cookielib.MozillaCookieJar()
cj.set_policy(policy)
fake_browser = urllib2.build_opener(urllib2.HTTPCookieProcessor(cj))
fake_browser.addheaders = [('User-Agent', agent),
                           ('Accept', 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5'),
                           ('Accept-Language', 'en-us,en;q=0.5'),
                           # ('Accept-Encoding', 'gzip,deflate'),
                           ('Accept-Charset', 'UTF-8,*')]
