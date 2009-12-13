#! /usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap

import re
import sys
import string
import os
import tempfile

# make the top path data directory value
toppath = os.path.abspath('../../parldata')
if not os.path.exists(toppath):
        toppath = os.path.abspath('../../../parldata')
if not os.path.exists(toppath):
        toppath = os.path.abspath(os.path.expanduser('~/parldata/'))
if not os.path.exists(toppath):
        toppath = 'C:\\parldata'

# output directories used for the scraper
pwcmdirs = os.path.join(toppath, "cmpages")
pwxmldirs = os.path.join(toppath, "scrapedxml")
pwpatchesdirs = os.path.abspath("patches")  # made locally, relative to the lazyrunall.py module.  Should be relative to toppath eventually

if (not os.path.isdir(toppath)):
        raise Exception, 'Data directory %s does not exist, please create' % (toppath)
# print "Data directory (set in miscfuncs.py): %s" % toppath

# temporary files are stored here
tmppath = os.path.join(toppath, "tmp")
if (not os.path.isdir(tmppath)):
        os.mkdir(tmppath)
tempfilename = tempfile.mktemp("", "pw-gluetemp-", tmppath)

# find raw data path
rawdatapath = os.path.join(os.getcwd(), "../rawdata")
if (not os.path.isdir(toppath)):
        raise Exception, 'Raw data directory %s does not exist, you\'ve not got a proper checkout from CVS.' % (toppath)

# quiet flag
bNotQuiet = True
def SetQuiet():
	global bNotQuiet
	bNotQuiet = False
def IsNotQuiet():
	return bNotQuiet


# import lower down so we get the top-path into the contextexception file
from contextexception import ContextException


# use this to generate chronological scraped files of the same page
def NextAlphaString(s):
	assert re.match('[a-z]*$', s)
	if not s:
		return 'a'
	i = string.find(string.lowercase, s[-1]) + 1
	if i < len(string.lowercase):
		return s[:-1] + string.lowercase[i]
	return NextAlphaString(s[:-1]) + 'a'

def AlphaStringToOrder(s):
	assert re.match('[a-z]*$', s)
	res = 0
	while s:
		i = string.find(string.lowercase, s[0]) + 1
		res = res * 30 + i
		s = s[1:]
	return res

# Impossible to do 6pm, 7.15pm, 6.30pm, 6.45pm, 7pm without future timestamps
# So not caring any more about timestamp errors
# Need good timestamps for video ;-) So turning back on, might try different tack at some point

# This one used to break times into component parts: 7.10 pm
regparsetime = re.compile("^(\d+)[\.:]\s*(\d+)(?:\s?|&nbsp;)([\w\.]*)$")
# 7 pm
regparsetimeonhour = re.compile("^(\d+)()(?:\s?|&nbsp;)([\w\.]+)$")
def TimeProcessing(time, previoustimearr, bIsDivisionTime, stampurl):
	#print "time ", time

        previoustime = None
	if previoustimearr:
                previoustime = previoustimearr[-1]
		prevtimeMatch = re.match("(\d+):(\d+)", previoustime)
		previoustimehour = int(prevtimeMatch.group(1))

	# This code lifted from fix_time PHP code from easyParliament
	timeparts = regparsetime.match(time)
	if not timeparts:
		timeparts = regparsetimeonhour.match(time)
	if timeparts:
		hour = int(timeparts.group(1))
		if (timeparts.group(2) != ""):
			mins = int(timeparts.group(2))
		else:
			mins = 0
		meridien = timeparts.group(3)
		if re.match("p\.?m\.?", meridien):
			if hour != 12:
				hour += 12
		elif meridien == "midnight":
			assert hour == 12
			hour += 12
		elif meridien == "noon":
			assert hour == 12
		else:
			if hour == 12:
				hour -= 12
			if not re.match("a\.?m\.?", meridien):
                                if previoustime and previoustimehour > hour:
                                        hour += 12

		# skipping forward by twelve hours is a good sign an am/pm has gotten mixed
                # Assume it's that if it's exactly 12 hours, alert otherwise
		if previoustime and previoustimehour + 12 == hour:
                        hour -= 12

		if previoustime and previoustimehour + 12 <= hour:
			raise ContextException('time shift by 12 -- should a p.m. be an a.m.?', stamp=stampurl)

	elif time == 'Midnight':
                hour = 24
                mins = 0
	elif time == 'Noon':
                hour = 12
                mins = 0
	else:
		return None

	res = "%03d:%02d:00" % (hour, mins)


	# day-rotate situation where they went on beyond midnight
	# it's uncommon enough to handle by listing exceptional days
	# (sometimes the division time is out of order because that is where it is inserted in the record -- maybe should patch to handle)
	#print previoustime, res, bIsDivisionTime, stampurl.sdate
	if previoustime and res < previoustime:
		if stampurl.sdate in ["2005-03-10"]:
			if previoustime < "024":
				print "dayrotate on ", stampurl.sdate, (hour, mins), previoustime
			hour += 24

		# correction heading case -- a copy of some text that is to be inserted into a different day.
		elif stampurl.sdate in ["2002-10-28"]:
			return res

                elif hour in [0, 1, 2, 3] or stampurl.sdate in ["2003-10-20", "2000-10-03", "2000-07-24"]:
                        hour += 24
		else:
			print (hour, mins), "time=", time, "previoustime=", previoustime
			raise ContextException('time rotation not close to midnight', stamp=stampurl)

		res = "%03d:%02d:00" % (hour, mins)


	# capture the case where we are out of order by more than a few minutes
	# (divisions are often out of order slightly)

	# out of order case
	if previoustime and res < previoustime:
		# if it's a division type, we can tolerate a few minutes
		timeminutes = int(hour) * 60 + int(mins)
		previoustimeminutes = previoustimehour * 60 + int(prevtimeMatch.group(2))
		if timeminutes < previoustimeminutes:
			if not bIsDivisionTime or (previoustimeminutes - timeminutes > 10):
				print "previous time out of order", res, previoustime, bIsDivisionTime
				raise ContextException('time out of order', stamp=stampurl)
	return res


# The names of entities and what they are are here:
# http://www.bigbaer.com/reference/character_entity_reference.htm
# Make sure you update WriteXMLHeader in xmlfilewrite.py also!
entitymap = {
        '&nbsp;':' ',
        '&':'&amp;',

        # see http://www.cs.tut.fi/~jkorpela/www/windows-chars.html for a useful, if now dated in
        # terms of browser support for the proper solutions, info on windows ndash/mdash (150/151)
        '&#150;':'&ndash;',  # convert windows latin-1 extension ndash into a real one
        '&#151;':'&mdash;',  # likewise mdash
        '&#161;':'&iexcl;',  # inverted exclamation mark
        '&#247;':'&divide;', # division sign

        '&#232;':'&egrave;',   # this is e-grave
        '&#233;':'&eacute;',   # this is e-acute
        '&#234;':'&ecirc;',   # this is e-hat
        '&#235;':'&euml;',   # this is e-double-dot

        '&#224;':'&agrave;',   # this is a-grave
        '&#225;':'&aacute;',   # this is a-acute
        '&#226;':'&acirc;',   # this is a-hat as in debacle
        '&#227;':'&atilde;',   # this is a-hat as in debacle
        '&#228;':'&auml;',

        '&#244;':'&ocirc;',   # this is o-hat
        '&#246;':'&ouml;',   # this is o-double-dot
        '&#214;':'&Ouml;',   # this is capital o-double-dot
        '&#243;':'&oacute;',   # this is o-acute
        '&#248;':'&oslash;',   # this is o-slash
        '&#245;':'&otilde;', # this is o-tilde

        '&#237;':'&iacute;', # this is i-acute
        '&#238;':'&icirc;', # this is i-circumflex
        '&#239;':'&iuml;',  # this is i-double-dot, as in naive

        '&#231;':'&ccedil;',   # this is cedilla
        '&#250;':'&uacute;',
        '&#252;':'&uuml;',   # this is u-double-dot
        '&#241;':'&ntilde;',   # spanish n as in Senor
        '&#254;':'&thorn;',

        '&#177;':'&plusmn;',   # this is +/- symbol
        '&#163;':'&pound;',   # UK currency
        '&#167;':'&sect;',   # UK currency
        '&#169;':'&copy;',
        '&#183;':'&middot;',   # middle dot
        '&#176;':'&deg;',   # this is the degrees
        '&#186;':'&ordm;',   # this is the M ordinal
        '&#174;':'&reg;',   # this is the degrees
        '&#182;':'&para;',  # end-paragraph (pi) symbol

        '&#181;':'&micro;',   # this is one quarter symbol
        '&#188;':'&frac14;',   # this is one quarter symbol
        '&#189;':'&frac12;',   # this is one half symbol
        '&#190;':'&frac34;',   # this is three quarter symbol

        '&#035;':'#',    # this is hash
        '&#095;':'_',    # this is underscore symbol
        '&#95;':'_',    # this is underscore symbol

		'&#039;':"'",   # posession apostrophe
        "&#8364;":'&euro;', # this is euro currency
        "&#8482;":'&trade;',
        "&#8226;":'&bull;',
        '&lquo;':"'",
        '&rquo;':"'",
        '&minus;':"-",

        '&#145;':"'",
        '&#146;':"'",
        '&#147;':'&quot;',
        '&#148;':'&quot;',
        '&#133;':'...',
        '&#134;':'&dagger;',

        '&#178;':'&sup2;',
        '&rsquo;':"'",
        '&oelig;':'&#339;',
        '&#230;':'&aelig;',
        '&dagger;':'&dagger;',
}
entitymaprev = entitymap.values()


def StripAnchorTags(text):
        raise Exception, "I've never called this function, so test it"

        abf = re.split('(<[^>]*>)', text)

        ret = ''
	for ab in abf:
		if re.match('<a[^>]*>(?i)', ab):
                        pass

		elif re.match('</a>(?i)', ab):
			pass

                else:
                        ret = ret + ab

        return ret


def WriteCleanText(fout, text, striphref=True):
        text = re.sub('<!--.*?-->', '', text)
    	abf = re.split('(<[^>]*>)', text)
        for ab in abf:
		# delete comments and links
		if re.match('<!-[^>]*?->', ab):
			pass

                # XXX Differs from pullgluepages version
		elif striphref and re.match('<a[^>]+>(?i)', ab):
			anamem = re.match('<a name\s*?=(?i)', ab)
                        if anamem:
                                fout.write(re.sub('\s', ' ', ab))

		elif striphref and re.match('</?a>(?i)', ab):
			pass

		# spaces only inside tags
		elif re.match('<[^>]*>', ab):
			fout.write(re.sub('\s', ' ', ab))

		# take out spurious > symbols and dos linefeeds
		else:
			fout.write(re.sub('>|\r', '', ab))


# Legacy patch system, use patchfilter.py and patchtool now
def ApplyFixSubstitutions(text, sdate, fixsubs):
	for sub in fixsubs:
		if sub[3] == 'all' or sub[3] == sdate:
			(text, n) = re.subn(sub[0], sub[1], text)
			if (sub[2] != -1) and (n != sub[2]):
				print sub
				raise Exception, 'wrong number of substitutions %d on %s' % (n, sub[0])
	return text


# this only accepts <sup> and <i> tags
def StraightenHTMLrecurse(stex, stampurl):
	# split the text into <i></i> and <sup></sup> and <sub></sub> and <a href></a>
        qisup = re.search(r'(<(a|i|b|small|sup|sub)( href="[^"]*")?>(.*?)</\2>)(?i)', stex)
        if qisup:
                qtagtype = qisup.group(2)
                qhref = qisup.group(3) or ''
                qtag = ('<%s%s>' % (qtagtype, qhref), '</%s>' % qtagtype)
	if not qisup:
		qisup = re.search('(<(a) href="([^"]*)">(.*?)</a>)(?i)', stex)
		if qisup:
			qtag = ('<a href="%s">' % qisup.group(3), '</a>')

	if qisup:
		sres = StraightenHTMLrecurse(stex[:qisup.start(1)], stampurl)
		sres.append(qtag[0])
		sres.extend(StraightenHTMLrecurse(qisup.group(4), stampurl))
		sres.append(qtag[1])
		sres.extend(StraightenHTMLrecurse(stex[qisup.end(1):], stampurl))
		return sres

	sres = re.split('(&[a-z0-9]*?;|&#\d+;|"|\xa3|&|\x01|\x0e|\x14|\x92|\xb0|\xab|\xe9|\xc3\xb8|\xc3\xb1|<[^>]*>|<|>)', stex)
	for i in range(len(sres)):
                #print "sresi ", sres[i], "\n"
                #print "-----------------------------------------------\n"

		if not sres[i]:
			pass
		elif re.match('&#[0-9]+;', sres[i]):
                        pass
		elif sres[i][0] == '&':
			if sres[i] in entitymap:
				sres[i] = entitymap[sres[i]]
			elif sres[i] in entitymaprev:
				pass
			elif sres[i] == '&mdash;': # special case as entitymap maps it with spaces
				pass
			elif sres[i] in ('&quot;', '&amp;', '&lt;', '&gt;'):
				pass
			elif sres[i] in ('&ldquo;', '&rdquo;'):
				sres[i] = '&quot;'
			else:
				raise Exception, sres[i] + ' unknown ent'
				sres[i] = 'UNKNOWN-ENTITY'

		elif sres[i] == '"':
			sres[i] = '&quot;'

		# junk chars sometimes get in
		# NB this only works if the characters are split in the regexp above
		elif sres[i] == '\x01':
			sres[i] = ''
		elif sres[i] == '\x0e':
			sres[i] = ' '
		elif sres[i] == '\x14':
			sres[i] = ' '
		elif sres[i] == '\x92':
			sres[i] = "'"
		elif sres[i] == '\xa3':
			sres[i] = '&pound;'
		elif sres[i] == '\xb0':
			sres[i] = '&deg;'
		elif sres[i] == '\xab':
			sres[i] = '&eacute;'
		elif sres[i] == '\xe9':
			sres[i] = '&eacute;'
                elif sres[i] == '\xc3\xb8':
			sres[i] = '&oslash;'
                elif sres[i] == '\xc3\xb1':
                        sres[i] = '&ntilde;'

		elif re.match('</?i>$(?i)', sres[i]):
			sres[i] = '' # 'OPEN-i-TAG-OUT-OF-PLACE' 'CLOSE-i-TAG-OUT-OF-PLACE'

		elif re.match('<xref locref=\d+>$', sres[i]): # what is this? wrans 2003-05-13 has one
			sres[i] = ''

		# allow brs through
		elif re.match('<br>$(?i)', sres[i]):
			sres[i] = '<br/>'

		# discard garbage that appears in recent today postings
		elif re.match('<jf\d+>$(?i)', sres[i]):
			sres[i] = ''

		elif sres[i][0] == '<' or sres[i][0] == '>':
			print "Part:", sres[i][0]
			print "All:",sres[i]
			print "stex:", stex
			print "raising"
			raise ContextException('tag %s tag out of place in %s' % (sres[i], stex), stamp=stampurl, fragment=stex)

	return sres


def FixHTMLEntitiesL(stex, signore='', stampurl=None):
	# will formalize this into the recursion later
	if signore:
		stex = re.sub(signore, '', stex)
	return StraightenHTMLrecurse(stex, stampurl)

def FixHTMLEntities(stex, signore='', stampurl=None):
	res = string.join(FixHTMLEntitiesL(stex, signore, stampurl), '')
	try:
		res = res.decode('utf-8')
		return res.encode("latin-1")
	except Exception, e:
		print "Encoding problem with:", res
		raise ContextException(str(e), stamp=stampurl, fragment=res)




# The lookahead assertion (?=<table) stops matching tables when another begin table is reached
paratag = '</?p(?: style="margin-left: 20px;")?(?: align=(?:left|"center"))?(?: id="[^"]*" class="timestamp")?(?: class[= ]"(?:tabletext|normaltext|amendment_hs_quote|amendment_indentone|amendment_indenttwo|clause_heading)")?>'
restmatcher = paratag + '|<ul><ul><ul>|</ul></ul></ul>|</?ul>|<br>|</?font[^>]*>(?i)'
reparts = re.compile('(<table[\s\S]*?(?:</table>|(?=<table))|' + restmatcher + ')')
reparts2 = re.compile('(<table[^>]*?>|' + restmatcher + ')')

retable = re.compile('<table[\s\S]*?</table>(?i)')
retablestart = re.compile('<table[\s\S]*?(?i)')
reparaspace = re.compile(paratag + '|<ul><ul><ul>|</ul></ul></ul>|</?ul>|</?br>|</?font[^>]*>|<table[^>]*>$(?i)')
reparaempty = re.compile('(?:\s|</?i>|&nbsp;)*$(?i)')
reitalif = re.compile('\s*<i>\s*$(?i)')

# Break text into paragraphs.
# the result alternates between lists of space types, and strings
def SplitParaSpace(text, stampurl):
	res = []

	# used to detect over breaking in spaces
	bprevparaalone = True

	# list of space objects, list of string
	spclist = []
	pstring = ''
	parts = reparts.split(text)
	newparts = []
	# split up the start <table> bits without end </table> into component parts
	for nf in parts:

		# a tiny bit of extra splitting up as output
		if retablestart.match(nf) and not retable.match(nf):
			newparts.extend(reparts2.split(nf))
		else:
			newparts.append(nf)

		# get rid of blank and boring paragraphs
		if reparaempty.match(nf):
			if pstring and re.search('\S', nf):
				print text
				print '---' + pstring
				print '---' + nf
				raise Exception, ' it carried across empty para '
			continue

		# list of space type objects
		if reparaspace.match(nf):
			spclist.append(nf)
			continue

		# sometimes italics are hidden among the paragraph choss
		# bring forward onto the next string
		if reitalif.match(nf):
			if pstring:
				print text
				print spclist
				print pstring
				raise Exception, ' double italic in paraspace '
			pstring = '<i>'
			continue


		# we now have a string of a paragraph which we are putting into the list.

		# table type
		bthisparaalone = False
		if retable.match(nf):
			if pstring:
				print text
				raise Exception, ' non-empty preceding string '
			pstring = nf
			bthisparaalone = True

		else:
			lnf = re.sub("\s+", " ", nf)
			if pstring:
				pstring = pstring + " " + string.strip(lnf)
			else:
				pstring = string.strip(lnf)


		# check that paragraphs have some text
		if re.match('(?:<[^>]*>|\s)*$', pstring):
			print "\nspclist:", spclist
			print "\npstring:", pstring
			print "\nthe text:", text[:100]
			print "\nnf:", nf
			raise ContextException('no text in paragraph', stamp=stampurl, fragment=pstring)

		# check that paragraph spaces aren't only font text, and have something
		# real in them, unless they are breaks because of tables
		if not (bprevparaalone or bthisparaalone):
			bnonfont = False
			for sl in spclist:
				if not re.match('</?font[^>]*>(?i)', sl):
					bnonfont = True
			if not bnonfont:
				print "text:", text
				print "spclist:", spclist
				print "pstring", pstring
				print "----------"
				print "nf", nf
				print "----------"
				raise ContextException('font found in middle of paragraph should be a paragraph break or removed', stamp=stampurl, fragment=pstring)
		bprevparaalone = bthisparaalone


		# put the preceding space, then the string into output list
		res.append(spclist)
		res.append(pstring)
		#print "???%s???" % pstring

		spclist = [ ]
		pstring = ''

	# findal spaces into the output list
	res.append(spclist)

	return res


# Break text into paragraphs and mark the paragraphs according to their <ul> indentation
def SplitParaIndents(text, stampurl):
	dell = SplitParaSpace(text, stampurl)
        #print "dell", dell

	res =  [ ]
	resdent = [ ]
	bIndent = 0
	for i in range(len(dell)):
		if (i % 2) == 0:
			for sp in dell[i]:
				if re.match('(?:<ul><ul>)?<ul>(?i)', sp):
					if bIndent==1:
						print dell[i - 1: i + 1]
						raise ContextException(' already indented ', stamp=stampurl, fragment=sp)
					bIndent = 1
				elif re.match('(?:</ul></ul>)?</ul>(?i)', sp):
					# no error
					#if not bIndent:
					#	raise Exception, ' already not-indentented '
					bIndent = 0
                                elif re.match('<p style="margin-left: 20px;">', sp):
                                        bIndent = 2
                                elif bIndent == 2 and re.match('</p>', sp):
                                        bIndent = 0
			continue

		# we have the actual text between the spaces
		# we might have full italics indent style
		# (we're ignoring fonts for now)

		# separate out italics type paragraphs
		tex = dell[i]
		cindent = bIndent > 0 and 1 or 0

		qitbod = re.match('<i>([\s\S]*?)</i>[.:]?$', tex)
		if qitbod:
			tex = qitbod.group(1)
			cindent = cindent + 2

		res.append(tex)
		resdent.append(cindent)

	#if bIndent:
	#	print text
	#	raise ' still indented after last space '
	return (res, resdent)







