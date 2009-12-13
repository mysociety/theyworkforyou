#!/usr/bin/env python2.4
# -*- coding: latin-1 -*-
# $Id: bbcconv.py,v 1.4 2005/03/25 23:33:35 theyworkforyou Exp $

# Screen scrape list of links to Lords on Wikipedia, so we can link to the articles.

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

import datetime
import sys
import urllib
import urlparse
import re
import sets

sys.path.append("../pyscraper")
sys.path.append("../pyscraper/lords")
from resolvemembernames import memberList

# Get region pages
wiki_index_url = "http://en.wikipedia.org/wiki/MPs_elected_in_the_UK_general_election,_2005"
date_parl = {
    1997: '1999-01-01',
    2001: '2003-01-01',
    2005: datetime.date.today().isoformat()
}
wikimembers  = {}

# Grab page 
for year in (1997, 2001, 2005):
    ur = open('../rawdata/Members_of_the_House_of_Commons_%d' % year)
    content = ur.read()
    ur.close()

# <tr>
#<td><a href="/wiki/West_Ham_%28UK_Parliament_constituency%29" title="West Ham (UK Parliament constituency)">West Ham</a></td>
#<td><a href="/wiki/Lyn_Brown" title="Lyn Brown">Lyn Brown</a></td>
#<td>Labour</td>
    matcher = '<tr>\s+<td><a href="/wiki/[^"]+" [^>]*?title="[^"]+">([^<]+)</a>(?:<br />\s+<small>.*?</small>)?</td>\s+<td>(?:Dr |Sir |The Rev\. )?<a href="(/wiki/[^"]+)" [^>]*?title="[^"]+"[^>]*>([^<]+)</a></td>|by-election,[^"]+">([^<]+)</a> [^ ]{1,3} <a href="(/wiki/[^"]+)" title="[^"]+">([^<]+)</a>';
    matches = re.findall(matcher, content)
    for (cons, url, name, cons2, url2, name2) in matches:
        id = None
        if cons2:
            cons = cons2
            name = name2
            url = url2
        cons = cons.decode('utf-8')
        cons = cons.replace('&amp;', '&')
        name = name.decode('utf-8')
        try:
            (id, canonname, canoncons) = memberList.matchfullnamecons(name, cons, date_parl[year])
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

#wikimembers = sets.Set(wikimembers.keys())
#print "len: ", len(wikimembers)

# Check we have everybody -- ha! not likely yet
#allmembers = sets.Set(memberList.currentmpslist())
#symdiff = allmembers.symmetric_difference(wikimembers)
#if len(symdiff) > 0:
#    print >>sys.stderr, "Failed to get all MPs, these ones in symmetric difference"
#    print >>sys.stderr, symdiff


