#!/usr/bin/python2.4

import sys
import os
import random
import datetime
import time
import urllib
import glob
import re

sys.path.append('../')
from BeautifulSoup import MinimalSoup
from BeautifulSoup import NavigableString
from BeautifulSoup import Tag
from BeautifulSoup import Comment

from common import month_name_to_int
from common import non_tag_data_in
from common import tidy_string
from common import fix_spid

from findquotation import ScrapedXMLParser
from findquotation import WrittenAnswerParser
from findquotation import find_quotation_from_text
from findquotation import find_speech_with_trailing_spid

from mentions import Mention
from mentions import add_mention_to_dictionary
from mentions import load_question_mentions
from mentions import save_question_mentions

from mtimes import get_file_mtime
from mtimes import most_recent_mtime

from optparse import OptionParser

spid_re =                   '(S[0-9][A-LN-Z0-9]+\-[0-9]+)'
spid_re_bracketed =         '\(\s*'+spid_re+'\s*\)'
spid_re_at_start =          '^\s*'+spid_re
spid_re_bracketed_at_end =  spid_re_bracketed+'\s*$'

parser = OptionParser()
parser.add_option('-f', "--force", dest="force", action="store_true",
                  default=False, help="don't load the old files first, regenerate everything")
parser.add_option('-v', "--verbose", dest="verbose", action="store_true",
                  default=False, help="output verbose progress information")
parser.add_option('-m', "--modified", dest="modified", action="store_true",
                  default=False, help="parse only modified files")
(options, args) = parser.parse_args()
force = options.force
verbose = options.verbose
modified = options.modified
if force and modified:
    raise Exception, "It doesn't make sense to specify --force and --modified"

# Look at the filenames to find the last time that this apparently
# ran, and only consider the bulletins for a fortnight before that
# point.

mentions_prefix = "../../../parldata/scrapedxml/sp-questions/"

filenames = glob.glob( mentions_prefix + "up-to-*.xml" )
filenames.sort()

all_after_date = datetime.date(1999,5,1)
modified_after = None

if force:
    # Just leave all_after_date as it is...
    pass
elif modified:
    modified_after = most_recent_mtime(filenames)
    if not modified_after:
        modified_after = datetime.datetime(1999,5,1,0,0,0)
else:
    if filenames:
        m = re.search('up-to-(\d{4}-\d{2}-\d{2})(.*).xml',filenames[-1])
        if not m:
            raise Exception, "Couldn't find date from last mentions file: "+filenames[-1]
        all_after_date = datetime.date(*time.strptime(m.group(1),"%Y-%m-%d")[:3])

# Build an array of dates to consider:

dates = []
currentdate = all_after_date

enddate = datetime.date.today()
while currentdate < enddate:
    dates.append( currentdate )
    currentdate += datetime.timedelta(days=1)

# This is the dictionary we are building up and will write out at the
# end:

id_to_mentions = { }

# There are three stages to adding question mentions:
# 
#   (a) Look through each Business Bulletin in the date range
#       (sections A, D and E) to look for questions that have been
#       tabled and so on.
# 
#   (b) Look through the Written Answers for each day in the date
#       range to find.
#
#   (c) Look through the Official Reports for questions that were
#       actually asked in the parliament.
# 
# ------------------------------------------------------------------------

# First (a) the Business Bulletins:

bulletin_prefix = "http://www.scottish.parliament.uk/business/businessBulletin/"
bulletins_directory = "../../../parldata/cmpages/sp/bulletins/"

bulletin_filenames = glob.glob( bulletins_directory + "day-*" )
bulletin_filenames.sort()

for day_filename in bulletin_filenames:

    if modified and get_file_mtime(day_filename) < modified_after:
        continue

    m = re.search('(?i)day-(bb-(\d\d))_([ab]b-(\d\d)-(\d\d)-?(\w*)\.html?)$',day_filename)

    if not m:
        if verbose: print "Couldn't parse file %s" % ( day_filename )
        continue

    subdir = m.group(1)
    two_digit_year = m.group(2)
    page = m.group(3)
    two_digit_month = m.group(4)
    two_digit_day = m.group(5)
    section = m.group(6)

    # if not (two_digit_year == '08' or two_digit_year == '07'):
    # if not (two_digit_year == '08'):
    #     continue

    day_url = bulletin_prefix + subdir + '/' + page

    oral_question = False
    todays_business = False

    if section == 'a':
        oral_question = False
        todays_business = True
    elif section == 'd':
        oral_question = True
        todays_business = False
    elif section == 'e':
        oral_question = False
        todays_business = False
    else:
        continue

    if verbose: print "------------------------------"
    if verbose: print "Parsing file: "+day_filename

    # Now we have the file, soup it:

    fp = open(day_filename)
    day_html = fp.read()
    fp.close()

    day_html = re.sub('&nbsp;',' ',day_html)
    day_html = fix_spid(day_html)
    
    filename_leaf = day_filename.split('/')[-1]

    date = None

    date_from_filename = None
    date_from_filecontents = None

    # First, guess the date from the filename:
    filename_year = None
    filename_month = int(two_digit_month,10)
    filename_day = int(two_digit_day,10)
    if two_digit_year == '99':
        filename_year = 1999
    else:
        filename_year = int('20'+two_digit_year,10)
    try:
        date_from_filename = datetime.date(filename_year,filename_month,filename_day)
    except ValueError:
        date_from_filename = None
        if verbose: print "Date in filename %s-%s-%s" % ( filename_year, filename_month, filename_day )

    # Don't soup it if we don't have to:
    if date_from_filename and date_from_filename < all_after_date:
        continue

    day_soup = MinimalSoup(day_html)

    day_body = day_soup.find('body')
    if day_body:
        page_as_text = non_tag_data_in(day_body)
    else:
        error = "File couldn't be parsed by MinimalSoup: "+day_filename
        raise Exception, error

    # Now guess the date from the file contents as well:
    m = re.search('(?ims)((Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\s+)(\d+)\w*\s+(\w+)(\s+(\d+))?',page_as_text)
    if not m:
        m = re.search('(?ims)((Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\s+)?(\d+)\w*\s+(\w+)(\s+(\d+))?',page_as_text)
    if m:
        day_of_week = m.group(2)
        day = m.group(3)
        month = month_name_to_int(m.group(4))
        if month == 0:
            print "Whole match was '" + str(m.group(0)) + "'"
            raise Exception, "Month name '"+m.group(4)+"' not known in file: "+day_filename
        else:
            year = m.group(6)
            # Sometimes the date string doesn't have the year:
            if not year:
                m = re.search('(?i)day-[ab]b-(\d\d)',day_filename)
                if m.group(1) == '99':
                    year = '1999'
                else:
                    year = '20' + m.group(1)
            date_from_filecontents = datetime.date( int(year,10), month, int(day,10) )

    if date_from_filename == date_from_filecontents:
        date = date_from_filename
    else:
        # Sometimes one method works, sometime the other:
        if date_from_filename and not date_from_filecontents:
            date = date_from_filename
        elif date_from_filecontents and not date_from_filename:
            date = date_from_filecontents
        else:
            # So toss a coin here, more or less.  Let's go with the
            # filename, since there many be many dates in the file
            # itself, except in 1999 since in that year the format of
            # the filenames changes from DD-MM to MM-DD half way
            # through (aarrgh!)
            if filename_year == 1999:
                date = date_from_filecontents
            else:
                date = date_from_filename
        
    if verbose: print "Date: "+str(date)+" from "+day_filename

    if date < all_after_date:
        continue

    matches = []

    ps = day_body.findAll( lambda t: t.name == 'p' or t.name == 'li' )
    if verbose and len(ps) == 0: print "  Found no paragraphs!"
    questions_found = 0
    for p in ps:
        plain = non_tag_data_in(p)
        m_end_bracketed = re.search(spid_re_bracketed_at_end,plain)
        m_start = re.search(spid_re_at_start,plain)
        spid = None
        if oral_question:
            spid = m_end_bracketed and m_end_bracketed.group(1)
        elif todays_business:
            spid = (m_start and m_start.group(1)) or (m_end_bracketed and m_end_bracketed.group(1))
        else:
            spid = m_start and m_start.group(1)
        plain = re.sub(spid_re_bracketed_at_end,'',plain)
        plain = re.sub(spid_re_at_start,'',plain)
        if m_start or m_end_bracketed:
            if not spid:
                print "SPID seemed to be at the wrong end of the paragraph:"
                print "   oral_question was: "+str(oral_question)
                print "   todays_business was: "+str(todays_business)
                print "   m_start was: "+str(m_start)
                print "   m_end_bracketed was: "+str(m_end_bracketed)
                raise Exception("Problem.")
            questions_found += 1
            spid = fix_spid(spid)
            matches.append(spid)
            allusions = re.findall(spid_re,plain)
            for a in allusions:
                a = fix_spid(a)
                mention = Mention(a,None,None,'referenced-in-question-text',spid)
                add_mention_to_dictionary(a,mention,id_to_mentions)
            if oral_question:
                mention = Mention(spid,str(date),day_url,'business-oral',None)
                add_mention_to_dictionary(spid,mention,id_to_mentions)
            elif todays_business:
                mention = Mention(spid,str(date),day_url,'business-today',None)
                add_mention_to_dictionary(spid,mention,id_to_mentions)
            else:
                mention = Mention(spid,str(date),day_url,'business-written',None)
                add_mention_to_dictionary(spid,mention,id_to_mentions)
    if verbose: print "  Questions found: "+str(questions_found)


# Second (b) the Written Answers:

wap = WrittenAnswerParser()

for d in dates:
    h = wap.find_spids_and_holding_dates(str(d),verbose,modified_after)
    for k in h.keys():
        for t in h[k]:
            date, k, holding_date, gid = t
            if date >= str(all_after_date):
                value = Mention(k,date,None,"answer",gid)
                add_mention_to_dictionary(k,value,id_to_mentions)
                if holding_date:
                    holding_value = Mention(k,holding_date,None,"holding",None)
                    add_mention_to_dictionary(k,holding_value,id_to_mentions)

# Third (c) look through the Official Reports for oral questions:

sxp = ScrapedXMLParser("../../../parldata/scrapedxml/sp/sp%s.xml")

for d in dates:
    gids_and_matches = sxp.find_all_ids_for_quotation(str(d),[spid_re_bracketed_at_end],modified_after)
    if gids_and_matches == None:
        # Then we just didn't find files for those dates:
        continue
    for t in gids_and_matches:
        gid, m = t
        spid = m.group(1)
        spid = fix_spid(spid)
        value = Mention(spid,str(d),None,"oral-asked-in-official-report",gid)
        add_mention_to_dictionary(spid,value,id_to_mentions)
    # If we didn't find any, and this is a Thursday, that's suspicious:
    if len(gids_and_matches) == 0 and d.isoweekday() == 4:
        print "Didn't find any question IDs in official report on "+str(d)

# Finally, write out the updated file:

save_question_mentions(id_to_mentions)
