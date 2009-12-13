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

yearstr = '200607'
expmembers = sets.Set() # for storing who we have found links for
year = str( (int(yearstr)+2100)/101 )
yeardate = '2007-03-31'
#otheryeardate = '2004-05-01'
xmlstr = 'expenses' + year
fout = open('expenses' + yearstr + '.xml', 'w')
fout.write('''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>\n''')

file = open('../rawdata/mpsexpenses' + yearstr + '.tsv')
content = file.readlines()
file.close()

for line in content:
	line = line.strip()
	if not line or re.match('#', line):
		continue
	cols = line.split("\t")
	name = cols[0]
	m = re.match('(.*?), (.*)$', name)
	name = '%s %s' % (m.group(2), m.group(1))
	money = cols[1:16]
	money = map(lambda x: re.sub("\xa3","", x), money)
	money = map(lambda x: re.sub(",","", x), money)
	id = None
	cons = None
	if name == 'Mr Michael Foster':
		cons = 'Worcester'
	id, name, cons =  memberList.matchfullnamecons(name, cons, yeardate)
	#if not id:
	#	id, name, newcons =  memberList.matchfullnamecons(first + ' ' + last, cons, otheryeardate)
	if not id:
		raise Exception, "Failed to find MP in line %s" % line
	pid = memberList.membertoperson(id)
#	print >>sys.stderr, last, first, money
	if id in expmembers:
		print >>sys.stderr, "Ignored repeated entry for " , id
	else:
		fout.write('<personinfo id="%s" ' % pid)
		for i in [ 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14 ]:
			if i==0 or i==1 or i==2 or i==3:
				col = i + 1
			elif i==4: col = '5a'
			elif i==5: col = '5b'
			elif i==6: col = '5c'
			elif i==7: col = '5d'
			elif i==8: col = '5e'
			elif i==9: col = '5f'
			elif i==10 or i==11:
				col = i -4
			elif i==12: col = '7a'
			elif i==13 or i==14:
				col = i - 5
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
