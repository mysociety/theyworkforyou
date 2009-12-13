import sys
import re
import string
import cStringIO

import mx.DateTime

from parlphrases import parlPhrases
from miscfuncs import SplitParaIndents

from filterwransreply import reletterinlibrary
from filterwransreplytable import ParseTable

from miscfuncs import FixHTMLEntities

#from filtersentence import FilterSentence
from filtersentence import PhraseTokenize

# lots of work to do here on the internal structure of each speech

pcode = [ '', 'indent', 'italic', 'indentitalic' ]


# this is the main function.
def FilterWMSSpeech(qs):

	# split into paragraphs.  The second results is a parallel array of bools
	(textp, textpindent) = SplitParaIndents(qs.text, qs.sstampurl)

	qs.stext = [ ]
	i = 0

	# go through the rest of the paragraphs
	while i < len(textp):

		# deal with tables
		if re.match('<table(?i)', textp[i]):
			qs.stext.extend(ParseTable(textp[i], qs.sstampurl))
			i += 1
			continue

#		qletterinlibrary = reletterinlibrary.match(textp[i])
#		print qletterinlibrary
#		if qletterinlibrary:
#			pht = PhraseTokenize(qs, qletterinlibrary.group(0))
#			stext.append(pht.GetPara('letterinlibrary'))
#			textp[i] = textp[i][qletterinlibrary.span(0)[1]:]
#			if not textp[i]:
#				i += 1
#			continue

		# nothing special about this paragraph (except it may be indented)
		pht = PhraseTokenize(qs, textp[i])
		tpx = pht.GetPara(pcode[textpindent[i]])
		qs.stext.append(tpx)
		i += 1



