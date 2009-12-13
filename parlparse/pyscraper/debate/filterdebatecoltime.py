# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string

import mx.DateTime

from miscfuncs import ApplyFixSubstitutions, TimeProcessing
from splitheadingsspeakers import StampUrl
from contextexception import ContextException

# this filter converts column number tags of form:
#     <B>9 Dec 2003 : Column 893</B>
# into xml form
#     <stamp coldate="2003-12-09" colnum="893"/>

# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = 	[

        ('<b>[^<]*?</b>\s+<br>&nbsp;<br><ul><ul><ul>\s+</ul></ul></ul>\s*$', '', -1, 'all'),
        ( '\(Sylvia Heal </B>\s*\)\:', '(Sylvia Heal):</B>', 1, '2003-06-20'),
	( '(<H4><center>THE PARLIAMENTARY DEBATES</center></H4>)', '<P>\n\n<B>14 Jul 2003 : Column 1</B></P>\n\\1', 1, '2003-07-14'),

	( '<B>27 Mar 2003 : Column 563</B></P>\s*<UL><UL><UL>\s*</UL></UL></UL>', '', 1, '2003-03-27'),
	( '<B>10 Mar 2003 : Column 141</B></P>\s*<UL><UL><UL>\s*</UL></UL></UL>', '', 1, '2003-03-10'),
	( '<B>4 Feb 2003 : Column 251</B></P>\s*<UL><UL><UL>\s*</UL></UL></UL>', '', 1, '2003-02-04'),
	( '<H4>2.20</H4>', '<H4>2.20 pm</H4>', 1, '2003-02-28'),
        ( '(<H5>2.58)(</H5>)', '\\1 pm\\2', 1, '2004-01-13'),

        ( '<B>30 Apr 2003 : Column 401</B></P>\s*<UL><UL><UL>\s*</UL></UL></UL>', '', 1, '2003-04-30'),
        ( '<B>6 May 2003 : Column 671</B></P>\s*<UL><UL><UL>\s*</UL></UL></UL>', '', 1, '2003-05-06'),

        ( '(fine, or both.".\'\&\#151\;)', '\\1</FONT></TD></TR></TABLE>', 1, '2003-05-20'),
        ( '(</FONT></TD><TD><FONT SIZE=-1>"Section)', '<TR><TD>\\1', 1, '2003-05-20'),

        ( '(<B>)( 8. )(Mr. Alistair Carmichael)', '\\2\\1\\3', 1, '2003-01-07'),


        # Column numbers are totally knackered here
        ( '<B>16 Jun 2003 : Column 184</B></P>', '', 1, '2003-06-16'),
        ( '<B>8 May 2003 : Column 928</B></P>', '', 1, '2003-05-08'),
        ( '<B>7 May 2003 : Column 808</B></P>', '', 1, '2003-05-07'),
        ( '<B>14 Mar 2003 : Column 598</B></P>', '', 1, '2003-03-14'),
        ( '<i>14 Mar 2003 : Column 598&#151;continued</i></P>', '', 1, '2003-03-14'),
        ( '<B>16 Jan 2003 : Column 914</B></P>', '', 1, '2003-01-16'),
]


# <B>9 Dec 2003 : Column 893</B>
regcolcore = '<b>[^:<]*:\s*column\s*\d+(?:WH)?\s*</b>'
regcolumnum1 = '<p>\s*%s</p>\n' % regcolcore
regcolumnum2 = '<p>\s*</ul>\s*%s</p>\n<ul>' % regcolcore
regcolumnum3 = '<p>\s*</ul></font>\s*%s</p>\n<ul><font[^>]*>' % regcolcore
regcolumnum4 = '<p>\s*</font>\s*%s</p>\n<font[^>]*>' % regcolcore
regcolumnum5 = '\s<br>\s*&nbsp;<br>\s*%s\s*<br>&nbsp;<br>' % regcolcore
regcolumnum6 = '\s<br>\s*&nbsp;<br>\s*</ul>\s*%s\s*<br>&nbsp;<br>\s*<ul>' % regcolcore
regcolumnum7 = '<br>(?:</br>)?%s<br>(?:</br>)?' % regcolcore
recolumnumvals = re.compile('(?:<p>|<a name=".*?">|</ul>|</font>|<br>\s*&nbsp;<br>|<br>(?:</br>)?|\s)*<b>([^:<]*):\s*column\s*(\d+)(WH)?\s*</b>(?:</p>|<ul>|<font[^>]*>|<br>&nbsp;<br>|<br>(?:</br>)?|\s)*$(?i)')




#<i>13 Nov 2003 : Column 431&#151;continued</i>
# these occur infrequently
regcolnumcont = '<(?:i|b)>[^:<]*:\s*column\s*\d+(?:WH)?(?:</b>)?&#151;continued</(?:i|p)>'
recolnumcontvals = re.compile('<(?:i|b)>([^:<]*):\s*column\s*(\d+)(WH)?(?:</b>)?&#151;continued</(?:i|p)>(?i)')


# <H5>12.31 pm</H5>
# <p>\n12.31 pm\n<p>
# [3:31 pm<P>    -- at the beginning of divisions
regtime = '(?:</?p>\s*|<h[45](?: align="left")?>\s?|\[|\n)(?:\d+(?:[:\.]\d+)?(?:\s*|&nbsp;)(?:[ap]\.?m\.?(?:</st>)?| noon)?)(?:\s*</?p>|\s*</h[45]>|\n)'
retimevals = re.compile('(?:</?p>\s*|<h\d(?: align="left")?>|\[|\n)\s*(\d+(?:[:\.]\s*\d+)?(?:\s*|&nbsp;)(?:[apm.]+|noon|midnight)?)(?:\s*</?p>|\s*</h\d>|</st>|\n)*$(?i)')

# <a name="column_1099">
reaname = '<a name="\S*?">(?:</a>)?'
reanamevals = re.compile('<a name="(\S*?)">(?i)')


recomb = re.compile('(%s|%s|%s|%s|%s|%s|%s|%s|%s|%s)(?i)' % (regcolumnum1, regcolumnum2, regcolumnum3, regcolumnum4, regcolumnum5, regcolumnum6, regcolumnum7, regcolnumcont, regtime, reaname))

# this covers all the different kinds of things we should have picked up
remarginal = re.compile(':\s*column\s*(\d+)|\n(?:\d+[.:])?\d+\s*[ap]\.?m\.?[^,\w](?i)|<a(?:\s+name|>)')


def FilterDebateColTime(fout, text, sdate, typ):
	# old style fixing (before patches existed)
	if typ == "debate":
		text = ApplyFixSubstitutions(text, sdate, fixsubs)

	stamp = StampUrl(sdate) # for error messages
	btodaytype = re.match('<pagex [^>]*type="today"', text)
	if btodaytype:
		fout.write('<stamp colnum="000"/>\n')

	colnum = -1
	previoustime = []
	for fss in recomb.split(text):
		# column number type
		columng = recolumnumvals.match(fss)
		if columng:
			assert not btodaytype  # no columns in today

			# check date
			ldate = mx.DateTime.DateTimeFrom(columng.group(1)).date
			if sdate != ldate:
				raise ContextException("Column date disagrees %s -- %s" % (sdate, fss), stamp=stamp, fragment=fss)

			# check number
			lcolnum = string.atoi(columng.group(2))
			if lcolnum == colnum - 1:
				pass	# spurious decrementing of column number stamps
			elif (colnum == -1) or (lcolnum == colnum + 1):
				pass  # good
			# column numbers do get skipped during division listings
			elif lcolnum < colnum:
				raise ContextException("Colnum not incrementing %d smaller than %d -- %s" % (lcolnum, colnum, fss), stamp=stamp, fragment=fss)

			# write a column number stamp (has to increase no matter what)
			if lcolnum > colnum:
				colnum = lcolnum
				stamp.stamp = '<stamp coldate="%s" colnum="%sW"/>' % (sdate, lcolnum)
			fout.write('<stamp coldate="%s" colnum="%s"/>' % (sdate, colnum))
			continue

		columncg = recolnumcontvals.match(fss)
		if columncg:
			ldate = mx.DateTime.DateTimeFrom(columncg.group(1)).date
			if sdate != ldate:
				raise ContextException("Column date disagrees %s -- %s" % (sdate, fss), stamp=stamp, fragment=fss)

			lcolnum = string.atoi(columncg.group(2))
			if colnum != lcolnum and sdate<'2006-05-08':
				raise ContextException("Cont column number disagrees %d -- %s" % (colnum, fss), stamp=stamp, fragment=fss)

			continue

		timeg = retimevals.match(fss)
		if timeg:
			time = TimeProcessing(timeg.group(1), previoustime, (timeg.group(0)[0] == '['), stamp)
			if not time:
				raise ContextException("Time not matched: " + timeg.group(1), stamp=stamp, fragment=fss)

			fout.write('<stamp time="%s"/>' % time)
			previoustime.append(time)
			continue

		# anchor names from HTML <a name="xxx">
		anameg = reanamevals.match(fss)
		if anameg:
                        aname = anameg.group(1)
                        stamp.aname = '<stamp aname="%s"/>' % aname
                        fout.write('<stamp aname="%s"/>' % aname)
                        continue


		# nothing detected
		# check if we've missed anything obvious
		if recomb.match(fss):
			print "$$$", fss, "$$$"
			print regcolnumcont
			print re.match(regcolnumcont + "(?i)", fss)
			raise ContextException('regexpvals not general enough', stamp=stamp, fragment=fss)
		if remarginal.search(fss):
			print fss
			print '--------------------------------\n'
			print "marginal found: ", remarginal.search(fss).groups()
			print "zeroth: ", remarginal.search(fss).group(0)
			print '--------------------------------\n'
			raise ContextException('marginal coltime/a detection case', stamp=stamp, fragment=fss)
		fout.write(fss)


