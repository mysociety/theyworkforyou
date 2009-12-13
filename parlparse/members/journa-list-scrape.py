#!/usr/local/bin/python2.4
# -*- coding: latin-1 -*-

import datetime
import sys
import urllib
import urlparse
import re
import sets

sys.path.append("../pyscraper/")
from resolvemembernames import memberList

allmembers = sets.Set(memberList.mpslistondate('9999-12-31'))

for member in allmembers:
    try:
        attr = memberList.getmember(member)
        fullname = attr["firstname"] + " " + attr["lastname"]

        # Load search page from journa-list
        params = {}
        params['name'] = fullname
        params = urllib.urlencode(params)
        ur = urllib.urlopen("http://www.journalisted.com/list", params)
        content = ur.read()
        ur.close()

        # Find match count
        match = re.search("""<p\>(\d+) Matches\<\/p\>""", content) 
        assert match, "%s\ndidn't find matches count %s" % (content, fullname)
        matches = match.groups()[0]
        matches = int(matches)

        if matches > 0:
            print fullname.encode('utf-8'), matches
            print memberList.membertoperson(member)

            links = re.findall("""\<li\>\<a href="([^"]+)">[^<]+\<\/a\>\<\/li\>""", content)
            assert links, "%s\ndidn't find links despite matches %s" % (content, fullname)
            print links
    except:
        print >>sys.stderr, "trouble with " + member



