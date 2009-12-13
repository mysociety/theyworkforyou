#!/usr/bin/python

# parse.py does the awful trying to match a line from his page to our current
# XML IDs (because he uses creation date and we use oath date, you'll probably
# cringe at my 120 day leeway - still not enough for Lord Robertson... ;) )

import sys
import os
sys.path.append('./')
sys.path.append('lords/')
os.chdir('../../pyscraper/')
import urllib2
from resolvelordsnames import lordsList
from contextexception import ContextException
import re
import mx.DateTime
import codecs

ranks = {
    'L.':'Lord',
    'B.':'Baroness',
    'V.':'Viscount',
    'E.':'Earl',
    'C.':'Countess',
    'M.':'Marquess',
    'D.':'Duke',
}

fout = codecs.open('../members/beamish/BeamishParsed', 'w', 'utf-8')
fout.write("ID,Created,Type,Rank,Lord Name,Lordof Name,Lord Place,County,Forenames,Surname,Hereditary,Died\n")
fh = file('../members/beamish/peerages.htm').readlines()
for row in fh:
    if re.search('<BR>|<A HREF|</?(table|p)|Chronological|DOCTYPE|LINK|META|HEAD|STYLE(?i)', row):
        continue
    m = re.match('''(?x)<tr><td\ bgcolor=".*?">
        <A\ NAME=".*?">
            (?P<date>.*?)\ \((?P<type>[^)]*?)\)
        </A></td><td>
        (?P<rank>.\.)\ <b>(?P<lordname>[^<]*?)</b>\ of\ (?P<lordplace>.*?)
        (?:\ in\ (?P<county>.*?))?\ &\#8211;\ (?P<forenames>.*?)\ <i>(?P<surname>.*?)</i>
        (?:\ \((?P<hereditary>[^d].*?)\))?
        (?:\ \(died\ (?P<died>.*?)\))?
        </td></tr>''', row)
    if m:
        date = re.sub(' \((a|p)\.m\.\)', '', m.group('date'))
        date = mx.DateTime.DateTimeFrom(date)
        rank = m.group('rank')
        lordname = re.sub('&#8217;', "'", m.group('lordname'))
        lordplace = re.sub('&#8217;', "'", m.group('lordplace'))

        if m.group('hereditary'):
            n0 = re.match('^\d\w+ (\w\.) of (.*?)$', m.group('hereditary'))
            n1 = re.match('^\d\w+ (\w\.) (.*?)$', m.group('hereditary'))
            n0b = re.match('^(\w\.) of (.*?)$', m.group('hereditary'))
            n2 = re.match('^(\w\.) (.*?)$', m.group('hereditary'))
            if n0:
                (rank, lordplace) = n0.groups()
                county = ''
                lordname = ' of ' + lordplace
            elif n0b:
                (rank, lordplace) = n0b.groups()
                county = ''
                lordname = ' of ' + lordplace
            elif n1:
                (rank, lordname) = n1.groups()
            elif n2:
                (rank, lordname) = n2.groups()

        name = ranks[rank] + ' ' + lordname
        ofmatch = re.search('^(.*?) of (.*?)$', lordname)
        if ofmatch:
            (lordname, lordofname) = ofmatch.groups()
        else:
            lordofname = ''
        surname = re.sub('&#8217;', "'", m.group('surname'))
        died = m.group('died')
        if died:
            try:
                died = mx.DateTime.DateTimeFrom(died).date
            except:
                continue
        try:
            id = lordsList.GetLordIDfname(name, None, date.date)
            line = "%s,%s,%s,%s,%s,%s,%s,%s," % (id, date.date, m.group('type'), ranks[rank], lordname, lordofname, lordplace, m.group('county') or '')
            line += m.group('forenames').decode('iso-8859-1')
            line += ",%s,%s,%s" % (surname, m.group('hereditary') or '', died or '')
            fout.write(line+"\n")
        except ContextException, e:
            try:
                id = lordsList.GetLordIDfname(name, None, (date+120).date)
                line = "%s,%s,%s,%s,%s,%s,%s,%s," % (id, date.date, m.group('type'), ranks[rank], lordname, lordofname, lordplace, m.group('county') or '')
                line += m.group('forenames').decode('iso-8859-1')
                line += ",%s,%s,%s" % (surname, m.group('hereditary') or '', died or '')
                fout.write(line+"\n")
            except ContextException, e:
                print name, 'didn\'t match at all', date.date
                id = 'ERROR'
#        except Exception, e:
#            print "*", e, "*", name
#            id = 'ERROR'
        #id = re.sub('uk.org.publicwhip/lord/', '', id)
    else:
        pass
fout.close()
