#! /usr/bin/env python2.4
# vim:sw=4:ts=4:et:nowrap

import sys
import os
import urllib
from Ft.Xml.Domlette import NonvalidatingReader
import xml.sax.saxutils
import re
import string

# Load in our constituency names
consdoc = NonvalidatingReader.parseUri("file:constituencies.xml")
allcons = {}
mainname = {}
for consnode in consdoc.xpath('//constituency'):
    fromdate = consnode.xpath('string(@fromdate)')
    todate = consnode.xpath('string(@todate)')
    if fromdate <= "2005-05-05" and "2005-05-05" <= todate:
        done = False
        for name in consnode.xpath('./name'):
            id = name.xpath('string(../@id)')
            strname = name.xpath('string(@text)')
            allcons[strname] = id
            if not done:
                mainname[id] = strname
                done = True

# Load in our MP names
memberdoc = NonvalidatingReader.parseUri("file:all-members.xml")
memberbyconsid = {}
for membernode in memberdoc.xpath('//member'):
    fromdate = membernode.xpath('string(@fromdate)')
    todate = membernode.xpath('string(@todate)')
    if fromdate <= "2005-05-05" and "2005-05-05" <= todate:
        entry = {}
        entry['firstname'] = membernode.xpath('string(@firstname)')
        entry['lastname'] = membernode.xpath('string(@lastname)')
        entry['party'] = membernode.xpath('string(@party)')
        constituency = membernode.xpath('string(@constituency)')
        cons_id = allcons[constituency]
        #print repr(cons_id), entry
        memberbyconsid[cons_id] = entry


# Load in parliament list of MPs and check it
bbc_ids = {}
consfile = open("../rawdata/Members2005.htm")
content = consfile.read()
count = 0
for row in re.split("<TR[^>]*>(?i)", content):
    # Find rows for MP and data in them
    parts = re.match("<TD>(.+),\s*(.+)\s*\((.+)\)<TD>(.+)</TD>", row)
    if not parts:
        continue
    (last, first, party, constituency) = map(lambda x: x.strip().decode("latin-1"), parts.groups())
    if re.match("^Ynys M", constituency):
        constituency = "Ynys Mon"
    first = first.replace("Mr ", "")
    first = first.replace("Ms ", "")
    first = first.replace("Miss ", "")
    first = first.replace("Mrs ", "")
    first = first.replace("Sir ", "")
    first = first.replace("Dr ", "")
    first = first.replace("Rev ", "")
    first = first.replace("The Reverend ", "")
    first = first.replace("Rt Hon ", "")
    first = first.replace("Hon ", "")
    first = first.replace(" QC", "")
    first = first.replace(".", " ")
    first = first.strip()
    if party == "LD":
        party = "LDem"
    if party == "Lab/Co-op":
        party = "Lab"
    if party == "Respect":
        party = "Res"
    # Hmmm, these aren't elected yet, so why like that on parl website?
    if party == "CWM":
        party = "Lab"
        if last == "Haselhurst":
            party = "Con"
    if party == "DCWM":
        party = "Lab"
        if last == "Lord":
            party = "Con"
    if party == "SPK":
        party = "Lab"
    if constituency == "North East Milton Keynes":
        first = "Mark"
    if last == "Mackay":
        last = "MacKay"
    if last == "Mackinlay":
        last = "MacKinlay"

    # Get constituency for the row, and get expected MP info
    cons_id = allcons[constituency]
    entry = memberbyconsid[cons_id]

    # Check they match exactly
    #print (first, last, party, constituency)
    #print entry
    assert entry['firstname'] == first
    assert entry['lastname'] == last
    assert entry['party'] == party
    entry['done'] = 1

    count = count + 1

print "Count: %d" % count
for entry in memberbyconsid.itervalues():
    if not 'done' in entry:  
        print "Missing:", entry



