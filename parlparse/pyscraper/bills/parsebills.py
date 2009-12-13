#!/usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap
# To do:
# Make links absolute (against baselink) not relative

# Converts all the daily parsed bill pages from chggpages
# into one XML file containing all the data from it

import os
import os.path
import sys
import re
import elementtree.ElementTree
from elementtree.ElementTree import ElementTree,Element,ProcessingInstruction

sys.path.append("../")
import miscfuncs

# Patterns
billpattern=re.compile('''<tr[^>]*>\s*<td[^>]*>\s*<img src="/pa/img/(?P<house>sqrgrn.gif|diamdrd.gif)"[^>]*></TD>\s*<TD><FONT size=\+1><A HREF="(?P<link>[^"]*)"\s*TITLE="Link to (?P<billname1>[-a-z.,A-Z0-9()\s]*?) Bill(\s*\[HL\]\s*)?"><B>(?P<billname2>[-a-z.,A-Z0-9()\s]*?) Bill(\s*\[HL\])?\s*\((?P<billno>\d+)\)\s*</B></A></FONT>(?P<rest>[\s\S]*?)</td></tr>(?i)''')

pat=re.compile('\s*(<br>)?(\s|&nbsp;)*<a\s*href="(?P<href>[^"]*?)"\s*title="(?P<title>[^"]*?)"\s*>\s*<b>\s*<i>(?P<desc>[^<]*?)(</i>\s*</b>\s*</a>|<br>)(?i)')

sessionpattern=re.compile('''<FONT size=\+3><B>Public Bills before Parliament (?P<session>\d{4}-\d{2})</B></FONT>(?i)''')

#

mandatory_attrs=['link','billname']

# Setting up files (to be changed when integrated into whole system)

def makeattrdict(gdict, context):
	rest=gdict.pop('rest')
	attrdict={}
	attrdict.update(gdict)

	pos=0
	#print rest
	mobj=pat.match(rest)
	while mobj and len(rest)>0:
		pos=pos+1
		rest=rest[mobj.end():]

		linkdict=mobj.groupdict()
		desc=linkdict.pop('desc')
		desc=re.sub('\s+',' ',desc)
		desc.strip()
		type=linktype(desc)

		mobj2=re.match('\s*\(to previous (print|version) of bill\)\s*',rest)
		if mobj2:
			type=type+'-previous'
			rest=rest[mobj2.end():]
	
		attrdict.update({type:linkdict['href']})

		mobj=pat.match(rest)


	rest=re.sub('</TD>\s*</TR>\s*<tr><td>(&nbsp;|\s)*(?i)','',rest)
	rest=re.sub('(<br>)?</TD>\s*</TR>\s*<TR valign=top>\s*<TD valign=top><a name="m"><FONT size=\+1><B>M</b></FONT></a><BR>(?i)','',rest)
	rest=re.sub('\s*<br>\s*$','',rest)
	rest=rest.strip()
	if len(rest)>0:
		raise Exception, "Additional material %s at bill %s:\n%s" % (context, gdict['billname1'], rest)

	return attrdict

def linktype(desc):
	if re.match('Explanatory Note(s)?', desc):
		return 'explanatory_note'
	
	if re.match('Amendment(s)?',desc):
		return 'amendment'
	
	if re.match('Standing Committee Proceedings',desc):
		return 'standing_committee'

	if re.match('Petitions against the(?P<billname>[-a-z.,A-Z0-9()\s]*?)Bill',desc):
		return 'petitions'

	if re.match('Report Stage Proceedings',desc):
		return 'report'

	if re.match('Committee of the Whole House Proceedings',desc):
		return 'house_committee'

	return 'unknown'

def addprint(billdict, session, no, house, attrdict, sourcefilename):
	'''Adds a bill, to a dictionary of bills indexed by session, printno

	Attrdict must contain the session and printno keys, and may have keys
	for other attributes to be added to the final xml file.

	If billdict already has an entry for (session, printno), it checks
	that any attribute already present (including the link to the printing)
	are identical, otherwise it throws an error.
	'''

	if billdict.has_key((session, no, house)):
		billattrdict=billdict[(session, no, house)]
		for attr in mandatory_attrs:
			if billattrdict[attr]!=attrdict[attr]:
				print "Error: sourcefilename=%s session=%s no=%s house=%s attr=%s billattrdict[attr]=%s attrdict[attr]=%s\n" %(sourcefilename, session, no, house, attr, billattrdict[attr], attrdict[attr])
		
		for attr in attrdict:
			if billattrdict.has_key(attr):
				if billattrdict[attr]!=attrdict[attr]:
					
					# horrid fix

					if not (attr=='explanatory_note' and re.search('toc.htm$',billattrdict[attr])):
						#raise Exception, "sourcefilename=%s session=%s no=%s house=%s attr=%s billattrdict[attr]=%s attrdict[attr]=%s\n\n billdict[attr]=%s\n attrdict=%s" %(sourcefilename, session, no, house, attr, billattrdict[attr], attrdict[attr], billdict, attrdict)
						print "Attribute changed: sourcefilename=%s session=%s no=%s house=%s attr=%s billattrdict[attr]=%s attrdict[attr]=%s\n" %(sourcefilename, session, no, house, attr, billattrdict[attr], attrdict[attr])	
			else:
				billattrdict[attr]=attrdict[attr]
				#print "(%s) adding to (%s, %s, %s) attrdict[%s]=%s" % (sourcefilename, session, no, house, attr, attrdict[attr])			
	else:
		billdict[(session, no, house)]=attrdict
		#print "(%s) adding (%s, %s, %s)" % (sourcefilename, session, no, house)
	
def parsebillfile(sourcefilename, billdict):

	#print "parsing %s" % sourcefilename

	sourcefile=open(sourcefilename,'r')

	source=sourcefile.read()

	# Some files have commented out older entries, for the moment I think
	# it is best to ignore them -- FD

	xmlcommentreplace=re.compile('''<!--[\s\S]*?-->''')
	source=xmlcommentreplace.sub('',source)

	mobj=sessionpattern.search(source)
	if mobj:
		session=mobj.groupdict()['session']
	else:
		raise Exception, "Cannot determine Parliamentary Session from sourcefile %s" % sourcefilename	

	mobj=re.search('bills\d{4}_(?P<date>\d{4}-\d{2}-\d{2})',sourcefilename)
	if not mobj:
		print "fail",sourcefilename
		sys.exit()

	date=mobj.groupdict()['date']	
	attrdict={}

	m=billpattern.search(source)
	while m:
		gdict=m.groupdict()

		# check that billnames are identical, use one
		billname1=gdict.pop('billname1').strip()
		billname2=gdict.pop('billname2').strip()
		if billname1 != billname2:
			raise Exception, "Two bill names differ (%s) and (%s)" % (billname1,billname2)
	
		if gdict['house']=='sqrgrn.gif':
			house='commons'
		elif gdict['house']=='diamdrd.gif':
			house='lords'
		else:
			raise Exception, "Unrecognised graphic -- I cannot tell which house the bill is currently before"
	
		gdict.update([('house',house),('billname',billname1),('session',session)])
	
		billno=gdict['billno']

		printdict=makeattrdict(gdict,sourcefilename)
		addprint(billdict, session, billno, house, printdict, sourcefilename)
	
		source=source[m.end():]
		m=billpattern.search(source)


def maketree(printdict):
	root=Element('top')
	
	i=0

	for (session, no, house) in printdict.keys():
		i=i+1
		elem=Element('print',printdict[(session, no, house)])

		root.insert(i, elem)	

	billtree=ElementTree(root)

	return billtree

	
def MakeBillPrint(billprint_file = os.path.join(miscfuncs.pwxmldirs, "chgpages/bills/billprint.xml")):
        baselink="http://www.publications.parliament.uk"

        billsdir='cmpages/chgpages/bills'

        billsourcedir=os.path.join(miscfuncs.toppath,billsdir)

        billsources=filter(lambda s:re.search('bills',s),os.listdir(billsourcedir))

        billdict={}
        for sourcefilename in billsources:
                parsebillfile(os.path.join(billsourcedir,sourcefilename), billdict)

        outtree=maketree(billdict)

        #outtree.write('billprint-temp.xml')
        s=elementtree.ElementTree.tostring(outtree.getroot())
        s=s.replace("/><print", "/>\n<print")
        s='<?xml version="1.0" ?>\n<?xml-stylesheet type="text/xsl" href="http://ukparse.kforge.net/svn/parlparse/pyscraper/bills/billprint.xsl"?>\n' + s

        fout=open(billprint_file,'w')
        fout.write(s)
        fout.close()

if __name__ == '__main__':
        MakeBillPrint('billprint.xml')



