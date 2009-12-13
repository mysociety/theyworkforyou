# A few functions that turn out to be useful in many of the Scottish
# Parliament scraping scripts.

import sys
sys.path.append('../')
from BeautifulSoup import NavigableString
from BeautifulSoup import Tag
from BeautifulSoup import Comment

import re

# A number of SPIDs have a 0 (zero) in place of an O (letter O), and
# this converts a string containing them.  It also fixes leading 0s in
# the number after the hyphen.
def fix_spid(s):
    result = re.sub('(S[0-9]+)0-([0-9]+)',r'\1O-\2',s)
    return re.sub('(S[0-9]+\w+)-0*([0-9]+)',r'\1-\2',result)

months = { "january"   : 1,
           "february"  : 2,
           "march"     : 3,
           "april"     : 4,
           "may"       : 5,
           "june"      : 6,
           "july"      : 7,
           "august"    : 8,
           "september" : 9,
           "october"   : 10,             
           "november"  : 11,
           "december"  : 12 }

abbreviated_months = { }
for k in months.keys():
    abbreviated_months[k[0:3]] = months[k]

def month_name_to_int( name ):

    lowered = name.lower()

    if months.has_key(lowered):
        return months[lowered]

    if abbreviated_months.has_key(lowered):
        return abbreviated_months[lowered]

    return 0

def non_tag_data_in(o):
    if o.__class__ == NavigableString:
        return re.sub('(?ms)[\r\n]',' ',o)
    elif o.__class__ == Tag:
        if o.name == 'script':
            return ''
        else:
            return ''.join( map( lambda x: non_tag_data_in(x) , o.contents ) )
    elif o.__class__ == Comment:
        return ''
    else:
        # Hope it's a string or something else concatenatable...
        return o

def tidy_string(s):
    result = re.sub('(?ims)\s+',' ',s)
    return result.strip()

# These two methods from:
#
#  http://snippets.dzone.com/posts/show/4569

from htmlentitydefs import name2codepoint

def substitute_entity(match):
    ent = match.group(2)
    if match.group(1) == "#":
        return unichr(int(ent))
    else:
        cp = name2codepoint.get(ent)
        if cp:
            return unichr(cp)
        else:
            return match.group()

def decode_htmlentities(string):
    entity_re = re.compile("&(#?)(\d{1,5}|\w{1,8});")
    return entity_re.subn(substitute_entity, string)[0]

def compare_spids(a,b):
    ma = re.search('S(\d+\w+)-(\d+)',a)
    mb = re.search('S(\d+\w+)-(\d+)',b)
    if ma and mb:
        mas = ma.group(1)
        mbs = mb.group(1)
        mai = int(ma.group(2),10)
        mbi = int(mb.group(2),10)
        if mas < mbs:
            return -1
        elif mas > mbs:
            return 1
        else:
            if mai < mbi:
                return -1
            if mai > mbi:
                return 1
            else:
                return 0
    else:
        raise Exception, "Couldn't match spids: "+a+" and "+b
