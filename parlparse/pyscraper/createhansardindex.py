#! /usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap

import sys
import os
import urllib
import urlparse
import string
import re
import xml.sax

# In Debian package python2.4-egenix-mxdatetime
import mx.DateTime

import miscfuncs

toppath = miscfuncs.toppath

# Generates an xml file with all the links into the daydebates, written questions, etc.
# The output file is used as a basis for planning the larger scale scraping.
# This prog is not yet reliable for all cases.

# this only does the commons index.
# Lords index is a completely different format.

# An index page is a page with links to a set of days,
#   NOT an index page into the pages for a single day.

# url for commons index
urlcmindex = "http://www.publications.parliament.uk/pa/cm/cmhansrd.htm"
urlvotesindex = "http://www.publications.parliament.uk/pa/cm/cmvote/cmvote.htm"
urlqbookindex = "http://www.publications.parliament.uk/pa/cm/cmordbk.htm"
# index file which is created
pwcmindex = os.path.join(toppath, "cmindex.xml")

# scrape limit date
earliestdate = '1997-05-01' # start of 1997 parliament
#earliestdate = '1994-05-01'

# regexps for decoding the data on an index page
monthnames = 'January|Janaury|February|March|April|May|June|July|August|September|October|November|December'
daynames = '(?:Sun|Mon|Tues|Wednes|Thurs|Fri|Satur)day'
# the chunk [A-Za-z<> ] catches the text "Questions tabled during the recess
# and answered on" on http://www.publications.parliament.uk/pa/cm/cmvol390.htm
redatename = '<b>[A-Za-z<> ]*?\s*(\S+\s+\d+\s+(?:%s)(?:\s+\d+)?)\s*</b>' % monthnames
revotelink  = '<a\s+href="([^"]*)">(?:<b>\s*)?((?:%s),(?:&nbsp;| )\d+\S*?(?:&nbsp;| )(?:%s)(?:&nbsp;| )\d+)\s*(?:</b>)?</(?:a|td)>(?i)' % (daynames, monthnames)
reqbooklink = '<a\s+href="([^"]*)">(?:<b>\s*)?((?:%s)(?:&nbsp;| )\d+\S*?(?:&nbsp;|\s+)(?:%s)(?:&nbsp;| )\d+)</(?:b|)>(?i)' % (daynames, monthnames)
relink = '<a\s+href="([^"]*)">(?:<b>)?([^<]*)(?:</b>)?</(?:a|font)>(?i)'
relink2 = '<a\s+href="([^"]*)">([^<]*<br>[^<]*)</a>(?i)'

# we lack the patching system here to overcome these special cases
respecialdate1 = "<b>Friday 23 July 2004 to(?:<br>|\s*)Friday 27 August(?: 2004)?</b>"
respecialdate2 = "<b>Friday 17 September 2004 to<br>Thursday 30 September 2004</b>"

redateindexlinks = re.compile('%s|(%s|%s)|%s|%s|%s|%s' % (redatename, respecialdate1, respecialdate2, revotelink, reqbooklink, relink, relink2))

# map from (date, type) to (URL-first, URL-index)
# e.g. ("2003-02-01", "wrans") to ("http://...", "http://...")
# URL-first is the first (index) page for the day,
# URL-index is the refering page which linked to the day
reses = {}

# this pulls out all the direct links on this particular page
def CmIndexFromPage(urllinkpage):
	urlinkpage = urllib.urlopen(urllinkpage)
        #print "urllinkpage ", urllinkpage
	srlinkpage = urlinkpage.read()
	urlinkpage.close()

	# remove comments because they sometimes contain wrong links
	srlinkpage = re.sub('<!--[\s\S]*?-->', ' ', srlinkpage)


	# <b>Wednesday 5 November 2003</b>
	#<td colspan=2><font size=+1><b>Wednesday 5 November 2003</b></font></td>
	# <a href="../cm199900/cmhansrd/vo000309/debindx/00309-x.htm">Oral Questions and Debates</a>

	# this was when I didn't use the match objects, and prefered this more direct detction thing
	datelinks = redateindexlinks.findall(srlinkpage)

	# read the dates and links in order, and associate last date with each matching link
	sdate = ''
	for link1 in datelinks:
		if link1[0]:
			odate = re.sub('\s', ' ', link1[0])
                        if odate == 'Wednesday 1 November' and urllinkpage == 'http://www.publications.parliament.uk/pa/cm/cmhn0611.htm':
                                odate = 'Wednesday 1 November 2006'
                        if odate == 'Tuesday 9 November 2008' and sdate=='':
                                odate = 'Tuesday 9 December 2008'
                        if odate == 'Wednesday 10 November 2008' and sdate=='':
                                odate = 'Wednesday 10 December 2008'
                        if odate == 'Tuesday 8 June 2008' and sdate=='':
                                odate = 'Tuesday 8 July 2008'
			sdate = mx.DateTime.DateTimeFrom(odate).date
			continue

		# these come from the special dates (of ranges) listed from above.
		# any more of these and I'll have to make special code to handle them
		if link1[1]:
			if link1[1][0:22] == "<b>Friday 23 July 2004":
				odate = "1 Sept 2004" # the date quoted on the wrans page
			elif link1[1][0:27] == "<b>Friday 17 September 2004":
				odate = "4 October 2004" # the date quoted on the wrans page
			else:
				assert False
			sdate = mx.DateTime.DateTimeFrom(odate).date
			continue

                if link1[2]:
                        odate = re.sub('&nbsp;', ' ', link1[3])
                        if link1[3] == 'Friday, 6 February 2003':
                                odate = '7 February 2003'
                        if link1[3] == 'Thursday, 24th February 1999':
                                odate = '25 February 1999'
                        sdate = mx.DateTime.DateTimeFrom(odate).date
                        if sdate < earliestdate:
                                continue
                        uind = urlparse.urljoin(urllinkpage, re.sub('\s', '', link1[2]))
                        typ = "Votes and Proceedings"
                elif link1[4]:
                        odate = re.sub('\s+', ' ', link1[5].replace('&nbsp;', ' '))
                        sdate = mx.DateTime.DateTimeFrom(odate).date
                        if sdate < earliestdate:
                                continue
                        uind = urlparse.urljoin(urllinkpage, re.sub('\s', '', link1[4]))
                        typ = "Question Book"
                elif link1[6]:
                        linkhref = link1[6]
                        linktext = link1[7]
                	# the link types by name
		        if not re.search('debate|westminster|written(?i)', linktext):
			        continue

               		if re.search('Chronology', linktext):
        			# print "Chronology:", link
	        		continue

		        # get rid of the new index pages
        		if re.search('/indexes/|cmordbk', linkhref):
	        		continue

                        if (re.search('Written Answers received between Friday 26 May and Thursday 1 June\s+2006', linktext)):
                                odate = '2 June 2006'
                                sdate = mx.DateTime.DateTimeFrom(odate).date

        		if not sdate:
        			raise Exception, 'No date for link 1 in: ' + urllinkpage + ' ' + ','.join(link1)
        		if sdate < earliestdate:
        			continue

        		# take out spaces and linefeeds we don't want
        		uind = urlparse.urljoin(urllinkpage, re.sub('\s', '', linkhref))
        		typ = string.strip(re.sub('\s+', ' ', linktext))
                        if typ == 'Recess Written Answers':
                                typ = 'Written Answers'

                elif link1[8]:
                        linkhref = link1[8]
                        linktext = link1[9]

                        if re.match('Written Answers and Statements received between<br>\s*Monday 4 September and Friday 8 September 2006', linktext):
                                odate = '11 September 2006'
                        elif re.match('Written Answers received between<br>\s*Wednesday 26 July and Friday 1 September 2006', linktext):
                                odate = '4 September 2006'
                        elif re.match('Written Answers and Statements received between<br>\s*Monday 11 September and Wednesday 13 September 2006', linktext):
                                odate = '13 September 2006'
                        elif re.match('Written Answers and Statements received between<br>\s*Thursday 14 September and Monday 18 September 2006', linktext):
                                odate = '18 September 2006'
                        elif re.match('Written Answers received between<br>\s*Tuesday 19 September and Friday 29 September 2006', linktext):
                                odate = '2 October 2006'
                        elif re.match('Written Answers received between<br>\s*Wednesday 20 December 2006 and Friday 5 January 2007', linktext):
                                odate = '5 January 2007'
                        elif re.match('Written Answers received between<br>\s*Monday 12 February 2007 and Friday 16 February 2007', linktext):
                                odate = '16 February 2007'
                        elif re.match('Written Answers received between<br>\s*Wednesday 12 February 2007 and Friday 16 February 2007', linktext):
                                odate = '16 February 2007'
                        else:
        			raise Exception, 'No date for link 2 in: ' + urllinkpage + ' ' + ','.join(link1)

                        sdate = mx.DateTime.DateTimeFrom(odate).date
        		uind = urlparse.urljoin(urllinkpage, re.sub('\s', '', linkhref))
        		typ = 'Written Answers'

                uind = uind.replace('080227a', '080227')

                # 21st July 2005 has a link, but there was none
                if uind == 'http://www.publications.parliament.uk/pa/cm200506/cmhansrd/vo050721/hallindx/50721-x.htm':
                        continue
                if uind == 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm071203/hallindx/71203-x.htm':
                        continue
                if uind == 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080218/hallindx/80218-x.htm':
                        continue
                if uind == 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080225/hallindx/80225-x.htm':
                        continue
                if uind == 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080229/hallindx/80229-x.htm':
                        continue
                # 21st June 2005 WHall links to 22nd June
                if sdate=='2005-06-21' and uind=='http://www.publications.parliament.uk/pa/cm200506/cmhansrd/vo050622/hallindx/50622-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200506/cmhansrd/vo050621/hallindx/50621-x.htm'
                # 25th May 2006 WMS links to 4th May
                if sdate=='2006-05-25' and uind=='http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060504/wmsindx/60504-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060525/wmsindx/60525-x.htm'
                if sdate=='2007-06-28' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070627/wmsindx/70628-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070628/wmsindx/70628-x.htm'

                if sdate=='2007-02-26' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070129/index/70129-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070226/index/70226-x.htm'
                if sdate=='2007-02-26' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070129/wmsindx/70129-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070226/wmsindx/70226-x.htm'
                if sdate=='2007-02-26' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070129/debindx/70129-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070226/debindx/70226-x.htm'

                if sdate=='2007-01-19' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070126/index/70126-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070119/index/70119-x.htm'
                if sdate=='2007-01-19' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070126/wmsindx/70126-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070119/wmsindx/70119-x.htm'
                if sdate=='2007-01-19' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070126/debindx/70126-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm070119/debindx/70119-x.htm'

                if sdate=='2007-10-23' and uind=='http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm071016/debindx/71023-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200607/cmhansrd/cm071023/debindx/71023-x.htm'
                if sdate=='2007-11-15' and uind=='http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm071114/debindx/71115-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm071115/debindx/71115-x.htm'
                if sdate=='2008-01-15' and uind=='http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080116/index/80115-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080115/index/80115-x.htm'

                # 7th May 2008 debates links to 8th May
                if sdate=='2008-05-07' and uind=='http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080508/debindx/80508-x.htm':
                        uind = 'http://www.publications.parliament.uk/pa/cm200708/cmhansrd/cm080507/debindx/80507-x.htm'
                if sdate>='2006-12-05' and sdate<='2006-12-14' and typ=='Westminster Hall':
                        uind = uind.replace('200506', '200607')

		# check for repeats where the URLs differ
		if (sdate, typ) in reses:

			rc = reses[(sdate, typ)]
			otheruind = rc[0]
			if otheruind == uind:
				continue

			# sometimes they have old links to the cm edition as
			# well as the vo edition, we pick the newer vo ones
			# make sure that discrepancies are explainable
			test1 = uind.replace('cmhansrd/cm', 'cmhansrd/vo')
			test2 = otheruind.replace('cmhansrd/cm', 'cmhansrd/vo')
			if test1 != test2:
				raise Exception, '------\nRepeated link to %s %s differs:\nurl1: %s\nurl2: %s\nfrom index page1: %s\nindex2: %s\n------' % (sdate, typ, uind, otheruind, urllinkpage, rc[1])

			# case of two URLs the same only vo/cm differ like this:
			# (which is a bug in Hansard, should never happen)
			#http://www.publications.parliament.uk/pa/cm200203/cmhansrd/vo031006/index/31006-x.htm
			#http://www.publications.parliament.uk/pa/cm200203/cmhansrd/cm031006/index/31006-x.htm
			# we replace both with just the vo edition:
			#print "done replace of these two URLs into the vo one\nurl1: %s\nurl2: %s" % (uind, otheruind)
			uind = test1

		reses[(sdate, typ)] = (uind, urllinkpage)
		#print sdate, uind

# Find all the index pages from the front index page by recursing into the months
# and then the years and volumes pages
def CmAllIndexPages(urlindex):

	# all urls pages which have links into day debates are put into this list
	# except the first one, which will have been looked at
	res = [ ]

	urindex = urllib.urlopen(urlindex)
	srindex = urindex.read()
	urindex.close()

	# extract the per month volumes
	# <a href="cmhn0310.htm"><b>October</b></a>
	monthindexp = '<a href="([^"]*)"[^>]*>(?:%s)</a>(?i)' %  monthnames
	monthlinks = re.findall(monthindexp, srindex)
	for monthl in monthlinks:
		res.append(urlparse.urljoin(urlindex, re.sub('\s', '', monthl)))

	# extract the year links to volumes
	# <a href="cmse9495.htm"><b>Session 1994-95</b></a>
	# sessions before 94 have the bold tag the wrong way round,
	# but we don't want to go that far back now anyhow.
	yearvollinks = re.findall('<a href="([^"]*)"><b>session[^<]*</b></a>(?i)', srindex);

	# extract the volume links
	for yearvol in yearvollinks:
		urlyearvol = urlparse.urljoin(urlindex, re.sub('\s', '', yearvol))
		uryearvol = urllib.urlopen(urlyearvol)
		sryearvol = uryearvol.read()
		uryearvol.close()

		# <a href="cmvol352.htm"><b>Volume 352</b>
		vollinks = re.findall('<a href="([^"]*)">(?:<p class="style1">)?<b>volume[^<]*</b>(?i)', sryearvol)
		for vol in vollinks:
			res.append(urlparse.urljoin(urlyearvol, re.sub('\s', '', vol)))
	return res


def CmAllIndexPagesVotes(urlindex):

	# all urls pages which have links into day debates are put into this list
	# except the first one, which will have been looked at
	res = [ ]

	urindex = urllib.urlopen(urlindex)
	srindex = urindex.read()
	urindex.close()

	# extract the per month volumes
	# <a href="cmhn0310.htm"><b>October</b></a>
	monthindexp = '<a href="([^"]*)"><b>(?:%s)(?: \d+)?</b>(?i)' %  monthnames
	monthlinks = re.findall(monthindexp, srindex)
	for monthl in monthlinks:
		res.append(urlparse.urljoin(urlindex, re.sub('\s', '', monthl)))
	return res

def CmAllIndexPagesQuestionBook(urlindex):

	# all urls pages which have links into day debates are put into this list
	# except the first one, which will have been looked at
	res = [ ]

	urindex = urllib.urlopen(urlindex)
	srindex = urindex.read()
	urindex.close()

	# extract the per month volumes
	# <a href="cmhn0310.htm"><b>October</b></a>
	monthindexp = '<a href="([^"]*)"><b>(?:%s)(?: \d+)?</b>(?i)' %  monthnames
	monthlinks = re.findall(monthindexp, srindex)
	for monthl in monthlinks:
		res.append(urlparse.urljoin(urlindex, re.sub('\s', '', monthl)))
	return res

def WriteXML(fout, urllist):
	fout.write('<?xml version="1.0" encoding="ISO-8859-1"?>\n')
	fout.write("<publicwhip>\n\n")

	# avoid printing duplicates
	for i in range(len(urllist)):
		r = urllist[i]
		if (i == 0) or (r != urllist[i-1]):
			if r[0] >= earliestdate:
				fout.write('<cmdaydeb date="%s" type="%s" url="%s"/>\n' % r)

	fout.write("\n</publicwhip>\n")


# gets the old file so we can compare the head values
class LoadOldIndex(xml.sax.handler.ContentHandler):
	def __init__(self, lpwcmindex):
		self.res = []
                self.resv = []
                self.resq = []
		if not os.path.isfile(lpwcmindex):
			return
		parser = xml.sax.make_parser()
		parser.setContentHandler(self)
		parser.parse(lpwcmindex)

	def startElement(self, name, attr):
		if name == "cmdaydeb":
                        if attr['type'] == 'Votes and Proceedings':
                                self.resv.append( (attr['date'], attr['type'], attr['url']) )
                        elif attr['type'] == 'Question Book':
                                self.resq.append( (attr['date'], attr['type'], attr['url']) )
                        else:
        			ddr = (attr["date"], attr["type"], attr["url"])
        			self.res.append(ddr)

	def CompareHeading(self, urllisthead):
		if not self.res:
			return False
                res = 0
                resv = 0
                resq = 0
		for i in range(len(urllisthead)):
                        if urllisthead[i][1] == 'Votes and Proceedings':
                                if i >= len(self.resv) or urllisthead[i] != self.resv[resv]:
                                        return False
                                else:
                                        resv += 1
                        elif urllisthead[i][1] == 'Question Book':
                                if i >= len(self.resq) or urllisthead[i] != self.resq[resq]:
                                        return False
                                else:
                                        resq += 1
                        else:
                                if i >= len(self.res) or urllisthead[i] != self.res[res]:
        				return False
                                else:
                                        res += 1
		return True



###############
# main function
###############
def UpdateHansardIndex(force):
	global reses
	reses = {}

	# get front page (which we will compare against)
	CmIndexFromPage(urlcmindex)
	CmIndexFromPage(urlvotesindex)
	CmIndexFromPage(urlqbookindex)
	urllisth = map(lambda r: r + (reses[r][0],), reses.keys())
	urllisth.sort()
	urllisth.reverse()
	#print urllisth

	if not force:
		# compare this leading term against the old index
		oldindex = LoadOldIndex(pwcmindex)
		if oldindex.CompareHeading(urllisth):
			#print ' Head appears the same, no new list '
			return

		oindex = oldindex.res
		oindex.extend(oldindex.resv)
		oindex.extend(oldindex.resq)
		for i in urllisth:
			if i not in oindex:
				oindex.append(i)
		oindex.sort()
		oindex.reverse()
		urllisth = oindex
	else:
		# extend our list to all the pages
		cres = CmAllIndexPages(urlcmindex)
		cres += CmAllIndexPagesVotes(urlvotesindex)
		cres += CmAllIndexPagesQuestionBook(urlqbookindex)
		for cr in cres:
			CmIndexFromPage(cr)
		urllisth = map(lambda r: r + (reses[r][0],), reses.keys())
		urllisth.sort()
		urllisth.reverse()

	fpwcmindex = open(pwcmindex, "w");
	WriteXML(fpwcmindex, urllisth)
	fpwcmindex.close()

