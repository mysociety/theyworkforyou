#! /usr/bin/python2.4
# vim:sw=8:ts=8:et:nowrap

# Pulls in Bills, glues them together, removes comments,
# and stores them on the disk

import sys
import urllib
import urlparse
import re
import os.path
import time
import mx.DateTime
import tempfile

sys.path.append('../')
import miscfuncs
toppath = miscfuncs.toppath

# output directories
pwcmdirs = os.path.join(toppath, "cmpages")
pwcmbills = os.path.join(pwcmdirs, "bills")

tempfilename = tempfile.mktemp("", "pw-gluetemp-", miscfuncs.tmppath)

def GlueByNext(fout, url, regmemdate):
	# loop which scrapes through all the pages following the nextlinks
        starttablewritten = False
        sections = 0
	while 1:
		print " reading " + url
		ur = urllib.urlopen(url)
		sr = ur.read()
		ur.close();

                sections += 1

		# write the marker telling us which page this comes from
                lt = time.gmtime()
                fout.write('<page url="%s" scrapedate="%s" scrapetime="%s"/>\n' % \
			(url, time.strftime('%Y-%m-%d', lt), time.strftime('%X', lt)))

		# split by sections
		hrsections = re.split(
                        '<a name="top"></a>|' +
                        '<!-- end of variable data -->|' +
                        '(?i)', sr)

		# write the body of the text
#		for i in range(0,len(hrsections)):
#                        print "------"
#                        print hrsections[i]
                text = hrsections[1] 
                m = re.search('<TABLE .*?>([\s\S]*)</TABLE>', text)
                if m:
                        text = m.group(1)
                m = re.search('<TABLE .*?>([\s\S]*)', text)
                if m:
                        text = m.group(1)
                if not starttablewritten and re.search('COLSPAN=4', text):
                        text = "<TABLE>\n" + text
                        starttablewritten = True
                miscfuncs.WriteCleanText(fout, text)

		# find the lead on with the footer
		footer = hrsections[2]

                nextsectionlink = re.findall('<a href="([^>]*?)"><img border=0\s+align=top src="/pa/img/conu(?:grn|drd).gif" alt="continue"></a>', footer)
		if not nextsectionlink:
			break
		if len(nextsectionlink) > 1:
			raise Exception, "More than one Next Section!!!"
		url = urlparse.urljoin(url, nextsectionlink[0])

        # you evidently didn't find any links
        assert sections > 1
        
        fout.write('</TABLE>')


# read through our index list of daydebates
def GlueAllType(pcmdir, cmindex, fproto, deleteoutput):
	if not os.path.isdir(pcmdir):
		os.mkdir(pcmdir)

	for dnu in cmindex:
		# make the filename
		dgf = os.path.join(pcmdir, (fproto % dnu[0]))

                if deleteoutput:
                    if os.path.isfile(dgf):
                            os.remove(dgf)
                else:
                    # hansard index page
                    url = dnu[1]

                    # if we already have got the file, check the pagex link agrees in the first line
                    # no need to scrape it in again
                    if os.path.exists(dgf):
                            fpgx = open(dgf, "r")
                            pgx = fpgx.readline()
                            fpgx.close()
                            if pgx:
                                    pgx = re.findall('<page url="([^"]*)"[^/]*/>', pgx)
                                    if pgx:
                                            if pgx[0] == url:
                                                    #print 'skipping ' + url
                                                    continue
                            #print 'RE-scraping ' + url
                    else:
                            pass
                            #print 'scraping ' + url

                    # now we take out the local pointer and start the gluing
                    dtemp = open(tempfilename, "w")
                    GlueByNext(dtemp, url, dnu[0])

                    # close and move
                    dtemp.close()
                    os.rename(tempfilename, dgf)


###############
# main function
###############
def BillPullGluePages(deleteoutput):
	# make the output firectory
	if not os.path.isdir(pwcmdirs):
		os.mkdir(pwcmdirs)
                
        # Hardcoded versions
        # http://www.publications.parliament.uk/pa/cm/cmhocpap.htm#register
        urls = [ 
                ('200506b111', 'http://www.publications.parliament.uk/pa/cm200506/cmbills/111/06111.1-4.html'),
                ('200506b141', 'http://www.publications.parliament.uk/pa/cm200506/cmbills/141/06141.1-7.html'),
                ('200506hl109', 'http://www.publications.parliament.uk/pa/ld200506/ldbills/109/06109.i-ii.html'),
                ('200506hl146', 'http://www.publications.parliament.uk/pa/ld200506/ldbills/146/06146.i-ii.html'),
                ]
        
	# bring in and glue together parliamentary register of members interests and put into their own directories.
	# third parameter is a regexp, fourth is the filename (%s becomes the date).
	GlueAllType(pwcmbills, urls, 'bill%s.html', deleteoutput)

if __name__ == '__main__':
        BillPullGluePages(False)

