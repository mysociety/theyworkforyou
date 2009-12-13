# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string

import mx.DateTime

from contextexception import ContextException
from splitheadingsspeakers import StampUrl
from miscfuncs import IsNotQuiet, TimeProcessing

# this filter converts column number tags of form:
#     <B>9 Dec 2003 : Column 893</B>
# into xml form
#     <stamp coldate="2003-12-09" colnum="893"/>



# the new Lords thing
# <B>19 Nov 2003 : Column 1926</B></P>
# <p></UL>\n<B>29 Jan 2004 : Column 321</B></P>\n<UL>
# <P>\n</UL><FONT SIZE=3>\n<B>29 Jan 2004 : Column 369</B></P>\n<UL><FONT SIZE=2>
# <P>\n<FONT SIZE=3>\n<B>29 Jan 2004 : Column 430</B></P>\n<FONT SIZE=2>
# <P><a name="column_1442"><B>1 Apr 2004 : Column 1442</B></P><FONT SIZE=3>
# <P></UL><a name="column_1519"><B>1 Apr 2004 : Column 1519</B></P><UL><FONT SIZE=3>

regcolmat = '\s*<a name="column_(?:WS|WA)?\d+">(?:</a>)?\s*<b>[^:<]*:\s*column\s*(?:WS|WA)?\d+\s*</b>'
regcolp = ['(?:<p>|<br>&nbsp;<br>|<br>)', '(?:</p>|<br>&nbsp;<br>|<br>)' ]
regcolumnum11 = '<p>%s</p>\s*<font size=3>' % regcolmat
regcolumnum1 = '%s%s%s' % (regcolp[0], regcolmat, regcolp[1])
regcolumnum2 = '<p>\s*<font size=3>%s</p>\s*<font size=2>' % regcolmat
regcolumnum3 = '%s\s*</ul>%s%s\s*<ul>' % (regcolp[0], regcolmat, regcolp[1])
regcolumnum3i = '%s\s*</i>%s%s\s*<i>' % (regcolp[0], regcolmat, regcolp[1])
regcolumnum4 = '<p>\s*</ul><font size=3>%s</p>\s*<ul><font size=2>' % regcolmat
#regcolumnum5 = '<p>\s*(?:<font size=3>\s*)?%s</p>\s*<font size=[23]>' % regcolmat
regcolumnum6 = '<p>\s*</ul>(?:</ul></ul>)?%s</p>\s*<ul>(?:<ul><ul>)?<font size=3>' % regcolmat

recolumnumvals = re.compile('(?:<br>&nbsp;<br>|<br>|<p>|</ul>|</i>|<font size=\d>|\s|</?a[^>]*>)*?<b>([^:<]*)\s*:\s*column\s*(\D*?)(\d+)\s*</b>(?:<br>&nbsp;<br>|<br>|</p>|<ul>|<i>|<font size=\d>|\s)*$(?i)')

# <H5>12.31 p.m.</H5>
# the lords times put dots in "p.m."  but the commons never do.
regtime1 = '(?:</?p>\s*|<h[45]>|\[|\n)(?:\d+(?:[:\.]\s?\d+)?\.?\s*[ap]\.?m\.?\s*|12 noon)(?:</st>)?(?:\s*</?p>|\s*</h[45]>|\n)'
regtime2 = '<h5>(?:Noon|Midnight)?\s*(?:</st>)?</h5>' # accounts for blank <h5></h5>

# we'll just lift out this special case to force a time onto it
# We can't have '<h4 align=center>(?:<a name="[^"]*">)?' at the front because the <a> tag won't get encoded
regtime3 = 'The House met at .*? of the clock.*?</h4>'

retimevals = re.compile('(?:</?p>\s*|<h\d>|\[|\n)\s*(?:<I>)?(\d+(?:[:\.]\s?\d+)?\.?\s*[ap][m\.]+|(?:12 )?Noon|Midnight|</h5>|</st>)(?i)')
regtime3vals = re.compile('The House met at (.*?) of the clock(?i)')

# <a name="column_1099">
reaname = '<a name\s*=\s*"[^"]*">\s*(?:</a>)?(?i)'
reanamevals = re.compile('<a name\s*=\s*"([^"]*)">(?i)')

# match in right order so the longer ones get checked first.  (prob a good way to do r3 and r6 variants but don't know it
recomb = re.compile('(%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s)(?i)' % (regcolumnum11, regcolumnum1, regcolumnum2, regcolumnum6, regcolumnum4, regcolumnum3, regcolumnum3i, regtime1, regtime2, reaname, regtime3))

remarginal = re.compile(':\s*column\s*\D*(\d+)(?i)')

def FilterLordsColtime(fout, text, sdate):
	colnum = -1
	time = ''

	stampurl = StampUrl(sdate)
	previoustime = []
	for fss in recomb.split(text):
		# column number type

		# we need some very elaboirate checking to sort out the sections, by
		# titles that are sometimes on the wrong side of the first column,
		# and by colnums that miss the GC code in that section.
		# column numbers are also missed during divisions, and this exception
		# should be detected and noted.

		# That implies that this is the filter which detects the boundaries
		# between the standard four sections.
		columng = recolumnumvals.match(fss)
		if columng:
			# check date
			ldate = mx.DateTime.DateTimeFrom(columng.group(1)).date
			if sdate != ldate:
				raise ContextException("Column date disagrees %s -- %s" % (sdate, fss), stamp=stampurl, fragment=fss)

			# check number
                        # ltype = columng.group(2)
			lcolnum = string.atoi(columng.group(3))
			if lcolnum == colnum - 1:
				pass	# spurious decrementing of column number stamps
			elif lcolnum == colnum:
				pass	# spurious repeat of column number stamps
			# good (we get skipped columns in divisions)
			elif (colnum == -1) or (colnum + 1 <= lcolnum <= colnum + 5):  # was 2 but this caused us to miss ones
				colnum = lcolnum
				fout.write('<stamp coldate="%s" colnum="%s%s"/>' % (sdate, colnum, ""))

			# column numbers do get skipped during division listings
			else:
				pass #print "Colnum not incrementing %d -- %d -- %s" % (colnum, lcolnum, fss)
				#raise Exception, "Colnum not incrementing %d -- %d -- %s" % (colnum, lcolnum, fss)

			#print (ldate, colnum, lindexstyle)
			continue

		timeg = retimevals.match(fss)
		if timeg:
			time = timeg.group(1)
			if not re.match('(?:</h5>|</st>)(?i)', time):
				time = TimeProcessing(time, previoustime, False, stampurl)
				fout.write('<stamp time="%s"/>' % time)
				if time:
                                        previoustime.append(time)
			continue

		# special lift a time out of the heading
		regtime3 = regtime3vals.match(fss)
		if regtime3:
			fout.write(fss) # put this heading back into the flow of text
			assert not previoustime
			lntimematch = re.match("(half[\- ]past )?(\w+)(-thirty)?$", regtime3.group(1))
			lnhour = lntimematch and lntimematch.group(2)
			# strange way to do it, but I'm keeping tab on examples, and the transition between am and pm
			if lnhour == "two":
				lntimep = "2:%s pm"
			elif lnhour == "three":
				lntimep = "3:%s pm"
			elif lnhour == "six":
				lntimep = "6:%s pm"
			elif lnhour == "nine":
				lntimep = "9:%s am"
			elif lnhour == "eleven":
				lntimep = "11:%s am"
			elif lnhour == "ten":
				lntimep = "10:%s am"
			else:
				print "-------------'%s'" % regtime3.group(1)
				assert False
			assert not lntimematch.group(1) or not lntimematch.group(3)
			ntime = lntimep % ((lntimematch.group(1) or lntimematch.group(3)) and "30" or "00")
			time = TimeProcessing(ntime, previoustime, False, stampurl)
			fout.write('<stamp time="%s"/>' % time)
			continue

		# anchor names from HTML <a name="xxx">
		anameg = reanamevals.match(fss)
		if anameg:
			aname = anameg.group(1)
			fout.write('<stamp aname="%s"/>' % aname)
			stampurl.aname = aname
			continue

		# nothing detected
		# check if we've missed anything obvious
		if recomb.match(fss):
			print "$$$", fss, "$$-$"
			raise ContextException(' regexpvals not general enough ', stamp=stampurl, fragment=fss) # a programming error between splitting and matching
		if remarginal.search(fss):
			print remarginal.search(fss).group(0)
			lregcolumnum6 = '<p>\s*</ul>\s*<a name="column_\d+">(?:</a>)?\s*<b>[^:<]*:\s*column\s*\d+\s*</b></p>\s*<ul><font size=3>(?i)'
			print re.findall(lregcolumnum6, fss)
			#print fss
			raise ContextException(' marginal coltime detection case ', stamp=stampurl, fragment=fss)
		fout.write(fss)




##############
# lords filters stuff -- to be cleared up in a bit.

# first split the text into the four categories:
# Main Debate, Grand Committee, Written Ministerial Statements, Written Answers

# The parsing of each differs, which is why it is important to split this document
# into streams first, even though the boundaries are quite blurred and
# sometimes the column code numbering on either side is errant.


regacol = '<a name="column_([^\d>"]*)\d+">(?:</a>)?'

def SplitLordsText(text, sdate):
	res = [ '', '', '', '' ]

	# Use a name tags
	wagc = re.search('(?:<br>&nbsp;<br>\s*)?<a name\s*=\s*"(?:gc|column_(?:GC|CWH)\d+|[0-9\-]+_cmtee0)">(?:</a>)?(?i)', text)
	wams = re.search('(?:<br>&nbsp;<br>\s*)?<a name="(?:wms|column_WS\d+)">(?:</a>)?(?i)', text)
	wama = re.search('(?:<br>&nbsp;<br>\s*)?<a name="(?:column_WA\d+|[\dw]*_writ0)">(?:</a>)?(?i)', text)

	# the sections are always in the same order, but sometimes there's one missing.

	# set end of house of lords section and check order
	if wagc:
		holend = wagc.start(0)
		if wams:
			assert holend < wams.start(0)
		elif wama:
			assert holend < wama.start(0)
	elif wams:
		holend = wams.start(0)
		if wama:
			assert holend < wama.start(0)
	elif wama:
		holend = wama.start(0)
	else:
		holend = len(text)

	# set the grand committee end
	res[0] = text[:holend]
	if wagc:
		if wams:
			gcend = wams.start(0)
		elif wama:
			gcend = wama.start(0)
		else:
			gcend = len(text)
		res[1] = text[holend:gcend]
	else:
		gcend = holend

	# set the ministerial statements end
	if wams:
		if wama:
			msend = wama.start(0)
		else:
			msend = len(text)
		res[2] = text[gcend:msend]
	else:
		msend = gcend

	# set the written answers end
	maend = len(text)
	if wama:
		res[3] = text[msend:]

	# lords splitting
	if IsNotQuiet():
		print "Lords splitting into parts of size: ", map(len, res)

	# check the wrong column numbering or wrong titles aren't found in the wrong place
	assert res[0]  # there always is a main debate
	chns = re.search('<a name="column_\D+\d+">', res[0])
	if chns:
		print chns.group(0)
		raise ContextException("wrong column numbering in main debate", fragment=chns.group(0))

	# check that there is always an adjournment in the main debate, with some of the trash that gets put before it
	# this kind of overguessing is to get a feel for the variation that is encountered.
	if sdate != '2007-10-01' and sdate != '2008-09-29' and sdate != '2009-10-05' and not re.search('(?:<ul><ul><ul>|<ul><ul><p>|\s*(?:<ul>|<p>)?|<p>\s*<ul><ul>(?:<ul>)?)\s*(?:Parliament was prorogued|House adjourned )(?i)', res[0]):
		raise ContextException("house adjourned failure", stamp=None, fragment=res[0][-100:])

        page = re.findall('<page[^>]*>', res[0])[-1]
        if (re.match('(<page[^>]*>\s*)+$', res[0])):
                res[0] = ''

	# check the title of the Grand Committee
	if res[1]:
                res[1] = page + res[1]
                page = re.findall('<page[^>]*>', res[1])[-1]
		assert not re.search('<a name="column_(?!(?:GC|CWH))\D+\d+">', res[1])
		if not re.search('<(?:h[23] align=)?"?center"?>(?:<a name="[^"]*">(?:</a>)?)?\s*(?:(?:Official Report of the )?(?:(?:the)?Northern Ireland Orders )?Grand Committee|Second Reading Committee)', res[1]):
			raise ContextException("grand committee title failure", stamp=None, fragment=res[1][:100])

	# check the title is in the Written Statements section
	if res[2]:
                res[2] = page + res[2]
                page = re.findall('<page[^>]*>', res[2])[-1]
		assert not re.search('<a name="column_(?!WS)\D+\d+">', res[2])
		assert re.search('center"?>(?:<a name="[^"]*">(?:</a>)?)?Written Statements?', res[2])

	# check the title and column numbering in the written answers
	if res[3]:
                res[3] = page + res[3]
		assert not re.search('<a name="column_(?!WA)\D+\d+">', res[3])
		if not re.search('<(?:h3 align=)?"?center"?>(?:<a name="[^"]*">(?:</a>)?)?\s*Written Answers?', res[3]): # sometimes the s is missing
			raise ContextException("missing written answer title", fragment=res[3])

	return res




