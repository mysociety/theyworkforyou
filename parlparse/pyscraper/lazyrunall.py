#! /usr/bin/env python2.4
# vim:sw=8:ts=8:et:nowrap

# Run the script with --help to see command line options

import sys
import os
import datetime

# change current directory to pyscraper folder script is in
os.chdir(os.path.dirname(sys.argv[0]) or '.')

sys.path.append('debate')
sys.path.append('wrans')
sys.path.append('common')
sys.path.append('lords')
sys.path.append('miniposts')
sys.path.append('wms')
sys.path.append('votes')
sys.path.append('bills')
sys.path.append('standing')

from crongrabpages import GrabWatchCopies
from minpostparse import ParseGovPosts

from optparse import OptionParser
from createhansardindex import UpdateHansardIndex
from lordscreatehansardindex import UpdateLordsHansardIndex
from standingcreatehansardindex import UpdateStandingHansardIndex
from pullgluepages import PullGluePages, PullGlueToday
from lordspullgluepages import LordsPullGluePages
from standingpullgluepages import StandingPullGluePages
from runfilters import RunFiltersDir, RunDebateFilters, RunWransFilters, RunLordsFilters, RunWestminhallFilters, RunWMSFilters, RunNIFilters
from regmemfilter import RunRegmemFilters
from parsevote import RunVotesFilters
import ni.scrape

if sys.platform != "win32":  # just to get working again
	from parsebills import MakeBillPrint

from regmempullgluepages import RegmemPullGluePages
from miscfuncs import SetQuiet, bNotQuiet, IsNotQuiet

# Parse the command line parameters

parser = OptionParser()

parser.set_usage("""
Crawls the website of the proceedings of the UK parliament, also known as
Hansard, and more things under parliament.uk.  Converts them into handy XML
files, tidying up HTML errors, generating unique identifiers for speeches,
reordering sections, name matching MPs and so on as it goes.

Specify at least one of the following actions to take:
scrape          update Hansard page index, and download new raw pages
parse           process scraped HTML into tidy XML files

And choose at least one of these sections to apply them to:
all             All normal daily things
wrans           Written Answers
debates         Debates
westminhall     Westminster Hall
wms             Written Ministerial Statements
lords           House of Lords
regmem          Register of Members Interests
chgpages        Special pages that change, like list of cabinet ministers
questionbook    The Question Book (Questions for Oral/Written Answer)
votes           Votes and Proceedings
today           Today in the Commons
standing        Public Bill (Standing) Committees
ni              Northern Ireland Assembly

Example command line
        ./lazyrunall.py --date=2004-03-03 --force-scrape scrape parse wrans
It forces redownload of the Written Answers for 3rd March, and reprocesses them.""")


# See what options there are

parser.add_option("--force-parse",
                  action="store_true", dest="forceparse", default=False,
                  help="forces reprocessing of wrans/debates by first deleting output files")
parser.add_option("--force-scrape",
                  action="store_true", dest="forcescrape", default=False,
                  help="forces redownloading of HTML first deleting output files")
parser.add_option("--force-index",
                  action="store_true", dest="forceindex", default=False,
                  help="forces redownloading of HTML index files")

parser.add_option("--from", dest="datefrom", metavar="date", default="1000-01-01",
                  help="date to process back to, default is start of time")
parser.add_option("--to", dest="dateto", metavar="date", default="9999-12-31",
                  help="date to process up to, default is present day")
parser.add_option("--date", dest="date", metavar="date", default=None,
                  help="date to process (overrides --from and --to)")

parser.add_option("--patchtool",
                  action="store_true", dest="patchtool", default=None,
                  help="launch ./patchtool to fix errors in source HTML")
parser.add_option("--quietc",
                  action="store_true", dest="quietc", default=None,
                  help="low volume error messages; continue processing further files")

(options, args) = parser.parse_args()
if (options.date):
	options.datefrom = options.date
	options.dateto = options.date
if options.quietc:
	SetQuiet()

# See what commands there are

# can't you do this with a dict mapping strings to bools?
options.scrape = False
options.parse = False
options.wrans = False
options.debates = False
options.westminhall = False
options.wms = False
options.lords = False
options.regmem = False
options.chgpages = False
options.votes = False
options.qbook = False
options.today = False
options.standing = False
options.ni = False
for arg in args:
        if arg == "scrape":
                options.scrape = True
        elif arg == "parse":
                options.parse = True
        elif arg == "wrans":
                options.wrans = True
        elif arg == "debates":
                options.debates = True
        elif arg == "westminhall":
                options.westminhall = True
        elif arg == "wms":
                options.wms = True
        elif arg == "lords":
                options.lords = True
        elif arg == "regmem":
                options.regmem = True
        elif arg == "chgpages":
                options.chgpages = True
        elif arg == "questionbook":
                options.qbook = True
        elif arg == "votes":
                options.votes = True
        elif arg == "today":
                options.today = True
        elif arg == "standing":
                options.standing = True
        elif arg == "ni":
                options.ni = True
        elif arg == 'all':
                options.wrans = True
                options.debates = True
                options.westminhall = True
                options.wms = True
                options.lords = True
                options.standing = True
                options.ni = True
        else:
                print >>sys.stderr, "error: no such option %s" % arg
                parser.print_help()
                sys.exit(1)
if len(args) == 0:
        parser.print_help()
        sys.exit(1)
if not options.scrape and not options.parse:
        print >>sys.stderr, "error: choose what to do; scrape, parse, or both"
        parser.print_help()
        sys.exit(1)
if not options.debates and not options.westminhall and not options.wms and not options.wrans and not options.regmem and not options.lords and not options.chgpages and not options.votes and not options.today and not options.qbook and not options.standing and not options.ni:
        print >>sys.stderr, "error: choose what work on; debates, wrans, regmem, wms, votes, chgpages, questionbook, today or several of them"
        parser.print_help()
        sys.exit(1)


# Do the work - all the conditions are so beautifully symmetrical, there
# must be a nicer way of doing it all...

options.anyerrors = False

#
# First do indexes
#
if options.scrape:
	# get the indexes
	if options.wrans or options.debates or options.westminhall or options.wms or options.votes or options.qbook:
		UpdateHansardIndex(options.forceindex)
	if options.lords:
		UpdateLordsHansardIndex(options.forceindex)
	if options.standing:
		UpdateStandingHansardIndex(options.forceindex)

	# get the changing pages
	if options.chgpages:
		GrabWatchCopies(datetime.date.today().isoformat())

	# these force the rescraping of the html by deleting the old copies (should be a setting in the scraper)
	if options.forcescrape:
		if options.regmem:
			RegmemPullGluePages(True)

#
# Download/generate the new data
#
pullgluetodaydate = None
if options.scrape:
	if options.wrans:
		PullGluePages(options, "wrans", "answers")
	if options.debates:
		PullGluePages(options, "debates", "debates")
	if options.westminhall:
		PullGluePages(options, "westminhall", "westminster")
	if options.wms:
		PullGluePages(options, "wms", "ministerial")
	if options.lords:
		LordsPullGluePages(options.datefrom, options.dateto, options.forcescrape)
	if options.standing:
		StandingPullGluePages(options.datefrom, options.dateto, options.forcescrape)
        if options.ni:
                ni.scrape.scrape_new_ni() # forcescrape not used, only scrapes new, checks all.
	if options.votes:
		PullGluePages(options, "votes", "votes")
	if options.qbook:
		PullGluePages(options, "questionbook", "questionbook")
	if options.today:
		pullgluetodaydate = PullGlueToday(options.forcescrape)
	if options.regmem:
		# TODO - date ranges when we do index page stuff for regmem
		RegmemPullGluePages(options.forcescrape)

#
# Parse it into XML
#
if options.parse:
	if options.wrans:
		RunFiltersDir(RunWransFilters, 'wrans', options, options.forceparse)
	if options.debates:
		RunFiltersDir(RunDebateFilters, 'debates', options, options.forceparse)
	if options.westminhall:
		RunFiltersDir(RunWestminhallFilters, 'westminhall', options, options.forceparse)
	if options.wms:
		RunFiltersDir(RunWMSFilters, 'wms', options, options.forceparse)
	if options.lords:
		RunFiltersDir(RunLordsFilters, 'lordspages', options, options.forceparse)
	if options.ni:
		RunFiltersDir(RunNIFilters, 'ni', options, options.forceparse)
	if options.regmem:
		RunFiltersDir(RunRegmemFilters, 'regmem', options, options.forceparse)
	if options.votes:
		RunFiltersDir(RunVotesFilters, 'votes', options, options.forceparse)
	if options.chgpages:
                #MakeBillPrint()
		ParseGovPosts()

	# special run-on case of same day
	if options.today:
		if pullgluetodaydate:
			options.datefrom = pullgluetodaydate
			options.dateto = pullgluetodaydate
			RunFiltersDir(RunDebateFilters, 'debates', options, options.forceparse)

# Error reporting
if options.anyerrors:
        sys.exit(1)
else:
        sys.exit(0)



