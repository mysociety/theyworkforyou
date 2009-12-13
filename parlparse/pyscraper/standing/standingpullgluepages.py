# vim:sw=8:ts=8:et:nowrap

import sys
import urllib
import urlparse
import re
import os.path
import xml.sax
import time
import string
import tempfile

import miscfuncs
toppath = miscfuncs.toppath
pwcmdirs = miscfuncs.pwcmdirs
tempfilename = miscfuncs.tempfilename

from miscfuncs import NextAlphaString, AlphaStringToOrder
from pullgluepages import ReplicatePatchToNewScrapedVersion


# index file which is created
pwstandingindex = os.path.join(toppath, "standingindex.xml")

# output directories (everything of one day in one file).
pwstandingpages = os.path.join(pwcmdirs, "standing")


# this does the main loading and gluing of the initial day debate
# files from which everything else feeds forward

# gets the index file which we use to go through the pages
class LoadStandingIndex(xml.sax.handler.ContentHandler):
	def __init__(self, lpwcmindex):
		self.res = []
		if not os.path.isfile(lpwcmindex):
			return
		parser = xml.sax.make_parser()
		parser.setContentHandler(self)
		parser.parse(lpwcmindex)

	# <standingcttee shortname=""
	#				 session="1997"
	#				 date="1998-03-03"
	#				 sittingnumber="21"
	#				 sittingpart="0"
	#				 daypart="afternoon"
	#				 committeename="Standing Committee A"
	#				 billtitle="School Standards and Framework Bill"
	#				 urlindex="http://www.publications.parliament.uk/pa/cm199798/cmstand/a/cmschol.htm"
	#				 url="st980303/pm/80303s01.htm"/>
	def startElement(self, name, attr):
		if name == "standingcttee":
			#print attr["sittingnumber"]
			if attr["sittingpart"] != "99999":  # evidence type
				ddr = (attr["shortname"], attr["url"], attr["date"], attr["billtitle"])
				self.res.append(ddr)



def GlueByNext(fout, urlx, billtitle):
	# put out the indexlink for comparison with the hansardindex file
	lt = time.gmtime()
	fout.write('<pagex url="%s" scrapedate="%s" scrapetime="%s" billtitle="%s"/>\n' % \
			(urlx, time.strftime('%Y-%m-%d', lt), time.strftime('%X', lt), billtitle))
	url = urlx

	pageheader = '<img\s*src="/pa/img/portsgrn.gif"\s*alt="House\s*of\s*Commons\s*portcullis"><BR>'
	# there are various green button gifs, including two which say "continue", but with different filenames
	pagefooter = '<a href\s*=\s*"[^"]*">\s*<img border=0(?: align=top)? src="/pa/img/(?:ctntgrn|conugrn|prevgrn|contgrn).gif"'
	if re.search("/pa/cm200203/cmstand/d/st030401/am/30401s01.htm$", urlx):
		pageheader = "<!--end of UK Parliament banner for Publications -->" 
	if re.search("/pa/cm200102/cmstand/d/st020115/am/20115s01.htm$", urlx):
		pageheader = "<!--end of UK Parliament banner for Publications -->"		  
	if re.search("/pa/cm200304/cmstand/c/st040428/pm/40428s01.htm$", urlx):
		pageheader = "<!--end of UK Parliament banner for Publications-->"
	if re.search("/pa/cm200203/cmstand/c/st030402/30402s01.htm$", urlx):
		pageheader = "<!--end of UK Parliament banner for Publications-->"
	if re.search("/pa/cm200102/cmstand/g/st020213/am/20213s01.htm$", urlx):
		pageheader = "<!--end of UK Parliament banner for Publications-->"
	if re.search("/pa/cm199900/cmstand/f/st000525/00525s10.htm#pm$", urlx):
		pageheader = "<a name=pm>"
		url = re.sub("#pm", "", url)

	# loop which scrapes through all the pages following the nextlinks
	# knocking off the known links as we go in case a "next page" is missing.
	while True:
		if re.search("/pa/cm199798/cmstand/b/st971106/am/71106s04.htm$", url):
			url = re.sub("s04.htm", "s05.htm", url)  # skip over missing page

		ur = urllib.urlopen(url)
		sr = ur.read()
		ur.close();

		# write the marker telling us which page this comes from
		fout.write('<page url="' + url + '"/>\n')
		
		repagebody = '(?si).*?%s(.*?)%s' % (pageheader, pagefooter)
		mbody = re.match(repagebody, sr)
		if not mbody:
			if re.search("/pa/cm199899/cmstand/e/st990429/am/90429s03.htm$", url):  # continuation does not exist
				break
			if re.search("/pa/cm199899/cmstand/special/st990420/pm/pt3/90420s12.htm$", url):  # continuation does not exist
				break
			if re.search("/pa/cm200203/cmstand/d/st031016/pm/31016s06.htm$", url): # continuation does not exist
				break

			print "\n", pageheader, "\n\n", pagefooter, "\n\n"
			print "header", re.search('(?si)' + pageheader, sr)
			print "footer", re.search('(?si)' + pagefooter, sr)
			print url
			print sr[:2000]
			assert False

		miscfuncs.WriteCleanText(fout, mbody.group(1), False)
		# the files are sectioned by the <hr> tag into header, body and footer.
		mnextsectionlink = re.search('(?si)<\s*a\s+href\s*=\s*"?([^"]*?)"?\s*>\s*<img border=0 align=top src="/pa/img/conugrn.gif"', sr[mbody.end(1):])
		#print "   nextsectionlink", mnextsectionlink
		if not mnextsectionlink:
			break
		url = urlparse.urljoin(url, mnextsectionlink.group(1))
		if miscfuncs.IsNotQuiet():
			print "  ", re.sub(".*?cmstand/", "", url)

		# second and subsequent pages
		pageheader = '<p align=right>\[<a href="[^"]*">back to previous text</a>\]'

	pass  #endwhile urla



###############
# main function
###############
def StandingPullGluePages(datefrom, dateto, bforcescrape):
	# make the output firectory
	if not os.path.isdir(pwstandingpages):
		os.mkdir(pwstandingpages)

	# load the index file previously made by createhansardindex
	cstandingindex = LoadStandingIndex(pwstandingindex)

	# scan through the directory and make a mapping of all the copies for each
	lshortnamemap = { }
	for ldfile in os.listdir(pwstandingpages):
		mnums = re.match("(standing.*?)([a-z]*)\.html$", ldfile)
		if mnums:
			lshortnamemap.setdefault(mnums.group(1), []).append((AlphaStringToOrder(mnums.group(2)), mnums.group(2), ldfile))
		elif os.path.isfile(os.path.join(pwstandingpages, ldfile)):
			print "not recognized file:", ldfile, " in ", pwlordspages

	# loop through the index of each lord line.
	for dnu in cstandingindex.res:
		# implement date range
		if dnu[2] < datefrom or dnu[2] > dateto:
			continue

		# make the filename
		dgflatestalpha, dgflatest = "", None
		if dnu[0] in lshortnamemap:
			ldgf = max(lshortnamemap[dnu[0]])
			dgflatestalpha = ldgf[1]
			dgflatest = os.path.join(pwstandingpages, ldgf[2])
		dgfnextalpha = NextAlphaString(dgflatestalpha)
		ldgfnext = '%s%s.html' % (dnu[0], dgfnextalpha)
		dgfnext = os.path.join(pwstandingpages, ldgfnext)
		assert not dgflatest or os.path.isfile(dgflatest)
		assert not os.path.isfile(dgfnext)
		dgfnextstem = "%s%s" % (dnu[0], dgfnextalpha)
		dgflateststem = "%s%s" % (dnu[0], dgflatestalpha)

		# hansard index page
		urlx = dnu[1]

		# if not force scrape then we may choose to scrape it anyway
		# where the header doesn't match
		if not bforcescrape and dgflatest:
			fpgx = open(dgflatest, "r")
			pgx = fpgx.readline()
			fpgx.close()
			if pgx:
				pgx = re.findall('<pagex url="([^"]*)"[^/]*/>', pgx)
				if pgx:
					if pgx[0] == urlx:
						continue

		# make the message
		if miscfuncs.IsNotQuiet():
			print dnu[0], (dgflatest and 'RE-scraping' or 'scraping'), re.sub(".*?cmstand/", "", urlx)
			print dnu[3]

		# now we take out the local pointer and start the gluing
		# we could check that all our links above get cleared.
		dtemp = open(tempfilename, "w")
		GlueByNext(dtemp, urlx, dnu[3])
		dtemp.close()

		# now we have to decide whether it's actually new and should be copied onto dgfnext.
		if dgflatest: # the removal of \r makes testing sizes unreliable -- : and os.path.getsize(tempfilename) == os.path.getsize(dgflatest):
			# load in as strings and check matching
			fdgflatest = open(dgflatest)
			sdgflatest = fdgflatest.readlines()
			fdgflatest.close()

			fdgfnext = open(tempfilename)
			sdgfnext = fdgfnext.readlines()
			fdgfnext.close()

			# first line contains the scrape date
			if sdgflatest[1:] == sdgfnext[1:]:
				if miscfuncs.IsNotQuiet():
					print "  matched with:", dgflatest
				continue

		ReplicatePatchToNewScrapedVersion('standing', dgflateststem, dgflatest, dgfnext, dgfnextstem)
		if miscfuncs.IsNotQuiet():
			print dnu[0], (dgflatest and 'RE-scraped' or 'scraped'), re.sub(".*?cmpages[/\\\\]", "", dgfnext)
		os.rename(tempfilename, dgfnext)



