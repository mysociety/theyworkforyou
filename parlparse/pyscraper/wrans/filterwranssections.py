# vim:sw=8:ts=8:et:nowrap
import sys
import re
import os
import string


import mx.DateTime


from miscfuncs import ApplyFixSubstitutions

from splitheadingsspeakers import SplitHeadingsSpeakers
from splitheadingsspeakers import StampUrl

from clsinglespeech import qspeech
from parlphrases import parlPhrases

from miscfuncs import FixHTMLEntities

from filterwransques import FilterQuestion
from filterwransreply import FilterReply

from contextexception import ContextException

# Legacy patch system, use patchfilter.py and patchtool now
fixsubs = 	[
	( '<h2><center>written answers to</center></h2>\s*questions(?i)', \
	  	'<h2><center>Written Answers to Questions</center></h2>', -1, 'all'),
	( '<h\d align=center>written answers[\s\S]{10,150}?\[continued from column \d+?W\](?:</h\d>)?(?i)', '', -1, 'all'),
	( '<h\d><center>written answers[\s\S]{10,150}?\[continued from column \d+?W\](?i)', '', -1, 'all'),
]



# parse through the usual intro headings at the beginning of the file.
def StripWransHeadings(headspeak, sdate):
	# check and strip the first two headings in as much as they are there
	i = 0
	if (headspeak[i][0] != 'Initial') or headspeak[i][2]:
		print headspeak[0]
		raise ContextException('non-conforming Initial heading ')
	i += 1

	if (not re.match('(?:<stamp aname="[^"]*"/>)*written answers?(?: to questions?)?(?i)', headspeak[i][0])) or headspeak[i][2]:
		if not re.match('The following answers were received.*', headspeak[i][0]):
			pass
                        # print headspeak[i]
	else:
		i += 1

        givendate = string.replace(headspeak[i][0], "&nbsp;", " ")
        givendate = re.sub("</?i>", "", givendate)
        gd = re.match('(?:<stamp aname="[^"]*"/>)*(.*)$', givendate)
        if gd:
                givendate = gd.group(1)
       	if (not re.match('(?i)(?:<stamp[^>]*>)*(?:<i>)?\s*The following answers were received.*', headspeak[i][0]) and
               not re.match('(?:<stamp[^>]*>)?The following question was answered on.*', headspeak[i][0]) and \
      			(sdate != mx.DateTime.DateTimeFrom(givendate).date)) or headspeak[i][2]:
       		if (not parlPhrases.wransmajorheadings.has_key(headspeak[i][0])) or headspeak[i][2]:
       			print headspeak[i]
       			raise ContextException('non-conforming second heading', stamp=None, fragment=headspeak[i][0])
       	else:
       		i += 1

	# find the url and colnum stamps that occur before anything else
	stampurl = StampUrl(sdate)
	for j in range(0, i):
		stampurl.UpdateStampUrl(headspeak[j][0])
		stampurl.UpdateStampUrl(headspeak[j][1])

        # Later editions seem to miss first column number, sigh
        if not stampurl.stamp:
                for speeches in headspeak:
                        text = ''.join([ speech[1] for speech in speeches[2] ])
                        m = re.search('colnum="(\d+)W"', text)
                        if m:
                                stampurl.UpdateStampUrl('<stamp coldate="%s" colnum="%dW"/>' % (sdate, int(m.group(1))-1))
                                break

        if not stampurl.stamp or not stampurl.pageurl or not stampurl.aname:
	        	raise ContextException('missing stamp url at beginning of file')
	return (i, stampurl)







################
# main function
################
def FilterWransSections(text, sdate, lords=False):
	text = ApplyFixSubstitutions(text, sdate, fixsubs)
	headspeak = SplitHeadingsSpeakers(text)


	# break down into lists of headings and lists of speeches
	(ih, stampurl) = StripWransHeadings(headspeak, sdate)


	# full list of question batches
	# We create a list of lists of speeches
	flatb = [ ]
        justhadnewtitle = False # For when they put another "Written Answers to Questions" and date
	for sht in headspeak[ih:]:
		# triplet of ( heading, unspokentext, [(speaker, text)] )
		headingtxt = stampurl.UpdateStampUrl(string.strip(sht[0]))  # we're getting stamps inside the headings sometimes
		unspoketxt = sht[1]
		speechestxt = sht[2]

		# update the stamps from the pre-spoken text
		if (not re.match('(?:<[^>]*>|\s)*$', unspoketxt)):
			raise ContextException("unspoken text under heading in wrans", stamp=stampurl, fragment=unspoketxt)
		stampurl.UpdateStampUrl(unspoketxt)

		# headings become one unmarked paragraph of text

		# detect if this is a major heading
		if not re.search('[a-z]', headingtxt) and not speechestxt:
			if not parlPhrases.wransmajorheadings.has_key(headingtxt):
				raise ContextException("unrecognized major heading, please add to parlPhrases.wransmajorheadings (a)", fragment = headingtxt, stamp = stampurl)
			majheadingtxtfx = parlPhrases.wransmajorheadings[headingtxt] # no need to fix since text is from a map.
			qbH = qspeech('nospeaker="true"', majheadingtxtfx, stampurl)
			qbH.typ = 'major-heading'
			qbH.stext = [ majheadingtxtfx ]
			flatb.append(qbH)
			continue
                elif not speechestxt and sdate > '2006-05-07':
                        if headingtxt == 'Written Answers to Questions':
                                justhadnewtitle = True
                                continue
			if not parlPhrases.wransmajorheadings.has_key(headingtxt.upper()):
                                if justhadnewtitle:
                                        justhadnewtitle = False
                                        continue
				raise ContextException("unrecognized major heading, please add to parlPhrases.wransmajorheadings (b)", fragment = headingtxt, stamp = stampurl)
			majheadingtxtfx = parlPhrases.wransmajorheadings[headingtxt.upper()] # no need to fix since text is from a map.
			qbH = qspeech('nospeaker="true"', majheadingtxtfx, stampurl)
			qbH.typ = 'major-heading'
			qbH.stext = [ majheadingtxtfx ]
			flatb.append(qbH)
                        justhadnewtitle = False
			continue
                elif not speechestxt:
                        raise ContextException('broken heading %s' % headingtxt, stamp=stampurl, fragment=headingtxt)


		# non-major heading; to a question batch
		if parlPhrases.wransmajorheadings.has_key(headingtxt):
			raise Exception, ' speeches found in major heading %s' % headingtxt

		headingtxtfx = FixHTMLEntities(headingtxt)
		headingmark = 'nospeaker="true"'
		bNextStartofQ = True

		# go through each of the speeches in a block and put it into our batch of speeches
		qnums = []	# used to account for spurious qnums seen in answers
		for ss in speechestxt:
			qb = qspeech(ss[0], ss[1], stampurl)
			#print ss[0] + "  " + stampurl.stamp
			lqnums = re.findall('\[(?:HL)?(\d+)R?\]', ss[1])

			# question posed
			if re.match('(?:<[^>]*?>|\s)*?(to ask|asked (Her Majesty(&#039;|&#146;|\')s Government|the ))(?i)', qb.text) or \
                           re.search('<wrans-question>', qb.text):
                                qb.text = qb.text.replace('<wrans-question>', '')
				qb.typ = 'ques'

				# put out the heading for this question-reply block.
				# we don't assert true since we can have multiple questions answsered in a block.
				if bNextStartofQ:
					# put out a heading
					# we need to make the heading of from the same stampurl as the first question
					qbh = qspeech(headingmark, headingtxtfx, qb.sstampurl)
					qbh.typ = 'minor-heading'
					qbh.stext = [ headingtxtfx ]
					flatb.append(qbh)

					bNextStartofQ = False

					# used to show that the subsequent headings in this block have been created,
					# and weren't in the original text.
					headingmark = 'nospeaker="true" inserted-heading="true"'
					qnums = lqnums # reset the qnums count
				else:
					qnums.extend(lqnums)

				qb.stext = FilterQuestion(qb, sdate, lords)
				if not lqnums:
					errmess = ' <p class="error">Question number missing in Hansard, possibly truncated question.</p> '
					qb.stext.append(errmess)

				flatb.append(qb)

			# do the reply
			else:
				if bNextStartofQ:
					raise ContextException('start of question expected', stamp = qb.sstampurl, fragment = qb.text)
				qb.typ = 'reply'

				# this case is so rare we flag them in the corrections of the html with this tag
				if re.search("\<another-answer-to-follow\>", qb.text):
					qb.text = qb.text.replace("<another-answer-to-follow>", "")
				else:
					bNextStartofQ = True

				# check against qnums which are sometimes repeated in the answer code
                                # Don't care if qnum is given in an answer!
				#for qn in lqnums:
				#	# sometimes [n] is an enumeration or part of a title
				#	nqn = string.atoi(qn)
				#	if (not qnums.count(qn)) and (nqn > 100) and ((nqn < 1900) or (nqn > 2010)):
				#		if qb.text.find("<ok-extra-qnum>") >= 0:
				#			qb.text = qb.text.replace("<ok-extra-qnum>", "", 1)
				#		else:
				#			raise ContextException('unknown qnum %s present in answer, make it clear' % qn, stamp = qb.sstampurl, fragment = qb.text)
				qb.stext = FilterReply(qb)
				flatb.append(qb)

		if not bNextStartofQ:
                        print speechestxt
                        # Note - not sure if this should be speechestxt[-1][1] here.  Does what I want for now...
			raise ContextException("missing answer to question", stamp=stampurl, fragment=speechestxt[-1][1])


	# we now have everything flattened out in a series of speeches,
	# where some of the speeches are headings (inserted and otherwise).
	return flatb


