#!/usr/bin/env python

# Screen scrape list of who's standing down in the 2010 general election

# Copyright (C) 2009 Matthew Somerville
# This is free software, and you are welcome to redistribute it under
# certain conditions.  However, it comes with ABSOLUTELY NO WARRANTY.
# For details see the file LICENSE.html in the top level of the source.

import datetime
import sys
import urlparse
import re
#from xml.dom import minidom

sys.path.append("../pyscraper")
from resolvemembernames import memberList

today = datetime.date.today().isoformat()

#mp_links = minidom.parse('wikipedia-commons.xml').getElementsByTagName('personinfo')
#id_to_wikipedia = dict( (m.attributes['id'].value, m.attributes['wikipedia_url'].value) for m in mp_links )
#wikipedia_to_id = dict( (v,k) for k, v in id_to_wikipedia.iteritems() )
#
# Get region pages
page = open('../rawdata/Next_United_Kingdom_general_election').read()

m = re.search('MPs not seeking re-election</span></h2>(.*?)<h2><span class="editsection">(?s)', page)
if not m:
    raise Exception, "Could not find Wikipedia section"
section = m.group(1)

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''
m = re.findall('<li><a href="([^"]*)"[^>]*>([^<]*)</a>', section)
for row in m:
    url, name = row
    id, canonname, canoncons = memberList.matchfullnamecons(name.decode('utf-8'), None, today) 
    pid = memberList.membertoperson(id)
    print '  <personinfo id="%s" standing_down="1" />' % pid
print '</publicwhip>'

