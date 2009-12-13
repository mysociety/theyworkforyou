# vim:sw=8:ts=8:et:nowrap

import sys
import re
import os
import string
import copy

import mx.DateTime

from miscfuncs import ApplyFixSubstitutions
from contextexception import ContextException

from splitheadingsspeakers import SplitHeadingsSpeakers
from splitheadingsspeakers import StampUrl

from clsinglespeech import qspeech
from parlphrases import parlPhrases

from miscfuncs import FixHTMLEntities, IsNotQuiet

from filterdebatespeech import FilterDebateSpeech

from debdivisionsections import PreviewDivisionTextGuess
from debdivisionsections import DivisionParsingPart
from debdivisionsections import GrabDivisionProced

# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = 	[

        ( 'Taylor, Andrew', 'Turner, Andrew', 1, '2003-02-26'),
        ( '(Brown, Russell),', '\\1', 1, '2003-09-10'),
        ( 'Baird Vera', 'Baird, Vera', 1, '2003-09-10'),
        ( 'Livingston\)', '(Livingston)', 1, '2003-10-27'),
        ( '<BR>\n, David', '<BR>\nBorrow, David', 1, '2003-11-18'),
	( "(<H3 align=center>TENTH VOLUME OF SESSION 2002&#150;2003)(House of Commons</H3>)", \
		'\\1</H3>\n<H3 align=center>\\2', 1, '2003-04-07'),
	( "(<H3 align=center>NINTH VOLUME OF SESSION 2002&#150;2003)ana(House of Commons</H3>)", \
		'\\1</H3>\n<H3 align=center>\\2', 1, '2003-03-24'),
        ( '(2003)(House of Commons)', '\\1</center></H4>\n<H4><center>\\2', 1, '2003-06-03'),
        ( '(2003)(House of Commons)', '\\1</center></H4>\n<H4><center>\\2', 1, '2003-05-12'),
        ( '(2003)(House of Commons)', '\\1</center></H4>\n<H4><center>\\2', 1, '2003-04-28'),


	( '<i> </i>', '', 1, '2003-01-27'),

	( '<UL><UL>Adjourned', '</UL><UL><UL><UL>Adjourned', 1, '2003-05-22'), # putting a consistent error back in
	( '<UL><UL>End', '</UL><UL><UL><UL>End', 1, '2002-11-07'), # as above
        ( '<UL><UL>', '<UL><UL><UL>', 1, '2003-06-25'),

	( '<UL><UL><UL>(?i)', '<UL>', -1, 'all'),
	( '</UL></UL></UL>(?i)', '</UL>', -1, 'all'),
		]

# parse through the usual intro headings at the beginning of the file.
#[Mr. Speaker in the Chair] 0
def StripDebateHeading(hmatch, ih, headspeak, bopt=False):
	# print "StripDebateHeading", hmatch
	reheadmatch = '(?:<stamp aname="[^"]*"/>)*\s*' + hmatch
	if (not re.match(reheadmatch, headspeak[ih][0])) or headspeak[ih][2]:
		if bopt:
			return ih
		print "headspeak", headspeak[ih][:2]
		if headspeak[ih][2]:
			raise ContextException('non-conforming section after "%s" heading. FOR EXAMPLE: "in the chair" missing <h4><center> ' % hmatch, fragment=headspeak[ih][0])
		print reheadmatch
		print headspeak[ih][2]
		raise ContextException('non-conforming "%s" heading ' % hmatch, fragment=headspeak[ih][0])
	return ih + 1

def StripDebateHeadings(headspeak, sdate):
	# check and strip the first two headings in as much as they are there
	ih = 0
	ih = StripDebateHeading('Initial', ih, headspeak)  # the 'Initial' is inserted by the splitheadingsspeakers function

	# volume type heading
	if re.search('THE$', headspeak[ih][0]):
		ih = StripDebateHeading('THE', ih, headspeak)
		ih = StripDebateHeading('PARLIAMENTARY(?:&nbsp;)+DEBATES', ih, headspeak)
	elif re.search('THE PARLIAMENTARY DEBATES', headspeak[ih][0]):
		ih = StripDebateHeading('THE PARLIAMENTARY DEBATES', ih, headspeak)
	if re.search('OFFICIAL REPORT', headspeak[ih][0]):
		ih = StripDebateHeading('OFFICIAL REPORT', ih, headspeak)
		ih = StripDebateHeading('IN THE .*? SESSION OF THE .*? PARLIAMENT OF THE', ih, headspeak, True)
		ih = StripDebateHeading('UNITED KINGDOM OF GREAT BRITAIN AND NORTHERN IRELAND', ih, headspeak, True)
		ih = StripDebateHeading('\[WHICH OPENED .*?\]', ih, headspeak, True)
		ih = StripDebateHeading('.*? YEAR OF THE REIGN OF.*?', ih, headspeak, True)
		ih = StripDebateHeading('HER MAJESTY QUEEN ELIZABETH II', ih, headspeak, True)
                ih = StripDebateHeading('SI.*? SERIES.*?VOLUME \d+', ih, headspeak, True)
		ih = StripDebateHeading('SI.*? SERIES', ih, headspeak, True)
		ih = StripDebateHeading('VOLUME \d+', ih, headspeak, True)
		ih = StripDebateHeading('.*? VOLUME OF SESSION .*?', ih, headspeak)


	#House of Commons
	ih = StripDebateHeading('house of commons(?i)', ih, headspeak)

	# Tuesday 9 December 2003
	if not re.match('the house met at .*(?i)', headspeak[ih][0]):
                givendate = re.sub('&nbsp;',' ',headspeak[ih][0])
                givendate = re.sub('</?i>',' ', givendate)
                gd = re.match('(?:<stamp aname="[^"]*"/>)*(.*)$(?i)', givendate)
                if gd:
                        givendate = gd.group(1)
		if ((sdate != mx.DateTime.DateTimeFrom(givendate).date)) or headspeak[ih][2]:
			raise Exception, 'date heading %s mismatches with date %s' % (repr(headspeak[ih]), sdate)
		ih = ih + 1

        gstarttime = None
        if sdate != "2001-06-13":
                #The House met at half-past Ten o'clock
                gstarttime = re.match('(?:<stamp aname="[^"]*"/>)*(?:<i>)?\s*the\s+house (?:being |having )?met at?\s+(?:</i><i>\s*)?(.*?)(?:, and the Speaker-Elect having taken the Chair;)?(?:</i>)?$(?i)', headspeak[ih][0])
                if (not gstarttime) or headspeak[ih][2]:
                        raise ContextException('non-conforming "the house met at" heading %s' % repr(headspeak[ih]), "")
                ih = ih + 1

        # Start of a new parliament is special
        if sdate not in ["2001-06-14", "2001-06-13", "2005-05-11", "2005-05-12"]:

                #PRAYERS
                ih = StripDebateHeading('prayers(?i)', ih, headspeak, True)

                ih = StripDebateHeading('pursuant to the Standing Order\.', ih, headspeak, True)

                # in the chair
                ih = StripDebateHeading('\[.*?[ >]in the chair[<>i/\.]*\](?i)', ih, headspeak, True)

	# find the url, colnum and time stamps that occur before anything else in the unspoken text
	stampurl = StampUrl(sdate)

	# set the time from the wording 'house met at' thing.
        if gstarttime:
                time = gstarttime.group(1)
                time = re.sub('</?i>',' ', time)
                time = re.sub('\s+',' ', time)
                if re.match("^half-past Nine(?i)", time):
                        newtime = '09:30:00'
                elif re.match("^half-past Ten(?i)", time):
                        newtime = '10:30:00'
                elif re.match("Eleven o&#039;clock(?i)", time):
                        newtime = '11:00:00'
                elif re.match("^twenty-five minutes past\s*Eleven(?i)", time):
                        newtime = '11:25:00'
                elif re.match("^twenty-six minutes past\s*Eleven(?i)", time):
                        newtime = '11:26:00'
                elif re.match("^half-past Eleven(?i)", time):
                        newtime = '11:30:00'
                elif re.match("^half-past Two(?i)", time):
                        newtime = '14:30:00'
                elif re.match("^Ten o'clock(?i)", time):
                        newtime = '10:00:00'
                elif re.match("^Six o'clock(?i)", time):
                        newtime = '18:00:00'
                else:
                        newtime = "unknown " + time
                        raise ContextException, "Start time not known: " + time
                stampurl.timestamp = '<stamp time="%s"/>' % newtime

	for j in range(0, ih):
		stampurl.UpdateStampUrl(headspeak[j][1])

	if (not stampurl.stamp) or (not stampurl.pageurl):
		raise Exception, ' missing stamp url at beginning of file '
	return (ih, stampurl)


def StripWestminhallHeadings(headspeak, sdate):
	# check and strip the first two headings in as much as they are there
	ih = 0
	ih = StripDebateHeading('Initial', ih, headspeak)

	# Westminster Hall
	ih = StripDebateHeading('westminster hall(?i)', ih, headspeak)

	# date line
        givendate = re.sub('</?i>',' ', headspeak[ih][0])
        gd = re.match('(?:<stamp aname="[^"]*"/>)*(.*)$(?i)', givendate)
        if gd:
                givendate = gd.group(1)
	if ((sdate != mx.DateTime.DateTimeFrom(givendate).date)) or headspeak[ih][2]:
		raise Exception, 'date heading %s mismatches with date %s' % (repr(headspeak[ih]), sdate)
	ih = ih + 1

	# next line is:
	# <H3><center>[Mr. John McWilliam in the Chair]</center></H3>
	# but we leave it as a title.

	# find the url, colnum and time stamps that occur before anything else in the unspoken text
	stampurl = StampUrl(sdate)
	stampurl.timestamp = '<stamp time="%s"/>' % "unknown"

	for j in range(0, ih):
		stampurl.UpdateStampUrl(headspeak[j][1])

	if (not stampurl.stamp) or (not stampurl.pageurl):
		raise Exception, ' missing stamp url at beginning of file '
	return (ih, stampurl)


# Handle normal type heading
def NormalHeadingPart(headingtxt, stampurl, state):
	# This is an attempt at major heading detection.
        # The main wrap code spots adjournment debates, and does its best with some procedural things
        # But it's pretty flawed Also, Oral questions heading is a super-major heading,
	# so doesn't fit into the scheme.

	# remove junk italic settings that appear in the today pages
	headingtxt = re.sub("</?(?:i|sup)>(?i)", "", headingtxt)

	# detect if this is a major heading and record it in the correct variable

	bmajorheading = False
	boralheading = False
	binsertedheading = False

	if re.search('-- lost heading --(?i)', headingtxt):
		binsertedheading = True

	# Oral question are really a major heading
	elif re.match("Oral Answers to Questions(?i)", headingtxt):
		boralheading = True
	# Check if there are any other spellings of "Oral Answers to Questions" with a loose match
	elif re.search('oral(?i)', headingtxt) and re.search('ques(?i)', headingtxt) and (not re.search(" Not ", headingtxt)) and \
                        (not re.search("electoral", headingtxt)) and \
			stampurl.sdate != "2002-06-11": # has a genuine title with Oral in it
		print headingtxt
		raise ContextException('Oral question match not precise enough', stamp=stampurl, fragment=headingtxt)

	# All upper case headings - UGH
	elif not re.search('[a-z]', headingtxt) and not re.match('[A-Z\d/]+[\d/][A-Z\d/]+$', headingtxt) and not \
            ('remaining_private_bills' in state and re.search(' Bill$(?i)', headingtxt)):
		bmajorheading = True

	elif 'just_had_points_of_order' in state:
		bmajorheading = True
		del state['just_had_points_of_order']

	# If this is labeled major, then it gets concatenated with the
	# subsequent major heading.  It's kind of a procedural info about the
	# running of things, so fair to have it as a minor heading alone.
	elif re.match("\[.*? in\s*the\s*Chair\.?\]$(?i)", headingtxt):
		bmajorheading = False

	elif re.search("in\s*the\s*chair(?i)", headingtxt):
		print headingtxt
		raise ContextException('in the chair match not precise enough', stamp=stampurl, fragment=headingtxt)

	# Other major headings, marked by _head in their anchor tag
	elif re.search('^hd_|_head', stampurl.aname):
		bmajorheading = True

        # Wah
        if stampurl.sdate > '2006-05-07':
                if re.match("(Private business|Business of the House|Orders of the day|Opposition Day|Deferred Division|Petition)(?i)", headingtxt):
                        bmajorheading = True
                if re.match("Points? of Order(?i)", headingtxt):
                        bmajorheading = True
                        state['just_had_points_of_order'] = True
                if re.match("Remaining Private Members[^ ]* Bills(?i)", headingtxt):
                        bmajorheading = True
                        state['remaining_private_bills'] = True

	# we're not writing a block for division headings
	# write out block for headings
	headingtxtfx = FixHTMLEntities(headingtxt)
	assert not re.search("[<>]", headingtxtfx)  # an assertion in gidmatching
	qb = qspeech('nospeaker="true"', headingtxtfx, stampurl)
	if binsertedheading:
		qb.typ = 'inserted-heading'
	elif boralheading:
		qb.typ = 'oral-heading'
	elif bmajorheading:
		qb.typ = 'major-heading'
	else:
		qb.typ = 'minor-heading'

	# headings become one unmarked paragraph of text
	qb.stext = [ headingtxtfx ]
	return qb


# designed to capture the section
#Sitting suspended for a Division in the House.
#On resuming-
def GrabWestminDivisionInterruptProced(qbp, rawtext):
	if len(qbp.stext) < 3:
		return None
	iskip = 0
	if re.search("italic.*?>on resuming&\S*</p>(?i)", qbp.stext[-1]):
		if not re.search("italic.*?>sitting suspended(?: for (?:a division|divisions) in the house)?[\.\s]*(?i)", qbp.stext[-2]):
			print "failed to detect sitting suspended interruption"
			print qbp.stext[-2]
			assert False
		iskip = -2

	elif re.search("italic.*?>sitting suspended(?: for| until| till|\.)(?i)", qbp.stext[-1]):
		iskip = -1

	# copy the lines into a non-speaking paragraph.
	if iskip:
		dumtext = re.sub('<p>(?:<stamp aname="[^"]*?"/>)?<i>sitting suspended.*(?si)','',rawtext)
                # Why didn't I make a note of why I did the following lines? Must be something to do with the timestamps...
		s = copy.copy(qbp.sstampurl)
		qbdp = qspeech('nospeaker="true"', dumtext, s)
		qbdp = qspeech('nospeaker="true"', "", s)
		qbdp.typ = 'speech'
		qbdp.stext = qbp.stext[iskip:]
		# trim back the given one by two lines
		qbp.stext = qbp.stext[:iskip]
		return qbdp
	return None


################
# main function
################
def FilterDebateSections(text, sdate, typ):
	# make the corrections at this level which enables the headings to be resolved.
	# old style fixing (before patches existed)
	if typ == "debate":
		text = ApplyFixSubstitutions(text, sdate, fixsubs)
	else:
		assert typ == "westminhall"
		# this is crap!!!
		text = re.sub('<ul><ul><ul>(?i)', '<ul>', text)
		text = re.sub('</ul></ul></ul>(?i)', '</ul>', text)
		text = re.sub('<h5></h5>(?i)', '', text)


	# split into list of triples of (heading, pre-first speech text, [ (speaker, text) ])
	headspeak = SplitHeadingsSpeakers(text)

	# break down into lists of headings and lists of speeches
	if typ == "debate":
		(ih, stampurl) = StripDebateHeadings(headspeak, sdate)
	elif typ == "westminhall":
		(ih, stampurl) = StripWestminhallHeadings(headspeak, sdate)
	else:
		assert False # to be for writminstat?

	# loop through each detected heading and the detected partitioning of speeches which follow.
	# this is a flat output of qspeeches, some encoding headings, and some divisions.
	# see the typ variable for the type.
	flatb = [ ]
        state = {}
        #lastheading = None
	for sht in headspeak[ih:]:
		try:
			# triplet of ( heading, unspokentext, [(speaker, text)], major? )
			headingtxt = stampurl.UpdateStampUrl(string.strip(sht[0]))  # we're getting stamps inside the headings sometimes
                        headingmajor = sht[3]
                        if typ == 'debate' and (headingmajor or sht == headspeak[-1]): # UGH again
                                headingtxt = headingtxt.upper()
			unspoketxt = sht[1]
			speechestxt = sht[2]

			# the heading detection, as a division or a heading speech object
			# detect division headings
			gdiv = re.match('Division No. (\d+)(?i)', headingtxt)

			# heading type
			if not gdiv: # and lastheading != headingtxt:
				qbh = NormalHeadingPart(headingtxt, stampurl, state)
        			# print "h ", qbh.typ, qbh.stext

        			# ram together minor headings into previous ones which have no speeches
        			if qbh.typ == 'minor-heading' and len(flatb) > 0 and flatb[-1].typ == 'minor-heading':
        				flatb[-1].stext.append(" &mdash; ")
        				flatb[-1].stext.extend(qbh.stext)

        			# ram together major headings into previous ones which have no speeches
        			elif qbh.typ == 'major-heading' and len(flatb) > 0 and flatb[-1].typ == 'major-heading':
        				flatb[-1].stext.append(" &mdash; ")
	        			flatb[-1].stext.extend(qbh.stext)

                                elif qbh.typ == 'minor-heading' and len(flatb) > 0 and flatb[-1].typ == 'major-heading' and \
                                    ( re.search('Allotted Day(?i)', qbh.stext[-1]) or re.search('^Petition$(?i)', flatb[-1].stext[-1]) ):
                                        flatb[-1].stext.append(" &mdash; ")
                                        flatb[-1].stext.extend(qbh.stext)

                                elif re.search("(?:sitting suspended(?: for| until| till|\.))|(on resuming&)(?i)", qbh.stext[0]):
                                        if len(flatb) > 0 and flatb[-1].typ == 'speech':
        			                qb = qspeech('nospeaker="true"', qbh.stext[0], stampurl)
                        			qb.typ = 'speech'
                        			FilterDebateSpeech(qb)
                        			flatb.append(qb)

	        		# this is where we suck in a trailing "Clause" part of the title that is mistakenly outside the heading.
        			elif (qbh.typ == 'minor-heading' or qbh.typ == 'major-heading') and len(flatb) > 0 and flatb[-1].typ == 'speech':
        				mmm = re.match('\s*<p>((?:New )?(?:clause|schedule) \d+\w?)</p>(?i)', flatb[-1].stext[-1])
        				if mmm:
        					if IsNotQuiet():
        						print "Clause/schedule moving", flatb[-1].stext[-1]
        					qbh.stext.insert(0, " &mdash; ")
        					qbh.stext.insert(0, mmm.group(1))
        					flatb[-1].stext = flatb[-1].stext[:-1]  # delete final value

        					# remove an empty speech
        					if not flatb[-1].stext:
        						if IsNotQuiet():
        							print "removing empty speech after moving 'clause/schedule' out"
        						assert flatb[-1].speaker == 'nospeaker="true"'
        						del flatb[-1]

        				# converting a search into a match, for safety, and double checking
	        			else:
		        			if re.search('<p>\s*((?:New )?\s*(?:clause|schedule)\s*\w+)\s*</p>(?i)', flatb[-1].stext[-1]):
			        			print flatb[-1].stext[-1]
				       			assert False

				        flatb.append(qbh)

        			# otherwise put out this heading
        			else:
        				flatb.append(qbh)

			# division case
			elif gdiv:
				(unspoketxt, qbd) = DivisionParsingPart(string.atoi(gdiv.group(1)), unspoketxt, stampurl, sdate)

				# grab some division text off the back end of the previous speech
				# and wrap into a new no-speaker speech
				qbdp = GrabDivisionProced(flatb[-1], qbd)
				if qbdp:
					flatb.append(qbdp)
				flatb.append(qbd)

				# write out our file with the report of all divisions
				PreviewDivisionTextGuess(flatb)

                        #lastheading = headingtxt

			# continue and output unaccounted for unspoken text occuring after a
			# division, or after a heading
			if (not re.match('(?:<[^>]*>|\s)*$', unspoketxt)):
				qb = qspeech('nospeaker="true"', unspoketxt, stampurl)
				qb.typ = 'speech'
				FilterDebateSpeech(qb)
				flatb.append(qb)

			# there is no text; update from stamps if there are any
			else:
				stampurl.UpdateStampUrl(unspoketxt)

			# go through each of the speeches in a block and put it into our batch of speeches
			for ss in speechestxt:
				qb = qspeech(ss[0], ss[1], stampurl)
				qb.typ = 'speech'
				FilterDebateSpeech(qb, bDebateBegToMove=True)

				qbdp = GrabWestminDivisionInterruptProced(qb,ss[1]) # captures tail off westminster hall speeches
				flatb.append(qb)
				if qbdp:
					flatb.append(qbdp)

		except ContextException, e:
			raise
		# add extra stamp info to the exception
#		except Exception, e:
#			raise ContextException(str(e), stamp=stampurl, fragment=unspoketxt)


	# we now have everything flattened out in a series of speeches
	return flatb




