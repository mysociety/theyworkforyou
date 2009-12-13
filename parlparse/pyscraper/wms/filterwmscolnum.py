# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string

import mx.DateTime

from miscfuncs import ApplyFixSubstitutions
from splitheadingsspeakers import StampUrl
from contextexception import ContextException

regcolcore = '[^:<]*:\s*column\s*\d+(?:WS)?'
regcolumnum1 = '<br>&nbsp;<br>\s*%s\s*<br>&nbsp;<br>' % regcolcore
regcolumnum2 = '<p>\s*<p>%s<p>' % regcolcore
regcolumnum3 = '<p>\s*</ul>(?:</font>)?<p>%s<p>\s*<ul>(?:<font[^>]*>)?' % regcolcore
regcolumnum4 = '(?:<br>)?\s*(?:</br>)?<b>%s</b><br>(?:</br>)?' % regcolcore

recolumnumvals = re.compile('(?:<br>&nbsp;<br>|</ul>|</font>|<p>|<br>(?:</br>)?|<b>|\s)*([^:<]*):\s*column\s*(\d+)(WS)?\s*(?:<br>&nbsp;<br>|<ul>|<font[^>]*>|<p>|</b><br>(?:</br>)?|\s)*$(?i)')

regcolnumcont = '<i>[^:<]*:\s*column\s*\d+(?:WS)?&#151;continued\s*</i>(?i)'
recolnumcontvals = re.compile('<i>([^:<]*):\s*column\s*(\d+)(WS)?&#151;continued</i>(?i)')

reaname = '<a name="\S*?">(?i)'
reanamevals = re.compile('<a name="(\S*?)">(?i)')

recomb = re.compile('\s*(%s|%s|%s|%s|%s|%s)\s*(?i)' % (regcolumnum1, regcolumnum2, regcolumnum3, regcolumnum4, regcolnumcont, reaname))
remarginal = re.compile(':\s*column\s*\d+(?i)|</?a[\s>]')

def FilterWMSColnum(fout, text, sdate):
	stamp = StampUrl(sdate) # for error messages

	colnum = -1
	for fss in recomb.split(text):
		columng = recolumnumvals.match(fss)
		if columng:
			ldate = mx.DateTime.DateTimeFrom(columng.group(1)).date
			if sdate != ldate:
				raise ContextException("Column date disagrees %s -- %s" % (sdate, fss), fragment=fss, stamp=stamp)

			lcolnum = string.atoi(columng.group(2))


			if (colnum == -1) or (lcolnum == colnum + 1):
				pass  # good
			elif lcolnum < colnum:
				raise ContextException("Colnum not incrementing %d -- %s" % (lcolnum, fss), fragment=fss, stamp=stamp)
			colnum = lcolnum
			stamp.stamp = '<stamp coldate="%s" colnum="%sWS"/>' % (sdate, lcolnum)
			fout.write(' ')
			fout.write(stamp.stamp)
			continue

		columncontg = recolnumcontvals.match(fss)
		if columncontg:
			ldate = mx.DateTime.DateTimeFrom(columncontg.group(1)).date
			if sdate != ldate:
				raise ContextException("Cont column date disagrees %s -- %s" % (sdate, fss), fragment=fss, stamp=stamp)
			lcolnum = string.atoi(columncontg.group(2))
			if colnum != lcolnum:
				raise ContextException("Cont column number disagrees %d -- %s" % (colnum, fss), fragment=fss, stamp=stamp)

			continue

		# anchor names from HTML <a name="xxx">
		anameg = reanamevals.match(fss)
		if anameg:
			aname = anameg.group(1)
			stamp.aname = '<stamp aname="%s"/>' % aname
			fout.write(stamp.aname)
			continue

                # nothing detected
		# check if we've missed anything obvious
		if recomb.match(fss):
			raise ContextException('regexpvals not general enough', fragment=fss, stamp=stamp)
		#if remarginal.search(fss):
		#	raise ContextException('marginal colnum detection case',
		#	        fragment=remarginal.search(fss).group(0),
		#		      stamp=stamp)
		fout.write(fss)

