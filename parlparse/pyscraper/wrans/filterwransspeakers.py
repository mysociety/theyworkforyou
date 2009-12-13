# -*- coding: latin-1 -*-
# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string
from resolvemembernames import memberList
from splitheadingsspeakers import StampUrl

from miscfuncs import ApplyFixSubstitutions
from contextexception import ContextException

# this filter finds the speakers and replaces with full itendifiers
# <speaker name="Eric Martlew  (Carlisle)"><p>Eric Martlew  (Carlisle)</p></speaker>

# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = 	[
        ( 'Mr. Jim McNulty', 'Mr. McNulty', 1, '2003-01-07'),

	( '<UL>(Beverley Hughes):', '<B>\\1</B>', 1, '2003-04-10'),

	( '<UL>\(1\) (Tim Loughton): (To ask the Deputy Prime Minister) (how many times he has been in residence at Dorneywood since June 2001; and on what dates;  \[97476\])', '<B>\\1</B> \\2 (1) \\3 <p><UL>', 1, '2003-03-28'),
	( '<UL>Mr. Denham:', '<B>Mr. Denham</B>', 1, '2003-03-06'),


	( '<TR valign=top><TD><FONT SIZE=-1>\s*<P>\s*<page', '</TABLE>\n<page', 1, '2002-07-24'),
	( '<UL>Paul Goggins:([^<]*)<P></UL>', '<B>Paul Goggins:</B> \\1', 1, '2003-11-19'),
	( '\): To ask', ' To ask', 1, '2003-05-06'),


        ( ' \((141053)\)', '[\\1]', 1, '2004-01-05'),

        ( ' \((142642)\)', '[\\1]', 1, '2003-12-18'),

        ( '(David Davis: ):', '\\1', 1, '2003-12-15'),

	# completely delete an answer that refers to the next response as answer
	( '<B> Mr. Jamieson </B>[\s\S]*?\[114072\]\.', '', 1, '2003-06-03'),
        ( '<B> Mr. McNulty: </B>\s*? I refer the hon. Member to the answer given today \[144178\].', '', 1, '2003-12-18'),

        ( 'McLoughin', 'McLoughlin', 1, '2003-03-07'), 

	( '<UL>\(5\)', '(5)', 1, '2003-02-06'),
	( '<UL>(We are intending to have)', '<B>Beverley Hughes</B> \\1', 1, '2003-01-29'), 

	# this removes a bogus y-dotdot character that the latin-1 encoding can't deal with
	( 'credit of .45 per', 'credit of 45 per', 1, '2002-12-02'),
	( 'units of .5,000 or more', 'units of 5,000 or more', 1, '2002-11-26'),
	( 'available .23 million', 'available 23 million', 1, '2002-11-26'),
	( 'approved a .4 million', 'approved a 4 million', 1, '2002-11-26'),
	( '<FONT SIZE=-1>[^\d]0\.3\s*</FONT>', '<FONT SIZE=-1>0.3</FONT>', 2, '2002-10-22'), # note the 2; the problem is a dropped dot
	( 'Larch 3 .2305', 'Larch 3 ?2305', 1, '2003-06-04'), # as above

        ( '<B>  Howells: </B>', '<B> Dr. Howells: </B>', 1, '2003-02-24'),

        ( 'è', '&euro;', 3, '2003-06-12'),
        ( '(16. )(Keith Vaz: )(To ask)', '\\1<B>\\2</B>\\3', 1, '2003-06-12'),

]

def FilterWransSpeakers(fout, text, sdate):
	text = ApplyFixSubstitutions(text, sdate, fixsubs)

        # Fix things like this, to put bold in. We use bold below to detect names, but
        # occasionally the reporters miss it out, and we catch such cases here:
        # <p><a name="qnpa_0">Caroline Flint: This information is not held centrally. </p>
        # <p><a name="qnpa_15">Ms Harman: The information can be found in the following table. </p>
        missingbolds = re.findall('(\n?<p>(?:<stamp aname="[^"]+"/>)+)((?:<b></b>)?\s*)([A-Za-z.\-\s]+)(:\s)', text)
        for p1,p2,p3,p4 in missingbolds:
                missingbold = "%s%s%s%s" % (p1,p2,p3,p4)
                bold = "%s<b>%s%s</b>" % (p1,p3,p4)
                namematches = memberList.fullnametoids(p3, sdate)
                # Only fix if we found a matching name in the middle (and do it even if ambiguous)
                if namematches:
                        #print "Fixing missing bold, had name matches:\n\t%s\n\t%s" % (missingbold.strip(), bold.strip())
                        if not missingbold in text:
                                print "ERROR: missing bold text found, but then vanished when replacing"
                        text = text.replace(missingbold, bold)
                #else:
                        #print "Plausible missing bold not fixed, as no name matches:\n\t%s\n\t%s" % (missingbold.strip(), bold.strip())

	# <B> Mrs. Iris Robinson: </B>
	lspeakerregexp = '<b>.*?</b>(?:\s*:)?'
	ltableregexp = '<table[^>]*>[\s\S]*?</table>'	# these have bolds, so must be separated out
	tableregexp = ltableregexp + '(?i)'

	lregexp = '(%s|%s)(?i)' % (ltableregexp, lspeakerregexp)

	# setup for scanning through the file.
	fs = re.split(lregexp, text)

        # for error messages
	stampurl = StampUrl(sdate)


	for i in range(len(fs)):
		fss = fs[i]
		fss = stampurl.UpdateStampUrl(fss) # Speakers have new stamps in them

		if re.match(tableregexp, fss):
			continue

		speakerg = re.findall('<b>\s*([^:]*)[:\s]*?([^<:]*)</b>(?i)', fss)
		if not speakerg:
			continue

		# we have a string in bold
		boldnamestring = string.strip(speakerg[0][0])

		# trailing text after the colon in the bold speech bit
		if re.search('\S', speakerg[0][1]):
			fs[i+1] = speakerg[0][1] + fs[i+1]


		# push the square brackets outside of the boldstring if there is one
		# <B> Mr. Miliband [ </B> <i>holding answer 24 March</i>]:
		sqb = re.findall('^([^\[]*)(\[.*)$', boldnamestring)
		if sqb:
			boldnamestring = string.strip(sqb[0][0])
			fs[i+1] = sqb[0][1] + fs[i+1]

		# get rid of blank bold strings
		if not re.search('\S', boldnamestring):
			fs[i] = ''
			continue

		# try to pull in the question number if preceeding
		# These signify aborted oral questions, and are normally
		# useless and at the start of the page.
		# 27. <B> Mr. Steen: </B>
		if i > 0:
			oqnsep = re.findall('^([\s\S]*?)Q?(\d+\.?)(\s*?(?:<stamp aname=".*?"/>)?)$', fs[i-1])
			if oqnsep:
				fs[i-1] = oqnsep[0][0] + oqnsep[0][2]
				boldnamestring = oqnsep[0][1] + ' ' + boldnamestring

		# take out the initial digits and a dot which we may have just put in
		# (although sometimes it would have already been there)
		robj = re.match(r"(\d*\.? )(.*)$", boldnamestring)
		deci = None
		if robj:
			(deci, boldnamestring) = robj.groups()
			# TODO: do something with deci here (it is the "failed
			# oral questions" signifier)

		# see if it is an explicitly bad/ambiguous name which will never match
		if boldnamestring.find('<broken-name>') >= 0:
			id = 'unknown'
			boldnamestring = boldnamestring.replace('<broken-name>', '')
			remadename = ' speakername="%s" error="Name ambiguous in Hansard"' % (boldnamestring)
		else:
			# split bracketed cons out if present
			brakmatch = re.match("(.*)\s+\((.*)\)", boldnamestring)
			if brakmatch:
				(name, cons) = brakmatch.groups()
			else:
				(name, cons) = (boldnamestring, None)

			# match the member to a unique identifier
			(id, remadename, remadecons) = memberList.matchfullnamecons(name, cons, sdate, alwaysmatchcons = False)
			if id and remadename:
				remadename = ' speakername="%s"' % (remadename)
			if not id:
				if remadename == "MultipleMatch":
                                        if boldnamestring == 'Mr. Michael Foster':
                                                if remadecons[1] == 'uk.org.publicwhip/member/1939':
                                                        id = remadecons[1]
                                                        remadename = ' speakername="Michael Foster"'
                                                        remadecons = 'Worcester'
                                                elif remadecons[0] == 'uk.org.publicwhip/member/896':
                                                        id = remadecons[0]
                                                        remadename = ' speakername="Michael Foster"'
                                                        remadecons = 'Worcester'
                                        else:
        					id = 'unknown'
        					remadename = ' speakername="%s" error="MultipleMatch"' % boldnamestring
				else:
					print "  No name,const match (%s,%s)" % (name, cons)
					raise ContextException("No name match", stamp=stampurl, fragment=boldnamestring)


		# put record in this place
		fs[i] = '<speaker speakerid="%s"%s>%s</speaker>\n' % \
				(id.encode("latin-1"), remadename.encode("latin-1"), boldnamestring)


	# scan through everything and output it into the file
	fout.writelines(fs)

