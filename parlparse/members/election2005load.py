#! /usr/bin/env python2.4
# vim:sw=4:ts=4:et:nowrap

# Loads data from BBC election flash applet, and produces XML in the
# same format as all-members.xml

import sys
import os
import urllib
from Ft.Xml.Domlette import NonvalidatingReader
import xml.sax.saxutils

# Load in our constituency names
consdoc = NonvalidatingReader.parseUri("file:constituencies.xml")
cons = {}
mainname = {}
for consnode in consdoc.xpath('//constituency'):
    fromdate = consnode.xpath('string(@fromdate)')
    todate = consnode.xpath('string(@todate)')
    if fromdate <= "2005-05-05" and "2005-05-05" <= todate:
        done = False
        for name in consnode.xpath('./name'):
            id = name.xpath('string(../@id)')
            strname = name.xpath('string(@text)')
            cons[strname] = id
            if not done:
                mainname[id] = strname
                done = True

# Load in BBC identifiers and constituency names
bbc_ids = {}
consfile = open("../rawdata/bbc-constituencies2005.txt")
for line in consfile:
    line = " ".join(line.split()) # replace all contiguous whitespace with one space
    line = line.strip()
    (bbc_id, name) = line.split("|")
    name = name.replace(" (ex Speaker)", "")
    if name not in cons:
        assert False, "Failed to look up cons '%s'" % name
    id = cons[name]
    bbc_ids[int(bbc_id)] = mainname[id]

# Map from BBC party identifiers to ones used in all-members.xml
party_map = {
    'LAB':'Lab',
    'LD':'LDem',
    'CON':'Con',

    'DUP':'DU',
    'SDLP':'SDLP',
    'SF':'SF',
    'UUP':'UU',

    'SNP':'SNP',

    'PC':'PC',

    'RES':'Res',
    'IND':'Ind',
    'IKHH':'Ind',
}
    
# Read XML files from flash applet
mp_id = 1367
items = bbc_ids.iteritems()
#Debug loop for skipping
#for i in range(0, 1668-mp_id):
#    mp_id = mp_id + 1
#    items.next() 
for bbc_id, cons_name in items:
    mp_id = mp_id + 1

    # Download and parse XML
    url = "http://news.bbc.co.uk/1/shared/vote2005/flash_map/resultdata/%d.xml" % bbc_id
    content = urllib.urlopen(url).read()
    #print content
    #print url
    content = content.replace("skinkers:", "skinkers-") # remove XML namespace shite
    content = " ".join(content.split()) # replace all contiguous whitespace with one space
    doc = NonvalidatingReader.parseString(content, url)

    # Find winner
    bbc_win_party = doc.xpath('string(//winningParty)')
    # Missing data (bugs in BBC feed)
    if (cons_name == "North East Hertfordshire"):
        bbc_win_party = "CON"
    if (cons_name == "Lagan Valley"):
        bbc_win_party = "DUP"
    if (cons_name == "South Staffordshire"):
        continue # byelection due to candidate death during election campaign
    win_party = party_map[bbc_win_party]
    win_name = doc.xpath('string(//Party[string(Code)="%s"]/CandidateName)' % bbc_win_party)
    win_name = win_name.strip()
    #print win_party, win_name

    # Make into first and surname
    names = win_name.split(" ")
    if len(names) == 2:
        (first_name, last_name) = names
    elif win_name == "Iain Duncan Smith":
        (first_name, last_name) = ("Iain", "Duncan Smith")
    else:
        assert "Unknown multi-name '%s'" % win_name

    escaped_cons = xml.sax.saxutils.escape(cons_name)
    # Print out our XML
    unicode_out = """<member
        id="uk.org.publicwhip/member/%d"
        house="commons"
        title="" firstname="%s" lastname="%s"
        constituency="%s" party="%s"
        fromdate="2005-05-05" todate="9999-12-31" fromwhy="general_election" towhy="still_in_office"
    />
    """ % (mp_id, first_name, last_name, escaped_cons, win_party)
    print unicode_out.encode("latin-1")


     

