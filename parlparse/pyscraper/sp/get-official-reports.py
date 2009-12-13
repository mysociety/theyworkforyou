#!/usr/bin/python2.4

import sys
import os
import random
import datetime
import time
import urllib
import glob
from optparse import OptionParser

sys.path.append('../')
from BeautifulSoup import BeautifulSoup
from BeautifulSoup import NavigableString

from common import month_name_to_int

agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)"

class MyURLopener(urllib.FancyURLopener):
    version = agent

urllib._urlopener = MyURLopener()

parser = OptionParser()
parser.add_option('-y', "--year", dest="year", default=1999,
                  help="year from which to fetch")
parser.add_option('-q', "--quiet", dest="verbose", action="store_false",
                  default=True, help="don't print status messages")
(options, args) = parser.parse_args()
options.year = int(options.year)

import re

currentyear = datetime.date.today().year

output_directory = "../../../parldata/cmpages/sp/official-reports/"
official_report_template = output_directory + "or%s_%d.html"
official_report_urls_template = output_directory + "or%s.urls"

fetched_urls_hash = {}
urls_filenames = glob.glob( output_directory + "or*.urls" )
for urls_filename in urls_filenames:
    fp = open(urls_filename,"r")
    for line in fp.readlines():
        line = line.rstrip()
        if len(line) == 0:
            continue
        # Optionally may have the Last-Modified date after a 0 byte
        fields = line.split("\0")
        if len(fields) == 2:
            fetched_urls_hash[fields[0]] = fields[1]
        else:
            fetched_urls_hash[fields[0]] = True
    fp.close()

# Fetchthe year indices that we either don't have
# or is the current year's...

official_reports_prefix = "http://www.scottish.parliament.uk/business/officialReports/meetingsParliament/"
official_reports_year_template = official_reports_prefix + "%d.htm"

for year in range(options.year, currentyear+1):
    index_page_url = official_reports_year_template % year
    output_filename = output_directory + str(year) + ".html"
    if (not os.path.exists(output_filename)) or (year == currentyear):
        if options.verbose: print "Fetching %s" % index_page_url
        ur = urllib.urlopen(index_page_url)
        fp = open(output_filename, 'w')
        fp.write(ur.read())
        fp.close()
        ur.close()

for year in range(options.year, currentyear+1):

    year_index_filename = output_directory  + str(year) + ".html"
    if not os.path.exists(year_index_filename):
        raise Exception, "Missing the year index: '%s'" % year_index_filename
    fp = open(year_index_filename)
    html = fp.read()
    fp.close()

    soup = BeautifulSoup( html )
    link_tags = soup.findAll( 'a' )

    for t in link_tags:

        if t.has_key('href') and re.match('^or-',t['href']):

            s = ""
            for c in t.contents:
                if type(c) == NavigableString:
                    s = s + str(c)
            s = re.sub(',','',s)
            # print year_index_filename + "==> " + s
            d = None
            m = re.match( '^\s*(\d+)\s+(\w+)', s )
            if not m:
                raise Exception, "Unrecognized date format in '%s'" % s
            d = datetime.date( year, month_name_to_int(m.group(2)), int(m.group(1)) )

            page = str(t['href'])

            contents_url = official_reports_prefix + page
            contents_last_modified = None

            if fetched_urls_hash.has_key(contents_url):
                continue

            output_filename = official_report_template %  ( str(d), 0 )
            if not os.path.exists(output_filename):
                if options.verbose: print "Fetching %s" % contents_url
                ur = urllib.urlopen(contents_url)
                if ur.info().has_key('last-modified'):
                    contents_last_modified = ur.info()['last-modified']
                fp = open(output_filename, 'w')
                fp.write(ur.read())
                fp.close()
                ur.close()
                print str(d), 'scraped SP official report'

            fp = open(output_filename)
            contents_html = fp.read()
            fp.close()

            contents_soup = BeautifulSoup( contents_html )

            detail_urls = []

            report_id = None

            for possible_report_link in contents_soup.findAll( 'a' ):
                link = possible_report_link.get('href')
                if not link:
                    continue
                m = re.match( '^(s?o[rR](.*)(\d{2})\.htm)', link )
                if m and m.group(3) != "01":
                    if report_id:
                        if report_id != m.group(2):
                            raise Exception, "report ID "+report_id+" and "+m.group(2)+" didn't match."
                    else:
                        report_id = m.group(2)
                    if m.group(1) not in detail_urls:
                        detail_urls.append(m.group(1))

            if not detail_urls:
                continue
                        
            detail_urls.sort()

            all_urls = [ contents_url ]
            all_urls_last_modifieds = [ contents_last_modified ]

            for i in range(0,len(detail_urls)):

                fetched_this_time = False

                output_filename = official_report_template % ( str(d), i + 1 )
                url_to_fetch = re.sub( '[^/]+$', detail_urls[i], contents_url )
                all_urls.append(url_to_fetch)
                all_urls_last_modifieds.append(None)

                if not os.path.exists(output_filename):
                    fetched_this_time = True
                    ur = urllib.urlopen(url_to_fetch)
                    if ur.info().has_key('last-modified'):
                        all_urls_last_modifieds[i+1] = ur.info()['last-modified']
                    fp = open(output_filename, 'w')
                    fp.write(ur.read())
                    fp.close()
                    ur.close()
                else:
                    # Already there, skipping...
                    pass

                if fetched_this_time:
                    # Sleep for a random time up to 2 minutes ...
                    # amount_to_sleep = int( 120 * random.random() )
                    amount_to_sleep = int( 20 * random.random() )
                    # print "Sleeping for " + str(amount_to_sleep) + " seconds"
                    # time.sleep( amount_to_sleep )
                    
            # Write out the URLs of the contents and detail pages:
            urls_filename = official_report_urls_template % str(d)
            if not os.path.exists(urls_filename):
                fp = open(urls_filename,"w")
                for i in range(0,len(all_urls)):
                    u = all_urls[i]
                    lm = all_urls_last_modifieds[i]
                    fp.write(u)
                    if lm:
                        fp.write("\0")
                        fp.write(lm)
                    fp.write("\n")
                fp.close()
