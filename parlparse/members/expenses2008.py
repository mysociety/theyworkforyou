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


def firstname_from_string(string):
	string = re.sub(" QC$","", string)
	string = re.sub("^Earl of, Rt Hon ", "", string)
	string = re.sub("^Lady ", "", string)
	string = re.sub("^Rt Hon ", "", string)	
	return string
	
yearstr = '200708'
expmembers = sets.Set() # for storing who we have found links for
year = str( (int(yearstr)+2100)/101 )
yeardate = '2008-03-31'
otheryeardate = '2007-03-31'
xmlstr = 'expenses' + year
fout = open('expenses' + yearstr + '.xml', 'w')
fout.write('''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>\n''')

file = open('../rawdata/mpsexpenses' + yearstr + '.tsv')
content = file.readlines()
file.close()

line_index = 0
for line in content:
	line = line.strip()
	cols = line.split("\t")
	# ignore two header lines
	line_index += 1
	if line_index == 1 or line_index == 2:
	    continue
	# ignore 'SOURCE' and 'DATALINK' lines
	first_col = cols[0]
	if first_col == 'SOURCE' or first_col == 'DATALINK':
	    continue
	lastname = first_col
	firstname_and_honorific = firstname_from_string(cols[1])
	
	name = '%s %s' % (firstname_and_honorific, lastname)
	name = name.decode("latin-1", "replace")
	money = cols[2:28]
	money = map(lambda x: re.sub("\xa3","", x), money)
	money = map(lambda x: re.sub(",","", x), money)
	money = map(lambda x: re.sub(".00$","", x), money)
	id = None
	cons = None
	# other Michael Foster is Michael Jabez Foster
  	if name == 'Mr Michael Foster':
		cons = 'Worcester'
	id, found_name, cons =  memberList.matchfullnamecons(name, cons, yeardate)
	if not id:
		id, found_name, newcons =  memberList.matchfullnamecons(name, cons, otheryeardate)
	if not id:
		raise Exception, "Failed to find MP in line %s %d" % (line, line_index)
	pid = memberList.membertoperson(id)
	# print >>sys.stderr, lastname, firstname_and_honorific, money
	if id in expmembers:
		print >>sys.stderr, "Ignored repeated entry for " , id
	else:
		fout.write('<personinfo id="%s" ' % pid)
		expense_cols = ['total_inc_travel',
					    'total_exc_travel', 
					    'total_travel',
					    '1',
						'2',
						'3',
						'4', 
						'7',
						'7a',
						'8',
						'9',
						'comms_allowance',
						'mp_reg_travel_a',
						'mp_reg_travel_b',
						'mp_reg_travel_c',
						'mp_reg_travel_d',
						'mp_other_travel_a',
						'mp_other_travel_b',
						'mp_other_travel_c',
						'mp_other_travel_d',
						'spouse_travel_a',
						'spouse_travel_b',
						'family_travel_a',
						'family_travel_b',
						'employee_travel_a',
						'employee_travel_b']
		for i in range(26):
			col = expense_cols[i]
			if col != '':
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
