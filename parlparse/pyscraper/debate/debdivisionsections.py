# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string

import mx.DateTime

from splitheadingsspeakers import StampUrl
from contextexception import ContextException

from clsinglespeech import qspeech
from parlphrases import parlPhrases

from miscfuncs import FixHTMLEntities

from filterdivision import FilterDivision
from filterdebatespeech import FilterDebateSpeech

import miscfuncs
toppath = miscfuncs.toppath

housedivtxt = "The (?:House|Committee) (?:(?:having )?divided|proceeded to a Division)"
rehousediv = re.compile('<p[^>]*>(?:<i>)?\s*%s(?:</i>|:|-)+ Ayes,? (\d+), Noes (\d+)\.</p>$' % housedivtxt)

foutdivisionreports = open(os.path.join(miscfuncs.tmppath, "divreport.html"), "w")
#foutdivisionreports = None

def PreviewDivisionTextGuess(flatb):
	if not foutdivisionreports:
		return

	# loop back to find the heading title
	# (replicating the code in the publicwhip database builder, for preview)
	iTx = 1
	i = 1
	heading = "NONE"
	while i < len(flatb):
		if re.search("division", flatb[-i].typ) and iTx == 1:
			iTx = i
		if re.search("heading", flatb[-i].typ):
			heading = string.join(flatb[-i].stext)
			break
		i += 1
	# the place we search for motion text from
	if iTx == 1:
		iTx = i
 	# keep going back to find the major heading
 	if i < len(flatb) and not re.search("major-heading", flatb[-i].typ):
		while i < len(flatb):
			i += 1
			if re.search("major-heading", flatb[-i].typ):
				heading = '%s %s' % (string.join(flatb[-i].stext), heading)
				break

	divno = re.search('divnumber="(\d+)"', flatb[-1].speaker).group(1)
	link = flatb[-1].sstampurl.GetUrl()
	pwlink = 'http://www.publicwhip.org.uk/division.php?date=%s&number=%s' % (flatb[-1].sstampurl.sdate, divno)
	foutdivisionreports.write('<h2><a href="%s">Division %s</a>   <a href="%s">%s</a></h2>\n' % (pwlink, divno, link, heading))

	hdivcg = re.match('\s*<divisioncount ayes="(\d+)" noes="(\d+)"', flatb[-1].stext[0])
	hdivcayes = string.atoi(hdivcg.group(1))
	hdivcnoes = string.atoi(hdivcg.group(2))

	# check for house divided consistency in vote counting
	bMismatch = False
	hdg = rehousediv.match(flatb[-2].stext[-1])
	if hdg:
		hdivayes = string.atoi(hdg.group(1))
		hdivnoes = string.atoi(hdg.group(2))

		if (hdivayes != hdivcayes) or (hdivnoes != hdivcnoes):
			bMismatch = True

	if bMismatch:
		foutdivisionreports.write('<p><b>Mismatch Count Ayes: %d, Count Noes: %d.</b></p>\n' % (hdivcayes, hdivcnoes))

	# write out the detected motion text for Francis
	while iTx >= 3:
		iTx -= 1
		j = 0
		while j < len(flatb[-iTx].stext):
			if re.search('pwmotiontext="yes"', flatb[-iTx].stext[j]):
				foutdivisionreports.write("%s\n" % flatb[-iTx].stext[j])
			j += 1

	foutdivisionreports.flush()

# handle a division case
strexplicitenddiv = '<explicit-end-division>'
regenddiv = '(The (?:Deputy )?Speaker declared|Question accordingly|It appearing on the [Rr]eport|%s)' % strexplicitenddiv
def DivisionParsingPart(divno, unspoketxt, stampurl, sdate):
	# find the ending of the division and split it off.
	gquesacc = re.search(regenddiv, unspoketxt)
	if gquesacc:
		divtext = unspoketxt[:gquesacc.start(1)]
		unspoketxt = unspoketxt[gquesacc.start(1):]
		if re.match(strexplicitenddiv, unspoketxt):  # strip off signal tag
			unspoketxt = unspoketxt[len(strexplicitenddiv):]
	else:
		divtext = unspoketxt
		print unspoketxt
		print "division missing %s" % regenddiv
		print "try inserting <explicit-end-division>"
		unspoketxt = ''

	# Add a division object (will contain votes and motion text)
	spattr = 'nospeaker="true" divdate="%s" divnumber="%s"' % (sdate, divno)
	qbd = qspeech(spattr, divtext, stampurl)
	qbd.typ = 'division' # this type field seems easiest way

	# filtering divisions here because we may need more sophisticated detection
	# of end of division than the "Question accordingly" marker.
	qbd.stext = FilterDivision(qbd.text, stampurl, sdate)

	return (unspoketxt, qbd)


# pull out the lines in the previous speech
#	<p><i>Question put,</i> That the amendment be made: &mdash; </p>
#	<p class="announce-division" ayes="145" noes="366"><i>The House divided:</i> Ayes 145, Noes 366.</p>
rehousedivmarginal = re.compile('house divided.*?ayes.*?Noes')

# these are cases where a division is a correction, so there is no text above
# (in the database the result gets substituted)
redivshouldappear = re.compile('.*?Division .*? should appear as follows:|.*?in col.*?insert')

#<a name="40316-33_para15"><i>It being Seven o'clock,</i> Madam Deputy Speaker <i>put the Question already proposed from the Chair, pursuant to Order [5 January].</i>
regqput = ".*?question put.*?</p>"
regqputt = ".*?question, .*?, put.*?</p>"
regitbe = "(?:<[^>]*>|\s)*It being .*?o'clock(?i)"
regitbepq = "(?:<[^>]*>|\s)*It being .*? hours .*? put the question(?i)"
regitbepq1 = "(?:<[^>]*>|\s)*It being .*? (?:hour|minute).*?(?i)"
reqput = re.compile('%s|%s|%s|%s|%s(?i)' % (regqput, regqputt, regitbe, regitbepq1, regitbepq))

# this hack sets the motion text flag on a set of paragraphs
# for use by the publicwhip motion text stuff
def SubsPWtextset(stext):
	res = [ ]
	for st in stext:
		if re.search('pwmotiontext="yes"', st) or not re.match('<p', st):
			res.append(st)
		else:
			res.append(re.sub('<p(.*?)>', '<p\\1 pwmotiontext="yes">', st))
	return res

def GrabDivisionProced(qbp, qbd):
	if qbp.typ != 'speech' or len(qbp.stext) < 1:

		# this is that crazy correction one
		if qbp.sstampurl.sdate == '2003-12-18':
			return None

		print qbp.stext
		raise Exception, "previous to division not speech"

        qbp.stext[-1] = re.sub(' </i><i> ', ' ', qbp.stext[-1])
        qbp.stext[-1] = re.sub('</i><i> ', ' ', qbp.stext[-1])
	hdg = rehousediv.match(qbp.stext[-1])
	if not hdg:
		hdg = redivshouldappear.match(qbp.stext[-1])
	if not hdg:
		# another correction one
		if qbp.sstampurl.sdate != '2003-09-16':
			raise ContextException, "no house divided before division: %s" % qbp.stext[-1]
		return None

	# if previous thing is already a no-speaker, we don't need to break it out
	# (the coding on the question put is complex and multilined)
	if re.search('nospeaker="true"', qbp.speaker):
		qbp.stext = SubsPWtextset(qbp.stext)
		return None

	# look back at previous paragraphs and skim off a part of what's there
	# to make a non-spoken bit reporting on the division.
	iskim = 1
	if re.search('Serjeant at Arms|peaceful outcome', qbp.stext[-2]):
		pass
	else:
		while len(qbp.stext) >= iskim:
			if reqput.match(qbp.stext[-iskim]):
				break
			iskim += 1

		# haven't found a question put before we reach the front
		if len(qbp.stext) < iskim:
			iskim = 1
			# VALID in 99% of cases: raise Exception, "no question put before division"

	# copy the two lines into a non-speaking paragraph.
	qbdp = qspeech('nospeaker="true"', "", qbp.sstampurl)
	qbdp.typ = 'speech'
	qbdp.stext = SubsPWtextset(qbp.stext[-iskim:])


	# trim back the given one by two lines
	qbp.stext = qbp.stext[:-iskim]

	return qbdp

