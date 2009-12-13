#!/usr/local/bin/python2.4
# -*- coding: latin-1 -*-

# Makes file connecting MP ids to their expenses

import datetime
import sys
import urllib
import urlparse
import re
import sets

sys.path.append("../pyscraper/")
import re
from resolvemembernames import memberList

# date_today = datetime.date.today().isoformat()

for yearstr in ['200102', '200203', '200304']:
	expmembers = sets.Set() # for storing who we have found links for
	year = str( (int(yearstr)+2100)/101 )
	yeardate = year + '-03-31'
	xmlstr = 'expenses' + year
	fout = open('expenses' + yearstr + '.xml', 'w')
	fout.write('''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>\n''')

	file = open('../rawdata/mpsexpenses' + yearstr + '.tsv')
	content = file.readlines()
	file.close()

#    if re.search("Not Found(?i)", content):
#        raise Exception, "Failed to get content in url %s" % test_url

#    matcher = '<TD ALIGN="LEFT" VALIGN="TOP"><A HREF="(/weblink/html/member.html/.*)/log=\d+/pos=\d+" TARGET="_parent"><font face="arial,helvetica" size=2>(.*)/(.*)</A></TD>\s*<TD ALIGN="LEFT" VALIGN="TOP"><font face="arial,helvetica" size=2>(.*)</TD>'
#    matches = re.findall(matcher, content)

	for line in content:
		cols = line.split("\t")
		first = cols[0]
		last = cols[1]
		cons = cols[2]
		money = cols[3:]
		money = map(lambda x: re.sub("\xa3","", x), money)
		money = map(lambda x: re.sub(",","", x), money)
		id, name, cons =  memberList.matchfullnamecons(first + " " + last, cons, yeardate)
		if not id:
			raise Exception, "Failed to find MP %s %s" % (first, last)

		pid = memberList.membertoperson(id)
#		print >>sys.stderr, last, first, money
		if pid in expmembers:
			print >>sys.stderr, "Ignored repeated entry for " , pid
		else:
			fout.write('<personinfo id="%s" ' % pid)
			for i in [ 0,1,2,3,4,5,6,7,8,9 ]:
				if (year=='2004'):
					if (i==7):
						col = '7a'
					elif (i==8 or i==9):
						col = i
					else:
						col = i+1
				else:
					if (i<9):
						col = i+1
					else:
						continue
				fout.write('%s_col%s="%s" ' % (xmlstr, col, money[i].strip()))
			fout.write('/>\n')
		expmembers.add(pid)

	sys.stdout.flush()

	fout.write('</publicwhip>\n')
	fout.close()

# Check we have everybody
#allmembers = sets.Set(memberList.currentmpslist())
#symdiff = allmembers.symmetric_difference(expmembers)
#if len(symdiff) > 0:
#    print >>sys.stderr, "Failed to get all MPs, these ones in symmetric difference"
#    print >>sys.stderr, symdiff
