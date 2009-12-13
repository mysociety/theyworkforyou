#!/usr/bin/python2.4

import sys
import os
import random
import datetime
import time
import urllib
import glob
import re
import codecs
import xml.sax

from optparse import OptionParser

sys.path.append('../')
from BeautifulSoup import BeautifulSoup
from BeautifulSoup import NavigableString
from BeautifulSoup import Tag
from BeautifulSoup import Comment

from common import month_name_to_int
from common import non_tag_data_in
from common import tidy_string
from common import fix_spid
from common import decode_htmlentities
from common import compare_spids

parser = OptionParser()

parser.add_option('-f', '--force', dest='force', action="store_true",
                  help='force reparse of everything')
parser.add_option('-v', "--verbose", dest="verbose", action="store_true",
                  default=False, help="output verbose progress information")
(options, args) = parser.parse_args()

input_directory = "../../../parldata/cmpages/sp/bulletins/"
output_directory = "../../../parldata/scrapedxml/sp-motions/"
last_date_parsed_filename = output_directory + "last-date-parsed"

filenames = glob.glob( input_directory + "day-bb-*.htm" )
filenames.sort()

spid_motion_re = '(S[0-9]M-[0-9\.]+)'
spid_motion_at_start_re = '^\s*[\*\#]?\s*'+spid_motion_re

class Motion:

    def __init__(self,spid,date,filename,text):
        self.spid = spid
        self.date = date
        m = re.search('(day-(.*)_(.*))',filename)
        if not m:
            raise Exception, "Couldn't parse the filename: "+filename
        self.filename = m.group(1)
        self.text = text

    def get_escaped_text(self):
        result = re.sub('&','&amp;',self.text)
        result = re.sub('<','&lt;',result)
        result = re.sub('>','&gt;',result)
        return result

    def get_original_url(self):
        m = re.search('day-(.*)_(.*)',self.filename)
        if not m:
            raise Exception, "Couldn't parse the filename: "+self.filename
        return "http://www.scottish.parliament.uk/business/businessBulletin/%s/%s" % (m.group(1),m.group(2))

    def to_xml_element(self):
        result = "<spmotion spid=\"%s\" date=\"%s\" filename=\"%s\" url=\"%s\">\n" % (
            self.spid,
            self.date,
            self.filename,
            self.get_original_url() )
        result += self.get_escaped_text()
        result += "\n</spmotion>\n"
        return result

    def remove_motions_with_same_source(self,other_motions):
        new_list = []
        for o in other_motions:
            if (o.spid == self.spid) and (o.date == self.date) and (o.filename == self.filename):
                pass
            else:
                new_list.append(o)
        return new_list

class MotionsFileParser(xml.sax.handler.ContentHandler):
    def __init__(self):
        self.parser = xml.sax.make_parser()
        self.parser.setContentHandler(self)
    def startElement(self,name,attr):
        if name == "spmotion":
            self.spid = attr["spid"]
            self.filename = attr["filename"]
            self.date = datetime.date(*time.strptime(attr["date"],"%Y-%m-%d")[:3])
            self.in_motion = True
            self.text_so_far = ""
    def endElement(self,name):
        if name == "spmotion":
            m = Motion(self.spid,self.date,self.filename,self.text_so_far)
            self.motion_list.append(m)
            self.in_motion = False
    def characters(self,c):
        if self.in_motion:
            if not re.search('(?ims)^\s*$',c):
                self.text_so_far += unicode(c)
    def motion_list_from_filename(self,filename):
        self.motion_list = []
        self.in_motion = False
        self.parser.parse(filename)
        return self.motion_list

motions_file_parser = MotionsFileParser()

def write_motions_list(motions,output_filename):
    fp = codecs.open(output_filename,"w","UTF-8")
    fp.write( '<?xml version="1.0" encoding="utf-8"?>\n' )
    fp.write( '<publicwhip>\n\n' )
    for m in motions:
        fp.write(m.to_xml_element())
        fp.write('\n')
    fp.write( '</publicwhip>\n' )

all_new_motions = {}

old_last_date_parsed = None
if os.path.exists(last_date_parsed_filename):
    fp = open(last_date_parsed_filename,'r')
    contents = fp.read().strip()
    fp.close()
    old_last_date_parsed = datetime.date(*time.strptime(contents,"%Y-%m-%d")[:3])

if options.verbose: print "old_last_date_parsed: " + str(old_last_date_parsed)

last_date_parsed = None

done = 0
max_to_do = -1

for filename in filenames:
    if max_to_do > 0 and done > max_to_do:
        break
    m = re.search("day-bb-([0-9][0-9])_([aAbB][bB]-(\d\d)-(\d\d)-?(\w*)\.htm)",filename)
    if not m:
        raise Exception, "Didn't match " + str(filename)
    y2 = int(m.group(1),10)
    year = None
    if y2 < 99:
        year = 2000 + y2
    else:
        year = 1900 + y2
    month = int(m.group(3),10)
    day = int(m.group(4),10)
    section = m.group(5)
    if section != "f":
        continue
    date = None
    # This file is misnamed:
    if year == 1999 and month == 2 and day == 29:
        date = datetime.date(2000,2,29)
    # Some files have the month and date
    # exchanged:
    elif month > 12:
        date = datetime.date(year,day,month)
    else:
        date = datetime.date(year,month,day)

    if options.verbose: print "Considering file %s (date probably %s)" % (filename,str(date))

    if (not last_date_parsed) or (date > last_date_parsed):
        last_date_parsed = date

    if (not options.force) and old_last_date_parsed and (date <= old_last_date_parsed):
        if options.verbose: print " -- skipping"
        continue
    if options.verbose: print " -- parsing"

    fp = open(filename,'r')
    html = fp.read()
    fp.close()
    # FrontPage seems to like generating incorrect entities, unless
    # I misunderstand:
    #   em- and en- dash
    html = re.sub('&#151;','&#8212;',html)
    html = re.sub('&#150;','&#8211;',html)
    #   left and right single quotes
    html = re.sub('&#146;','&#8217;',html)
    html = re.sub('&#145;','&#8216;',html)
    #   pound sterling signs
    html = re.sub('\xA3','&#163;', html)
    spids = re.findall(spid_motion_re,html)
    spids_found_here = {}
    soup = BeautifulSoup(html)
    body = soup.find('body')
    if not body:
        raise Exception, "Couldn't find the body in " + filename
    for s in body.findAll('strong'):
        s_as_text = non_tag_data_in(s)
        m = re.search(spid_motion_at_start_re,s_as_text)
        if m:
            spids_found_here[m.group(1)] = s.parent
    for p in body.findAll('p'):
        s_as_text = non_tag_data_in(p)
        m = re.search(spid_motion_at_start_re,s_as_text)
        if m:
            spids_found_here[m.group(1)] = p
    for spid, element in spids_found_here.iteritems():
        if options.verbose: print "    " + spid
        motion_elements = list()
        current_element = element
        while True:
            motion_elements.append(current_element)
            current_element = current_element.nextSibling
            if not current_element:
                break
            element_as_text = non_tag_data_in(current_element)
            if re.search(spid_motion_at_start_re,element_as_text):
                break
        as_plain_text = ""
        for e in motion_elements:
            as_plain_text += non_tag_data_in(e)
        as_plain_text = re.sub('(?ims)\s+',' ',as_plain_text)
        as_plain_text = decode_htmlentities(as_plain_text)
        motion = Motion(spid,date,filename,as_plain_text)
        all_new_motions.setdefault(spid,[])
        all_new_motions[spid].append(motion)
    done += 1

ks = all_new_motions.keys()
ks.sort(compare_spids)

for k in ks:
    new_motions = all_new_motions[k]
    output_filename = output_directory + k + ".xml"
    # Read in the motions that are already stored there:
    existing_motions = []
    if os.path.exists(output_filename):
        existing_motions = motions_file_parser.motion_list_from_filename(output_filename)
    # We're going to replace any with the same source, so remove each
    # of those:
    for m in new_motions:
        existing_motions = m.remove_motions_with_same_source(existing_motions)
    # Add the new versions:
    existing_motions.extend(new_motions)
    existing_motions.sort( lambda a,b: cmp(a.date,b.date) )
    write_motions_list(existing_motions,output_filename)

# Write out last_date_parsed:
fp = open(last_date_parsed_filename,'w')
fp.write(str(last_date_parsed))
fp.close()
