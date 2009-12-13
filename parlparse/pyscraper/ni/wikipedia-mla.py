#!/usr/bin/env python2.4
# -*- coding: latin-1 -*-

# Screen scrape list of links to MLAs on Wikipedia, so we can link to the articles.

# Copyright (C) 2007 Matthew Somerville
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

import datetime
import sys
import urlparse
import re
import sets

sys.path.extend((".", ".."))
from resolveninames import memberList
date_today = datetime.date.today().isoformat()

# Get region pages
wiki_index_url = "http://en.wikipedia.org/wiki/Members_of_the_Northern_Ireland_Assembly_elected_in_2007"
wikimembers  = {}

# Grab page 
ur = open('../rawdata/Members_of_the_NIA_2007')
content = ur.read()
ur.close()

matcher = '<tr>\s+<td><a href="(/wiki/[^"]+)"[^>]*? title="[^"]+"[^>]*>([^<]+)</a></td>\s+<td><a href="/wiki/[^"]+" title="[^"]+">([^<]+)</a></td>';
matches = re.findall(matcher, content)
matches.append(('/wiki/Alastair_Ian_Ross', 'Alastair Ian Ross', 'East Antrim'))
matches.append(('/wiki/Danny_Kinahan', 'Danny Kinahan', 'South Antrim'))
for (url, name, cons) in matches:
    if name == 'David Burnside': continue # He's left
    id = None
    #cons = cons.decode('utf-8')
    #cons = cons.replace('&amp;', '&')
    name = name.decode('utf-8')
    try:
        id, str = memberList.match(name, date_today)
    except Exception, e:
        print >>sys.stderr, e
    if not id:
        continue
    pid = memberList.membertoperson(id)
    wikimembers[pid] = url

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''
k = wikimembers.keys()
k.sort()
for id in k:
    url = urlparse.urljoin(wiki_index_url, wikimembers[id])
    print '<personinfo id="%s" wikipedia_url="%s" />' % (id, url)
print '</publicwhip>'

wikimembers = sets.Set(wikimembers.keys())
allmembers = sets.Set([ memberList.membertoperson(id) for id in memberList.list() ])
symdiff = allmembers.symmetric_difference(wikimembers)
if len(symdiff) > 0:
    print >>sys.stderr, "Failed to get all MLAs, these ones in symmetric difference"
    print >>sys.stderr, symdiff


