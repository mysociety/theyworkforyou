#! /usr/bin/env python2.4
# Common utilities for handling Parliamentary Bill Committees
#

import re

def construct_shortname(committeedate, letter, sittingnumber, sittingpart, date):
    """Generate a shortname from document attributes"""
    return "standing%s_%s_%02d-%d_%s" % (committeedate, letter, sittingnumber, sittingpart, date)

def shortname_atts(shortname):  
    """Get a tuples of committee attributes from shortname string"""
    m = re.match("standing(?P<committeedate>.*?)_(?P<letter>[A-Z]*?|2)_(?P<sittingnumber>\d\d)-(?P<sittingpart>\d)_(?P<date>.*?)(?P<version>[a-z])$", shortname)
    if not m:
        raise Exception, "Attributes cannot be extracted from shortname %s" % shortname
    return m.groupdict()

def create_committee_letters(indexurl):
    """Create a set of letters for the shortname for a committee based on the Bill title"""
    #get the name of the index file for the committee
    pBill = 'http://www.publications.parliament.uk/pa/cm(?:\d*)/(?:cmpublic/)?cmpb(.*?).htm'
    return re.match(pBill, indexurl).group(1).upper()
