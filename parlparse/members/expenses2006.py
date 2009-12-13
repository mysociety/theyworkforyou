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

yearstr = '200506'
expmembers = sets.Set() # for storing who we have found links for
year = str( (int(yearstr)+2100)/101 )
yeardate = '2005-04-05'
otheryeardate = '2006-03-01'
xmlstr = 'expenses' + year

for file in ('', 'former'):
	fout = open('expenses200506' + file + '.xml', 'w')
	fout.write('''<?xml version="1.0" encoding="ISO-8859-1"?>
	<publicwhip>\n''')
	fh = open('../rawdata/mpsexpenses200506'+file+'.tsv')
	content = fh.readlines()
	fh.close()
	for line in content:
		line = line.strip()
		if not line or re.match('#', line):
			continue
		cols = line.split("\t")
		name = cols[0]
		name = re.sub('(Earl of, | (QC|CBE|BEM))', '', name)
		name = re.sub('^.pik', 'Opik', name)
		name = re.sub('Simon, Mr Si.n', 'Simon, Mr Sion', name)
		name = re.sub('^(.*?), (.*?)$', r'\2 \1', name)
		cons = cols[1]
		if re.match('Ynys M.n', cons):
			cons = 'Ynys Mon'
		money = cols[2:12]
		money = map(lambda x: re.sub("\xa3","", x), money)
		money = map(lambda x: re.sub(",","", x), money)
		id = None
		id, newname, newcons =  memberList.matchfullnamecons(name, cons, yeardate)
		if not id:
			id, newname, newcons =  memberList.matchfullnamecons(name, cons, otheryeardate)
		if not id:
			raise Exception, "Failed to find MP in line %s" % line
		name = newname
		cons = newcons
		pid = memberList.membertoperson(id)
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
		expmembers.add(pid)

	sys.stdout.flush()
	fout.write('</publicwhip>\n')
	fout.close()

# Check we have everybody
allmembers = memberList.mpslistondate(yeardate)
allmembers = map(lambda x: memberList.membertoperson(x), allmembers)
allmembers2 = memberList.mpslistondate(otheryeardate)
allmembers2 = map(lambda x: memberList.membertoperson(x), allmembers2)

allmembers = sets.Set(allmembers) | sets.Set(allmembers2)
symdiff = allmembers.symmetric_difference(expmembers)
if len(symdiff) > 0:
    print >>sys.stderr, "Failed to get all MPs, these ones in symmetric difference"
    print >>sys.stderr, symdiff
