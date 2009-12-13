import sys
import re
import string
import cStringIO

import mx.DateTime

from parlphrases import parlPhrases
from miscfuncs import FixHTMLEntities
from miscfuncs import FixHTMLEntitiesL
from miscfuncs import SplitParaIndents

from filterwransreplytable import ParseTable

#from filtersentence import FilterSentence
from filtersentence import PhraseTokenize

from contextexception import ContextException

# the set of known parliamentary offices.
rejobs = re.compile('((?:[Mm]y (?:rt\. |[Rr]ight )?[Fh]on\.? [Ff]riend )?[Tt]he (?:then |former )?(?:%s))' % parlPhrases.regexpjobs)




relettfrom = re.compile('(Letter from (.*?)(?: to (.*?))?(?:(?:,? dated| of)?,? %s)?):?$' % parlPhrases.datephrase)




# square bracket at the beginning of an answer
resqbrack = re.compile('\s*(?:\s|</?i>)*\[(?:\s|</?i>)*(.*?)(?:</i>|:|;|\s)*\](?:</i>|:|;|\s)*')


listlinlprorogue = 	[
		'I am unable to provide the information requested before the House prorogues. ?',
		'I have not been able to answer this Question before Prorogation. ',
		'I regret that it has not been possible to provide an answer before Prorogation, ',
		'The information requested will take some time to collate and ',
		'This information will take some time to collate. ',
		'The information is not readily available. ',
		'It will not be possible to collate the information requested by the hon. Gentleman within the accepted timescale. ',
			]

linliww = 'I will write to'
listlinlhm = [	' my hon.? Friend',
		' the hon. Member, and my hon. Friend',
		' the hon. Member',
		' the hon. Gentleman',
		' my right hon. Friend',
		' my hon. Member',
		' the right hon. and learned Member',
		' the right hon. Member',
	 ]
linlwi = ' with (?:the|this) information'
linlsoon = ' as soon as possible| in due course| shortly'
linlcopy = '(?: and place|, placing) a copy(?: of (?:my|the) letter)?|(?: and|, ) a copy(?: of (?:my|the) (?:letter|reply))? will be placed'
linlliby = ' in the (?:House of Commons Library|(?:Library|Libraries)(?: of the House)?)'


regletterinlib = '(?:%s)?%s(?:%s)(?:%s)?(?:%s)?(?:(?:%s)(?:%s))?\.' % \
		(string.join(listlinlprorogue, '|'), linliww, string.join(listlinlhm, '|'), linlwi, linlsoon, linlcopy, linlliby)
reletterinlibrary = re.compile(regletterinlib)

#print reletterinlibrary.findall('The information is not readily available. I will write to the hon. Member, placing a copy of my letter in the Library of the House.')
#sys.exit()


reaskedtoreply = re.compile('I have been asked to reply\.?\s*')
renotes = re.compile('Notes?:?|Source:?')


pcode = [ '', 'indent', 'italic', 'indentitalic' ]


###########################
# this is the main function
def FilterReply(qs):
	# split into paragraphs.  The second results is a parallel array of bools
	(textp, textpindent) = SplitParaIndents(qs.text, qs.sstampurl)
	if not textp:
		raise Exception, ' no paragraphs in result '


	# the resulting list of paragraphs
	stext = []

	# index into the textp array as we consume it.
	i = 0

	# deal with holding answer phrase at front
	# <i>[holding answer 17 September 2003]:</i>
	qholdinganswer = resqbrack.match(textp[0])
	if qholdinganswer:
		pht = PhraseTokenize(qs, qholdinganswer.group(1))
		stext.append(pht.GetPara('holdinganswer'))
		textp[i] = textp[i][qholdinganswer.span(0)[1]:]
		if not textp[i]:
			i += 1


	# asked to reply
	qaskedtoreply = reaskedtoreply.match(textp[i])
	if qaskedtoreply:
		pht = PhraseTokenize(qs, qaskedtoreply.group(0))
		stext.append(pht.GetPara('askedtoreply'))
		textp[i] = textp[i][qaskedtoreply.span(0)[1]:]
		if not textp[i]:
			i = i+1


	# go through the rest of the paragraphs
	while i < len(textp):
		# deal with tables
		if re.match('<table(?i)', textp[i]):
			if re.match('<table[^>]*>[\s\S]*?</table>$(?i)', textp[i]):
				stext.extend(ParseTable(textp[i], qs.sstampurl))
				i += 1
				continue
			else:
				print "textp[i]: ", textp[i]
				raise ContextException("table start with no end", stamp=qs.sstampurl, fragment=textp[i])

		qletterinlibrary = reletterinlibrary.match(textp[i])
		if qletterinlibrary:
			pht = PhraseTokenize(qs, qletterinlibrary.group(0))
			stext.append(pht.GetPara('letterinlibrary'))
			textp[i] = textp[i][qletterinlibrary.span(0)[1]:]
			if not textp[i]:
				i += 1
			continue

		# <i>Letter from Ruth Kelly to Mr. Frank Field dated 2 December 2003:</i>
		# introducing a previous letter from a civil servant to an MP
		# this should tokenize the pieces more
		qlettfrom = relettfrom.match(textp[i])
		if qlettfrom:
			pht = PhraseTokenize(qs, qlettfrom.group(1))
			stext.append(pht.GetPara('letterfrom'))
			i += 1
			continue

		# nothing special about this paragraph (except it may be indented)
		pht = PhraseTokenize(qs, textp[i])
		stext.append(pht.GetPara(pcode[textpindent[i]], bKillqnum=True))
		i += 1

	return stext


