#!/usr/bin/env python2.4
# $Id: faxyourmpfinaladdup.py,v 1.1 2005/03/08 18:22:33 frabcus Exp $
# vim:sw=4:ts=4:et:nowrap

# Converts Fax Your MP responsiveness dump HTML file into XML - matching member
# names and adding up data on the way.  Does it for year 2004 only, matching
# per person.

# "votes" here are votes by users of FaxYourMP as to how responsive
# their MP was.

# The Public Whip, Copyright (C) 2005 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

input = 'votescast-fymp-2004.html'

import sys
import string
import datetime
import sets
import re
import mx.DateTime
sys.path.append("../pyscraper")
from resolvemembernames import memberList

date_today = datetime.date.today().isoformat()

ih = open(input)
rows = ih.read().split('<tr>')
rows.pop(0)
rows.pop(0)

nohash = {}
yeshash = {}

for row in rows:
    votetoken,used,vote,email,constituency,sent,mp_name,standee_name,messagetype,made = re.split('</td><td>', row)
    votetoken = votetoken.replace('<td>', '')
    made = made.replace('</td></tr>\n', '')
    if made[0:4] != "2004":
        continue
    made_date = mx.DateTime.DateTimeFrom(made).date

    constituency = constituency.replace('\\', '')
    mp_name = mp_name.replace('\\', '')

    if constituency == "South Tomshire": # better keep rosa's membership of parliament secret
        continue
    if constituency == "Trumpton": # i didn't know james was religious
        continue
    if constituency == "Stefstown": # i didn't know stef was knighted
        continue
    
    try:
        mp_id, name, cons =  memberList.matchfullnamecons(mp_name, constituency, made_date)
    except Exception, e:
        print >>sys.stderr, "FaxYourMP name match failed", e
    else:
        if not mp_id:
            print >>sys.stderr, "FaxYourMP name match failed %s, %s" % (mp_name, constituency)
        else:
            id = memberList.membertoperson(mp_id)
            if vote.lower() == "no":
                nohash[id] = nohash.get(id, 0) + 1
            elif vote.lower() == "yes" or vote.lower() == "yes"+chr(160):
                yeshash[id] = yeshash.get(id, 0) + 1
            elif vote == "":
                # print >>sys.stderr, "Blank vote"
                # Ignore for now
                pass
            else:
                print >>sys.stderr, standee_name,made, made_date,vote,constituency,mp_name,"--",messagetype, id, name, cons
                print >>sys.stderr, "Strange vote %s" % vote

ih.close()

def responsiveness(id):
    yes = yeshash.get(id, 0)
    no = nohash.get(id, 0)
    resp = 100.0 * yes / (yes + no)
    samplesize = yes + no
    return resp, samplesize

# sort most responsive first
def rankingfunction(a, b):
    (resp_a, samplesize_a) = responsiveness(a)
    (resp_b, samplesize_b) = responsiveness(b)
    return cmp(resp_b, resp_a) 

ids = list(sets.Set(nohash.keys()).union(yeshash.keys()))
ids.sort(rankingfunction)

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''

rank = 0
prevresp = -1
activerank = 0
for id in ids:
    rank = rank + 1
    resp, samplesize = responsiveness(id)
    if resp != prevresp:
        activerank = rank
    print '<personinfo id="%s" fax_your_mp_responsiveness="%d%%" fax_your_mp_samplesize="%d" fax_your_mp_rank="%d" fax_your_mp_rank_outof="%d" fax_your_mp_data_date="%s" />' % (id, resp, samplesize, activerank, len(ids), date_today)
    prevresp = resp

print '</publicwhip>'

