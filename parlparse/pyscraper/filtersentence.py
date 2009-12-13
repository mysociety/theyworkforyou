#! /usr/bin/python2.4

import sys
import re
import string
import cStringIO

import mx.DateTime

from parlphrases import parlPhrases
from miscfuncs import FixHTMLEntities
from miscfuncs import FixHTMLEntitiesL
from miscfuncs import SplitParaIndents

from filterwransemblinks import rreglink
from filterwransemblinks import rregemail

from filterwransemblinks import rehtlink
from filterwransemblinks import ConstructHTTPlink

from resolvemembernames import memberList


# this code fits onto the paragraphs before the fixhtmlentities and
# performs difficult regular expression matching that can be
# used for embedded links.

# this code detects square bracket qnums [12345], standing order quotes,
# official report references (mostly in wranses), and hyperlinks (most of which
# are badly typed and full of spaces).

# the structure of each function is to search for an occurrance of the pattern.
# it sends the text before the match to the next function, it encodes the
# pattern itself however it likes, and sends the text after the match back to
# itself as a kind of recursion.

# in the future it should be possible to pick out direct references to
# other members of the house in speeches.

# This is solely here so that already existing links (which will only be correction links and links to deposited papers)
# can get through this tokenising stage without being mangled to death
rehreflink = re.compile('(<small>)?<a href="([^"]*)">(.*?)</a>(</small>)?')
# <small> is for 2008-09 Lords wrapping links in them, yuck. Plus this doesn't
# work if more than one link is so wrapped. XXX

reqnum = re.compile("\s*\[(\d+)\]\s*$")
refqnum = re.compile("\s*\[(\d+)\]\s*")

redatephraseval = re.compile('(?:(?:%s),? )?(\d+(?: |&nbsp;)*(?:%s)( \d+)?)' % (parlPhrases.daysofweek, parlPhrases.monthsofyear))
def TokenDate(ldate, phrtok):
	sdate_year = phrtok.sdate[0:4]
	tdate = ldate.group(0).replace('&nbsp;', ' ')
	noyear = False
	if not ldate.group(2):
		tdate += " %s" % sdate_year
		noyear = True
	try:
		lldate = mx.DateTime.DateTimeFrom(tdate)
		#if noyear and lldate > mx.DateTime.now():
		#	lldate = (lldate - mx.DateTime.RelativeDateTime(years=1))
		ldate = lldate.date
		phrtok.lastdate = ldate
	except:
		phrtok.lastdate = ''
	return ('phrase', ' class="date" code="%s"' % FixHTMLEntities(phrtok.lastdate))

restandingo = re.compile('''(?x)
		Standing\sOrder\sNo\.\s*
		(
		 \d+[A-Z]?               # number+letter
		 (?:\s*\(\d+\))?         # bracketted number
		 (?:\s*\([a-z]\))?		 # bracketted letter
		)
		(?:\s*
		\(([^()]*(?:\([^()]*\))?)\) # inclusion of title for clarity
		)?
''')
restandingomarg = re.compile("Standing Order No")
def TokenStandingOrder(mstandingo, phrtok):
	if mstandingo.group(2):
		return ('phrase', ' class="standing-order" code="%s" title="%s"' % (FixHTMLEntities(mstandingo.group(1)), FixHTMLEntities(re.sub('<[^>]*>', '', mstandingo.group(2)))))
	return ('phrase', ' class="standing-order" code="%s"' % mstandingo.group(1))

def TokenHttpLink(mhttp, phrtok):
	qstrlink = ConstructHTTPlink(mhttp.group(1), mhttp.group(2), mhttp.group(3))
	return ('a', ' href="%s"' % qstrlink)

def TokenHrefLink(mhttp, phrtok):
	return ('', '')

reoffrepw = re.compile('''(?ix)
    <i>\s*official(?:</i>|<i>|\s)*report                # Official Report
    (?:</i>|<i>|[,;\s])*
    (Commons|House\sof\sCommons|House\sof\sLords)?      # Optional house (1)
    (?:</i>|<i>|[,;\s])*
    (?:vol(?:ume|\.)\s\d+)?                             # Optional volume
    [,;]?
    \s*c(?:c|o|ol|olumn)?s?\.?                          # Various ways of saying "column"
    (?:[:\s]|&nbsp;)*(?:<i>)?
    (?:(W[AS]?)\s*)?                                    # Optional column number prefix (2)
    (\d+(?:(?:&\#150;|-)\d+)?)                          # Column number or numbers (3)
    ([WHSA]*)                                           # Optional column suffix (4)
''')
def TokenOffRep(qoffrep, phrtok):
    loc1 = qoffrep.group(1)
    qcolprefix = qoffrep.group(2)
    qcolsuffix = qoffrep.group(4)
    if qcolprefix:
        qcolprefix = qcolprefix.upper()
    if qcolsuffix:
        qcolsuffix = qcolsuffix.upper()
    #print '*', qoffrep.group(0), loc1, qcolprefix, qcolsuffix, qoffrep.group(3)
    qcpart = re.match('(\d+)(?:(?:&#150;|-)(\d+))?(?i)$', qoffrep.group(3))
    qcolnum = qcpart.group(1)
    if qcpart.group(2):
        qcpartlead = qcpart.group(1)[len(qcpart.group(1)) - len(qcpart.group(2)):]
        if string.atoi(qcpartlead) >= string.atoi(qcpart.group(2)):
            print ' non-following column leadoff ', qoffrep.group(0)
            #raise Exception, ' non-following column leadoff '

    if qcolsuffix == 'WH':
        sect = 'westminhall'
    elif qcolprefix == 'WS' or qcolsuffix == 'WS':
        sect = 'wms'
    elif qcolprefix == 'WA' or qcolsuffix == 'W' or qcolsuffix == 'WA':
        sect = 'wrans'
    elif loc1 == 'House of Lords':
        sect = 'lords'
    else:
        sect = 'debates'

    offrepid = '%s/%s.%s' % (sect, phrtok.lastdate, qcolnum)
    return ('phrase', ' class="offrep" id="%s"' % offrepid )

# Date in the middle, so need to match before the date-only parsing...
reoffrepwdate = re.compile('''(?ix)
    <i>\s*official(?:</i>|<i>|\s)*report                # Official Report
    (?:(?:</i>|<i>|,|\s)*(Westminster\sHall|House\sof\sLords|House\sof\sCommons))?  # Optionally followed by a chamber (1)
    [,;]?\s*(?:</i>)?[,;]?\s*
    (?:(Commons|Lords)[,;]?\s*)?                        # Optionally followed by a House (2)
    (\d+(?:\s|&nbsp;)\S+\s\d+|\d+/\d+/\d+)              # The date (3)
    (?:[;,]\s*Vol\.?(?:\s|&nbsp;)*\d+\.?\s*)?           # Optional volume number
    [,;]?
    (?:\s+|\s*c(?:c|o|ol|olumn)?s?\.?)                  # Various ways of saying "column"
    (?:\s|&nbsp;)*(?:<i>)?
    (?:(W[AS]?)\s*)?                                    # Optional column number prefix (4)
    (\d+)(?:(?:&\#150;|-)\d+)?                          # Column number or numbers (5)
    ([WHS]*)                                            # Optional column number suffix (6)
''')
def TokenOffRepWDate(qoffrep, phrtok):
    #print qoffrep.group(0)
    loc1 = qoffrep.group(1)
    loc2 = qoffrep.group(2)
    date = qoffrep.group(3).replace('&nbsp;', ' ')
    qcolprefix = qoffrep.group(4)
    qcolnum = qoffrep.group(5)
    qcolsuffix = qoffrep.group(6)
    m = re.match('(\d+)/(\d+)/(\d+)', date)
    if m:
        lordsdate = True
        date = mx.DateTime.DateTimeFrom('%s/%s/%s' % (m.group(2), m.group(1), m.group(3))).date
    else:
        lordsdate = False
        date = mx.DateTime.DateTimeFrom(date).date
    if qcolprefix:
        qcolprefix = qcolprefix.upper()
    if qcolsuffix:
        qcolsuffix = qcolsuffix.upper()
    if loc1 == 'Westminster Hall' or qcolsuffix == 'WH':
        sect = 'westminhall'
    elif qcolprefix == 'WS' or qcolsuffix == 'WS':
        sect = 'wms'
    elif qcolprefix == 'WA' or qcolsuffix == 'W':
        sect = 'wrans'
    elif loc1 == 'House of Lords' or loc2 == 'Lords' or lordsdate:
        sect = 'lords'
    else:
        sect = 'debates'

    offrepid = '%s/%s.%s' % (sect, date, qcolnum)
    return ('phrase', ' class="offrep" id="%s"' % offrepid )

#my hon. Friend the Member for Regent's Park and Kensington, North (Ms Buck)
# (sometimes there are spurious adjectives
rehonfriend = re.compile('''(?ix)
	the\.?
    # Privy counsellors, barrister, armed forces, status, etc.
	(?:(?:\s|&.{4};)*(?:right\.?|rt\.|very|old|new|now|current|then|visiting|former|distinguished|hon\.?|honourable|and|learned|gallant|Labour|Liberal Democrat|Conservative|reverend|independent|excellent|poor|rude|courageous|wonderful|brutal|redoubtable|mute|present|pious|formidable|fragrant))*
	(?:\s|&.{4};)*
	member\sfor\s
	([^(]{3,60}?)			# group 1 the name of the constituency
	\s*
	\(([^)]{5,60}?)(?:&\#146;s)?\)		# group 2 the name of the MP, inserted for clarity.
''')
rehonfriendmarg = re.compile('the\s+(hon\.\s*)?member for [^(]{0,60}\((?i)')
def TokenHonFriend(mhonfriend, phrtok):
	# will match for ids
	orgname = mhonfriend.group(2)
	res = memberList.matchfullnamecons(orgname, mhonfriend.group(1), phrtok.sdate, alwaysmatchcons = False)
	if not res[0]:  # comes back as None
		nid = "unknown"
		mname = orgname
	else:
		nid = res[0]
		mname = res[1]
	assert not re.search("&", mname)
	
	# remove any xml entities from the name
	orgname = res[1]

	# if you put the .encode("latin-1") on the res[1] it doesn't work when there are strange characters.
	return ('phrase', (' class="honfriend" id="%s" name="%s"' % (nid, orgname)).encode("latin-1"))



# the array of tokens which we will detect on the way through
tokenchain = [
	( 'hreflink',       rehreflink,     None,               TokenHrefLink ),
	( 'offrepwdate',	reoffrepwdate,	None, 				TokenOffRepWDate ),
	( "date",			redatephraseval,None, 				TokenDate ),
	( "offrep", 		reoffrepw, 		None, 				TokenOffRep ),
	( "standing order", restandingo, 	restandingomarg, 	TokenStandingOrder ),
	( "httplink", 		rehtlink, 		None, 				TokenHttpLink ),
	( "honfriend", 		rehonfriend, 	rehonfriendmarg, 	TokenHonFriend ),
]


# this handles the chain of tokenization of a paragraph
class PhraseTokenize:

	# recurses over itc < len(tokenchain)
	def TokenizePhraseRecurse(self, qs, stex, itc):

		# end of the chain
		if itc == len(tokenchain):
			self.toklist.append( ('', '', FixHTMLEntities(stex, stampurl=(qs and qs.sstampurl))) )
			return

		# keep eating through the pieces for the same token
		while stex:
			# attempt to split the token
			mtoken = tokenchain[itc][1].search(stex)
			if mtoken:   # the and/or method fails with this
				headtex = stex[:mtoken.span(0)[0]]
			else:
				headtex = stex

			# check for marginals
			if tokenchain[itc][2] and tokenchain[itc][2].search(headtex):
				pass
				#print "Marginal token match:", tokenchain[itc][0]
				#print tokenchain[itc][2].findall(headtex)
				#print headtex

			# send down the one or three pieces up the token chain
			if headtex:
				self.TokenizePhraseRecurse(qs, headtex, itc + 1)

			# no more left
			if not mtoken:
				break

			# break up the token if it is there
			tokpair = tokenchain[itc][3](mtoken, self)
			self.toklist.append( (tokpair[0], tokpair[1], FixHTMLEntities(mtoken.group(0), stampurl=(qs and qs.sstampurl))) )
			#print "Token detected:", mtoken.group(0)

			# the tail part
			stex = stex[mtoken.span(0)[1]:]



	def __init__(self, qs, stex):
		self.lastdate = ''
		self.toklist = [ ]
		self.sdate = qs and qs.sstampurl.sdate

		# separate out any qnums at end of paragraph
 		self.rmqnum = reqnum.search(stex)
		if self.rmqnum:
			stex = stex[:self.rmqnum.span(0)[0]]

		# separate out qnums stuffed into front of paragraph (by the grabber of the speakername)
		frqnum = refqnum.search(stex)
		if frqnum:
			assert not self.rmqnum
			self.rmqnum = frqnum
			stex = stex[frqnum.span(0)[1]:]

		self.TokenizePhraseRecurse(qs, stex, 0)


	def GetPara(self, ptype, bBegToMove=False, bKillqnum=False):

		if (not bKillqnum) and self.rmqnum:
			self.rqnum = ' qnum="%s"' % self.rmqnum.group(1)
		else:
			self.rqnum = ""


		if bBegToMove:
			res = [ '<p class="%s" pwmotiontext="yes">' % ptype ]
		elif ptype:
			res = [ '<p class="%s">' % ptype ]
		else:
			res = [ '<p%s>' % self.rqnum ]

		for tok in self.toklist:
			if tok[0]:
				res.append('<%s%s>' % (tok[0], tok[1]))
				res.append(tok[2])
				res.append('</%s>' % tok[0])
			else:
				res.append(tok[2])

		res.append('</p>')
		return string.join(res, '')



