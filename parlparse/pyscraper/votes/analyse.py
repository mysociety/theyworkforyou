# vim:sw=8:ts=8:et:nowrap

import elementtree
from elementtree.ElementTree import ElementTree, Element

import os
import sys
import xml
import re

os.chdir('votes')
cwdfiles=os.listdir(os.getcwd())
votesfiles=filter(lambda s:re.match('votes',s), cwdfiles)


topelement=Element('top')
i=1

for vf in votesfiles:
	print vf
	try:
		votetree=ElementTree(file=vf)
		voteroot=votetree.getroot()
		date=voteroot.get('date')
		m=re.match('(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})',date)
		if not m:
			print "internal error in date format"
			sys.exit()
		mgd=m.groupdict()
		mgd.update({'date':date})
		acts=votetree.findall('//royal_assent/act')
		if len(acts)>0:
			assent=Element('assent',mgd)
			for j in range(len(acts)):
				assent.insert(j,acts[j])
			topelement.insert(i,assent)
			i=i+1
	except xml.parsers.expat.ExpatError, errorinst:
		print errorinst
		print "XML parsing error in %s" % vf, sys.exc_info()[0]
	


top=ElementTree(topelement)

top.write('allvotes.xml')

	
