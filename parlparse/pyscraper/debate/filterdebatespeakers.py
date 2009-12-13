# vim:sw=8:ts=8:et:nowrap
# -*- coding: latin-1 -*-

import sys
import re
import os
import string
from resolvemembernames import memberList
from splitheadingsspeakers import StampUrl

from miscfuncs import ApplyFixSubstitutions
from contextexception import ContextException


# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = [

        ('23. (Mr. David Rendel)', '\\1', 1, '2003-06-30'),

        ('<B> Caroline Flint\): </B>', '<B> Caroline Flint: </B>', 1, '2003-07-14'),
        ('<B> Ms King </B>', '<B> Oona King </B>', 1, '2003-09-11'),

	('<B> Simon Hughes:  \(Southwark', '<B> Simon Hughes  (Southwark', 1, '2003-11-19'),
	( '<B>\("(The registers of political parties)</B>', '//1', 1, '2000-11-29'),
	( '\(Mr. Denis MacShane </B>\s*\)', '(Mr. Denis MacShane) </B>', 1, '2003-05-21'),

	( '\(Sir Alan Haselhurst </B>', '(Sir Alan Haselhurst) </B>', 1, '2003-03-25'),
	( '<B> Yvette Cooper: I </B>', '<B> Yvette Cooper: </B> I ', 1, '2003-02-03'),
	( '\(Mr. Nick Raynsford </B>\s*\)', '(Mr. Nick Raynsford) </B>', 1, '2003-01-23'),

        ( '(<B> Mr. Adrian Bailey  \()Blaby(\):</B> )', '\\1West Bromwich West\\2', 1, '2003-10-30'),

        ( '(<B> Ms Hazel Blears)\)', '\\1', 1, '2003-06-16'),
        ( 'Mr. Melanie Leigh', 'Mr. Leigh', 1, '2003-06-13'),
        ( 'The Chairman: Order', '<B>The Chairman:</B> Order', 1, '2003-06-05'),
        ( '(<B> Mr\. John Hutton)\)', '\\1', 1, '2003-06-03'),
        ( '(<B> Mr Jamieson)\)', '\\1', 1, '2003-05-16'),
        ( '(The Parliamentary Under-Secretary of State for Defence \()<P>\s*?<P>\s*?<P>(\s*?<stamp aname="30512-01_spnew16"/><B> )(Dr. Lewis Moonie\)\: </B>)', \
                '\\2\\1\\3', 1, '2003-05-12'),
        ( '(The Parliamentary Under-Secretary of State for the Home Department \()<P>\s*?<P>\s*?<P>(\s*?<stamp aname="30428-02_spnew0"/><B> )(Hilary Benn\)\: </B>)', \
                '\\2\\1\\3', 1, '2003-04-28'),
        ( '(The Parliamentary Under-Secretary of State for the Home Department \()<P>\s*?<P>\s*?<P>(\s*?<stamp aname="30428-04_spnew0"/><B> )(Mr\. Bob Ainsworth\)\: </B>)', \
                '\\2\\1\\3', 1, '2003-04-28'),
        ( '(The Parliamentary Under-Secretary of State for the Home Department \()<P>\s*?<P>\s*?<P>(\s*?<stamp aname="30428-05_spnew7"/><B> )(Mr\. Bob Ainsworth\)\: </B>)', \
                '\\2\\1\\3', 1, '2003-04-28'),
        ( '(The Parliamentary Under-Secretary of State for the Home Department \()<P>\s*?<P>\s*?<P>(\s*?<stamp aname="30324-04_spnew11"/><B> )(Hilary Benn\)\: </B>)', \
                '\\2\\1\\3', 1, '2003-03-24'),
        ( '(The Minister for Policing, Crime Reduction and Community Safety \()<P>\s*?<P>\s*?<P>(\s*?<stamp aname="30224-04_spnew4"/><B> )(Mr\. John Denham\)\: </B>)', \
                '\\2\\1\\3', 1, '2003-02-24'),

        ( '(<B> Mr\. Spellar)\)', '\\1', 1, '2003-03-31'),

        # wrong constituency in debates
        ( 'Sir Archy Kirkwood  \(Brecon and Radnorshire\)', 'Sir Archy Kirkwood (Roxburgh and Berwickshire)', 1, '2003-06-26'),

]

# 2. <B> Mr. Colin Breed  (South-East Cornwall)</B> (LD):
# <B> Mr. Hutton: </B>
# 2. <stamp aname="40205-06_para4"/><B> Mr. Colin Breed</B>:

# Q4.  [161707]<a name="40317-03_wqn5"><B> Mr. Andy Reed  (Loughborough)</B>

parties = "|".join(map(string.lower, memberList.partylist())) + "|uup|ld|dup|in the chair"

# Splitting condition
# this must be a generalization of the one below.  so changes need to be reflected in both.
recomb = re.compile('''(?ix)((?:[QT]?\d+\.\s*)?(?:\[\d+\]\s*)?
					(?:<stamp\saname="[^"]*"/>)?
					<b>
					(?:<stamp\saname="[^"]*"/>)*
					[^<]*
					</b>(?!</h[34]>)
					\s*\)?
					(?:\s*\([^)]*\))?
					(?:\s*\((?:%s)\))?
					\s*:?)''' % parties)


# Specific match:
# Notes - sometimes party appears inside bold tags, so we match and throw it away on either side
respeakervals = re.compile('''(?ix)
		(?:[QT]?(\d+)\.\s*)?			# oral question number (group1)
		(\[\d+\]\s*)?				# written qnum (group2)
		(<stamp\saname="[^"]*?"/>)?             # a stamp (group3)
		<b>\s*                                  # start of bold
		(<stamp\saname="[^"]*?"/>)*             # a stamp (group4)
		(?:[QT]?(\d+)\.)?			# second place of oral question number (group5)
		([^:<(]*?):?\s*				# speaker (group6)
		(?:\((.*?)\)?)?				# speaker bracket (group7)
		(?:\s*\((%s)\))?\s*     	# parties list (group8)
		:?\s*
		</b>(?!</h[34]>) 		# end bold tag, ensuring it's not in a heading
		\s*\)?                	# end of bold (we can get brackets outside the bold tag (which should match the missing on on the inside
		(?:\((.*?)\))?			# speaker bracket outside of bold (group9)
		(?:\s*\((%s)\))?		# parties on outside of bold (group10)
		''' % (parties, parties))



# <B>Division No. 322</B>
redivno = re.compile('(?:<stamp\saname="[^"]*"/>)?<b>(?:division no\. \d+\]?|AYES|NOES)</b>$(?i)')

remarginal = re.compile('<b>[^<]*</b>(?!</h[34]>)(?i)')

def FilterDebateSpeakers(fout, text, sdate, typ):

	if typ == "westminhall":
		depspeakerrg = re.search("\[(.*?)(?:<i>)? ?in the Chair(?:</i>)?\]", text)
		if not depspeakerrg:
			print "can't find the [... in the Chair] phrase"
		depspeaker = depspeakerrg.group(1)


	# old style fixing (before patches existed)
	if typ == "debate":
		text = ApplyFixSubstitutions(text, sdate, fixsubs)

        # for error messages
	stampurl = StampUrl(sdate)

        # Fix missing bold tags around names
        missingbolds = re.findall('(\n?<p>(?:<stamp aname="[^"]+"/>)+)((?:<b></b>)?\s*)([A-Za-z.\-\s]+)((?:\([^)]*\)\s*)*)(:\s)', text)
        for p1,p2,p3,p4,p5 in missingbolds:
                missingbold = "%s%s%s%s%s" % (p1,p2,p3,p4,p5)
                bold = "%s<b>%s%s%s</b>" % (p1,p3,p4,p5)
                namematches = memberList.fullnametoids(p3, sdate)
                if namematches:
                        if not missingbold in text:
                                print "ERROR: missing bold text found, but then vanished when replacing"
                        text = text.replace(missingbold, bold)

        # Move Urgent Question out of speaker name
        urgentqns = re.findall('(<p>(?:<stamp aname="[^"]+"/>)+)(<b>[^<]*?)(\s*<i>\s*\(Urgent Question\)</i>)(:</b>)(?i)', text)
        for p1,p2,p3,p4 in urgentqns:
                urgentqn = "%s%s%s%s" % (p1,p2,p3,p4)
                correction = "%s%s%s%s" % (p1,p2,p4,p3)
                text = text.replace(urgentqn, correction)

	# setup for scanning through the file.
	for fss in recomb.split(text):
		stampurl.UpdateStampUrl(fss)
                #print fss
                #print "--------------------"

		# division number detection (these get through the speaker detection regexp)
		if redivno.match(fss):
			fout.write(fss.encode("latin-1"))
			continue

		# CORRECTION title (these also get through) -- both these are surrounded by <center> tags usually.
		if fss == "<b>CORRECTION</b>":
			fout.write(fss.encode("latin-1"))
			continue

		# speaker detection
		speakerg = respeakervals.match(fss)
		if speakerg:
			# optional parts of the group
			# we can use oqnum to detect oral questions
			anamestamp = speakerg.group(4) or speakerg.group(3) or ""
			oqnum = speakerg.group(1)
			if speakerg.group(5):
				assert not oqnum
				oqnum = speakerg.group(5)
			if oqnum:
				oqnum = ' oral-qnum="%s"' % oqnum
			else:
				oqnum = ""

			# the preceding square bracket qnums
			sqbnum = speakerg.group(2) or ""

			party = speakerg.group(8) or speakerg.group(10)

			spstr = string.strip(speakerg.group(6))
			spstrbrack = speakerg.group(7) or speakerg.group(9) # the bracketted phrase (sometimes the constituency or name if it is a minister)
                        if spstrbrack:
                                spstrbrack = re.sub("\n", ' ', spstrbrack)

			# do quick substitution for dep speakers in westminster hall
			if typ == "westminhall" and re.search("deputy[ \-]speaker(?i)", spstr) and not spstrbrack:
				#spstrbrack = depspeaker
				spstr = depspeaker

			# match the member to a unique identifier and displayname
			try:
				#print "spstr", spstr, ",", spstrbrack
				#print speakerg.groups()
				result = memberList.matchdebatename(spstr, spstrbrack, sdate, typ)
			except Exception, e:
				# add extra stamp info to the exception
				raise ContextException(str(e), stamp=stampurl, fragment=fss)

			# put record in this place
			#print "ree", result.encode("latin-1")
			spxm = '%s<speaker %s%s>%s</speaker>\n%s' % (anamestamp, result.encode("latin-1"), oqnum, spstr, sqbnum)
			fout.write(spxm)
			continue


		# nothing detected
		# check if we've missed anything obvious
		if recomb.match(fss):
			raise ContextException('regexpvals not general enough', fragment=fss, stamp=stampurl)
		if remarginal.search(fss):
			raise ContextException(' marginal speaker detection case: %s' % remarginal.search(fss).group(0), fragment=fss, stamp=stampurl)

		# this is where we phase in the ascii encoding
		fout.write(fss)

