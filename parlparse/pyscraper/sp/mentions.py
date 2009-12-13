import xml.sax
import os
import re
import datetime
import time
import glob
from common import compare_spids

def add_mention_to_dictionary(key,mention,dictionary):
    dictionary.setdefault(key,[])
    if mention not in dictionary[key]:
        dictionary[key].append(mention)

class MentionsParser(xml.sax.handler.ContentHandler):
    def __init__(self):
        self.parser = xml.sax.make_parser()
        self.parser.setContentHandler(self)
    def startElement(self,name,attr):
        if name == "question":
            self.current_question_id = re.sub('uk.org.publicwhip/spq/','',attr["gid"])
        elif name == "mention":
            mention_type = None
            date = None
            spwrans = None
            url = None
            orgid = None
            if attr.has_key("type"):
                mention_type = attr["type"]
            if attr.has_key("date"):
                date = attr["date"]
            if attr.has_key("spwrans"):
                spwrans = attr["spwrans"]
            if attr.has_key("url"):
                url = attr["url"]
            if attr.has_key("orgid"):
                orgid = attr["orgid"]
            if attr.has_key("referrer"):
                referrer = re.sub('uk.org.publicwhip/spq/','',attr["referrer"])
            # Now create that mention and add it to the hash.
            m = None
            if mention_type == "business-today":
                m = Mention(self.current_question_id,
                            date,
                            url,
                            mention_type,
                            None)
            elif mention_type == "business-oral":
                m = Mention(self.current_question_id,
                            date,
                            url,
                            mention_type,
                            None)
            elif mention_type == "business-written":
                m = Mention(self.current_question_id,
                            date,
                            url,
                            mention_type,
                            None)
            elif mention_type == "answer":
                m = Mention(self.current_question_id,
                            date,
                            None,
                            mention_type,
                            spwrans)
            elif mention_type == "holding":
                m = Mention(self.current_question_id,
                            date,
                            None,
                            mention_type,
                            None)                
            elif mention_type == "oral-asked-in-official-report":
                m = Mention(self.current_question_id,
                            str(date),
                            None,
                            mention_type,
                            orgid)
            elif mention_type == "referenced-in-question-text":
                m = Mention(self.current_question_id,
                            None,
                            None,
                            mention_type,
                            referrer)
            else:
                raise Exception, "Unknown mention type: "+str(mention_type)
            add_mention_to_dictionary(self.current_question_id,m,self.id_to_mentions)

    def get_mentions_hash(self):
        self.id_to_mentions = {}
        mentions_prefix = "../../../parldata/scrapedxml/sp-questions/"
        filenames = glob.glob( mentions_prefix + "up-to-*.xml" )
        filenames.sort()
        for filename in filenames:
            self.parser.parse(filename)
        return self.id_to_mentions

def load_question_mentions():
    mentions_parser = MentionsParser()
    return mentions_parser.get_mentions_hash()

def save_question_mentions(id_to_mentions):
    xml_output_directory = "../../../parldata/scrapedxml/sp-questions/";
    # Make sure it exists:
    os.system("mkdir -p "+xml_output_directory)
    keys = id_to_mentions.keys()
    keys.sort(compare_spids)
    # keys.sort()
    now = datetime.datetime.now()
    filename_base = "%sup-to-%s.xml" % ( xml_output_directory, now.isoformat() )
    tmp_filename = filename_base + ".tmp"
    fp = open( tmp_filename, "w" )
    fp.write( '<?xml version="1.0" encoding="utf-8"?>\n' )
    fp.write( '<publicwhip>\n' )
    for k in keys:
        mentions = id_to_mentions[k]
        fp.write( '  <question gid="uk.org.publicwhip/spq/%s">\n' % k )
        mentions.sort( key = lambda m: str(m.iso_date) + m.mention_index )
        for mention in mentions:
            fp.write('    '+mention.to_xml_element(k))
        fp.write( '  </question>\n\n' )
    fp.write( '</publicwhip>' )
    fp.close()
    os.rename(tmp_filename,filename_base)
    fil = open('%schangedates.txt' % xml_output_directory, 'a+')
    fil.write('%d,up-to-%s.xml\n' % (time.time(), now.isoformat()))
    fil.close()

class Mention:
    mention_type_order = { "business-today" : "1",
                           "business-oral" : "2",
                           "business-written" : "3",
                           "answer" : "4",
                           "holding" : "5",
                           "oral-asked-in-official-report" : "6",
                           "referenced-in-question-text" : "7" }
    def __init__(self,spid,iso_date,url,mention_type,gid):
        self.spid = spid
        self.iso_date = iso_date
        self.url = url
        self.mention_type = mention_type
        self.gid = gid
        if not Mention.mention_type_order.has_key(mention_type):
            raise Exception, "Unknown mention_type: "+mention_type
        self.mention_index = Mention.mention_type_order[mention_type]
    def __str__(self):
        return "%s (%s) [%s] url: '%s', gid: '%s'" % ( self.spid, self.mention_type, self.iso_date, self.url, self.gid )
            
    def __eq__(self,other):
        return (self.spid == other.spid) and (self.iso_date == other.iso_date) and (self.mention_type == other.mention_type) and (self.gid == other.gid)
    def to_xml_element(self,spid):
        url_attribute = None
        gid_attribute = None
        date_attribute = ' date="%s"' % ( self.iso_date )
        if self.url:
            url_attribute = ' url="%s"' % self.url
        else:
            url_attribute = ''
        if self.mention_type == 'answer':
            gid_attribute = ' spwrans="%s"' % ( self.gid )
        elif self.mention_type == 'oral-asked-in-official-report':
            gid_attribute = ' orgid="%s"' % ( self.gid )
        elif self.mention_type == 'referenced-in-question-text':
            gid_attribute = ' referrer="uk.org.publicwhip/spq/%s"' % ( self.gid )
            date_attribute = ''
        else:
            gid_attribute = ''
        return '<mention%s type="%s"%s%s/>\n' % ( date_attribute, self.mention_type, url_attribute, gid_attribute )
