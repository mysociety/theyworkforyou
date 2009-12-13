#!/usr/bin/env python2.4
# -*- coding: latin-1 -*-

# Screen scrape list of links to MLAs on Wikipedia, so we can link to the articles.
# (Very slightly adapted to get MSPs instead by Mark Longair.)

# Copyright (C) 2007 Matthew Somerville
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

import datetime
import sys
import urlparse
import re
import sets

from resolvemembernames import memberList
date_today = datetime.date.today().isoformat()

# These were the original locations of these pages:
wiki_index_urls = [ "http://en.wikipedia.org/wiki/Members_of_the_1st_Scottish_Parliament",
                    "http://en.wikipedia.org/wiki/Members_of_the_2nd_Scottish_Parliament",
                    "http://en.wikipedia.org/wiki/Members_of_the_3rd_Scottish_Parliament" ]
wikimembers  = {}

content = ''

for u in wiki_index_urls:
    leaf = re.sub('.*/','',u)
    ur = open(leaf)
    content += ur.read()
    ur.close()

matcher = '(?ims)<a href="(/wiki/[^"]+)" [^>]*?title="[^"]+">([^<]+)</a>'
matches = re.findall(matcher, content)

matches.append(('/wiki/Dorothy_Grace_Elder','Dorothy-Grace Elder'))
matches.append(('/wiki/Chris_Harvie', 'Christopher Harvie'))
matches.append(('/wiki/Nicholas_Johnston', 'Nick Johnston'))

for (url, name) in matches:
    id_list = None
    #cons = cons.decode('utf-8')
    #cons = cons.replace('&amp;', '&')
    name = name.decode('utf-8')
    try:
        id_list = memberList.match_string_somehow(name, None, '', True)
    except Exception, e:
        print >>sys.stderr, e
    if not id_list:
        continue

    for id_to_add in id_list:
        pid = memberList.membertoperson(id_to_add)
        wikimembers[pid] = url

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''
k = wikimembers.keys()
k.sort()
for id in k:
    url = urlparse.urljoin(wiki_index_urls[0], wikimembers[id])
    print '<personinfo id="%s" wikipedia_url="%s" />' % (id, url)
print '</publicwhip>'

wikimembers = sets.Set(wikimembers.keys())
allmembers = sets.Set([ memberList.membertoperson(id) for id in memberList.list_all_dates() ])

symdiff = allmembers.symmetric_difference(wikimembers)
if len(symdiff) > 0:
    print >>sys.stderr, "Failed to get all MSPs, these ones in symmetric difference"
    print >>sys.stderr, "\n".join(symdiff)
