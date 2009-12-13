#! /usr/bin/python2.4
# vim:sw=8:ts=8:nowrap

import re
import sys
import string
import os
import xml.sax

from contextexception import ContextException
import miscfuncs
toppath = miscfuncs.toppath

def WriteXMLHeader(fout, encoding="ISO-8859-1"):
	fout.write('<?xml version="1.0" encoding="%s"?>\n' % encoding)

	# These entity definitions for latin-1 chars are from here:
	# http://www.w3.org/TR/REC-html40/sgml/entities.html
	# also available at: http://www.csparks.com/CharacterEntities.html
	fout.write('''

<!DOCTYPE publicwhip
[
<!ENTITY ndash   "&#8211;">
<!ENTITY mdash   "&#8212;">
<!ENTITY iexcl   "&#161;">
<!ENTITY divide  "&#247;">
<!ENTITY euro    "&#8364;">
<!ENTITY trade   "&#8482;">
<!ENTITY bull    "&#8226;">
<!ENTITY lsquo   "&#8216;">
<!ENTITY rsquo   "&#8217;">
<!ENTITY sbquo   "&#8218;">
<!ENTITY ldquo   "&#8220;">
<!ENTITY rdquo   "&#8221;">
<!ENTITY bdquo   "&#8222;">
<!ENTITY dagger  "&#8224;">

<!ENTITY Ouml   "&#214;" >
<!ENTITY agrave "&#224;" >
<!ENTITY aacute "&#225;" >
<!ENTITY acirc  "&#226;" >
<!ENTITY atilde "&#227;" >
<!ENTITY auml   "&#228;" >
<!ENTITY ccedil "&#231;" >
<!ENTITY egrave "&#232;" >
<!ENTITY eacute "&#233;" >
<!ENTITY ecirc  "&#234;" >
<!ENTITY euml   "&#235;" >
<!ENTITY iacute "&#237;" >
<!ENTITY icirc  "&#238;" >
<!ENTITY iuml	"&#239;" >
<!ENTITY ntilde "&#241;" >
<!ENTITY nbsp   "&#160;" >
<!ENTITY oacute "&#243;" >
<!ENTITY ocirc  "&#244;" >
<!ENTITY ouml   "&#246;" >
<!ENTITY oslash "&#248;" >
<!ENTITY uacute "&#250;" >
<!ENTITY uuml   "&#252;" >
<!ENTITY thorn  "&#254;" >

<!ENTITY pound  "&#163;" >
<!ENTITY sect   "&#167;" >
<!ENTITY copy   "&#169;" >
<!ENTITY reg    "&#174;" >
<!ENTITY deg    "&#176;" >
<!ENTITY plusmn "&#177;" >
<!ENTITY sup2   "&#178;" >
<!ENTITY micro  "&#181;" >
<!ENTITY para   "&#182;" >
<!ENTITY middot "&#183;" >
<!ENTITY ordm   "&#186;" >
<!ENTITY frac14 "&#188;" >
<!ENTITY frac12 "&#189;" >
<!ENTITY frac34 "&#190;" >
<!ENTITY oelig "&#339;" >
<!ENTITY aelig  "&#230;" >

]>

''');


# write out a whole file which is a list of qspeeches, and construct the ids.
def CreateGIDs(gidpart, sdate, sdatever, flatb):
	pcolnum = "####"
	picolnum = -1
	ncid = -1
	colnumoffset = 0

	# the missing gid numbers come previous to the gid they would have gone, to handle missing ones before the 0
	# 0-1, 0-2, 0, 1, 2, 3-0, 3-1, 3, ...
	ncmissedgidrun = 0
	ncmissedgid = 0

	for qb in flatb:

		# construct the gid
		realcolnum = re.search('colnum="([^"]*)"', qb.sstampurl.stamp).group(1)

		# this updates any column number corrections that were appended on the end of the stamp
		for realcolnum in re.findall('parsemess-colnum="([^"]*)"', qb.sstampurl.stamp):
			pass

		# this is to do a mass change of column number when they've got out of sync with the GIDs
		# (normally due to Hansard's cm->vo transition)
		for colnumoffset in re.findall('parsemess-colnumoffset="([^"]*)"', qb.sstampurl.stamp):
			colnumoffset = string.atoi(colnumoffset)

		realcolnumbits = re.match('(\d+)([WS]*)$', realcolnum)
		irealcolnum = int(realcolnumbits.group(1))
		colnumN = irealcolnum + colnumoffset
		colnum = str(colnumN) + realcolnumbits.group(2)

		qb.ignorenamemismatch = re.search('parsemess-ignorenamemismatch="yes"', qb.sstampurl.stamp)


		# this numbers the speech numbers in the column numbers
		if colnum != pcolnum:
			# check that the column numbers are increasing
			# this is essential if the gids are to be unique.
			icolnum = string.atoi(re.match('(\d+)[WS]*$', colnum).group(1))
			if icolnum <= picolnum:
				print qb.sstampurl.stamp
				raise ContextException("non-increasing column numbers %s %d" % (colnum, picolnum), stamp=qb.sstampurl, fragment=colnum)
			picolnum = icolnum

			pcolnum = colnum
			ncid = 0
			ncmissedgidrun = 0
			ncmissedgid = 0
		else:
			ncid += 1

		# this executes the missing ncid numbering command
		bmissgid = False
		lsmissgid = re.findall('parsemess-missgid="([^"]*)"', qb.sstampurl.stamp)
		for missgid in lsmissgid:
			if ncid == string.atoi(missgid):
				bmissgid = True

		if bmissgid:
			ncmissedgidrun += 1
			missedgidext = "-%d" % ncmissedgidrun
		else:
			ncmissedgidrun = 0
			missedgidext = ""

		# this is our GID !!!!
		qb.shortGID = '%s.%s.%d%s' % (sdatever, colnum, ncid - ncmissedgid, missedgidext)
		qb.GID = 'uk.org.publicwhip/%s/%s%s' % (gidpart, sdate, qb.shortGID)
		if bmissgid:
			ncmissedgid += 1

		# build the parallel set of GIDs for the paragraphs (in preparation for an upgrade)
		qb.stextptags = [ ' pid="%s/%d"' % (qb.shortGID, i+1)  for i in range(len(qb.stext)) ]

		# make a place to record the gidredirects which we obtain on the way through
		qb.gidredirect = [ ]


def WriteXMLspeechrecord(fout, qb, bMakeOldWransGidsToNew, bIsWrans):
	# Is this value needed?
	colnum = re.search('colnum="([^"]*)"', qb.sstampurl.stamp).group(1)

	# extract the time stamp (if there is one, which is optional)
	stime = ""
	if qb.sstampurl.timestamp:
		stime = re.match('<stamp( time=".*?")/>', qb.sstampurl.timestamp).group(1)

	fout.write('\n')

	if bMakeOldWransGidsToNew:
		assert bIsWrans
		fout.write('<gidredirect oldgid="%s" newgid="%s" matchtype="oldwransgid"/>\n' % (qb.GID, qb.qGID))

	# decompose so we can make the wrans types
	if bIsWrans:
		lid = qb.qGID
		lmidstr = 'oldstyleid="%s" %s' % (qb.GID, qb.speaker)
	else:
		lid = qb.GID
		lmidstr = qb.speaker

	# build the full tag for this object
	# some of the info is a repeat of the text in the GID
	fulltag = '<%s id="%s" %s colnum="%s" %s url="%s">\n' % (qb.typ, lid, lmidstr, colnum, stime, qb.sstampurl.GetUrl())
	fout.write(fulltag)

	# put out the paragraphs in body text, sneakily inserting some extra stuff into the <p> tags
	assert not qb.stextptags or len(qb.stextptags) == len(qb.stext)
	i = 0
	for lb in qb.stext:
		fout.write('\t')
		mp = re.match("\s*<(?:p|tr)", lb)
		if qb.stextptags and mp:
			fout.write(lb[:mp.end(0)])
			fout.write(qb.stextptags[i])  # the inserting of a tag (probably an id or something)
			fout.write(lb[mp.end(0):])
		else:
			fout.write(lb)
		fout.write('\n')
		i += 1

	# end tag
	fout.write('</%s>\n' % qb.typ)


# these altheadinggids were really only necessary when we were over-writing the xml files each time
# and had to defend against questions getting merged into groups.
# possibly they could be taken out as the linkforward is actually done between xml files and
# you'd merely get two separate questions pointing to one batch in the next file.

errco = 9900
class wransblock:
	def __init__(self, lqb):
		self.headingqb = lqb
		self.queses = [ ]
		self.replies = [ ]
		self.qnums = [ ]
		self.altheadinggids = [ ]

	def addqb(self, lqb):
		global errco
		if lqb.typ == "ques":
			self.queses.append(lqb)
			# this handles qnum list, or single qnum question (a bit terse)
			for lb in (lqb.stext[1:] or lqb.stext):
				reqnm = re.search('<p (?:class="numindent" )?qnum="([\d\w]+)">', lb)
				if not reqnm:   # missing qnum!
					if re.search('<p class="error">', lb):
						self.qnums.append("ZZZZerror%d" % errco)
						errco += 1
					else:
						print lb
						print lqb.GID
						assert False

				elif reqnm.group(1) == '0':
					print "missing qnum in", lqb.GID   # as long as there is at least one good number in the block of questions , we are okay
				else:
					self.qnums.append(reqnm.group(1))

		elif lqb.typ == "reply":
			self.replies.append(lqb)
		else:
			assert False

	# the function that does the business
	def regidcodes(self, minhgid, sdate, qnumsseen):
		# find minimal qnum which will be used as the basis
		self.qnums.sort()
		if not self.qnums:
			print self.headingqb.stext[0]
			for ques in self.queses:
				print ques.stext
			raise ContextException('missing qnums on question')
		basegidq = 'uk.org.publicwhip/wrans/%s.%s' % (sdate, self.qnums[0])
		self.headingqb.qGID = basegidq + ".h"  # this is what we link to
		for rqnum in self.qnums[1:]:   # the mapping for the other qnums
			self.altheadinggids.append('uk.org.publicwhip/wrans/%s.%s.h' % (sdate, rqnum))

		# renumber the parts of the question (which aren't going to be linked to anyway)
		for i in range(len(self.queses)):
			self.queses[i].qGID = "%s.q%d" % (basegidq, i)
		for i in range(len(self.replies)):
			self.replies[i].qGID = "%s.r%d" % (basegidq, i)

		# make sure all qnums are new
		for qnum in self.qnums:
			if qnum in qnumsseen:
				print "repeated qnum:", qnum
				raise ContextException('repeated qnum', None, qnum)
			qnumsseen[qnum] = 1

		# this value is used for labelling the major heading.
		# high probability that the value is stable, but it won't be used for linking
		if not minhgid or (basegidq < minhgid):
			minhgid = basegidq
		return minhgid

	def WriteXMLrecords(self, fout, bMakeOldWransGidsToNew):
		WriteXMLspeechrecord(fout, self.headingqb, bMakeOldWransGidsToNew, True)
		if self.altheadinggids:
			fout.write('\n')
		for ah in self.altheadinggids:
			fout.write('<gidredirect oldgid="%s" newgid="%s" matchtype="altques"/>\n' % (ah, self.headingqb.qGID))

		for qb in self.queses:
			WriteXMLspeechrecord(fout, qb, bMakeOldWransGidsToNew, True)
		for qb in self.replies:
			WriteXMLspeechrecord(fout, qb, bMakeOldWransGidsToNew, True)

	def FlattenTextWords(self):
		flatparas = [ ]
		for qb in self.queses:
			flatparas.extend(qb.stext)
		for qb in self.replies:
			flatparas.extend(qb.stext)

		res = [ ]
		for flatpara in flatparas:
			res.extend(re.split("<[^>]*>|\s+", flatpara))
		return res


# this is the code for implementing the new gids code
# keep your hacking to this area and things will be simple
def CreateWransGIDs(flatb, sdate):
	global errco
	errco = 9900  # reset
	# first divide into major blocks and wranswer pieces
	majblocks = [ ]
	for qb in flatb:
		if qb.typ == "major-heading":
			majblocks.append((qb, [ ]))
		elif qb.typ == "minor-heading":
			majblocks[-1][1].append(wransblock(qb))
		else:
			majblocks[-1][1][-1].addqb(qb)

	# now renumber the gids everywhere
	qnumsseen = { }
	for majblock in majblocks:
		minqnum = ""
		for qblock in majblock[1]:
			minqnum = qblock.regidcodes(minqnum, sdate, qnumsseen)
		assert minqnum
		majblock[0].qGID = minqnum + ".mh" # major heading
	return majblocks




