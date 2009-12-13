#!/usr/bin/python2.4

import urllib2
import csv
import xml.sax

uri = "http://spreadsheets.google.com/tq?tqx=out:csv&key=0AjWA_TWMI4t_dFI5MWRWZkRWbFJ6MVhHQzVmVndrZnc&hl=en_GB"

f = urllib2.urlopen(uri)
csv_data = f.read()
lines = csv_data.split("\n")
rows = csv.reader(lines.__iter__(), delimiter=',', quotechar='"')

class PeopleParser(xml.sax.handler.ContentHandler):
    def __init__(self):
        self.parser = xml.sax.make_parser()
        self.parser.setContentHandler(self)
    def parse(self,filename):
        self.office_id_to_person_id = {}
        self.parser.parse(filename)
    def startElement(self,name,attrs):
        if name == 'person':
            self.current_person_id = attrs['id']
        elif name == 'office':
            self.office_id_to_person_id[attrs['id']] = self.current_person_id
    def endElement(self,name):
        if name == 'person':
            self.current_person_id = None

people_parser = PeopleParser()
people_parser.parse("../members/people.xml")

person_id_to_twitter_username = {}

output_filename = "../members/twitter-commons.xml"
fp = open(output_filename,"w")
fp.write('''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>
''')

for r in rows:
    if len(r) < 5:
        continue
    member_id = r[2]
    twitter_username = r[4]
    if member_id == "url":
        # That's the header line...
        continue
    if len(twitter_username) == 0:
        continue
    if member_id not in people_parser.office_id_to_person_id:
        raise "No person ID found for %s in line %s" % (member_id,"#".join(r))
    person_id = people_parser.office_id_to_person_id[member_id]
    fp.write("<personinfo id=\"%s\" twitter_username=\"%s\"/>\n"%(person_id,twitter_username))

fp.write("</publicwhip>")
