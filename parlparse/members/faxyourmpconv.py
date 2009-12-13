#!/usr/bin/env python2.4
# $Id: faxyourmpconv.py,v 1.5 2004/12/18 18:11:57 theyworkforyou Exp $
# vim:sw=4:ts=4:et:nowrap

# Converts Fax Your MP responsiveness CSV file into XML - matching
# member names and adding up data on the way.

# "votes" here are votes by users of FaxYourMP as to how responsive
# their MP was.

# The Public Whip, Copyright (C) 2003 Francis Irving and Julian Todd
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

input = 'http://www.faxyourmp.com/mp_results_test.php3?format=csv'

import sys
import string
import datetime
import csv
import sets
import urllib
sys.path.append("../pyscraper")
from resolvemembernames import memberList

date_today = datetime.date.today().isoformat()

ih = urllib.urlopen(input)
ih.next() # skip first line
csvreader = csv.reader(ih)

nohash = {}
yeshash = {}

for row in csvreader:
    if row == ["</b>"]:
        break

    origname, origcons, voteside, votecount = map(string.strip, row)
    origname = origname.replace("\\", "")
    origcons = origcons.replace("\\", "")
    origcons = origcons.replace("Stretford and ~~~~~~~", "Stretford and Urmston")

    # no longer in house - TODO give better date
    if origname == "Dennis Canavan" or origname == "Rt Hon Paul Daisley": 
        continue
    
    if origcons == "South Tomshire": # better keep rosa's membership of parliament secret
        continue
    if origcons == "Trumpton": # i didn't know james was religious
        continue
    if origcons == "Stefstown": # i didn't know stef was knighted
        continue
    
    try:
        id, name, cons =  memberList.matchfullnamecons(origname, origcons, date_today)
    except Exception, e:
        print >>sys.stderr, "FaxYourMP name match failed"
        print >>sys.stderr, e
    else:
        if voteside.lower() == "no":
            nohash[id] = nohash.get(id, 0) + int(votecount)
        elif voteside.lower() == "yes" or voteside.lower() == "yes"+chr(160):
            yeshash[id] = yeshash.get(id, 0) + int(votecount)
        else:
            raise Exception, "Strange vote %s" % voteside

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
    print '<memberinfo id="%s" fax_your_mp_responsiveness="%d%%" fax_your_mp_samplesize="%d" fax_your_mp_rank="%d" fax_your_mp_rank_outof="%d" fax_your_mp_data_date="%s" />' % (id, resp, samplesize, activerank, len(ids), date_today)
    prevresp = resp

print '</publicwhip>'

