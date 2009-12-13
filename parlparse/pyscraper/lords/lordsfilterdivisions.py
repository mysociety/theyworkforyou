# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string


import mx.DateTime

from splitheadingsspeakers import StampUrl

from clsinglespeech import qspeech
from parlphrases import parlPhrases

from miscfuncs import FixHTMLEntities

from filterdivision import FilterDivision
from filterdebatespeech import FilterDebateSpeech

from resolvelordsnames import lordsList

from contextexception import ContextException


recontma = re.compile('<center><b>(.*?)\s*</b></center>(?i)')
retellma = re.compile('(.*?)\s*(?:<I>)?\[(Teller)\](?:</I>)?$')
reoffma = re.compile('(.*?\S)\s*(?:<I>)?[\[\(](.*?)[\)\]](?:</I>)?$')
def LordsFilterDivision(text, stampurl, sdate):

	# the intention is to splice out the known parts of the division
	fs = re.split('\s*(?:<br>|</?p>)\s*(?i)', text)

	contentlords = [ ]
	notcontentlords = [ ]
	contstate = ''

	for fss in fs:
		if not fss:
			continue
		cfs = recontma.match(fss)
		if cfs:
			if cfs.group(1) == "CONTENTS":
				assert contstate == ''
				contstate = 'content'
			elif cfs.group(1) == 'NOT-CONTENTS' or cfs.group(1) == 'NOT CONTENTS':
				assert contstate == 'content'
				contstate = 'not-content'
			else:
				print "$$$%s$$$" % cfs.group(1)
				raise ContextException("unrecognised content state", stamp=stampurl, fragment=fss)

		elif re.match("(?:\[\*|\*\[)[Ss]ee col\. \d+\]", fss):
			print "Disregarding cross-reference in Division", fss
		elif re.match("\[\*\s*The Tellers.*?[Tt]he Clerks.*?\]", fss):
			print "Disregarding clerk comment on numbers", fss
		elif re.match("\[\*\s*The name of a .*? removed from the voting lists\.\]", fss):
			print "Disregarding removed from list comment", fss

		else:
			if not contstate:
				raise ContextException("empty contstate", stamp=stampurl, fragment=fss)

			# split off teller case
			teller = retellma.match(fss)
			tels = ''
			lfss = fss
			if teller:
				lfss = teller.group(1)
				tels = ' teller="yes"'

			# strip out the office
			offm = reoffma.match(lfss)
			if offm:
				lfss = offm.group(1)
			if not lfss:
				raise ContextException("no name on line", stamp=stampurl, fragment=fss)
			lordid = lordsList.MatchRevName(lfss, sdate, stampurl)
			lordw = '\t<lord id="%s" vote="%s"%s>%s</lord>' % (lordid, contstate, tels, FixHTMLEntities(fss))

			if contstate == 'content':
				contentlords.append(lordw)
			else:
				notcontentlords.append(lordw)

	# now build up the return value
	stext = [ ]
	stext.append('<divisioncount content="%d" not-content="%d"/>' % (len(contentlords), len(notcontentlords)))
	stext.append('<lordlist vote="content">')
	stext.extend(contentlords)
	stext.append('</lordlist>')
	stext.append('<lordlist vote="not-content">')
	stext.extend(notcontentlords)
	stext.append('</lordlist>')

	return stext


# handle a division case (resolved comes from the lords)
regenddiv = '(Resolved in the|:ENDDIVISION:)'
def LordsDivisionParsingPart(divno, unspoketxt, stampurl, sdate):
	# find the ending of the division and split it off.
	gquesacc = re.search(regenddiv, unspoketxt)
	if gquesacc:
		divtext = unspoketxt[:gquesacc.start(1)]
		unspoketxt = unspoketxt[gquesacc.start(1):]
                unspoketxt = re.sub(':ENDDIVISION:', '', unspoketxt)
	elif sdate > '2008-12-01': # Sigh XXX
                m = re.match('.*<br>(?s)', unspoketxt)
                divtext = m.group()
                unspoketxt = unspoketxt[m.end():]
	else:
		divtext = unspoketxt
		print "division missing %s" % regenddiv
		print unspoketxt
		print "is there a linefeed before the </center> on the CONTENTS?"
		raise ContextException("Division missing resolved in the", stamp=stampurl, fragment="Division") # newly added
		unspoketxt = ''

	# Add a division object (will contain votes and motion text)
	spattr = 'nospeaker="true" divdate="%s" divnumber="%s"' % (sdate, divno)
	qbd = qspeech(spattr, divtext, stampurl)
	qbd.typ = 'division' # this type field seems easiest way

	if not stampurl.timestamp:
		raise ContextException("Division missing any timestamps; need to put one in to make it consistent.  like <h5>2.44 pm</h5>", stamp=stampurl, fragment="Division")

	# filtering divisions here because we may need more sophisticated detection
	# of end of division than the "Question accordingly" marker.
	qbd.stext = LordsFilterDivision(qbd.text, stampurl, sdate)

	return (unspoketxt, qbd)

