# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string

import mx.DateTime

from miscfuncs import ApplyFixSubstitutions
from contextexception import ContextException

from splitheadingsspeakers import SplitHeadingsSpeakers
from splitheadingsspeakers import StampUrl

from clsinglespeech import qspeech
from parlphrases import parlPhrases

from miscfuncs import FixHTMLEntities

from filterwmsspeech import FilterWMSSpeech

# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = 	[
]

# parse through the usual intro headings at the beginning of the file.
def StripWMSHeadings(headspeak, sdate, lords):
	# check and strip the first two headings in as much as they are there
	i = 0
	if (headspeak[i][0] != 'Initial') or headspeak[i][2]:
		print headspeak[i]
		raise ContextException, 'non-conforming Initial heading '
	i += 1

	if (not re.match('(?:<stamp aname="[^"]*"/>)*written (?:ministerial)? ?statements?(?i)', headspeak[i][0])) or headspeak[i][2]:
		print headspeak[i]
		raise ContextException, 'non-conforming Initial heading (looking for "written (?:ministerial)? ?statements?")'
	elif (not re.search('<date>', headspeak[i][0])):
		i += 1

        givendate = string.replace(headspeak[i][0], "&nbsp;", " ")
        givendate = re.sub("</?i>", "", givendate)
        gd = re.match('(?:<stamp aname="[^"]*"/>)*(.*)$', givendate)
        if gd:
                givendate = gd.group(1)
	if (not lords) and ((sdate != mx.DateTime.DateTimeFrom(givendate).date) or headspeak[i][2]):
#		if (not parlPhrases.wransmajorheadings.has_key(headspeak[i][0])) or headspeak[i][2]:
		print headspeak[i]
		print sdate
		raise ContextException, 'non-conforming second heading (looking for matching datetime)'
	else:
		i += 1

	# find the url and colnum stamps that occur before anything else
	stampurl = StampUrl(sdate)
	for j in range(0, i):
		stampurl.UpdateStampUrl(headspeak[j][0])
		stampurl.UpdateStampUrl(headspeak[j][1])

	if (not stampurl.stamp) or (not stampurl.pageurl) or (not stampurl.aname):
		raise ContextException('missing stamp url at beginning of file')
	return (i, stampurl)

def NormalHeadingPart(headingtxt, stampurl, sdate, speechestxt, lords):
	bmajorheading = False

	if lords:
		bmajorheading = False
	elif not re.search('[a-z]', headingtxt) and headingtxt != 'BNFL':
		bmajorheading = True
	elif re.search('_dpthd', stampurl.aname) or re.search('_head', stampurl.aname):
		bmajorheading = True
	if re.search('_sbhd', stampurl.aname):
		bmajorheading = False
        if sdate>'2006-05-07': # Assume major heading if no speeches in new style
                bmajorheading = not speechestxt

	if bmajorheading:
                if not parlPhrases.wransmajorheadings.has_key(headingtxt.upper()):
		        raise ContextException("unrecognized major heading, please add to parlPhrases.wransmajorheadings (a)", fragment = headingtxt, stamp = stampurl)
		headingtxt = parlPhrases.wransmajorheadings[headingtxt.upper()] # no need to fix since text is from a map.

	headingtxtfx = FixHTMLEntities(headingtxt)
	qb = qspeech('nospeaker="true"', headingtxtfx, stampurl)
	if bmajorheading:
		qb.typ = 'major-heading'
	else:
		qb.typ = 'minor-heading'

	# headings become one unmarked paragraph of text
	qb.stext = [ headingtxtfx ]
	return qb

def FilterWMSSections(text, sdate, lords=False):
	text = ApplyFixSubstitutions(text, sdate, fixsubs)
	# split into list of triples of (heading, pre-first speech text, [ (speaker, text) ])
	headspeak = SplitHeadingsSpeakers(text)

	(ih, stampurl) = StripWMSHeadings(headspeak, sdate, lords)

	flatb = [ ]
	for sht in headspeak[ih:]:
		try:
			headingtxt = stampurl.UpdateStampUrl(string.strip(sht[0]))  # we're getting stamps inside the headings sometimes
			unspoketxt = sht[1]
			speechestxt = sht[2]

			if (not re.match('(?:<[^>]*>|\s|&nbsp;)*$', unspoketxt)):
				raise ContextException("unspoken text under heading in WMS", stamp=stampurl, fragment=unspoketxt)

			qbh = NormalHeadingPart(headingtxt, stampurl, sdate, speechestxt, lords)
                        flatb.append(qbh)
                        stampurl.UpdateStampUrl(unspoketxt)
			for ss in speechestxt:
# Put everything in XML, de-dupe elsewhere
#                                if lords and re.search('My (?:right )?honourable friend .*? has made the following (?:Written )?Ministerial Statement', ss[1]):
#                                        continue
				qb = qspeech(ss[0], ss[1], stampurl)
				qb.typ = 'speech'
				FilterWMSSpeech(qb)
				flatb.append(qb)

		except ContextException, e:
			raise
		except Exception, e:
			# add extra stamp info to the exception
			raise ContextException(str(e), stamp=stampurl)

	return flatb

