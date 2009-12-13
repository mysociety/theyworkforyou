#!/usr/bin/env python2.4

import datetime
import sys
import urllib
import re

sys.path.append("../pyscraper")
sys.path.append("../pyscraper/lords")
from resolvelordsnames import lordsList

# Get region pages
date_today = datetime.date.today().isoformat()

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''

ur = urllib.urlopen('http://www.cofe.anglican.org/links/dios.html')
content = ur.read()
ur.close()
matcher = '<td><a href="(.*?)".*?>(.*?)</a><br /></td>';
matches = re.findall(matcher, content)
for (url, name) in matches:
    name = re.sub('^Saint', 'St', name)
    name = re.sub('&amp;', 'and', name)
    id = None
    title = 'Bishop'
    if name=='York' or name=='Canterbury':
        title = 'Archbishop'
    try:
        id = lordsList.GetLordIDfname('%s of %s' % (title,name), None, date_today)
    except Exception, e:
        print >>sys.stderr, e
    if not id:
        continue
    print '<memberinfo id="%s" diocese_url="%s" />' % (id, url)
print '</publicwhip>'
