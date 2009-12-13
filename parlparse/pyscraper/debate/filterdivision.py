import sys
import re
import string
import cStringIO

import mx.DateTime

from resolvemembernames import memberList
from parlphrases import parlPhrases
from miscfuncs import FixHTMLEntities
from contextexception import ContextException


# it's possible we want to make this a class, like with speeches.
# so that it sits in our list easily.

sionsm = "Sio\(r\)n|Sio\[circ\]n|Si\&\#244\;n|Si\&\#246\;n"
fullnm = "([ \w\-'#&;]*), ([ \w\-.#&;]*?|%s)(?:[ \.]rh)?" % sionsm
constnm = "(?:(?:,\s+)?(?:<i>|\()+([ \w&#;\d',.\-]*)(?:\)|</i>)+)"
reflipname = re.compile("%s\s*%s?$" % (fullnm, constnm))
renoflipname = re.compile("([^<\(]*)%s?$" % constnm)
reconstnm = re.compile("%s$" % constnm)

def MpList(fsm, vote, stampurl, sdate):
	# Merge lone listed constituencies onto end of previous line
	newfsm = []
	for fss in fsm:
		if reconstnm.match(fss):
			# print "constnm only %s appending to previous line %s" % (fss, newfsm[-1])
			newfsm[-1] += " " + fss
		else:
			newfsm.append(fss)

	res = [ ]
	pfss = ''

	multimatches = { }  # from tuple to number of matches accounted, and name

	for fss in newfsm:
		#print "fss ", fss

		# break up concattenated lines
		# Beresford, Sir PaulBlunt, Crispin

		while re.search('\S', fss):
			# there was an & in [A-Z] on line below, but it broke up this incorrectly:
			# Simon, Si&#244;n <i>(B'ham Erdington)</i>
			regsep = re.search('(.*?,.*?(?:[a-z]|</i>|\.|\)))([A-Z].*?,.*)$', fss)
			regsep2 = re.match('(.*?,.*?)  ([A-Z].*?,.*)$', fss)
			if regsep and not re.search('  Mc$', regsep.group(1)):
				fssf = regsep.group(1)
				fss = regsep.group(2)
			elif regsep2:
				fssf = regsep2.group(1)
				fss = regsep2.group(2)
			else:
				fssf = fss
				fss = ''

			# check alphabetical - but "rh" and so on confound so don't bother
			#if pfss and (pfss > fssf):
			#	print pfss, fssf
			#	raise Exception, ' out of alphabetical order %s and %s' % (pfss, fssf)
			#pfss = fssf

			# flipround the name
			# Bradley, rh Keith <i>(Withington)</i>
			# Simon, Sio(r)n <i>(Withington)</i>
			#print "fssf ", fssf
			ginp = reflipname.match(fssf)
			if ginp:
				#print "grps ", ginp.groups()
				fnam = '%s %s' % (ginp.group(2), ginp.group(1))
				cons = ginp.group(3)

			# name not being flipped, is firstname lastname
			else:
				ginp = renoflipname.match(fssf)
				if not ginp:
					raise ContextException("No flipped or non-flipped name match (filterdivision)", stamp=stampurl, fragment=fssf)
				fnam = ginp.group(1);
				cons = ginp.group(2);

			#print "fss ", fssf
			(mpid, remadename, remadecons) = memberList.matchfullnamecons(fnam, cons, sdate, alwaysmatchcons = False)
			if not mpid and remadename == "MultipleMatch":
				assert type(remadecons) == tuple  # actually the list of ids
				i = len(multimatches.setdefault(remadecons, []))  # the index we work with
				if i >= len(remadecons):
					print "Name", fnam, "used too many times for list", remadecons, "where other instances are", multimatches[remadecons]
					raise ContextException("Too many instances", stamp=stampurl, fragment=fnam)
				mpid = remadecons[i]
				multimatches[remadecons].append(fnam)

				# appears with multiple matching which is ignorable when both ambiguous people vote on same side of a division
				#print "For name", fnam, "returning id", mpid, ";", i, " out of ", remadecons

			elif not mpid and remadename != "MultipleMatch":
				print "filterdivision.py: no match for", fnam, cons, sdate
				raise ContextException("No match on name", stamp=stampurl, fragment=fnam)
			#print fnam, " --> ", remadename.encode("latin-1")
			res.append('\t<mpname id="%s" vote="%s">%s</mpname>' % (mpid, vote, FixHTMLEntities(fssf)))

	# now we have to check if the multimatched names were all exhausted
	for ids in multimatches:
		if len(multimatches[ids]) != len(ids):
			print "Insufficient vote matches on name", multimatches[ids], "ids taken to", ids
			raise ContextException("Not enough vote match on ambiguous name", stamp=stampurl, fragment=multimatches[ids][0])
	return res

# this pulls out two tellers with the and between them.
def MpTellerList(fsm, vote, stampurl, sdate):
	res = [ ]
	for fss in fsm:
		while fss: # split by lines, but linefeed sometimes missing
			gftell = re.match('\s*(?:and )?([ \w.\-\'&#;]*?)(?:\(([ \w.\-\'&#;]*)\))?(?: and(.*))?\s*\.?\s*$', fss)
			if not gftell:
				raise ContextException("no match on teller line", stamp=stampurl, fragment=fss)

			fssf = gftell.group(1)
			fssfcons = gftell.group(2)
			fss = gftell.group(3)

			if len(res) >= 2:
				print fsm
				raise ContextException(' too many tellers ', stamp=stampurl, fragment=fsm)

			# It always is
			if fssf == 'Mr. Michael Foster':
				fssfcons = 'Worcester'

			(mpid, remadename, remadecons) = memberList.matchfullnamecons(fssf.strip(), fssfcons, sdate)
                        #print fssf, " ++> ", remadename.encode("latin-1")
			if not mpid:
				raise ContextException("teller name bad match", stamp=stampurl, fragment=fssf)
			res.append('\t<mpname id="%s" vote="%s" teller="yes">%s</mpname>' % (mpid, vote, FixHTMLEntities(fssf)))

	return res


# this splitting up isn't going to deal with some of the bad cases in 2003-09-10
def FilterDivision(text, stampurl, sdate):
	# discard all italics
	text = re.sub("</?i>(?i)", "", text)
	text = re.sub(":", ":<br>", text)

	# the intention is to splice out the known parts of the division
	fs = re.split('\s*(?:</?br>|</?p>|<p align=left(?: class="tabletext")?>|</font>|\n)\s*(?i)', text)

	# extract the positions of the key statements
	statem = [ 	'AYES|<b>AYES</b>',
				'Tellers for the Ayes:',
				'NOES|<b>NOES</b>',
				'Tellers for the Noes:', ]
				#'Question accordingly.*|</FONT>|</p>' ]
	istatem = [ -1, -1, -1, -1 ]

	for i in range(len(fs)):
		for si in range(4):
			if re.match(statem[si], fs[i]):
				if istatem[si] != -1:
					print '--------------- ' + fs[i]
					raise ContextException(' already set ', stamp=stampurl, fragment=fs)
				else:
					istatem[si] = i

	# add fifth place (ending)
	istatem.append(len(fs))

	# deferred division, no tellers
	if istatem[1] == -1 and istatem[3] == -1:
		istatem[1] = istatem[2]
		istatem[3] = istatem[4]


	for si in range(5):
		if istatem[si] == -1:
			print istatem
			raise ContextException(' division delimiter not set ', stamp=stampurl, fragment=fs)

	mpayes = [ ]
	mptayes = [ ]
	mpnoes = [ ]
	mptnoes = [ ]

	if (istatem[0] < istatem[1]) and (istatem[0] != -1) and (istatem[1] != -1):
		mpayes = MpList(fs[istatem[0]+1:istatem[1]], 'aye', stampurl, sdate)
	if (istatem[2] < istatem[3]) and (istatem[2] != -1) and (istatem[3] != -1):
		mpnoes = MpList(fs[istatem[2]+1:istatem[3]], 'no', stampurl, sdate)

	if (istatem[1] < istatem[2]) and (istatem[1] != -1) and (istatem[2] != -1):
		mptayes = MpTellerList(fs[istatem[1]+1:istatem[2]], 'aye', stampurl, sdate)
	if (istatem[3] < istatem[4]) and (istatem[3] != -1) and (istatem[4] != -1):
		mptnoes = MpTellerList(fs[istatem[3]+1:istatem[4]], 'no', stampurl, sdate)


	stext = [ ]
	stext.append('<divisioncount ayes="%d" noes="%d" tellerayes="%d" tellernoes="%d"/>' % (len(mpayes), len(mpnoes), len(mptayes), len(mptnoes)))
	stext.append('<mplist vote="aye">')
	stext.extend(mpayes)
	stext.extend(mptayes)
	stext.append('</mplist>')
	stext.append('<mplist vote="no">')
	stext.extend(mpnoes)
	stext.extend(mptnoes)
	stext.append('</mplist>')

	return stext




