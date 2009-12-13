#!/usr/bin/python2.4

import xml.sax
import re
import os
import glob
import textwrap
import sys

minimum_passages_to_consider = 1

from optparse import OptionParser
parser = OptionParser()
parser.add_option('-v', '--verbose', dest='verbose', action="store_true",
                  default=False, help='noisy output')
parser.add_option('-o', '--output', dest='output_filename',
                  help='output filename for HTML table')
(options, args) = parser.parse_args()

# For mapping IDs to names, etc.
class PeopleParser(xml.sax.handler.ContentHandler):
    def __init__(self):
        self.parser = xml.sax.make_parser()
        self.parser.setContentHandler(self)
    def parse(self,filename):
        self.officeid_to_personid = {}
        self.personid_to_latestname = {}
        self.officeid_to_latestname = {}
        self.parser.parse(filename)
        self.officeid_to_latestname['unknown'] = "[Unknown]"
        self.personid_to_latestname['unknown'] = "[Unknown]"
        self.officeid_to_personid['unknown'] = "unknown"
    def startElement(self,name,attrs):
        if name == 'person':
            self.current_personid = attrs['id']
            self.current_latestname = attrs['latestname']
            self.personid_to_latestname[self.current_personid] = attrs['latestname']
        elif name == 'office':
            self.officeid_to_personid[attrs['id']] = self.current_personid
            self.officeid_to_latestname[attrs['id']] = self.current_latestname
    def endElement(self,name):
        if name == 'person':
            self.current_personid = None

people_parser = PeopleParser()
people_parser.parse("../../members/people.xml")

class TimedSpeechSection:
    def __init__(self):
        self.speakerid = None
        self.text = None
        self.minutes = None
        self.date_string = None
    def count_words(self):
        words = re.split('(?ims)\s+',self.text)
        return len(words)
    def __str__(self):
        result = ""
        result += ""+str(self.minutes)+"min speech from "+self.speakerid
        result += " ("+people_parser.personid_to_latestname[self.speakerid]+") "
        result += "on "+self.date_string+"\n"
        lines = textwrap.wrap(self.text,64)
        for line in lines:
            result += "  "+line+"\n"
        return result

class SpeakingSpeedParser(xml.sax.handler.ContentHandler):
    def __init__(self):
        self.parser = xml.sax.make_parser()
        self.parser.setContentHandler(self)
        self.all_timed_stretches = []
        self.current_date_string = None
    def complete_time_stretch(self,new_minutes_into_day):
        length_of_last_stretch = new_minutes_into_day - self.previous_minutes_into_day
        if options.verbose: print "Got speaker IDs: "+str(self.distinct_speakers_since_last_time)
        if len(self.distinct_speakers_since_last_time) == 1:
            # If there was only a unique speaker in that line:
            tss = TimedSpeechSection()
            tss.date_string = self.current_date_string
            tss.minutes = length_of_last_stretch
            tss.speakerid = self.distinct_speakers_since_last_time.pop()
            tss.text = self.text_since_last_time
            self.all_timed_stretches.append(tss)
        self.previous_minutes_into_day = new_minutes_into_day
        self.text_since_last_time = ""
        self.distinct_speakers_since_last_time = set()
    def startElement(self,name,attrs):
        if name == 'place-holder' and ('time' in attrs):
            m = re.search('^(\d\d):(\d\d)',attrs['time'])
            if not m:
                return
            minutes_into_day = 60 * int(m.group(1),10)
            minutes_into_day += + int(m.group(2),10)
            # If there are zero minutes between this and the last
            # time point, ignore it and wait for the next one:
            if minutes_into_day == self.previous_minutes_into_day:
                return
            if options.verbose: print "Got minutes_into_day: "+str(minutes_into_day)+" from "+attrs['time']
            # ------
            if self.previous_minutes_into_day == 0:
                self.previous_minutes_into_day = minutes_into_day
            else:
                self.complete_time_stretch(minutes_into_day)
        elif name == 'speech' and ('nospeaker' not in attrs):
            # if ('speakerid' in attrs) and re.search('^uk.org',attrs['speakerid']):
            if ('speakerid' in attrs):
                self.current_speakerid = people_parser.officeid_to_personid[attrs['speakerid']]
                if options.verbose: print "Got new speakerid: "+str(self.current_speakerid)
    def endElement(self,name):
        if name == 'speech':
            self.current_speakerid = None
    def characters(self,c):
        if self.current_speakerid:
            self.distinct_speakers_since_last_time.add(self.current_speakerid)
            self.text_since_last_time += c
    def parse(self,filename,date_string):
        self.current_date_string = date_string
        self.previous_minutes_into_day = 0
        self.text_since_last_time = ""
        self.distinct_speakers_since_last_time = set()
        self.current_speakerid = None
        self.parser.parse(filename)

filenames = glob.glob("../../../parldata/scrapedxml/sp/sp????-??-??.xml")
filenames.sort()
ssp = SpeakingSpeedParser()

max_days = -1

days_done = 0
for filename in filenames:
    if options.verbose: print "Parsing day: "+str(filename)
    m = re.search('(\d{4}-\d{2}-\d{2})',filename)
    ssp.parse(filename,m.group(1))
    days_done += 1
    if max_days >= 0 and days_done >= max_days:
        break

class SpeakerTotals:
    def __init__(self):
        self.total_words = 0
        self.total_time_in_minutes = 0
        self.total_passages = 0
    def words_per_minute(self):
        return self.total_words / float(self.total_time_in_minutes)

all_speakers = {}

for tss in ssp.all_timed_stretches:
    # We need to discard some here: anything including 'meeting
    # suspended' or 'meeting adjourned' typically includes the length
    # of the break afterwards.
    if re.search('(?ims)meeting\s+(suspended|adjourned)',tss.text):
        continue
    if re.search('(?ims)be\s+a\s+division',tss.text):
        continue
    if re.search('(?ims)voting\speriod',tss.text):
        continue
    if re.search('(?ims)short\sbreak',tss.text):
        continue
    # The one minute speeches are typically brief introductions by the
    # chair of the meeting, so subject to a lot more error.  Skip
    # those...
    if tss.minutes < 2:
        continue
    all_speakers.setdefault(tss.speakerid,SpeakerTotals())
    all_speakers[tss.speakerid].total_words += tss.count_words()
    all_speakers[tss.speakerid].total_time_in_minutes += tss.minutes
    all_speakers[tss.speakerid].total_passages += 1
    if options.verbose:
        print "====================================================="
        s = unicode(tss)
        print s.encode('UTF-8')

def sort_speakers(speakerid):
    return all_speakers[speakerid].words_per_minute()

speakers_found = all_speakers.keys()
speakers_found.sort(key=sort_speakers,reverse=True)

if options.verbose:
    for s in speakers_found:
        print s+" spoke at "+str(all_speakers[s].words_per_minute())+" words per minute"

people_to_exclude = set()

# Exclude people who presided over proceedings in the parliament:

# Presiding officers:
people_to_exclude.add('uk.org.publicwhip/person/13337') # Lord Steel of Aikwood
people_to_exclude.add('uk.org.publicwhip/person/14084') # George Reid
people_to_exclude.add('uk.org.publicwhip/person/13985') # Alex Fergusson

# Deputy presiding officers:
people_to_exclude.add('uk.org.publicwhip/person/13984') # Patricia Ferguson
people_to_exclude.add('uk.org.publicwhip/person/14110') # Murray Tosh
people_to_exclude.add('uk.org.publicwhip/person/13997') # Trish Godman
people_to_exclude.add('uk.org.publicwhip/person/10441') # Alasdair Morgan

# Also, Dr Winnie Ewing
people_to_exclude.add('uk.org.publicwhip/person/13981') # Winnie Ewing

if options.output_filename:
    fp = open(options.output_filename,"w")
    fp.write("<table>\n")
    fp.write("<tr>")
    fp.write("<th>Rank</th>")
    fp.write("<th>MSP</th>")
    fp.write("<th>Words Per Minute</th>")
    fp.write("<th>Total Words</th>")
    fp.write("<th>Total Time (Minutes)</th>")
    fp.write("<th>Measured Passages</th>")
    fp.write("</tr>\n")
    i = 1
    for s in speakers_found:
        totals = all_speakers[s]
        if totals.total_passages < minimum_passages_to_consider:
            continue
        if s in people_to_exclude:
            continue
        fp.write("<tr>")
        fp.write("<td>%d</td>"%(i,))
        url = re.sub('(?ims)^.*/(\d+)$',r'http://www.theyworkforyou.com/msp/?pid=\1',s)
        name = people_parser.personid_to_latestname[s]
        fp.write("<td><a href=\"%s\">%s</a></td>"%(url,name))
        fp.write("<td>%.1f</td>"%(totals.words_per_minute(),))
        fp.write("<td>%d</td>"%(totals.total_words))
        fp.write("<td>%d</td>"%(totals.total_time_in_minutes))
        fp.write("<td>%d</td>"%(totals.total_passages))
        fp.write("</tr>\n")
        i += 1
    fp.write("</table>")
    fp.close()
