# vim:sw=8:ts=8:et:nowrap
# -*- coding: latin-1 -*-

import sys
import re
import os
import string
from resolvelordsnames import lordsList

from miscfuncs import ApplyFixSubstitutions
from miscfuncs import IsNotQuiet
from contextexception import ContextException

from splitheadingsspeakers import StampUrl

# marks out center types bold headings which are never speakers
respeaker = re.compile('(<center><b>(?:<stamp aname="[^"]*"/>)?[^<]*</b></center>|<b>(?:<stamp aname="[^"]+"/>|</b><b>|[^<]+)*</b>(?:\s*:)?)(?i)')
respeakerb = re.compile('<b>\s*((?:<stamp aname="[^"]*"/>|</b><b>|[^<]+)*),?\s*</b>(\s*:)?(?i)')
respeakervals = re.compile('([^:(]*?)\s*(?:\(([^:)]*)\)?)?(:)?:*$')

renonspek = re.compile('division|contents|amendment(?i)')
reboldempty = re.compile('<b>\s*</b>(?i)')

regenericspeak = re.compile('the (?:deputy )?chairman of committees|(?:the )?deputy speaker|the clerk of the parliaments|several noble lords|the deputy chairman(?: of committees)?|the noble lord said(?i)')
#retitlesep = re.compile('(Lord|Baroness|Viscount|Earl|The Earl of|The Lord Bishop of|The Duke of|The Countess of|Lady)\s*(.*)$')



def LordsFilterSpeakers(fout, text, sdate):
	stampurl = StampUrl(sdate)

	officematches = {}

	# setup for scanning through the file.
	for fss in respeaker.split(text):

		# strip off the bolds tags
		# get rid of non-bold stuff
		bffs = respeakerb.match(fss)
		if not bffs:
			fout.write(fss)
			stampurl.UpdateStampUrl(fss)
			continue

		stampurl.UpdateStampUrl(fss)

		# grab a trailing colon if there is one
		fssb = bffs.group(1)
		if bffs.group(2):
			fssb = fssb + ":"

                # Remove the cruft
                fssb = re.sub('<stamp aname="[^"]*"/>', '', fssb)
                fssb = re.sub('</b><b>', '', fssb)

		# empty bold phrase
		if not re.search('\S', fssb):
			continue

		# division/contents/amendment which means this is not a speaker
		if renonspek.search(fssb):
			fout.write(fss)
			continue

		# part of quotes as an inserted title in an amendment
		if re.match('("|\[|&quot;)', fssb):
			fout.write(fss)
			continue

		# another title type (all caps)
		if not re.search('[a-z]', fssb):
			fout.write(fss)
			continue

		# start piecing apart the name by office and leadout type
		namec = respeakervals.match(fssb)
		if not namec:
			print fssb
			raise ContextException("bad format", stamp=stampurl, fragment=fssb)

		if namec.group(2):
			name = re.sub('\s+', ' ', namec.group(2))
			loffice = re.sub('\s+', ' ', namec.group(1))
		else:
			name = re.sub('\s+', ' ', namec.group(1))
			loffice = None

		colon = namec.group(3)
		if not colon:
			colon = ""

		# get rid of some standard ones
		if re.match('the lord chancellor|noble lords|a noble lord|a noble baroness|the speaker(?i)', name):
			fout.write('<speaker speakerid="%s" speakername="%s">%s</speaker>' % ('unknown', name, name))
			continue


		# map through any office information
		if loffice:
			if (not re.match("The Deputy ", loffice)) and (loffice in officematches):
				if officematches[loffice] != name:
					print officematches[loffice], loffice,  name
                                if officematches[loffice] <> name: 
                                        raise ContextException("office inconsistency, loffice: %s name: %s officematches: %s" % (loffice, name, officematches[loffice]), stamp=stampurl, fragment=fssb)
			else:
				officematches[loffice] = name
		elif name in officematches:
			loffice = name
			name = officematches[loffice]

		if regenericspeak.match(name):
			fout.write('<speaker speakerid="%s" speakername="%s">%s</speaker>' % ('unknown', name, name))
			continue

		lsid = lordsList.GetLordIDfname(name, loffice=loffice, sdate=sdate, stampurl=stampurl)  # maybe throw the exception on the outside

                if not lsid:
                        fout.write('<speaker speakerid="unknown" error="No match" speakername="%s" colon="%s">%s</speaker>' % (name, colon, name))
                else:
                        fout.write('<speaker speakerid="%s" speakername="%s" colon="%s">%s</speaker>' % (lsid, name, colon, name))





