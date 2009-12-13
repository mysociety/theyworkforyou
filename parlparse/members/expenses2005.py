#!/usr/local/bin/python2.3
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

yearstr = '200405'
expmembers = sets.Set() # for storing who we have found links for
year = str( (int(yearstr)+2100)/101 )
yeardate = '2005-03-31'
otheryeardate = '2004-05-01'
xmlstr = 'expenses' + year
fout = open('expenses' + yearstr + '.xml', 'w')
fout.write('''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>\n''')

file = open('../rawdata/mpsexpenses' + yearstr + '.tsv')
content = file.readlines()
file.close()

for line in content:
	cols = line.split("\t")
	cons = cols[0]
	money = cols[1:11]
	first = ''
	last = ''
	if (len(cols)>11):
		last = cols[11]
		first = cols[12]
	money = map(lambda x: re.sub("\xa3","", x), money)
	money = map(lambda x: re.sub(",","", x), money)
	id = None
	if first and last:
		id, name, newcons =  memberList.matchfullnamecons(first + ' ' + last, cons, yeardate)
		if not id:
			id, name, newcons =  memberList.matchfullnamecons(first + ' ' + last, cons, otheryeardate)
		cons = newcons
	if not id:
		id, name, cons =  memberList.matchcons(cons, yeardate)
	if not id:
		raise Exception, "Failed to find MP in line %s" % line
	pid = memberList.membertoperson(id)
#	print >>sys.stderr, last, first, money
	if id in expmembers:
		print >>sys.stderr, "Ignored repeated entry for " , id
	else:
		fout.write('<personinfo id="%s" ' % pid)
		for i in [ 0,1,2,3,4,5,6,7,8,9 ]:
			if (i==7):
				col = '7a'
			elif (i==8 or i==9):
				col = i
			else:
				col = i+1
			fout.write('%s_col%s="%s" ' % (xmlstr, col, money[i].strip()))
		fout.write('/>\n')
	expmembers.add(id)

sys.stdout.flush()

fout.write('</publicwhip>\n')
fout.close()

# Check we have everybody
allmembers = sets.Set(memberList.mpslistondate(yeardate))
symdiff = allmembers.symmetric_difference(expmembers)
if len(symdiff) > 0:
    print >>sys.stderr, "Failed to get all MPs, these ones in symmetric difference"
    print >>sys.stderr, symdiff
