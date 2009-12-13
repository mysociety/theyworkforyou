import sys
import re
import string
import cStringIO

import mx.DateTime

from parlphrases import parlPhrases
from miscfuncs import SplitParaIndents

from filterwransreplytable import ParseTable

from miscfuncs import FixHTMLEntities

#from filtersentence import FilterSentence
from filtersentence import PhraseTokenize

# lots of work to do here on the internal structure of each speech

pcode = [ '', 'indent', 'italic', 'indentitalic' ]


# this is the main function.
def FilterDebateSpeech(qs, bDebateBegToMove=False):

	# split into paragraphs.  The second results is a parallel array of bools
	(textp, textpindent) = SplitParaIndents(qs.text, qs.sstampurl)

	qs.stext = [ ]
	i = 0

	# go through the rest of the paragraphs
	bBegToMove = False
	while i < len(textp):

		# deal with tables
		if re.match('<table(?i)', textp[i]):
			qs.stext.extend(ParseTable(textp[i], qs.sstampurl))
			bBegToMove = False

		# nothing special about this paragraph (except it may be indented)
		else:
			if re.match("I beg to move", textp[i]):
				btBegToMove = True      # it would be elegant if this was moved out to the filterdebate stuff where it belongs
			else:
				btBegToMove = False

			pht = PhraseTokenize(qs, textp[i])
			tpx = pht.GetPara(pcode[textpindent[i]], (bDebateBegToMove and (bBegToMove or btBegToMove)))
			bBegToMove = btBegToMove

			qs.stext.append(tpx)

		i = i + 1



