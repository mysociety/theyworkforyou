#!/usr/bin/python2.4

import sys
import os
import random
import datetime
import time
import urllib
import glob
import re
from optparse import OptionParser

from common import month_name_to_int
from common import non_tag_data_in
from common import tidy_string

sys.path.append('../')
from BeautifulSoup import MinimalSoup

agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)"

class MyURLopener(urllib.FancyURLopener):
    version = agent

urllib._urlopener = MyURLopener()

parser = OptionParser()
parser.add_option('-q', "--quiet", dest="verbose", action="store_false",
                  default=True, help="don't print status messages")
parser.add_option('-a', "--all", dest="all", action="store_true",
                  help="go right back to the beginning of the history")
(options, args) = parser.parse_args()

currentdate = datetime.date.today()
currentyear = datetime.date.today().year

output_directory = "../../../parldata/cmpages/sp/bulletins/"
bulletin_template = output_directory + "wa%s_%d.html"
bulletin_urls_template = output_directory + "wa%s.urls"

# Fetch the year indices that we either don't have
# or is the current year's...

bulletin_prefix = "http://www.scottish.parliament.uk/business/businessBulletin/"
bulletin_year_template = bulletin_prefix + "%d.htm"

# Find the existing contents pages in the excluding those in the 90s...

existing_contents_pages = glob.glob( output_directory + "contents-bb-[0-8]*" )
existing_contents_pages.sort()

contents_pages_fetched = { }

for year in range(1999,currentyear+1):
    index_page_url = bulletin_year_template % year
    output_filename = output_directory + str(year) + ".html"
    if (not os.path.exists(output_filename)) or (year == currentyear):
        if options.verbose: print "Fetching %s" % index_page_url
        ur = urllib.urlopen(index_page_url)
        fp = open(output_filename, 'w')
        fp.write(ur.read())
        fp.close()
        ur.close()

for year in range(1999,currentyear+1):

    year_index_filename = output_directory  + str(year) + ".html"
    if not os.path.exists(year_index_filename):
        raise Exception, "Missing the year index: '%s'" % year_index_filename
    fp = open(year_index_filename)
    html = fp.read()
    fp.close()

    soup = MinimalSoup( html )
    link_tags = soup.findAll( 'a' )

    contents_pages = set()
    daily_pages = set()

    contents_hash = {}

    for t in link_tags:

        if t.has_key('href'):
            m = re.search('(^|/)(bb-[0-9]+/.*)$',t['href'])
            if m:
                page = m.group(2)

                subdir, leaf = page.split("/")
                if options.verbose: print "  == %s / %s ==" % (subdir,leaf)

                contents_pages.add( (subdir,leaf) )
                contents_hash[subdir+"_"+leaf] = True

    # Fetch all the contents pages:

    for (subdir,leaf) in contents_pages:

        contents_filename = output_directory + "contents-"+subdir+"_"+leaf
        contents_url = bulletin_prefix + subdir + "/" + leaf

        # Make sure we refetch the latest one, since it might have been updated.
        if not os.path.exists(contents_filename) or (len(existing_contents_pages) > 0 and existing_contents_pages[-1] == contents_filename):
            if options.verbose: print "  Fetching %s" % contents_url
            ur = urllib.urlopen(contents_url)
            fp = open(contents_filename, 'w')
            fp.write(ur.read())
            fp.close()
            ur.close()
            contents_pages_fetched[contents_filename] = True
                
    # Now find all the daily pages from the contents pages...

    for (subdir,leaf) in contents_pages:

        contents_filename = output_directory + "contents-"+subdir+"_"+leaf
        if options.verbose: print "  Contents page: " + contents_filename

        if (not options.all) and (not contents_filename in contents_pages_fetched):
            continue

        fp = open(contents_filename)
        contents_html = fp.read()
        fp.close()

        contents_html = re.sub('&nbsp;',' ',contents_html)
        contents_soup = MinimalSoup(contents_html)
        link_tags = contents_soup.findAll( lambda x: x.name == 'a' and x.has_key('href') and re.search('(?i)[ab]b-\d\d-\d\d',x['href']) )
        link_urls = [ ]
        if len(link_tags) == 0:
            # Annoyingly, some of these file are so broken that
            # BeautifulSoup can't parse them, and just returns a
            # single NavigableString.  So, if we don't find any
            # appropriate links, guess that this is the case and look
            # for links manually :(
            # 
            matches = re.findall( '(?ims)<a[^>]href\s*=\s*"?([^"> ]+)["> ]', contents_html )
            for m in matches:
                link_urls.append(m)
        else:
            for t in link_tags:
                link_urls.append(t['href'])
        link_leaves = [ ]
        for u in link_urls:
            parts = u.split('/')
            if len(parts) == 1:
                link_leaves.append(parts[0])
            if len(parts) > 1:
                if parts[-2] == subdir:
                    link_leaves.append(parts[-1])
                else:
                    if re.match('(?i)[ab]b-',parts[-2]):
                        print "Warning: subdirs differed between "+subdir+" and "+parts[-2]+"/"+parts[-1]+" when parsing file: "+contents_filename

        for l in link_leaves:

            m = re.match('(?i)^\s*([ab]b-\d\d-\d\d-?(\w*)\.htm)',l)

            if not m:
                print "Couldn't parse '%s' in file %s" % ( l, contents_filename )
                continue

            page = m.group(1)
            section = m.group(2)

            if section not in ( 'a', 'd', 'e', 'f' ):
                continue

            day_filename = output_directory + "day-" + subdir + "_" + page
            day_url = bulletin_prefix + subdir + "/" + page

            if not os.path.exists(day_filename):
                if options.verbose: print "    Fetching %s" % day_url
                ur = urllib.urlopen(day_url)
                fp = open(day_filename, 'w')
                fp.write(ur.read())
                fp.close()
                ur.close()
                amount_to_sleep = int( 4 * random.random() )
                time.sleep( amount_to_sleep )

