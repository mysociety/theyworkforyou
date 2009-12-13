#!/usr/bin/python2.4

import sys
import os
import random
import datetime
import time
from optparse import OptionParser

sys.path.append('../')
import xml.sax
xmlvalidate = xml.sax.make_parser()

from BeautifulSoup import BeautifulSoup
from BeautifulSoup import NavigableString
from BeautifulSoup import Tag
from BeautifulSoup import Comment

from subprocess import call

from resolvemembernames import memberList

import re
import glob
import libxml2

from findquotation import ScrapedXMLParser
from findquotation import find_quotation_from_text

from common import month_name_to_int
from common import non_tag_data_in
from common import tidy_string

import traceback

# ------------------------------------------------------------------------
#
# This script is quite horrendous, in my opinion - I'm sure it could
# be written much more simply by someone with better intuition for
# this type of webscraping.  (Or by me if I started again.)
#
# ------------------------------------------------------------------------

# If verbose is True then you'll get about a gigabyte of nonsense on
# standard output.

parser = OptionParser()
parser.add_option('-q', "--quiet", dest="quiet", action="store_true",
                  default=False, help="don't print status messages")
parser.add_option('-f', '--force', dest='force', action="store_true",
                  help='force reparse of everything')
(options, args) = parser.parse_args()
verbose = False # XXX Very verbose, needs command line option

# Up to and including 2003-05-29 is the old format of the official
# reports, and 2003-06-03 and after is the new format.  There's one
# particular date that has a different format again, which is
# 2004-06-30.
#
# Old format:
# ~~~~~~~~~~~
#
# Table rows with left <td> containing column information
#                right <td> containing speeches ("substance")
#
# The right <td> can have multiple speeches, which may be split in
# awkward places because of indicating the start of the another column
# in the left td.  Even the speakers sometimes split by col number,
# e.g. 1999-05-19_1.html#Col76
#
# New format:
# ~~~~~~~~~~~
#
# One <p> per speech.
#
# Some contain "Col", some contain times, some quotes (begin with ")
#
# e.g. or2003-12-10_1.html:
#     Assorted headings (e.g. the contents link and right aligned <td> with date)
#
# One huge <td> with alternate:
#   <p> with column information </p>
#   <div> which is really just a content cell as in the early format </div>
#
# ------------------------------------------------------------------------
#
# Other miscellaneous notes:
# ~~~~~~~~~~~~~~~~~~~~~~~~~~
#
# References to official reports look like:
#
#    [<em>Official Report</em>, 13 June 2001; c 1526.]
#
# Things in [<em>] may include:
#   atmosphere (laughter,intteruption)
#   references to official reports or committee reports
#   proposers of bills

# Things in normal <em> may include:
#
#   Amendment disagreed to.
#   Amendment agreed to.
#   Motion, as amended, agreed to.
#   Motion agreed to.
#   Motion debated,
#   Resolved,
#   Meeting closed at 17:46
#   Motion moved&#151;[Name of MSP]
#   Meeting suspended until 14:15.
#   On resuming&#151;
#   rose&#151;

# And some section headings...


cases = { }

def count_case( name ):
    cases.setdefault(name,0)
    cases[name] += 1

def report_cases():
    keys = cases.keys()
    keys.sort()
    for k in keys:
        print "%9d hits on %s" % ( cases[k], k )

def log_speaker(speaker,date,message):
    if True:
        out_file = open("speakers.txt", "a")
        out_file.write(str(date)+": ["+message+"] "+speaker+"\n")
        out_file.close()

# We add a PlaceHolder whenever the column number or time has
# changed since the last object that was added to all_stuff:
class PlaceHolder:
    def __init__(self,colnum,time):
        self.colnum = colnum
        self.time = time
    def to_xml(self):
        colnum_attribute = ""
        time_attribute = ""
        if self.colnum:
            colnum_attribute = ' colnum="%s"' % (self.colnum)
        if self.time:
            time_attribute = ' time="%s"' % (self.time)
        return '<place-holder%s%s/>' % (colnum_attribute,time_attribute)

def all_objects_except_placeholders(sequence):
    return filter( lambda x: x.__class__ != PlaceHolder, sequence )

class Heading:

    def __init__(self,id_within_column,colnum,time,url,date,heading_text,major):
        self.id_within_column = id_within_column
        self.time = time
        self.url = url
        self.date = date
        self.major = major
        self.set_colnum(colnum)
        self.heading_text = heading_text

    def set_colnum(self,column):
        self.colnum = column
        self.id = 'uk.org.publicwhip/spor/'+str(self.date)+'.'+str(self.colnum)+'.'+str(self.id_within_column)

    def to_xml(self):
        if self.major:
            heading_type = 'major'
        else:
            heading_type = 'minor'
        # We probably want to remove all the markup and extraneous
        # spaces from the heading text.
        text_to_display = re.sub('(?ims)<[^>]+>','',self.heading_text)
        text_to_display = re.sub('(?ims)\s+',' ',text_to_display)
        text_to_display = re.sub('"','&quot;',text_to_display)
        text_to_display = text_to_display.strip()
        time_info = ''
        if self.time:
            time_info = ' time="' + str(self.time) + '"'
        result = '<%s-heading id="%s" nospeaker="True" colnum="%s" url="%s"%s>%s</%s-heading>' % ( heading_type, self.id, self.colnum, self.url, time_info, text_to_display, heading_type )
        return result

class Speech:

    def __init__(self,id_within_column,colnum,time,url,date,parser):
        if verbose: print "- Creating Speech..."
        self.id_within_column = id_within_column
        self.time = time
        self.url = url
        self.date = date
        self.set_colnum(colnum)
        self.paragraphs = [ ]
        self.speakerid = None
        self.name = None
        self.question_number = None
        self.parser = parser
        self.open_quote = False
        self.close_quote = True
        self.opened_and_closed_quote = False
        self.after_open_quote = False

    def set_colnum(self,column):
        self.colnum = column
        self.id = 'uk.org.publicwhip/spor/'+str(self.date)+'.'+str(self.colnum)+'.'+str(self.id_within_column)

    def no_text_yet(self):
        return len(all_objects_except_placeholders(self.paragraphs)) == 0

    def add_paragraph(self,paragraph):
        # if verbose: print "- in add_paragraph, self.paragraphs was: "+str(self.paragraphs)
        if verbose: print "adding paragraph: "+re.sub('(?ims)\s+',' ',paragraph)
        if not paragraph:
            raise Exception, "Trying to add null paragraph..."
        self.paragraphs.append(paragraph)

    def add_placeholder_in_speech(self,placeholder):
        self.paragraphs.append(placeholder)

    def add_text_to_last_paragraph(self,text):
        if self.paragraphs:
            if self.paragraphs[-1].__class__ == PlaceHolder:
                self.paragraphs.append(text)
            else:
                last = self.paragraphs.pop()
                # if verbose: print "- last ["+str(last.__class__)+"] was: "+str(last)
                # if verbose: print "- text ["+str(text.__class__)+"] was: "+str(text)
                # if verbose: print "- self.paragraphs ["+str(self.paragraphs.__class__)+"] was: "+str(self.paragraphs)
                self.paragraphs.append(last+" "+text)
        else:
            self.paragraphs = [ text ]

    def set_speaker(self,speaker):
        if verbose: print "- setting self.name to: "+speaker
        self.name = speaker

    def complete(self):
        # Once we're sure the speech is completed we can decode the
        # name:
        if self.name:
            tidied_speaker = self.name.strip()
            tidied_speaker = re.sub( '(?ims)^\s*', '', tidied_speaker )
            tidied_speaker = re.sub( '(?ims):?\s*$', '', tidied_speaker )
            tidied_speaker = re.sub( '(?ms) +', ' ', tidied_speaker )
            if verbose: print '- New speech from ' + tidied_speaker

            ids = memberList.match_whole_speaker(tidied_speaker,str(self.date))

            final_id = None

            if ids != None:
                if len(ids) == 0:
                    log_speaker(tidied_speaker,str(self.date),"missing")
                elif len(ids) == 1:
                    final_id = ids[0]
                else:
                    # If there's an ambiguity there our best bet is to go
                    # back through the previous IDs used today, and pick
                    # the most recent one that's in the list we just got
                    # back...
                    for i in range(len(parser.speakers_so_far)-1,-1,-1):
                        older_id = parser.speakers_so_far[i]
                        if older_id in ids:
                            final_id = older_id
                            break
                    if not final_id:
                        log_speaker(tidied_speaker,str(self.date),"genuine ambiguity")
                        self.speakerid = None

                if final_id:
                    parser.speakers_so_far.append(final_id)
                    self.speakerid = final_id
                    self.name = tidied_speaker
                else:
                    self.speakerid = None
                    self.name = tidied_speaker

            else:
                # It's something we know about, but not an MSP (e.g
                # Lord Advocate)
                self.name = tidied_speaker
                self.speakerid = None

    def set_question_number(self,number):
        self.question_number = number

    def display(self):
        if verbose: print '- Speech from: '+self.name
        if self.question_number:
            if verbose: print '- Numbered: '+self.question_number
        for p in self.paragraphs:
            if verbose: print '   [paragraph] ' + p

    def find_quotation(self,content,citation):
        m = re.search(' (\d+) ([a-zA-Z]+) (\d{4})[^0-9]',citation)
        if not m:
            return None
        column_numbers_strings = None
        m_columns = re.search('c ([0-9 ,]+)',citation)
        if m_columns:
            column_numbers_strings = re.findall('\d+',m_columns.group(1))
        if verbose: print "year: "+m.group(3)+", month: "+m.group(2)+", day: "+m.group(1)
        d = datetime.date(int(m.group(3),10),month_name_to_int(m.group(2)),int(m.group(1),10))
        m = re.search('Written Answers.*(S\d[A-Z]-\d+)',citation)
        if m:
            return "uk.org.publicwhip/spwa/%s.%s.h" % ( str(d), m.group(1) )
        return find_quotation_from_text(self.parser.sxp,d,content,column_numbers_strings)

    def remove_trailing_placeholders(self):
        result = []
        i = len(self.paragraphs) - 1
        while i >= 0 and self.paragraphs[i].__class__ == PlaceHolder:
            result.insert(0,self.paragraphs[i])
            del self.paragraphs[i]
            i -= 1
        return result

    def tidy_paragraph_text(self,untidy_text):
        real_paragraph = re.sub('(?ims)(\s*)</?p[^>]*>(\s*)',r'\1\2',untidy_text)
        real_paragraph = re.sub('(?ims)\s+',' ',real_paragraph)

        m_start_and_end = re.match( '^\s*(&quot;)(.*)(&quot;.?)(&mdash;)?(\[[^\]]*\])?\s*$', real_paragraph )
        m_start         = re.match( '^\s*(&quot;)(.*)', real_paragraph )
        m_end           = re.match( '^\s*(.*)(&quot;.?)(&mdash;)?(\[[^\]]*\])?\s*$', real_paragraph )

        indent = False

        if m_start_and_end:
            self.opened_and_closed_quote = True
            if not re.search('&quot;',m_start_and_end.group(2)):
                indent = True
            if m_start_and_end.group(5):
                gid = self.find_quotation(m_start_and_end.group(2),m_start_and_end.group(5))
                if gid:
                    m = m_start_and_end
                    before = m.group(1)+m.group(2)+m.group(3)
                    if m.group(4):
                        before += m.group(4)
                    real_paragraph = before+('<citation id="%s">%s</citation>'%(gid,m.group(5)))
        elif m_start:
            self.open_quote = True
            if not re.search('&quot;',m_start.group(2)):
                indent = True
        elif m_end:
            self.close_quote = True
            if not re.search('&quot;',m_end.group(1)):
                indent = True
            if m_end.group(4):
                gid = self.find_quotation(m_end.group(1),m_end.group(4))
                if gid:
                    m = m_end
                    before = m.group(1)+m.group(2)
                    if m.group(3):
                        before += m.group(3)
                    real_paragraph = before+('<citation id="%s">%s</citation>'%(gid,m.group(4)))
        elif self.after_open_quote:
            indent = True
        return real_paragraph, indent

    def to_xml(self):

        # Those awkward alphabetical lists.

        if self.name and (len(self.name) == 1 or self.name == 'Abbreviations'):
            self.paragraphs.insert(0,'<b>'+self.name+'</b>')
            self.name = None

        # We only resolve from the list of MSPs in general, so make
        # George Younger a special case:
        if self.name and re.search('George Younger',self.name):
            self.speakerid = "uk.org.publicwhip/lord/100705"

        # Also we should try to recognize Her Majesty The Queen:
        if self.name and re.search('(?ims)Her Majesty The Queen',self.name):
            self.speakerid = "uk.org.publicwhip/royal/-1"

        # The speech id should look like:
        #    uk.org.publicwhip/spor/2003-06-26.1219.2
        # The speaker id should look like:
        #    uk.org.publicwhip/member/931

        if self.name:
            if self.speakerid:
                speaker_info = 'speakerid="%s" speakername="%s"' % ( self.speakerid, self.name )
            else:
                speaker_info = 'speakerid="unknown" speakername="%s"' % ( self.name )
        else:
            speaker_info = 'nospeaker="true"'

        question_info = ' '
        if self.question_number:
            question_info = 'questionnumber="%s" ' % ( self.question_number )

        time_info = ''
        if self.time:
            time_info = ' time="' + str(self.time) + '"'

        # FIXME: We should probaly output things that look like
        # questions or replies as <ques> and <reply>...

        result = '<speech id="%s" %s %scolnum="%s" url="%s"%s>' % ( self.id, speaker_info, question_info, self.colnum, self.url, time_info )
        html = ''

        paragraph_open = False

        last_real_paragraph = None
        last_indent = False
        for p in self.paragraphs:
            if p.__class__ == PlaceHolder:
                if not paragraph_open:
                    result += "\n<p>"
                    paragraph_open = True
                result += p.to_xml()
            else:
                real_paragraph, indent = self.tidy_paragraph_text(p)
                looks_like_continuation = re.search('^\s*[a-z]',real_paragraph)
                last_paragraph_unfinished = last_real_paragraph and re.search('[a-z0-9;,] *$',last_real_paragraph)
                # However, if it looks like someone's just said: "I
                # move," and the start of the next paragraph doesn't
                # look like a continuation then it *should* be a new
                # paragraph...
                move_match = last_real_paragraph and re.search('I move, *$',last_real_paragraph)
                # Sometimes the start of a paragraph is a list entry -
                # it looks less confusing if these are in a new
                # paragraph:
                looks_like_list_entry_next = re.search('^\s*(\([a-z]+\)|[0-9]+\.)',real_paragraph)
                if (move_match or looks_like_list_entry_next) and not looks_like_continuation:
                    last_paragraph_unfinished = False
                if paragraph_open and ((last_indent != indent) or not (last_paragraph_unfinished or looks_like_continuation)):
                    result += "</p>\n"
                    paragraph_open = False
                indent_text = ''
                if indent:
                        indent_text = ' class="indent"'
                if paragraph_open:
                    result += " "
                else:
                    result += "\n<p%s>" % (indent_text,)
                    paragraph_open = True
                result += "%s" % (real_paragraph,)
                last_real_paragraph = real_paragraph
                last_indent = indent
        if paragraph_open:
            result += "</p>\n"
        result += '</speech>'
        return result

class Division:

    def __init__(self,id_within_column,colnum,time,url,date,divnumber):
        self.id_within_column = id_within_column
        self.time = time
        self.url = url
        self.date = date
        self.divnumber = divnumber
        self.for_votes = list()
        self.against_votes = list()
        self.abstentions_votes = list()
        self.spoiled_votes_votes = list()
        self.set_colnum(colnum)
        self.candidate = None

    def set_colnum(self,column):
        self.colnum = column
        self.id = 'uk.org.publicwhip/spdivision/'+str(self.date)+'.'+str(self.colnum)+'.'+str(self.divnumber)

    def set_candidate(self,candidate):
        self.candidate = candidate

    def add_votes(self, which_way, name):
        if which_way == 'FOR':
            self.for_votes.append(name)
        elif which_way == 'AGAINST':
            self.against_votes.append(name)
        elif which_way == 'ABSTENTIONS':
            self.abstentions_votes.append(name)
        # There's one instance of a spoiled vote.  I'm not sure quite
        # how this happens with the electronic voting system - maybe
        # there's a "SPOIL VOTE" button, or you pour a glass of water
        # onto the machine...
        elif which_way == 'SPOILED VOTES':
            self.spoiled_votes_votes.append(name)
        else:
            raise Exception, "add_votes for unknown way: " + which_way

    def to_xml(self):
        candidate_info = ''
        if self.candidate:
            candidate_info = ' candidate="'+self.candidate+'"'
        result = '<division id="%s"%s nospeaker="True" divdate="%s" divnumber="%s" colnum="%d" time="%s" url="%s">' % ( self.id, candidate_info, self.date, self.divnumber, self.colnum, self.time, self.url )
        result += "\n"
        result += '  <divisioncount for="%d" against="%d" abstentions="%d" spoiledvotes="%d"/>' % ( len(self.for_votes), len(self.against_votes), len(self.abstentions_votes), len(self.spoiled_votes_votes) )
        result += "\n"
        if self.candidate:
            ways_to_list = [ "for" ]
        else:
            ways_to_list = [ "for", "against", "abstentions", "spoiled votes" ]
        for way in ways_to_list:
            votes = None
            if way == "for":
                votes = self.for_votes
            elif way == "against":
                votes = self.against_votes
            elif way == "abstentions":
                votes = self.abstentions_votes
            else:
                votes = self.spoiled_votes_votes
            result += '  <msplist vote="%s">' % ( way )
            result += "\n"
            for msp in votes:
                ids = memberList.match_whole_speaker(msp,str(self.date))
                if ids != None:
                    if len(ids) > 1:
                        raise Exception, "Ambiguous name in division results: "+msp
                    if len(ids) == 1:
                        result += '    <mspname id="%s" vote="%s">%s</mspname>' % ( ids[0], way, msp )
                        result += "\n"
                else:
                    raise Exception, "Odd voter in divison: "+msp
            result += "  </msplist>\n"
        result += '</division>'
        return result

speakers = []

or_prefix = "../../../parldata/cmpages/sp/official-reports/"

dates = []
currentdate = datetime.date( 1999, 5, 12 )

enddate = datetime.date.today()
while currentdate <= enddate:
    dates.append( currentdate )
    currentdate += datetime.timedelta(days=1)

# When we change to the new format...
cutoff_date = datetime.date( 2003, 06, 03 )

# It's helpful to have some way to spot the element that contains the
# right table - hopefully this will do that...

def two_cell_rows( table_tag ):
    rows = table_tag.findAll('tr',recursive=False)
    plausible_rows = 0
    for r in rows:
        cells = r.findAll('td',recursive=False)
        if len(cells) == 2:
            plausible_rows += 1
    return plausible_rows

def centred( t ):
    if t.__class__ == NavigableString:
        return False
    elif t.__class__ == Comment:
        return False
    elif t.__class__ == Tag:
        if t.name == 'center':
            return True
        if t.has_key('align') and t['align'].lower() == 'center':
            return True
        else:
            for c in t.contents:
                if centred(c):
                    return True
        return False
    else:
        raise Exception, "Unknown class: "+str(t.__class__)

def just_time( non_tag_text ):
    m = re.match( '^\s*(\d?\d)[:\.](\d\d)\s*$', non_tag_text )
    if m:
        return datetime.time(int(m.group(1),10),int(m.group(2),10))

def meeting_closed( non_tag_text ):
    m = re.match( '(?ims)^\s*Meeting\s+closed\s+at\s+(\d?\d)[:\.](\d\d)\s*\.?\s*$', non_tag_text )
    if m:
        return datetime.time(int(m.group(1),10),int(m.group(2),10))
    else:
        return None

def meeting_suspended( non_tag_text ):
    m = re.match( '(?ims)^\s*Meeting\s+suspended(\s+until\s+(\d?\d)[:\.](\d\d)\s*\.?\s*|\s*\.?\s*)$', non_tag_text )
    if m:
        if verbose: print "Got meeting suspended!"
        return True
    else:
        return False

def full_date( s ):
    if re.search('(?ims)^\s*\w+\s+\d+\s+\w+\s+\d+\s*$',s):
        try:
            return time.strptime(s.strip(),'%A %d %B %Y')
        except:
            return None
    else:
        return None

def full_date_without_weekday( s ):
    if re.search('(?ims)^\s*\d+\s+\w+\s+\d+\s*$',s):
        try:
            return time.strptime(s.strip(),'%d %B %Y')
        except:
            return None
    else:
        return None

def find_opening_paragraphs( body ):
    
    t = body.find( lambda x: x.name == 'p' and re.search('(?ims)(opened|recommenced).*at\s+([0-9]?[0-9]):([0-9][0-9])',non_tag_data_in(x)) )
    if t:

        previous_paragraphs = []
        next_paragraphs = []
        previous = t.previousSibling
        while previous:
            if previous.__class__ == Tag and previous.name == 'p':
                previous_paragraphs.insert(0,previous)
            previous = previous.previousSibling
        next = t.nextSibling
        while next:
            if next.__class__ == Tag and next.name == 'p':
                if len(str(next)) > 500:
                    break
                else:
                    next_paragraphs.append(next)
            next = next.nextSibling

        all_opening_paragraphs = previous_paragraphs + [ t ] + next_paragraphs

        useful_opening_paragraphs = previous_paragraphs + [ t ] + next_paragraphs[0:1]

        return useful_opening_paragraphs
        
    else:
        return None
        # raise Exception, "Couldn't find the opening announcement in "+detail_filename

class Parser:

    def __init__(self):
        # This should persist between parses (we're doing them in order...)

        self.major_regexp = None
        self.minor_regexp = None

        self.current_column = None
        self.current_id_within_column = 0

        self.current_anchor = None
        self.current_time = None

        self.current_speech = None
        self.current_division = None
        self.current_heading = None

        self.for_after_division = []

        self.results_expected = None

        self.started = False

        self.division_number = 0

        self.all_stuff = []
        self.speakers_so_far = []

        self.report_date = None

        self.sxp = ScrapedXMLParser()

    # Returns True if a column could be parsed out, and returns False otherwise
    def parse_column(self,tag):
        a_name_tag = tag.find('a')
        if a_name_tag:
            if verbose: print "a_name_tag class is: "+str(a_name_tag.__class__)
            a_name = a_name_tag['name']
            if a_name:
                self.current_anchor = a_name

        text_in_tag = non_tag_data_in(tag)

        m = re.search('Col\s*([0-9]+)',text_in_tag)
        if m:
            self.current_column = int(m.group(1))
            self.current_id_within_column = 0
            return True
        else:
            # It's probably the last row, with a "Scottish Parliament
            # 2000" notice, or empty for padding at the end...
            if not (re.search('Scottish Parliament',text_in_tag) or re.match('(?ims)^\s*$',text_in_tag)):
                raise Exception, "Couldn't find column from: "+text_in_tag+" prettified, was: "+tag.prettify()
            return False

    def parse_weird_day( self, body ):

        self.started = False

        # For some reason, one day (2004-06-30) has a different format from all the rest :(

        max_paragraphs = -1
        main_cell = None

        for cell in body.findAll('td'):

            paragraphs = filter( lambda x: x and x.__class__ == Tag and x.name == 'p', cell.contents )

            if len(paragraphs) > max_paragraphs:
                max_paragraphs = len(paragraphs)
                main_cell = cell

        if verbose: print "Picking cell with " + str(max_paragraphs) + " plausible paragraphs."

        # Now go through each of the contents of that <td>, which
        # should be either column indicators or paragraphs with "substance"

        self.parse_substance(main_cell)

    def parse_early_format( self, body ):

        opening_paragraphs = find_opening_paragraphs(body)
        
        opening_table = None
        if opening_paragraphs:
            self.started = False
            t = opening_paragraphs[0]
            if t.parent and t.parent.parent and t.parent.parent.parent:
                great_grand_parent = t.parent.parent.parent
                if great_grand_parent.name == 'table':
                    opening_table = great_grand_parent
        else:
            self.started = True # Since this may just be a continuation...

        # Look for all the tables in the page and pick the one that
        # has the most two cell rows...  (This is mostly right, but
        # for a few cases I've added some empty two cell rows :))
        
        main_table = None
        max_two_cell_rows = -1

        for table in body.findAll('table'):
            plausible_rows = two_cell_rows(table)
            if plausible_rows > max_two_cell_rows:
                main_table = table
                max_two_cell_rows = plausible_rows

        if verbose: print "Picking table with " + str(max_two_cell_rows) + " plausible rows."

        all_rows = []

        if opening_table and opening_table != main_table:
            for row in opening_table.findAll('tr',recursive=False):
                all_rows.append(row)
        for row in main_table.findAll('tr',recursive=False):
            all_rows.append(row)

        volume = None
        number = None

        date = None

        for row in all_rows:

            cells = row.findAll('td',recursive=False)

            col_cell = None
            substance_cell = None

            if len(cells) == 1:
                # This is probably the 'presiding officer opened' bit, or
                # one of the <hr>s at the top.
                # if verbose: print cells[0].prettify()
                substance_cell = cells[0]
            elif len(cells) == 2:
                col_cell = cells[0]
                substance_cell = cells[1]
            else:
                raise Exception, "Unexpected number of cells "+str(len(cells))+" in "+row.prettify()

            # If the substance cell is right aligned, then it's either
            # going to have the date or the volume + number information
            # (more or less)...

            # if verbose: print 'substance cell has name ' + substance_cell.prettify()

            if substance_cell.has_key('align'):
                if substance_cell['align'].lower() == 'right':
                    t = non_tag_data_in(substance_cell)
                    t = " ".join(t.split())
                    m = re.search('Vol (\d+)',t)
                    if m:
                        volume = int(m.group(1))
                    m = re.search('Num (\d+)',t)
                    if m:
                        number = int(m.group(1))
                    if number or volume:
                        continue
                    m = re.search('([0-9]+ \w+ [0-9]+)',t)
                    if m:
                        w = time.strptime(m.group(1),'%d %B %Y')
                        date = datetime.date( w[0], w[1], w[2] )
                    continue

            if col_cell:
                if self.parse_column(col_cell):
                    # AARGH: second duplicate is from here
                    self.add_placeholder_column()

            # Now deal with the cell with substance.  This is quite a bit trickier...

            self.parse_substance(substance_cell.contents)

    def parse_late_format( self, body ):

        self.started = False

        max_divs = -1
        main_cell = None

        for cell in body.findAll('td'):

            divs = filter( lambda x: x and x.__class__ == Tag and x.name == 'div', cell.contents )

            if len(divs) > max_divs:
                max_divs = len(divs)
                main_cell = cell

        if verbose: print "Picking cell with " + str(max_divs) + " plausible divs."

        # Now go through each of the contents of that <td>, which
        # should be either column indicators or divs with "substance"

        for m in main_cell.contents:

            if m.__class__ == Tag and m.name == 'p':
                non_tag_data = non_tag_data_in(m)
                if re.match('(?ims)^\s*$',non_tag_data):
                    continue
                if full_date_without_weekday(non_tag_data):
                    continue
                if self.parse_column(m):
                    self.add_placeholder_column()
            elif m.__class__ == Tag and m.name == 'div':
                self.parse_substance(m)
            elif m.__class__ == Tag:
                if m.name == 'span' and m['class'] and m['class'].lower() == 'largeheading':
                    self.add_heading(non_tag_data_in(m),True)
                    continue
                if m.name == 'span' and m['class'] and m['class'].lower() == 'orcolno':
                    if self.parse_column(m):
                        self.add_placeholder_column()
                    continue
                if m.name == 'br':
                    continue
                non_tag_data = non_tag_data_in(m)
                if re.match('(?ims)^\s*$',non_tag_data):
                    continue
                raise Exception, "Unknown element in contents of main cell: "+m.prettify()
            elif m.__class__ == Comment:
                continue
            else:
                if not re.match('(?ims)^\s*$',str(m)):
                    raise Exception, "Unknown non-empty navigable string in contents of main cell: "+str(m)

    def add_placeholder_time(self):
        self.add_placeholder_real(None,self.current_time)

    def add_placeholder_column(self):
        self.add_placeholder_real(self.current_column,None)

    def add_placeholder_real(self,column,time):
        p = PlaceHolder(column,time)
        if self.current_speech:
            self.current_speech.add_placeholder_in_speech(p)
        elif self.current_heading:
            self.complete_current()
            #if time:
            #    self.current_heading.time = time
            #if column:
            #    self.current_heading.id_within_column = 0
            #    self.current_heading.set_colnum(column)
            #    self.current_id_within_column += 1
            self.all_stuff.append(p)
        elif self.current_division:
            # We want to keep the divisions all together, so add any
            # column number or time place-holder after the division:
            self.for_after_division.append(p)
        else:
            self.all_stuff.append(p)

    def complete_current(self):
        if self.current_heading:
            self.all_stuff.append(self.current_heading)
            self.current_heading = None
        if self.current_speech:
            self.current_speech.complete()
            self.all_stuff.append(self.current_speech)
            self.current_speech = None
        if self.current_division:
            self.all_stuff.append(self.current_division)
            self.current_division = None
            self.all_stuff += self.for_after_division
            self.for_after_division = []

    def make_url(self):
        url_without_anchor = self.url
        if self.current_anchor:
            return url_without_anchor + "#" + self.current_anchor
        else:
            return url_without_anchor

    def add_to_speech_or_make_new_one(self,s):
        if self.current_speech:
            self.current_speech.add_paragraph( s )
        else:
            self.complete_current()
            self.ensure_heading_exists()
            self.current_speech = Speech(self.current_id_within_column,self.current_column,self.current_time,self.make_url(),self.report_date,self)
            self.current_id_within_column += 1
            self.current_speech.add_paragraph( s )

    def add_heading(self,text,major):
        self.complete_current()
        non_placeholders = all_objects_except_placeholders(self.all_stuff)
        if len(non_placeholders) > 1:
            last = non_placeholders[-1]
            # It's not just the introduction heading...
            if last.__class__ == Heading and (last.major == major):
                # Then just append this to the last heading with an em-dash:
                last.heading_text += ' &mdash; ' + text
                return
        self.current_heading = Heading(self.current_id_within_column,self.current_column,self.current_time,self.make_url(),self.report_date,text,major)
        self.current_id_within_column += 1

    def ensure_heading_exists(self):
        non_placeholders = all_objects_except_placeholders(self.all_stuff)
        if len(non_placeholders) < 1 and not self.current_heading:
            self.add_heading("Introduction",True)

    def parse_substance(self,contents):

        non_empty_contents = filter(lambda x: x.__class__ != NavigableString or not re.match('^\s*$',x), contents)

        for s in non_empty_contents:

            if verbose:
                print "/#####################"
                if s.__class__ == NavigableString:
                    print "[NavigableString] "+s
                elif s.__class__ == Tag:
                    print "[Tag] "+s.prettify()
                else:
                    print "[Comment]"+str(s)
                print "#####################/"

            if s.__class__ == Comment:
                continue

            non_tag_text = non_tag_data_in(s)

            # In the one Weird Day, this might be a column number
            # paragraph, so check for that:

            if re.match( '^\s*Col\s*[0-9]+\s*$', non_tag_text ):
                if self.parse_column(s):
                    # AARGH: one duplicate from here
                    self.add_placeholder_column()
                continue
            # So that we don't match a gigantic main column - FIXME: temporary
            elif re.match( '^\s*Col\s*[0-9]+\s', non_tag_text ):
                continue

            # This might just be the time:
            maybe_time = just_time(non_tag_text)
            if maybe_time:
                self.current_time = maybe_time
                self.add_placeholder_time()
                continue

            # It might be the "Meeting closed at" message:
            maybe_time = meeting_closed(non_tag_text)
            if maybe_time:
                self.current_time = maybe_time
                self.add_placeholder_time()
            # But carry on, because we still want to include the text...

            if meeting_suspended(non_tag_text):
                self.complete_current()

            # I don't think we ever care if there's no displayable text:

            if re.match('(?ims)^\s*$',non_tag_text):
                continue

            # Might this be one of the headings we parsed from the
            # contents page?

            for_matching = re.sub('(?ims)\s+',' ',non_tag_text).strip()

            minor_heading_match = False
            major_heading_match = False

            if major_regexp and re.match(major_regexp,for_matching):
                major_heading_match = True
            elif minor_regexp and re.match(minor_regexp,for_matching):
                minor_heading_match = True

            if major_heading_match or minor_heading_match:
                count_case("heading-from-regexp")
                self.add_heading(for_matching,major_heading_match)
                continue

            # It's sometimes hard to detect the headings at the
            # beginning of the page, so look out for them in
            # particular...

            if not self.started:

                non_tag_text = re.sub('(?ms)\s+',' ',non_tag_text)
                if verbose: print "Not started, and looking at '" + non_tag_text + "'"

                got_preamble = False

                m = re.search('(?ims)(opened|recommenced).*at\s+([0-9]?[0-9]):([0-9][0-9])',non_tag_text)

                if re.match('(?ims)\s*Scottish Parliament\s*',non_tag_text):
                    got_preamble = True
                elif full_date(non_tag_text):
                    got_preamble = True
                elif full_date_without_weekday(non_tag_text):
                    got_preamble = True
                elif re.match('^[\s\(]*(Afternoon|Morning)[\s\)]*$',non_tag_text):
                    got_preamble = True
                elif m:
                    self.current_time = datetime.time(int(m.group(2),10),int(m.group(3),10))
                    self.add_placeholder_time()
                    got_preamble = True

                if got_preamble:
                    t = str(s)
                    t = re.sub('(?ims)^\s*<p[^>]*>(.*)(</p>\s*$)',r'\1',t)
                    t = t.strip()
                    self.add_to_speech_or_make_new_one(t)
                    continue

            if NavigableString == s.__class__ or s.name == 'sup' or s.name == 'sub' or s.name == 'br':
                if self.results_expected:
                    if NavigableString == s.__class__:
                        # Then just add that name to the votes...
                        stripped = str(s).strip()
                        stripped = re.sub('(?ms)\s+',' ',stripped)
                        if len(stripped) > 0: # At the end of a list, we might get an empty one...
                            self.current_division.add_votes(self.results_expected,stripped)
                        continue
                    elif s.name == 'br':
                        # We can ignore that, it's just dividing up
                        # the names in the list of votes.
                        continue
                self.results_expected = None
                if self.current_speech:
                    self.current_speech.add_text_to_last_paragraph(str(s))
                else:
                    # If it's just a break, it's safe to ignore it in this situation:
                    if not (s.__class__ == Tag and s.name == 'br'):
                        raise Exception, "Wanted to add '"+text_to_add+"' to the current speech, but there wasn't a current speech."
                continue

            if verbose: print '- '+s.name+" <-- got a tag with this name"

            # So, there might be all manner of things here.  Mostly
            # they will be <p>, which is likely to be a speech or a
            # continuing speech.  If it is <center> or something
            # containing a <center> it is likely to be a heading,
            # however....

            if centred(s):
                count_case("centred")
                self.results_expected = None
                if verbose: print "- Centred, so a heading or something:\n" + s.prettify()
                non_tag_data = non_tag_data_in(s)
                if not re.match('(?ims)^\s*$',non_tag_data):
                    self.add_heading(s.prettify(),False)
                continue

            if len(s.contents) == 0:
                continue

            # As above, there may be empty NavigableStrings in the
            # contents of s, so filter them out:

            s_contents = filter(lambda x: x.__class__ != Comment and (x.__class__ != NavigableString or not re.match('^\s*$',x)), s.contents)

            if s.name == 'p' and len(s_contents) == 1 and s_contents[0].__class__ == Tag and (s_contents[0].name == 'em' or s_contents[0].name == 'i'):
                count_case("narrative")
                if verbose: print "Got what looks like some narrative..."
                # Then this is probably some narrative, just added
                # into the current speech.
                self.add_to_speech_or_make_new_one(str(s_contents[0]))
                continue

            # Sometimes we get lists or audience reaction in the
            # speech, so just add them.

            if s.name == 'ol' or s.name == 'i' or s.name == 'ul' or s.name == 'table':
                count_case("reaction-or-something")
                self.results_expected = None
                self.add_to_speech_or_make_new_one(s.prettify())
                continue

            if s.name != 'p':
                self.results_expected = None
                # If it's empty, we probably don't care either...
                just_text = non_tag_data_in(s)
                if re.match('^\s*$',just_text):
                    continue
                # We sometimes wrap awkward to parse thing in DIVs:
                if s.name == 'div':
                    self.add_to_speech_or_make_new_one(s.prettify())
                    continue
                else:
                    if s.name == 'strong':
                        count_case("strong-in-place-of-p")
                        # We only hit this twice; they're both headings...
                        self.add_heading(str(s),False)
                        continue
                    else:
                        raise Exception, "There was an unexpected s, which was: "+s.name+" with content: "+s.prettify()

            # So now this must be a paragraph...

            # Sometimes there's a pointless <br/> at the start of the <p>.
            if len(s_contents) > 0 and s_contents[0].__class__ == Tag and s_contents[0].name == 'br':
                count_case("leading-br-in-paragraph")
                if verbose: print "- removing leading <br> from contents..."
                s_contents = s_contents[1:]

            if len(s_contents) == 0:
                count_case("tag-now-empty")
                if verbose: print "- tag is now empty, so continuing"
                continue

            # If there is a <strong> element in the first place, that is
            # probably the name of a speaker, and this is the beginning of
            # a new speech, so separate the first element from the rest:

            first = s_contents[0]
            rest = s_contents[1:]

            if first.__class__ == Tag:
                if verbose: print "- first's name is "+first.name

            # This may be the results of a division, so test for that.

            if first.__class__ == Tag and (first.name == 'strong' or first.name == 'b') and (first.string):

                so_far = ''

                # Fetch the next sibling until we run out or they stop being "<strong>":
                next = first
                while True:
                    if not next:
                        break
                    if next.__class__ == Tag and (next.name == 'strong' or next.name == 'b') and (next.string):
                        so_far += next.string
                    elif next.__class__ == NavigableString:
                        if re.match('(?ms)^\s*$',str(next)):
                            so_far += str(next)
                        else:
                            break
                    next = next.nextSibling

                if verbose: print "Considering as division indicator: '"+so_far

                division_report = False

                if re.match('(?ms)^\s*F\s*OR[:\s]*$',so_far):
                    division_report = True
                    self.results_expected = 'FOR'
                elif re.match('(?ms)^\s*A\s*GAINST[:\s]*$',so_far):
                    division_report = True
                    self.results_expected = 'AGAINST'
                elif re.match('(?ms)^\s*A\s*BST?ENTIONS?[:\s]*$',so_far):
                    division_report = True
                    self.results_expected = 'ABSTENTIONS'
                elif re.match('(?ms)^\s*S\s*POILED\s+VOTES?[:\s]*$',so_far):
                    division_report = True
                    self.results_expected = 'SPOILED VOTES'
                else:
                    if verbose: print "Didn't match any in: '"+so_far+"'"
                if division_report and self.results_expected:
                    if not self.current_division:
                        if verbose: print '- Creating new division: ' + so_far
                        self.complete_current()
                        self.current_division = Division(self.current_id_within_column,self.current_column,self.current_time,self.make_url(),self.report_date,self.division_number)
                        self.division_number += 1
                    continue

            if first.__class__ == Tag and (first.name == 'strong' or first.name == 'b'):

                count_case("new-speaker")

                # Now we know it's probably a new speaker...

                self.results_expected = None

                # Remove all the empty NavigableString objects from rest:
                rest = filter( lambda x: not (x.__class__ == NavigableString and re.match('^\s*$',x)), rest )

                # This is a bit complicated - if the current speaker
                # only has a name, then this is probably a
                # continuation of the name, broken by a column
                # boundary...

                speaker = non_tag_data_in(first)

                while len(rest) > 0 and rest[0].__class__ == Tag and (rest[0].name == 'strong' or rest[0].name == 'b'):
                    count_case("extending-speaker-name-from-rest")
                    # In any case, sometimes there are two <strong>s
                    # next to each other that make up the name...
                    speaker = speaker + non_tag_data_in(rest[0])
                    rest = rest[1:]

                if verbose: print "ended up with speaker: '" + speaker + "'"
                question_number = None

                m = re.match('^\s*([\d ]+)\.?\s*(.*)$',speaker)
                if m:
                    # Then this is probably a numbered question...
                    number = re.sub('(?ms)\s','',m.group(1))
                    if len(number) > 0:
                        count_case("numbered-question")
                        question_number = int(number)
                        speaker = m.group(2)

                added_to_name = False

                if self.current_speech and self.current_speech.no_text_yet():
                    count_case("adding-to-current-name")
                    if verbose: print "- No text in current speech yet, so add to the name."
                    self.current_speech.set_speaker(self.current_speech.name+speaker)
                    added_to_name = True
                else:
                    if verbose: print "- Either there wasn't a current speech ("+str(self.current_speech)+") or there was text in it."
                    self.complete_current()
                    self.ensure_heading_exists()
                    self.current_speech = Speech(self.current_id_within_column,self.current_column,self.current_time,self.make_url(),self.report_date,self)
                    self.current_id_within_column += 1

                self.started = True

                if question_number:
                    self.current_speech.set_question_number(question_number)

                # When voting for particular candidates the results
                # come up like this; we treat these as a division...

                mcandidate = re.search('VOTES? FOR (.*)',speaker)
                if mcandidate:
                    count_case("votes-for-candidate")
                    self.current_speech.add_paragraph("<b>"+speaker.strip()+"</b>")
                    if verbose: "Found votes for a candiate: "+speaker
                    division_report = True
                    self.results_expected = 'FOR'
                    if verbose: print '- Creating new division for candidate: ' + so_far
                    self.complete_current()
                    self.current_division = Division(self.current_id_within_column,self.current_column,self.current_time,self.make_url(),self.report_date,self.division_number)
                    self.current_division.set_candidate(mcandidate.group(1))
                    self.division_number += 1
                    continue
                elif not added_to_name:
                    self.current_speech.set_speaker(speaker)

                add_to_last = False

                for r in rest:
                    maybe_time = just_time(non_tag_data_in(r))
                    if maybe_time:
                        self.current_time = maybe_time
                        self.add_placeholder_time()
                        continue
                    if r.__class__ == NavigableString:
                        if add_to_last:
                            self.current_speech.add_text_to_last_paragraph(r)
                        else:
                            self.current_speech.add_paragraph(r)
                        add_to_last = True
                    elif r.name == 'p':
                        if verbose: print "- Adding paragraph."
                        self.current_speech.add_paragraph( r.prettify() )
                    elif r.name == 'ol' or r.name == 'ul' or r.name == 'table':
                        self.current_speech.add_paragraph('<div>'+str(r)+'</div>')
                    else:
                        self.current_speech.add_text_to_last_paragraph(str(r))
                continue

            if s.name == 'p' and self.results_expected:
                count_case("more-results")
                if verbose: print "- We were expecting results and found: " + s.prettify()
                for v in s.contents:
                    if v.__class__ == NavigableString:
                        stripped = str(v).strip()
                        stripped = re.sub('(?ms)\s+',' ',stripped)
                        if verbose: print "  - in contents: "+str(stripped)
                        if len(stripped) > 0: # At the end of a list, we might get an empty one...
                            self.current_division.add_votes(self.results_expected,stripped)
                # There might be some more in the next substance cell
                # so don't reset results_expected yet.
                continue

            if s.__class__ == Tag and s.name == 'p':
                count_case("some-other-p")
                self.add_to_speech_or_make_new_one(str(s))



# Open the contents page, grab the debate headings and build regular
# expressions to match them.  This probably isn't strictly necessary,
# but might help.

def get_heading_regexps(contents_filename):

    fp = open(contents_filename)
    html = fp.read()
    fp.close()

    html = re.sub('(?i)&nbsp;',' ', html)

    soup = ScottishParliamentSoup( html, fromEncoding='iso-8859-15' )
    # Find the table with the most two-cell rows:

    main_contents_table = None
    max_two_cell_rows = -1

    for table in soup.findAll('table'):
        plausible_rows = two_cell_rows(table)
        if plausible_rows > max_two_cell_rows:
            main_contents_table = table
            max_two_cell_rows = plausible_rows

    major_headings = []
    minor_headings = []

    for row in main_contents_table.findAll('tr'):
        cells = row.findAll('td',recursive=False)
        if len(cells) == 2:
            c = cells[0]
            # links = c.findAll('a')
            # for link in links:
            text = non_tag_data_in(c)
            text = re.sub('(?ims)\s+',' ',text)
            if re.match('^\s*$',text):
                continue
            text = tidy_string(text)
            # Just ignore the listed MSP names, we only care about
            # the headings...
            msp_names = memberList.match_whole_speaker(text,str(d))
            if msp_names != None and len(msp_names) > 0:
                continue
            # The latter part of the condition is because sometimes
            # the titles of questions are just road names, e.g. A76
            if text.upper() == text and not re.match('^[A-Z]+[0-9]+$',text):
                major_headings.append(text.strip())
            else:
                minor_headings.append(text.strip())

    if verbose:
        print "On "+str(d)
        print "MAJOR HEADINGS:"
        for h in major_headings:
            print "   "+h
        print "Minor Headings:"
        for h in minor_headings:
            print "   "+h

    major_escaped = map( lambda x: re.escape(x), major_headings )
    minor_escaped = map( lambda x: re.escape(x), minor_headings )

    major_regexp = None
    minor_regexp = None

    if len(major_escaped) > 0:
        major_regexp = re.compile("(?ims)^("+"|".join(major_escaped)+")$")
        if verbose: print "major_regexp is: "+major_regexp.pattern
    if len(minor_escaped) > 0:
        minor_regexp = re.compile("(?ims)^("+"|".join(minor_escaped)+")$")
        if verbose: print "minor_regexp is: "+minor_regexp.pattern
    
    return ( major_regexp, minor_regexp )

class ScottishParliamentSoup(BeautifulSoup):
    # Changing NESTABLE_TAGS doesn't seem to work in the way that I
    # expect from the documentation, so just use the default parser..
    pass

def compare_filename(a,b):
    ma = re.search('_(\d+)\.html',a)
    mb = re.search('_(\d+)\.html',b)
    if ma and mb:
        mai = int(ma.group(1),10)
        mbi = int(mb.group(1),10)
        if mai < mbi:
            return -1
        if mai > mbi:
            return 1
        else:
            return 0
    else:
        raise Exception, "Couldn't match filenames: "+a+" and "+b

def get_last_column_and_id_in_column(previous_xml_filename):
    if verbose: print "Going to load '%s'" % (previous_xml_filename)
    doc = libxml2.parseFile(previous_xml_filename)
    a = doc.xpathEval('//@colnum')
    last_column = None
    for e in a:
        c = e.content
        if c and re.match('^\d+$',c):
            last_column = int(c,10)
    a = doc.xpathEval('//@id')
    last_id_in_column = None
    for e in a:
        c = e.content
        if c:
            m = re.search('\.(\d+)\.(\d+)$',c)
            if m:
                last_column_in_id = int(m.group(1),10)
                last_id_in_column_in_id = int(m.group(2),10)
    # The column number can advance without us hitting a new ID, in
    # which case last_column will be greater than last_column_in_id.
    # Reset the last_id_in_column_in_id in that case...
    if last_column > last_column_in_id:
        last_id_in_column_in_id = 0
    elif last_column_in_id != last_column:
        raise Exception, "The last colnum found anywhere was %d, but the last column number in an id was %d" % (last_column_in_id,last_column)
    if last_column != None and last_id_in_column_in_id != None:
        return (last_column,last_id_in_column_in_id)
    else:
        return None

# --------------------------------------------------------------------------
# End of function and class definitions...

last_column_number = 0
last_id_in_column = 0
last_skipped_file = None

for d in dates:

    xml_output_directory = "../../../parldata/scrapedxml/sp/"
    output_filename = xml_output_directory + "sp" + str(d) + ".xml"

    if verbose: print "Examining %s %s" % (d, output_filename)

    if (not options.force) and os.path.exists(output_filename):
        last_skipped_file = output_filename
        continue

    contents_filename = or_prefix + "or" +str(d) + "_0.html"
    if not os.path.exists(contents_filename):
        continue

    filenames = glob.glob( or_prefix + "or" + str(d) + "_*.html" )
    filenames.sort(compare_filename)

    if len(filenames) == 0:
        continue

    if last_skipped_file:
        last_column_from_skipped_file, last_id_in_column_from_skipped_file = get_last_column_and_id_in_column(last_skipped_file)
        if last_column_from_skipped_file:
            last_column_number = last_column_from_skipped_file
        if last_id_in_column_from_skipped_file:
            last_id_in_column = last_id_in_column_from_skipped_file
    last_skipped_file = None

    contents_filename = filenames[0]

    major_regexp, minor_regexp = get_heading_regexps(contents_filename)

    all = []

    parser = Parser()

    parser.report_date = d
    parser.current_column = last_column_number
    parser.current_id_within_column = last_id_in_column

    parser.major_regexp = major_regexp
    parser.minor_regexp = minor_regexp

    # Get the original URLs:

    original_urls = []

    urls_filename = or_prefix + "or" + str(d) + ".urls"
    fp = open(urls_filename)
    for line in fp.readlines():
        line = line.rstrip()
        fields = line.split("\0")
        url = fields[0]
        if len(url) > 0:
            original_urls.append(url)
    fp.close()

    if verbose:
        for o in original_urls:
            print "original url was: "+o

        for f in filenames:
            print "filename was: "+f

    detail_filenames = filenames[1:]

    for i in range(0,len(detail_filenames)):

        detail_filename = detail_filenames[i]
        original_url = original_urls[i+1]

        parser.url = original_url

        if verbose: print "Considering skipping: "+detail_filename

        if re.search('1999-05-12_[12]',detail_filename):
            continue # It's just a table of names...
        elif re.search('1999-06-02_[12]',detail_filename):
            continue # More lists of names...
        elif re.search('1999-09-01_[12]',detail_filename):
            continue # More lists of names...
        elif re.search('2000-07-06_[23]',detail_filename):
            if verbose: print "Matched the annexes"
            continue # Those are two annexes which are contained in main report anyway
        elif re.search('2003-05-15',detail_filename):
            continue # That's 404

        if not options.quiet: print "Parsing: "+detail_filename

        fp = open(detail_filename)
        html = fp.read()
        fp.close()

        # Swap the windows-1252 euro and iso-8859-1 pound signs for the
        # equivalent entities...

        html = re.sub('\x80','&#8364;', html) # windows-1252 euro
        html = re.sub('\xA3','&#163;', html) # iso-8859 pound (currency) sign
        html = re.sub('\xB0','&#176;', html) # iso-8859 degree sign
        html = re.sub('\x97','&#8212;', html) # windows-1252 euro
        html = re.sub('&#151;','&mdash;', html) # windows-1252 mdash

        # Remove all the font tags...
        html = re.sub('(?i)</?font[^>]*>','', html)

        # Remove all the <u> tags...
        html = re.sub('(?i)</?u>','', html)

        # Change non-breaking spaces to normal spaces:
        html = re.sub('(?i)&nbsp;',' ', html)

        # In the earlier format, the <td> with the text sometimes doesn't
        # start with a <p>...
        html = re.sub('(?ims)(<td[^>]*>)\s*(<strong)',r'\1<p>\2',html)

        # The square brackets around things like 'Laughter' and 'Applause'
        # tend to come outside the <i></i>, which is rather inconvenient.
        html = re.sub('(?ims)\[\s*<i>([^<]*)</i>([\s\.]*)\]',r' <i>[\1\2]</i>',html)

        # Similarly, swap <em><p></p></em> for <p><em></em></p>
        html = re.sub('(?ims)<em>\s*<p>([^<]*)</p>\s*</em>',r'<p><em>[\1]</em></i>',html)

        # Similarly, swap <strong><p></p></strong> for <p><strong></strong></p>
        html = re.sub('(?ims)<strong>\s*<p>([^<]*)</p>\s*</strong>',r'<p><strong>[\1]</strong></i>',html)

        # Or just remove any that doesn't catch:
        html = re.sub('(?ims)<em>\s*<p>',r'<p><em>',html)
        html = re.sub('(?ims)</p>\s*</em>',r'</em></p>',html)

        # The <center> tags are often misplaced, which completely
        # breaks the parse tree.
        # FIXME: this might break the detection of headings...
        html = re.sub('(?ims)<center>\s*<p','<p align="center"',html)
        html = re.sub('(?ims)<p>\s*<center','<p align="center"',html)
        html = re.sub('(?ims)</?center>','',html)

        # There are some useless <a name=""> tags...

        html = re.sub('(?ims)<a name="MakeMark[^>]+>[^<]*</a>','',html)

        # And sometimes they forget the "<strong>" before before
        # introducing a speaker:

        html = re.sub('(?ims)<p>([^<]*)</strong>',r'<p><strong>\1</strong>',html)

        log_speaker("------------------------------------",str(d),"")

        # Some of these seem to be windows-1252, some seem to be
        # iso-8859-1.  The decoding you set here doesn't actually seem to
        # solve these problems anyway (FIXME)...

        soup = ScottishParliamentSoup( html, fromEncoding='iso-8859-15' )

        body = soup.find('body')

        if body == None:
            raise Exception, "body was None for: "+str(d)
        
        if verbose:
            print "----- " + detail_filename
            print soup.prettify()
            print "---------------------------------"

        elements_before = len(parser.all_stuff)

        if str(d) == '2004-06-30':
            parser.parse_weird_day( body )
        elif d >= cutoff_date:
            parser.parse_late_format( body )
        else:
            parser.parse_early_format( body )
 
        elements_added = len(parser.all_stuff) - elements_before

        if elements_added < 3 and not re.search('1999-07-02_1',detail_filename):
            raise Exception, "Very suspicious: only "+str(elements_added)+" added by parsing: "+detail_filename

    parser.complete_current()

    last_column_number = parser.current_column
    last_id_in_column = parser.current_id_within_column

    temp_output_filename = xml_output_directory + "tmp.xml"
    o = open(temp_output_filename,"w")

    o.write('''<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE publicwhip [

<!ENTITY pound   "&#163;">
<!ENTITY euro    "&#8364;">

<!ENTITY szlig   "&#223;">
<!ENTITY agrave  "&#224;">
<!ENTITY aacute  "&#225;">
<!ENTITY egrave  "&#232;">
<!ENTITY eacute  "&#233;">
<!ENTITY ecirc   "&#234;">
<!ENTITY iacute  "&#237;">
<!ENTITY ograve  "&#242;">
<!ENTITY oacute  "&#243;">
<!ENTITY uacute  "&#250;">
<!ENTITY Aacute  "&#193;">
<!ENTITY Eacute  "&#201;">
<!ENTITY Iacute  "&#205;">
<!ENTITY Oacute  "&#211;">
<!ENTITY Uacute  "&#218;">
<!ENTITY Uuml    "&#220;">
<!ENTITY auml    "&#228;">
<!ENTITY euml    "&#235;">
<!ENTITY iuml    "&#239;">
<!ENTITY ouml    "&#246;">
<!ENTITY uuml    "&#252;">
<!ENTITY fnof    "&#402;">

<!ENTITY nbsp    "&#160;">
<!ENTITY shy     "&#173;">
<!ENTITY deg     "&#176;">
<!ENTITY middot  "&#183;">
<!ENTITY ordm    "&#186;">
<!ENTITY ndash   "&#8211;">
<!ENTITY mdash   "&#8212;">
<!ENTITY lsquo   "&#8216;">
<!ENTITY rsquo   "&#8217;">
<!ENTITY ldquo   "&#8220;">
<!ENTITY rdquo   "&#8221;">
<!ENTITY hellip  "&#8230;">
<!ENTITY bull    "&#8226;">

<!ENTITY acirc   "&#226;">
<!ENTITY Agrave  "&#192;">
<!ENTITY Aring   "&#197;">
<!ENTITY aring   "&#229;">
<!ENTITY atilde  "&#227;">
<!ENTITY Ccedil  "&#199;">
<!ENTITY ccedil  "&#231;">
<!ENTITY Egrave  "&#200;">
<!ENTITY Icirc   "&#206;">
<!ENTITY icirc   "&#238;">
<!ENTITY Igrave  "&#204;">
<!ENTITY igrave  "&#236;">
<!ENTITY ntilde  "&#241;">
<!ENTITY ocirc   "&#244;">
<!ENTITY aelig   "&#230;">
<!ENTITY oelig   "&#339;">
<!ENTITY otilde  "&#245;">
<!ENTITY Ograve  "&#210;">
<!ENTITY Oslash  "&#216;">
<!ENTITY oslash  "&#248;">
<!ENTITY Scaron  "&#352;">
<!ENTITY scaron  "&#353;">
<!ENTITY sup3    "&#179;">
<!ENTITY ugrave  "&#249;">
<!ENTITY ucirc   "&#251;">
<!ENTITY yacute  "&#253;">
]>

<publicwhip>

''')

    still_in_quote = False

    # When we have placeholders at the end of a speech it's more
    # natural to have them as separate top-level elements:
    expanded_all_stuff = []
    for i in parser.all_stuff:
        if i.__class__ == Speech:
            trailing_placeholders = i.remove_trailing_placeholders()
            expanded_all_stuff.append(i)
            expanded_all_stuff += trailing_placeholders
        else:
            expanded_all_stuff.append(i)

    for i in expanded_all_stuff:
        if i.__class__ == Speech:
            if still_in_quote:
                i.still_in_quote = True
            o.write( "\n" + i.to_xml() + "\n" )
            if i.open_quote:
                still_in_quote = True
            elif i.close_quote or i.opened_and_closed_quote:
                still_in_quote = False
        elif i.__class__ == Heading or i.__class__ == Division:
            still_in_quote = False
            o.write( "\n" + i.to_xml() + "\n" )
        elif i.__class__ == PlaceHolder:
            # FIXME: should this reset still_in_quote to False as well?
            # Test that out...
            o.write( "\n" + i.to_xml() + "\n" )
            
    o.write("\n\n</publicwhip>\n")
    o.close()

    changed_output = True
    if os.path.exists(output_filename):
        result = os.system("diff %s %s > /dev/null" % (temp_output_filename,output_filename))
        if 0 == result:
            changed_output = False

    retcode = call( [ "mv", temp_output_filename, output_filename ] )
    if retcode != 0:
        raise Exception, "Moving "+temp_output_filename+" to "+output_filename+" failed."

    xmlvalidate.parse(output_filename)
    #retcode = call( [ "xmlstarlet", "val", output_filename ] )
    #if retcode != 0:
    #    raise Exception, "Validating "+output_filename+" for well-formedness failed."

    if changed_output:
        fil = open('%schangedates.txt' % xml_output_directory, 'a+')
        fil.write('%d,sp%s.xml\n' % (time.time(), str(d)))
        fil.close()

if verbose:
    report_cases()
