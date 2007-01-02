#!/usr/local/bin/python2.4
# vim:sw=4:ts=4:et:nowrap

# Reads wikipedia dump and finds links to TheyWorkForYou

# bzcat ~/parldata/dumps/enwiki-latest-pages-articles.xml.bz2 | ./wikipedia-backlinks.py

import xml.sax
import sys
import re

def process_page(text):
    links = re.findall("http://(?:www.)?theyworkforyou.com/[^\s[\]]+", text)
    return links

class WikipediaReader(xml.sax.handler.ContentHandler):
	def __init__(self):
		pass

	def startElement(self, name, attr):
        self.currtag = name
        if name == 'title':
            self.title = ''
        if name == 'text':
            self.text = ''

    def characters(self, chrs):
        if self.currtag == 'title':
            self.title = self.title + chrs
        if self.currtag == 'text':
            self.text = self.text + chrs

    def endElement(self, name):
        if name == 'page':
            #print self.title.encode('utf-8')
            if "theyworkforyou.com" in self.text:
                links = process_page(self.text)
                for link in links:
                    print link, self.title.encode('utf-8')
        self.currtag = None

wr = WikipediaReader()
parser = xml.sax.make_parser()
parser.setContentHandler(wr)
parser.parse(sys.stdin)



page = '''
    * [http://www.epolitix.com/EN/MPWebsites/Ann+Widdecombe/ ePolitix.com <E2><80><94> Ann Widdecombe]
    * [http://politics.guardian.co.uk/person/0,9290,-5516,00.html Guardian Unlimited Politics <E2><80><94> Ask Aristotle: Ann Wid
    decombe MP]
    * [http://www.theyworkforyou.com/mp/ann_widdecombe/maidstone_and_the_weald TheyWorkForYou.com <E2><80><94> Ann Widdecombe MP]
    * [http://publicwhip.org.uk/mp.php?mpn=Ann_Widdecombe&amp;mpc=Maidstone+%26amp%3B+The+Weald The Public Whip <E2><80><94> Ann 
    Widdecombe MP] voting record
    * [http://news.bbc.co.uk/1/shared/mpdb/html/275.stm BBC News <E2><80><94> Ann Widdecombe] profile 10 February, 2005
    * [http://news.bbc.co.uk/1/hi/entertainment/tv_and_radio/3558378.stm BBC News <E2><80><94> The Widdecombe Project] about her 
    agony aunt television programme on BBC Two'''
print process_page(page)
sys.exit()

