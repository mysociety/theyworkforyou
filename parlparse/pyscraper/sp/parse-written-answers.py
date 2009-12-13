#!/usr/bin/python2.4

import sys
import os
import random
import datetime
import time
import traceback
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

from common import month_name_to_int
from common import non_tag_data_in
from common import tidy_string

from time import strptime

import re
import glob

parser = OptionParser()
parser.add_option('-q', "--quiet", dest="quiet", action="store_true",
                  help="don't print status messages")
(options, args) = parser.parse_args()
verbose = False # XXX This is /very/ verbose, add command line option for it

wa_prefix = "../../../parldata/cmpages/sp/written-answers/"
xml_output_directory = "../../../parldata/scrapedxml/sp-written/"

# ------------------------------------------------------------------------
# Keep a dictionary on disk that maps files to dates:

file_to_date = None
date_to_file = { }

file_to_date_mapping = wa_prefix + "file-to-date-mapping"

fp = open(file_to_date_mapping)
file_to_date_expression = fp.read()
fp.close()

file_to_date = eval(file_to_date_expression)
for k in file_to_date.keys():
    date_to_file[file_to_date[k]] = k

def add_file_to_date_mapping(filename,date_string):
    file_to_date[filename] = date_string
    date_to_file[date_string] = filename
    fp = open(file_to_date_mapping,"w")
    fp.write( "{\n" )
    first = True
    for k in file_to_date.keys():
        if first:
            first = False
        else:
            fp.write(",\n")
        fp.write('  "%s": "%s"' % ( k, file_to_date[k] ) )
    fp.write( "\n}\n" )
    fp.close()

# ------------------------------------------------------------------------

filenames = glob.glob( wa_prefix + "day-*.htm*" )

def paragraphs_in_tag(t):
    paragraphs = t.findAll('p',recursive=False)
    return len(paragraphs)

class QuestionOrReply:
    def __init__(self,sp_id,sp_name,parser):
        self.speaker_name = None
        self.speaker_id = None
        self.paragraphs = []
        self.sp_id = sp_id
        self.sp_name = sp_name
        self.is_question = None
        self.parser = parser
        self.holding_answer_was_issued = None
    def add_paragraph(self,paragraph):
        self.paragraphs.append(paragraph)
    def add_to_paragraph(self,text):
        if self.paragraphs:
            last = self.paragraphs.pop()
            self.paragraphs.append(last+" "+text)
        else:
            self.paragraphs = [ text ]
    def set_type( is_question ):
        self.is_question = is_question
    def to_xml(self,twfy_id):
        if self.is_question:
            element_name = 'ques'
        else:
            element_name = 'reply'
        original_url = parser.original_url
        if self.sp_name:
            original_url += "#"+self.sp_name
        speaker_info = None
        question_id_info = ''
        if self.sp_id:
            question_id_info = ' spid="%s"' % ( self.sp_id )
        if self.speaker_name:
            speaker_info = 'speakerid="%s" speakername="%s"' % ( self.speaker_id, self.speaker_name )
        else:
            speaker_info = 'nospeaker="True"'
        holding_info = ''
        if self.holding_answer_was_issued:
            holding_info = ' holdingdate="%s"' % str(self.holding_answer_was_issued)
        
        result = '<%s id="%s" %s%s url="%s"%s>\n'  % ( element_name, twfy_id, speaker_info, question_id_info, original_url, holding_info )

        for p in self.paragraphs:

            result += "\n  <p>%s</p>\n" % ( p )

        result += '\n</%s>\n' % ( element_name )
        return result

class Heading:
    def __init__(self,heading_text,sp_name,parser):
        self.heading_text = heading_text
        self.major = False
        self.sp_name = sp_name
        self.parser = parser
    def to_xml(self,twfy_id):
        if self.major:
            heading_type = "major"
        else:
            heading_type = "minor"
        original_url = parser.original_url
        if self.sp_name:
            original_url += "#"+self.sp_name
        return '<%s-heading id="%s" nospeaker="True" url="%s">%s</%s-heading>' % ( heading_type, twfy_id, original_url, self.heading_text, heading_type )

class Parser:
    
    def __init__(self):
        self.all_stuff = []
        self.paragraphs = []
        self.sp_id = None
        self.sp_heading = None
        self.sp_name = None
        self.current_question_or_reply = None
        self.soup = None
        # Ugh, this should be dealt with properly by extending the
        # lawofficers information in resolvmembernames.py instead, but
        # this is the quick and inflexible way of dealing with this.
        self.odd_unknowns = { "Lord Hardie" : True,
                              "Colin Boyd" : True,
                              "Mr Colin Boyd" : True,
                              "Neil Davidson QC" : True,
                              "Colin Boyd QC" : True,
                              "Right Hon Elish Angiolini QC" : True,
                              "Rt hon Elish Angiolini QC" : True,
                              "Elish Angiolini QC" : True,
                              "Eilish Angiolini QC": True,
                              "Mrs Elish Angiolini" : True,
                              "Mrs Elish Angiolini QC" : True,
                              "Frank Mulholland QC": True,
                              "Colin Boyd, QC" : True,
                              "John Beckett QC" : True,
                              "Neil Davidson" : True,
                              "Elish Angiolini" : True,
                              "Elish Angiolini, QC" : True }

    def complete_question(self):
        self.complete(True)

    def complete_answer(self):
        self.complete(False)

    def complete(self,is_question):
        if self.current_question_or_reply:
            if is_question:
                if verbose: print "Completing QUESTION..."
            else:
                if verbose: print "Completing ANSWER..."
            if not self.current_question_or_reply.speaker_name:
                if verbose: print "No speaker name, element would be: "+self.current_question_or_reply.to_xml("ignore")
                # raise Exception, "No speaker name when completing element."
            self.current_question_or_reply.is_question = is_question
            self.all_stuff.append(self.current_question_or_reply)
            self.current_question_or_reply = None

    def add_heading(self,s):
        if verbose: print "=== HEADING: "+s
        if self.all_stuff:
            previous = self.all_stuff[len(self.all_stuff)-1]
            if previous.__class__ == Heading:
                previous.major = True
        self.all_stuff.append( Heading( s, self.sp_name, self ))

    def something_centered(self,t):
        using_align = t.has_key('align') and t['align'].lower() == 'center'
        using_style = t.has_key('style') and re.search('(?i)align.*center',t['style'])
        short_enough = len(str(t)) < 256
        return (using_align or using_style) and short_enough

    def ensure_current_question_or_answer(self):
        if not self.current_question_or_reply:
            self.current_question_or_reply = QuestionOrReply( self.sp_id, self.sp_name, self )

    def add_large_heading(self,s):
        if verbose: print "=== LARGEHEADING: "+s
        to_add = Heading(s,self.sp_name,self)
        to_add.major = True
        self.all_stuff.append(to_add)

    def add_paragraph(self,s):
        if verbose: print "=== PARAGRAPH: "+s
        self.ensure_current_question_or_answer()
        self.current_question_or_reply.add_paragraph(s)

    def add_to_paragraph(self,s):
        self.ensure_current_question_or_answer()
        self.current_question_or_reply.add_to_paragraph(s)
            
    def set_speaker(self,speaker_name,speaker_id):
        if verbose: print "=== SPEAKER: "+speaker_name
        self.ensure_current_question_or_answer()
        self.current_question_or_reply.speaker_name = speaker_name
        self.current_question_or_reply.speaker_id = speaker_id

    def set_id(self,s):
        if verbose: print "=== ID: "+s
        self.current_question_or_reply.sp_id = s

    def set_date_holding_answer_was_issued(self,date):
        self.current_question_or_reply.holding_answer_was_issued = date

    def c1_heading(self,tag):
        right_class = tag.has_key('class') and tag['class'] == 'c1'
        contains_strong = tag.find('strong')
        return right_class and contains_strong

    def find_id_and_possible_holding_date(self,tag_or_string):
        if tag_or_string.__class__ == Tag:
            if verbose: print "Parsing tag: "+str(tag_or_string)
            s = tidy_string(non_tag_data_in(tag_or_string))
        else:
            if verbose: print "Parsing string: "+tag_or_string
            s = tag_or_string
        rest = self.find_holding_answer_issued(s)
        if rest:
            s = rest            
        if len(s) > 0:
            m = re.search('\((S[0-9][A-Z0-9]+\-) ?([0-9]+)\)',s)
            if m:
                sp_id = m.group(1) + m.group(2)
                self.set_id(sp_id)
                return True
        return False

    def find_holding_answer_issued(self,s):
        holding_match = re.match('(?ims)^(.*)Holding answer issued: (\d+) (\w+) (\d+)(.*)$',s)
        if holding_match:
            holding_answer_issued = datetime.date(int(holding_match.group(4),10),month_name_to_int(holding_match.group(3)),int(holding_match.group(2),10))
            self.set_date_holding_answer_was_issued(holding_answer_issued)
            return holding_match.group(1) + holding_match.group(5)

    def get_id(self,index,type):
        return "uk.org.publicwhip/spwa/"+str(self.date)+".%s.%s" % ( index, type )

    def valid_speaker(self,speaker_name):
        tidied_speaker = speaker_name
        if not tidied_speaker:
            return None
        if self.odd_unknowns.has_key(speaker_name):
            return "unknown"
        tidied_speaker = re.sub("((on behalf of the )?Scottish Parliamentary Corporate Body)",'',tidied_speaker)
        ids = memberList.match_whole_speaker(tidied_speaker,str(parser.date))
        if ids == None:
            return "unknown"
        if not ids: # i.e. it's the empty list...
            if verbose: print "No match for speaker: "+tidied_speaker+" on date "+str(parser.date)
            return None
        elif len(ids) > 1:
            if verbose: print "Too many matches found for speaker: "+tidied_speaker+" on date "+str(parser.date)
            return None
        else:
            return ids[0]
        
    def add_paragraph_removing_enclosure(self,t):
        paragraph = str(t)
        paragraph = re.sub('(?ims)^\s*<p[^>]*>\s*(.*)</p[^>]*>\s*$',r'\1',paragraph)
        paragraph = tidy_string(paragraph)
        self.add_paragraph(paragraph)

    def make_soup(self,filename):

        fp = open(filename)
        html = fp.read()
        fp.close()

        html = re.sub('&nbsp;', ' ', html)
        html = re.sub('&#9;', ' ', html)
        html = re.sub('&#160;', ' ', html)

        # Annoyingly, in the phrase "Sportscotland", the Sport is bold and
        # sometimes runs on from speaker names:

        html = re.sub('([Ss])port</strong>([Ss])',r'</strong>\1port\2',html)

        html = re.sub('(?ims)<a name="se_Minister">\s*</a>','',html)

        html = re.sub('&rsquo;Donnell',"'Donnell",html)
    
        # Swap the windows-1252 euro and iso-8859-1 pound signs for the
        # equivalent entities...

        html = re.sub('\x80','&#8364;', html) # windows-1252 euro
        html = re.sub('\x92','&#8217;', html) # right single quotation mark (in windows-1252)
        html = re.sub('\x96','&#8211;', html) # en dash in windows-1252
        html = re.sub('\x97','&#8212;', html) # windows-1252 euro
        html = re.sub('&#151;','&mdash;', html) # windows-1252 mdash
        html = re.sub('\xA0',' ', html) # iso-8859 pound (currency) sign
        html = re.sub('\xA3','&#163;', html) # iso-8859 pound (currency) sign
        html = re.sub('\xA8','&#168;', html) # diaresis
        html = re.sub('\xAD','&#173;', html) # soft hyphen windows-1252
        html = re.sub('\xB0','&#176;', html) # iso-8859 degree sign
        html = re.sub('\xB2','&#178;', html) # superscript 2 in windows-1252
        html = re.sub('\xB3','&#179;', html) # superscript 3 in windows-1252
        html = re.sub('\xB4','&#180;', html) # acute accent in windows-1252
        html = re.sub('\xB9','&#185;', html) # superscript 1 in windows-1252
        html = re.sub('\xBA','&#186;', html) # masculine ordinal indicator
        html = re.sub('\xBD','&#189;', html) # vulgar fraction 1/2
        html = re.sub('\xBE','&#190;', html) # vulgar fraction 3/4
        html = re.sub('\xE8','&#232;', html) # e grave
    
        # Remove all the font tags...
        html = re.sub('(?i)</?font[^>]*>','', html)

        # Some of these seem to be windows-1252, some seem to be
        # iso-8859-1.  The decoding you set here doesn't actually seem to
        # solve these problems anyway (FIXME)...

        self.soup = BeautifulSoup( html, fromEncoding='windows-1252' )

        # Test trying to find the body tag; if we can't, then the
        # parsing failed horribly:
        if not self.soup.find('body'):
            raise Exception, "Couldn't find body in souped "+filename

    def parse(self,filename):

        m = re.match('(?ims)^.*(day-(wa-\d\d)_([a-z0-9]+\.htm))',filename)
        if not m:
            raise Exception, "Couldn't parse filename: "+filename
        self.original_url = "http://www.scottish.parliament.uk/business/pqa/%s/%s" % (m.group(2),m.group(3))

        filename_leaf = m.group(1)

        # We need to know what date this is, so deal with that first
        # of all in a brutish fashion, but cache the results:

        self.date = None

        if file_to_date.has_key(filename_leaf):
            if verbose: print "Found file to date mapping in cache."
            self.date = datetime.date(*strptime(file_to_date[filename_leaf],"%Y-%m-%d")[0:3])
        else:
            self.make_soup(filename)            
            page_as_text = tidy_string(non_tag_data_in(self.soup.find('body')))
            m = re.search('(?ims) (Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday) (\d+)\w* (\w+) (\d+)?',page_as_text)
            if m:
                day_of_week = m.group(1)
                day = m.group(2)
                month = month_name_to_int(m.group(3))
                year = m.group(4)
                # Sometimes the date string doesn't have the year:
                if not year:
                    m = re.search('day-wa-(\d\d)',filename)
                    if m.group(1) == '99':
                        year = '1999'
                    else:
                        year = '20' + m.group(1)
                self.date = datetime.date( int(year,10), month, int(day,10) )
                if not options.quiet: "Adding file to date mapping to cache."
                add_file_to_date_mapping(filename_leaf,str(self.date))
            else:
                raise Exception, "No date found in file: "+filename

        temp_output_filename = xml_output_directory + "tmp.xml"
        output_filename = xml_output_directory + "spwa" + str(self.date) + ".xml"

        if os.path.exists(output_filename):
            #error = "The output file "+output_filename+" already exists - skipping "+re.sub('^.*/','',filename)
            # raise Exception, error
            #if not options.quiet: print error
            return

        if not options.quiet: print "Parsing %s" % filename

        self.make_soup(filename)

        self.ofp = open(temp_output_filename,"w")

        self.ofp.write('''<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE publicwhip [

<!ENTITY pound   "&#163;">
<!ENTITY euro    "&#8364;">

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
<!ENTITY aelig   "&#230;">
<!ENTITY dagger  "&#8224;">
<!ENTITY reg     "&#174;">
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
<!ENTITY oelig   "&#339;">
<!ENTITY Ograve  "&#210;">
<!ENTITY Oslash  "&#216;">
<!ENTITY oslash  "&#248;">
<!ENTITY Scaron  "&#352;">
<!ENTITY scaron  "&#353;">
<!ENTITY sup1    "&#185;">
<!ENTITY sup2    "&#178;">
<!ENTITY sup3    "&#179;">
<!ENTITY ugrave  "&#249;">
<!ENTITY ucirc   "&#251;">
<!ENTITY Ugrave  "&#217;">
<!ENTITY yacute  "&#253;">
<!ENTITY frac12  "&#189;">
<!ENTITY micro   "&#181;">
<!ENTITY sbquo   "&#8218;">
<!ENTITY trade   "&#8482;">
<!ENTITY Dagger  "&#8225;">
<!ENTITY radic   "&#8730;">
]>

<publicwhip>

''')

        self.ofp.write("<source url=\"%s\"/>" % self.original_url )
        
        tag_with_most_paragraphs = None
        most_paragraphs_so_far = -1
        
        for t in self.soup.findAll(True):
            ps = paragraphs_in_tag(t)
            if ps > most_paragraphs_so_far:
                tag_with_most_paragraphs = t
                most_paragraphs_so_far = ps
        
        if verbose: print "Using element name: "+tag_with_most_paragraphs.name+" with "+str(most_paragraphs_so_far)+" paragraphs from "+filename
        
        if verbose: print tag_with_most_paragraphs.prettify()
        
        # When we're parsing we might have multiple questions in a
        # row.  We say that something's a question rather than an
        # answer if (a) it's followed by an ID or (b) it begins with
        # "To ask", otherwise it's an answer.  If we hit a new
        # heading, that suggests that the previous thing was an answer
        # as well.
        
        # The business of "Holding answers" is a bit confusing.  At
        # the bottom of each page there may be a list of question IDs
        # which were given holding answers, but the text of the
        # question is not in the page - you only find it when the
        # question is eventually answered.
        
        for t in tag_with_most_paragraphs:
            if t.__class__ == NavigableString:
                s = str(t)
                s = re.sub('(?ims)\s+',' ',s)
                if re.match('(?ims)^\s*$',s):
                    continue
                else:
                    self.add_to_paragraph(tidy_string(str(t)))
                if verbose: print "string: "+str(s)            
            elif t.__class__ == Tag:
                # Look for any <a name=""> tags in here:
                a = t.find( lambda p: p.name == 'a' and p.has_key('name') )
                if a:
                    self.sp_name = a['name']
                if t.has_key('align') and t['align'].lower() == 'right':
                    # Right aligned tags just have the question ID.
                    if self.find_id_and_possible_holding_date(t):
                        self.complete_question()
                    else:
                        if verbose: print "Couldn't parse top-level right aligned tag: "+str(t)
                elif t.has_key('class') and t['class'] == 'largeHeading':
                    self.add_large_heading(tidy_string(non_tag_data_in(t)))
                elif self.something_centered(t) or self.c1_heading(t):
                    # Centred tags are headings for questions...
                    s = tidy_string(non_tag_data_in(t))
                    if len(s) > 0:
                        self.complete_answer()
                        if verbose: print "center: "+s
                        self.add_heading(s)
                elif t.name == 'table':
                    # This is probably a table that's inserted just to
                    # right align the question ID.  The left cell may
                    # contain something to indicate that it's a
                    # holding answer.
                    if self.find_id_and_possible_holding_date(t):
                        # Then also look for the "Holding answer
                        # issued" details...
                        s = non_tag_data_in(t)
                        self.find_holding_answer_issued(s)
                        self.complete_question()
                    else:
                        # Then maybe it's a table as part of the
                        # answer, so add it as a paragraph.
                        self.add_paragraph(str(t))
                elif t.name == 'p':
                    if re.search("(The following questions were given holding answers|Questions given holding answers)",tidy_string(non_tag_data_in(t))):
                        if verbose: print "Found the trailing holding question list!"
                        # This indicates the end of the day's report
                        # for us (just ignore the following list of
                        # answers - it's not very interesting until we
                        # parse some later day and we can tell what
                        # the question was...)                        
                        break
                    if verbose: print "Didn't find the trailing holding question list in: "+non_tag_data_in(t)
                    non_empty_contents = filter( lambda x: x.__class__ != NavigableString or not re.match('^\s*$',x), t.contents )
                    if len(non_empty_contents) == 0:
                        continue
                    initial_strong_text = ''
                    while len(non_empty_contents) > 0 and non_empty_contents[0].__class__ == Tag and (non_empty_contents[0].name == 'strong' or non_empty_contents[0].name == 'b'):
                        initial_strong_text += " " + non_tag_data_in(non_empty_contents[0])
                        non_empty_contents = non_empty_contents[1:]
                    if len(initial_strong_text) > 0:
                        speaker_name = tidy_string(initial_strong_text)
                        # In some files this will be the ID (possibly
                        # plus holding indication), not right aligned
                        # as usual :(
                        if self.find_id_and_possible_holding_date(speaker_name):
                            self.complete_question()
                        else:
                            speaker_name = re.sub('(?ims)\s*:\s*$','',speaker_name)
                            speaker_id = self.valid_speaker(speaker_name)
                            if speaker_name and speaker_id:
                                self.complete_answer()
                                self.set_speaker(speaker_name,speaker_id)
                                for e in non_empty_contents:
                                    s = tidy_string(str(e))
                                    self.add_to_paragraph(s)
                            else:
                                self.add_paragraph_removing_enclosure(t)
                    else:
                        self.add_paragraph_removing_enclosure(t)
                elif t.name == 'div' or t.name == 'blockquote' or t.name == 'ol' or t.name == 'ul' or t.name == 'center':
                    # Just add them in a paragraph anyway, even though
                    # that wouldn't be valid HTML 4 strict in the case
                    # of the last three (IIRC)
                    self.add_paragraph(str(t))
                else:
                    # Well, if it's empty of text we don't care...
                    s = non_tag_data_in(t)
                    if not re.match('(?ims)^\s*$',s):
                        raise Exception, "Unknown tag found of name '"+t.name+"' with text: "+t.prettify()
        self.complete_answer()

        # Now output all the XML, working out IDs for each element.
        # IDs are of the form:
        # 
        #   uk.org.publicwhip/spwa/YYYY-MM-DD.X.T
        # 
        #     .... where:
        #            - YYYY-MM-DD is an ISO 8601 date
        # 
        #            - X is a integer starting at 0 on each day, which
        #              should be incremented for each new heading and
        #              be the same for a group of questions and their
        #              answer.
        #
        #            - T is "mh" or "h" for major and minor headings,
        #             "q0", "q1", "q2", etc. for each group of
        #             questions and "r0", "r1", etc. for the answers

        x = -1
        last_heading = None
        current_sp_id = None

        index = 0

        for i in range(0,len(self.all_stuff)):

            if i > 0:
                previous = self.all_stuff[i-1]
            else:
                previous = None

            if i < (len(self.all_stuff) - 1):
                next = self.all_stuff[i+1]
            else:
                next = None
                
            a = self.all_stuff[i]

            self.ofp.write('\n\n')

            if a.__class__ == Heading:
                last_was_answer = True
                if a.major:
                    subtype = "mh"
                else:
                    subtype = "h"
                if next and next.__class__ == QuestionOrReply and next.sp_id:
                    # Then use the question's sp_id:
                    self.ofp.write(a.to_xml(self.get_id(next.sp_id,subtype)))
                else:
                    x += 1
                    self.ofp.write(a.to_xml(self.get_id(str(x),subtype)))
                last_heading = a
            elif a.__class__ == QuestionOrReply:
                # Occasionally we think questions are actually
                # answers, so check the beginning of the first
                # paragraph:
                if not a.is_question and len(a.paragraphs) > 0 and re.search('^(?ims)\s*To\s+ask',a.paragraphs[0]):
                    a.is_question = True
                # If we're suddenly in an answer, reset index.
                if (not a.is_question) and previous and not (previous.__class__ == QuestionOrReply and not previous.is_question):
                    index = 0
                # If we're suddenly in a question, reset index and increment x unless the previous is a heading
                elif a.is_question:
                    if previous:
                        if previous.__class__ == QuestionOrReply:
                            if previous.is_question:
                                # If the one before is a question, that's fine.
                                current_sp_id = a.sp_id
                            else:
                                current_sp_id = a.sp_id
                                # If the previous one was an answer
                                # then we need to replay the last
                                # heading:
                                if not last_heading:
                                    raise Exception, "Somehow there's been no heading so far."
                                last_heading.sp_name = a.sp_name
                                if current_sp_id:
                                    self.ofp.write(last_heading.to_xml(self.get_id(current_sp_id,"h")))
                                else:
                                    x += 1
                                    self.ofp.write(last_heading.to_xml(self.get_id(str(x),"h")))
                                self.ofp.write("\n\n")
                                index = 0
                        else:
                            # i.e. this is the normal case, a question after a heading:
                            current_sp_id = a.sp_id
                            index = 0
                    else:
                        raise Exception, "Nothing before the first question (no heading)"
                if a.is_question:
                    subtype = "q" + str(index)
                else:
                    subtype = "r" + str(index)
                if current_sp_id:
                    self.ofp.write(a.to_xml(self.get_id(current_sp_id,subtype)))
                else:
                    self.ofp.write(a.to_xml(self.get_id(str(x),subtype)))
                index += 1

        self.ofp.write("</publicwhip>")
        self.ofp.close()

        retcode = call( [ "mv", temp_output_filename, output_filename ] )
        if retcode != 0:
            raise Exception, "Moving "+temp_output_filename+" to "+output_filename+" failed."

        xmlvalidate.parse(output_filename)
        #retcode = call( [ "xmlstarlet", "val", output_filename ] )
        #if retcode != 0:
        #    raise Exception, "Validating "+output_filename+" for well-formedness failed."

        fil = open('%schangedates.txt' % xml_output_directory, 'a+')
        fil.write('%d,spwa%s.xml\n' % (time.time(), self.date))
        fil.close()

files_and_exceptions = []

for filename in filenames:

    # This one's missing...
    if re.search('day-wa-04_wa0713\.htm',filename):
        continue

    # These are duplicates, only found via a contents page still
    # linked to via a "Back to contents" link.
    if re.search('day-wa-01_war0816\.htm',filename):
        continue
    if re.search('day-wa-03_war1110\.htm',filename):
        continue
    if re.search('day-wa-03_war0922\.htm',filename):
        continue    
    if re.search('day-wa-03_war0805\.htm',filename):
        continue

    # This is a misnamed file, a duplicate of wa-02_wa1004.htm
    if re.search('day-wa-02_wa0104\.htm',filename):
        continue

    parser = Parser()

    parser.parse(filename)

    # try:
    #     parser.parse(filename)
    # except Exception, e:
    #     if verbose: print "An unhandled exception occured in parsing: "+filename
    #     if verbose: print "The exception was: "+str(e)
    #     traceback.print_exc(file=sys.stdout)
    #     files_and_exceptions.append( (filename,e) )

# if verbose: print "All exceptions:"
# for e in files_and_exceptions:
#     if verbose: print "%s: %s" % e
