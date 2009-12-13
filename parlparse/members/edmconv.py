#!/usr/bin/env python2.4
# -*- coding: latin-1 -*-
# $Id: edmconv.py,v 1.5 2004/12/17 11:06:19 theyworkforyou Exp $

# Makes file connecting MP ids to URL in the Early Day Motion EDM)
# database at http://edm.ais.co.uk/

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

# NOTE: These pages will probably not work until they have been viewed via
# EDM's crappy web interface, so that they are created and cached.
#
# curl http://edm.ais.co.uk/weblink/html/members.html/start=[a-z]/order=1/EDMI_SES=
# is your friend.

edm_index_url = "http://edmi.parliament.uk/EDMi/MemberList.aspx"
date_today = datetime.date.today().isoformat()

aismembers  = sets.Set() # for storing who we have found links for

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''

# Find viewstate key
mainpage = urllib.urlopen(edm_index_url).read()
matches = re.search('''<input type="hidden" name="__VIEWSTATE" value="([^"]+)"''', mainpage)
viewstate = matches.group(1)

#for letter in [ 'd' ]:
for letter in [ 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z' ]:
    # Create forum parameters
    params = {}
    params["__EVENTTARGET"] = "_Alpha1:_%s" % letter
    params["__EVENTARGUMENT"] = "_Alpha1$_%s" % letter
    params["__VIEWSTATE"] = viewstate
    params = urllib.urlencode(params)
    
    # Grab page 
    ur = urllib.urlopen(edm_index_url, params)
    content = ur.read()
    ur.close()

    matcher = '''<td><a href='(EDMByMember.aspx\?MID=\d+)\s+&SESSION=875'>(.*)[,.](.*)</a></td>\s+<td>(.*)</td>\s+<td>(.*)</td>\s+<td>(\d+)</td>'''
    matches = re.findall(matcher, content)
    for (url, last, first, cons, party, count) in matches:
        first = re.sub(" \(.*\)", "", first)
        fullname = first.strip() + " " + last.strip()
        id, name, cons =  memberList.matchfullnamecons(fullname, cons, date_today)
        url = urlparse.urljoin(edm_index_url, url)

        if id:
            if id in aismembers:
                print >>sys.stderr, "Ignored repeated entry for " , id
            else:
                print '<memberinfo id="%s" edm_ais_url="%s" />' % (id, url)
            aismembers.add(id)
        else:
            print >>sys.stderr, "Failed to find '%s'" % (fullname)

    sys.stdout.flush()

print '</publicwhip>'

# Check we have everybody
allmembers = sets.Set(memberList.currentmpslist())
symdiff = allmembers.symmetric_difference(aismembers)
if len(symdiff) > 0:
    print >>sys.stderr, "Failed to get all MPs, these ones in symmetric difference"
    print >>sys.stderr, symdiff


