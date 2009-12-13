# vim:sw=8:ts=8:et:nowrap

import os
import datetime
import re
import sys
import urllib
import string


import miscfuncs
import difflib

pwcmdirs = miscfuncs.pwcmdirs


# This code is for grabbing pages of government and party positions off the hansard webpage,
# testing for changes and filing them into directories where they can be parsed at leisure

# These links come from the page "http://www.parliament.uk/directories/directories.cfm"

watchpages = {  "govposts":"http://www.parliament.uk/mpslordsandoffices/government_and_opposition/hmg.cfm",
				"offoppose":"http://www.parliament.uk/mpslordsandoffices/government_and_opposition/opp.cfm",
				"libdem":"http://letters.libdems.org.uk/mysociety.php",
				"dup":"http://www.parliament.uk/mpslordsandoffices/government_and_opposition/dup.cfm",
				"ulsterun":"http://www.parliament.uk/mpslordsandoffices/government_and_opposition/ulster.cfm",
				"plaidsnp":"http://www.parliament.uk/mpslordsandoffices/government_and_opposition/PCSNP.cfm",
				"privsec":"http://www.parliament.uk/mpslordsandoffices/government_and_opposition/Pps.cfm",
				"selctee":"http://www.parliament.uk/mpslordsandoffices/mps_and_lords/selmem.cfm",
				"clerks":"http://www.publications.parliament.uk/pa/cm/listgovt.htm",

				"bills":"http://www.publications.parliament.uk/pa/pabills.htm",
				"privatebills":"http://www.publications.parliament.uk/pa/privbill.htm",

				"hybridbillscfm":"http://www.parliament.uk/bills/hybrid_bills.cfm",
				"draftbillscfm":"http://www.parliament.uk/bills/draftbills.cfm",
				"billlist":"http://www.publications.parliament.uk/pa/cm/cmpblist/cmpblist.htm",

				"newlords":"http://www.parliament.uk/mpslordsandoffices/mps_and_lords/new_members.cfm",
				"deadlords":"http://www.parliament.uk/mpslordsandoffices/mps_and_lords/deceased_members.cfm",
				"alphalistlords":"http://www.parliament.uk/mpslordsandoffices/mps_and_lords/alphabetical_list_of_members.cfm",

				"financialsanctions":"http://www.bankofengland.co.uk/publications/financialsanctions/sanctionsconlist.htm",
			 }


# go through each of the pages in the above map and make copies where there are changes
# compared to the last version that's there.
# This is general code that works for single pages at single urls only and doesn't strip any of the garbage.
def GrabWatchCopies(sdate):
	# make directories that don't exist
	chggdir = os.path.join(pwcmdirs, "chgpages")
	if not os.path.isdir(chggdir):
		raise Exception, 'Data directory %s does not exist, you\'ve not got a proper checkout from CVS.' % (chggdir)

	for ww in watchpages:
		watchdir = os.path.join(chggdir, ww)
		if not os.path.isdir(watchdir):
			os.mkdir(watchdir)
		wl = os.listdir(watchdir)
		wl.sort()

		lastval = ""
		lastnum = 0
		if wl:
			lin = open(os.path.join(watchdir, wl[-1]), "r")
			lastval = lin.read().strip()
			lin.close()
			numg = re.match("\D*(\d+)_", wl[-1])
			assert numg
			lastnum = string.atoi(numg.group(1))

		# get copy from web
		#print "urling", watchpages[ww]
		ur = urllib.urlopen(watchpages[ww])
		currval = ur.read().strip()  # sometimes there are trailing spaces
		ur.close()

		# comparison with previous page
		# we use 4 digit numbering at the front to ensure that cases are separate and ordered

		# Strip current date, used for pointless Big Ben flash animation
		spcurrval = re.sub('<div id="banner_time">\s*<div id="bigben">\s*</div>.*?</div>', '', currval)
		splastval = re.sub('<div id="banner_time">\s*<div id="bigben">\s*</div>.*?</div>', '', lastval)

		# Compare the two pages with the spaces removed
		spcurrval = re.sub("\s+", "", spcurrval)
		splastval = re.sub("\s+", "", splastval)
		if spcurrval != splastval:
			#print len(currval), len(lastval)
			#print len(spcurrval), len(splastval), (spcurrval == splastval)

			# build the name for this page and make sure it follows, even when we have the same date
			wwn = "%s%04d_%s.html" % (ww, lastnum + 1, sdate)
			wwnj = os.path.join(watchdir, wwn)
			# print "changed page", wwnj

			assert not os.path.isfile(wwn)
			wout = open(wwnj, "w")
			wout.write(currval)
			wout.close()

			# make a report of the diffs (can't find a way to use charjunk to get rid of \r's)
			#diffs = list(difflib.Differ().compare(lastval.splitlines(1), currval.splitlines(1)))
			#sys.stdout.writelines(diffs[:50])


