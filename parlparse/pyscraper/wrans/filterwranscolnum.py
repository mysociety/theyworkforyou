# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string

import mx.DateTime

from miscfuncs import ApplyFixSubstitutions
from splitheadingsspeakers import StampUrl
from contextexception import ContextException

# this filter converts column number tags of form:
#     <I>23 Oct 2003 : Column 637W</I>
# into xml form
#     <stamp coldate="2003-10-23" colnum="637W"/>

# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = 	[
        ( '(<TABLE BORDER=1>)(\s*?<a name="30613w06.html_sbhd5">)', '\\2', 1, '2003-06-13'),
        ( '(</FONT>\s*?)<TABLE BORDER=1>(\s*?<P>\s*?<P>)', '\\1\\2', 1, '2003-06-13'),
        ( '(<TABLE BORDER=1>\s*?)<TABLE BORDER=1>', '\\1', 1, '2003-06-13'),
        ( '<TABLE BORDER=1>(\s*?<a name="30613w19.html_sbhd5">)', '\\1', 1, '2003-06-13'),

        # NIGHTMARE table day - works now
        # Excess table tag
        ( '<TABLE BORDER=1>(\s*?<a name="30612w07.html_sbhd1">)', '\\1', 1, '2003-06-12'),
        ( '(<P><I>12 Jun 2003 : Column 1065W</I><P>\s*?)<TABLE BORDER=1>', '\\1', 1, '2003-06-12'),
        # Title with end /TH /TR, but no begin TH TR, bad bolding
        ( '(<center>)<B>(&#163;000Country/YearTotal DFID Programme</center>)</B>(\s*?</FONT>)</FONT></TH></TR>', '\\1\\2\\3', 1, '2003-06-12'),
        # Table immediately followed by spurious end TH end TR - heading then in TDs
        ( '(<TABLE BORDER=1>)\s*?<P>\s*?</FONT></TH></TR>', '\\1', 1, '2003-06-12'),
        # Malformed heading
        ( '(<center>)<B>(&#163;000</center>)</B>', '\\1\\2', 1, '2003-06-12'),

        ( '(</FONT>\s*?)<TABLE BORDER=1>(\s*?<a name="30611w01.html_sbhd8">)', '\\1\\2', 1, '2003-06-11'),
        ( '<TABLE BORDER=1>(\s*?<a name="30611w09.html_sbhd6">)', '\\1', 1, '2003-06-11'),
        ( '(Ethnic Minority Business Forum members</center></B>\s*)</FONT><P>\s*</FONT></TH></TR>', '\\1', 1, '2003-06-11'),
        ( '<TABLE BORDER=1>\s*?<P>\s*?(<P>\s*?<a name="30611w13.html_wqn3">)', '\\1', 1, '2003-06-11'),
        ( '<TABLE BORDER=1>\s*(<a name="30611w14.html_dpthd0">)', '\\1', 1, '2003-06-11'),
        ( '(<P><I>11 Jun 2003 : Column 907W</I><P>\s*)<TABLE BORDER=1>', '\\1', 1, '2003-06-11'),
        ( '(<P><I>11 Jun 2003 : Column 945W</I><P>\s*)<TABLE BORDER=1>', '\\1', 1, '2003-06-11'),
        ( '<TABLE BORDER=1>\s*?(<P>\s*?<page)', '\\1', 2, '2003-06-11'),
        ( '<TABLE BORDER=1>(\s*?The Northern Ireland)', '\\1', 1, '2003-06-11'),

        ( '\x01', '', 1, '2003-06-11'),

        # weird fragment
        ('(<P><I>10 Jun 2003 : Column 764W</I><P>)\s*?<P>\s*?<UL>We have a duty to provide our troops with the best available equipment with which to protect themselves and succeed in conflict. Depleted Uranium munitions provide a unique anti-armour capability. Therefore, British Forces deployed to the Gulf have DU munitions available as part of their armoury, and<P></UL>', '\\1', 1, '2003-06-10'),


        ( '(\[109374\]<P>)</UL>', '\\1', 1, '2003-04-30'),

        ( '(Mr. Kenneth Clarke: )(To ask the Chancellor of the Exchequer)', '</UL>\n\n<B>\\1</B>\\2', 1, '2003-04-30'),
        ( '<B>  Barbara Follett </B>\s*?\(4\)', '(4)', 1, '2003-02-06'),
        ( '(<B> Mr )(</B>\s*?)(Jamieson:)', '\\1\\3\\2', 1, '2003-01-30'),

        ( '(<UL>)(The Solicitor-General)( <i>\[holding answer 14 May 2003\]:</i>)', '<B>\\2</B>\\3', 1, '2003-06-12'),

        # Stop the remarginal matching this reference to a column number
        ( 'from that stated in Hansard 16 January 2003: column 792W', 'Hansard 16 January 2003: col. 792W', 1, '2004-01-13' )

]

#<P>
#</UL><P><I>20 Nov 2003 : Column 1203W</I><P>
#<UL>
# <I>23 Oct 2003 : Column 637W</I>

# These are very specific cases which attempt to undo the full column inserting macro which
# they use, which pushes column stamps right into the middle of sentences and paragraphs that
# may be indented with ul and font changed.
# Undoing the insertion fully means we can automatically glue paragraphs back together.

# columns never show up in the middle of tables.


regcolumnum1 = '<p>\s<p><i>[^:<]*:\s*column:?\s*\d+w?\s*</i><p>(?i)'
regcolumnum2 = '<p>\s</ul><p><i>[^:<]*:\s*column:?\s*\d+w?\s*</i><p>\s<ul>(?i)'
regcolumnum3 = '<p>\s</ul>(?:</font>)+<p><i>[^:<]*:\s*column:?\s*\d+w?\s*</i><p>\s<ul>(?:<font[^>]*>)?(?i)'
regcolumnum4 = '<br>(?:&nbsp;<br>|\s+)?<i>[^:<]*:\s*column:?\s*\d+w?\s*</i><br>&nbsp;<br>\s(?i)'
regcolumnum5 = '<br>&nbsp;<br></ul><i>[^:<]*:\s*column:?\s*\d+w?\s*</i><br>&nbsp;<br>\s<ul>(?i)'
regcolumnum6 = '(?:<br>)?\s*(?:</br>)?<b>[^:<]*:\s*column:?\s*\d+w?\s*</b><br>(?:</br>)?(?i)'

recolumnumvals = re.compile('(?:<p>|\s|</ul>|</font>|<br>&nbsp;<br>|<br>|</br>)*<(?:i|b)>([^:<]*):\s*column:?\s*(\d+)w?\s*</(?:i|b)>(?:<p>|\s|<ul>|<font[^>]*>|<br>&nbsp;<br>|<br>|</br>)*$(?i)')

#<i>23 Oct 2003 : Column 640W&#151;continued</i>
regcolnumcont = '<p><b>[^:<]*:\s*column\s*\d+w?</b>&#151;continued</p>|<i>[^:<]*:\s*column\s*\d+w?&#151;continued\s*</i>|\[Continued from column \d+w?\]|\[Continued in Column \d+W\](?i)'
recolnumcontvals = re.compile('<p><b>([^:<]*):\s*column\s*(\d+)w?</b>&#151;continued</p>|<i>([^:<]*):\s*column\s*(\d+)w?&#151;continued</i>|\[Continued from column (\d+)w?\]|\[Continued in Column (\d+)W\](?i)')

# <a name="column_1099">
reaname = '<a name="\S*?">(?i)'
reanamevals = re.compile('<a name="(\S*?)">(?i)')

recomb = re.compile('\s*(%s|%s|%s|%s|%s|%s|%s|%s)\s*' % (regcolumnum1, regcolumnum2, regcolumnum3, regcolumnum4, regcolumnum5, regcolumnum6, regcolnumcont, reaname))
remarginal = re.compile(':\s*column\s*\d+(?i)|</?a[\s>]')



def FilterWransColnum(fout, text, sdate):
        # Legacy individual substitution rules
	text = ApplyFixSubstitutions(text, sdate, fixsubs)

        # Remove junk
        text = text.replace("{**con**}{**/con**}", "")

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
			# column numbers do get skipped during division listings

			colnum = lcolnum
                        stamp.stamp = '<stamp coldate="%s" colnum="%sW"/>' % (sdate, lcolnum)
			fout.write(' ')
                        fout.write(stamp.stamp)
			continue

		columncontg = recolnumcontvals.match(fss)
                if columncontg:
                        ldate = columncontg.group(1) or columncontg.group(3) or None
                        lcolnum = columncontg.group(2) or columncontg.group(4) or None
                        if ldate:
        			ldate = mx.DateTime.DateTimeFrom(ldate).date
        			if sdate != ldate:
	        			raise ContextException("Cont column date disagrees %s -- %s" % (sdate, fss), fragment=fss, stamp=stamp)
        			lcolnum = string.atoi(lcolnum)
        			if colnum != lcolnum and sdate<'2006-05-08':
        				raise ContextException("Cont column number disagrees %d -- %s" % (colnum, fss), fragment=fss, stamp=stamp)

        			# no need to output anything
        			fout.write(' ')
        			continue
                        if columncontg.group(5):
                                lcolnum = string.atoi(columncontg.group(5))
                                if colnum != lcolnum and colnum != lcolnum+1:
                                        raise ContextException("Cont column number disagrees %d -- %s" % (colnum, fss), fragment=fss, stamp=stamp)
                                fout.write(' ')
                                continue
                        if columncontg.group(6):
                                lcolnum = string.atoi(columncontg.group(6))
                                if colnum+1 != lcolnum:
                                        raise ContextException("Cont column number disagrees %d -- %s" % (colnum, fss), fragment=fss, stamp=stamp)
			        colnum = lcolnum
                                stamp.stamp = '<stamp coldate="%s" colnum="%sW"/>' % (sdate, lcolnum)
        			fout.write(' ')
                                fout.write(stamp.stamp)
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
                # Removed FAI 2007-05-25, I really don't care!
		#if remarginal.search(fss):
		#	raise ContextException('marginal colnum detection case',
		#	        fragment=remarginal.search(fss).group(0),
                #                stamp=stamp)

		fout.write(fss)

