#!/usr/bin/env python2.4
# -*- coding: latin-1 -*-
# $Id: bbcconv.py,v 1.4 2005/03/25 23:33:35 theyworkforyou Exp $

# Makes file connecting MP ids to URL of their BBC political profile
# http://news.bbc.co.uk/1/shared/mpdb/html/mpdb.stm

# This is out of date.  Data is now static in bbc-links-200504.xml.

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

sys.path.append("../pyscraper/")
import re
from resolvemembernames import memberList

# Get region pages
bbc_index_url = "http://news.bbc.co.uk/1/shared/mpdb/html/region_%d.stm"
date_today = datetime.date.today().isoformat()
bbcmembers  = sets.Set() # for storing who we have found links for

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''

for i in range(12):
    # Grab page 
    ur = urllib.urlopen(bbc_index_url % (i+1))
    content = ur.read()
    ur.close()

    # print i + 1
    matcher = '<a\s*href="(/1/shared/mpdb/html/\d+.stm)" title="Profile of the MP for (.*?)(?: \(.*?\))?"><b>\s*([\s\S]*?)\s*</b></a></td>';
    matches = re.findall(matcher, content)
    for match in matches:
        match = map(lambda x: re.sub("&amp;", "&", x), match)
        match = map(lambda x: re.sub("\s+", " ", x), match)
        match = map(lambda x: re.sub("\xa0", "", x), match)
        match = map(lambda x: x.strip(), match)
        (url, cons, name) = match

        # Not in aliases file - see comment there (it's to
        # avoid ambiguity in debates parsing)
        if cons == 'Great Yarmouth' and name == 'Tony Wright':
            name = 'Anthony D Wright'

        id, canonname, canoncons =  memberList.matchfullnamecons(name, cons, date_today)
        if not id:
            print >>sys.stderr, "Failed to match %s %s %s" % (name, cons, date_today)
            continue
        url = urlparse.urljoin(bbc_index_url, url)

        pid = memberList.membertoperson(id)
        if pid in bbcmembers:
            print >>sys.stderr, "Ignored repeated entry for " , pid
        else:
            print '<personinfo id="%s" bbc_profile_url="%s" />' % (pid, url)

        bbcmembers.add(pid)

    sys.stdout.flush()

print '</publicwhip>'

# Check we have everybody
allmembers = sets.Set([ memberList.membertoperson(id) for id in memberList.currentmpslist() ])
symdiff = allmembers.symmetric_difference(bbcmembers)
if len(symdiff) > 0:
    print >>sys.stderr, "Failed to get all MPs, these ones in symmetric difference"
    print >>sys.stderr, symdiff


