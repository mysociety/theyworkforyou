#! /usr/bin/env python2.4

# XXX Pagination has been introduced for the 1998-2003 pages, so any
# rescraping of those will break with this current code.

import urllib
import urlparse
import re
import time
import os

root = ['http://www.niassembly.gov.uk/record/hansard.htm']
#for i in range(1997,2003):
#    root.append('http://www.niassembly.gov.uk/record/hansard_session%d.htm' % i)
for i in range(2005,2007):
    root.append('http://www.niassembly.gov.uk/record/hansard_session%d_A.htm' % i)
root.append('http://www.niassembly.gov.uk/record/hansard_session%d_TA.htm' % i)
for i in range(2006,2010):
    root.append('http://www.niassembly.gov.uk/record/hansard_session%d.htm' % i)

ni_dir = os.path.dirname(__file__)

def scrape_new_ni():
    for url in root:
        ur = urllib.urlopen(url)
        page = ur.read()
        ur.close()

        # Manual fixes
        page = page.replace('990315', '990715').replace('000617', '000619').replace('060706', '060606')
        page = page.replace('060919', '060919p').replace('071101', '071001').replace('071102', '071002')

        match = re.findall('<a href="([^"]*(p?)(\d{6})(i?)\.htm)">View (?:as|in) HTML *</a>', page)
        for day in match:
            url_day = urlparse.urljoin(url, day[0])
            date = time.strptime(day[2], "%y%m%d")
            filename = '%s/../../../parldata/cmpages/ni/ni%d-%02d-%02d%s%s.html' % (ni_dir, date[0], date[1], date[2], day[1], day[3])
            if not os.path.isfile(filename):
                print "NI scraping %s" % url_day
                ur = urllib.urlopen(url_day)
                fp = open(filename, 'w')
                fp.write(ur.read())
                fp.close()
                ur.close()

if __name__ == '__main__':
    scrape_new_ni()
