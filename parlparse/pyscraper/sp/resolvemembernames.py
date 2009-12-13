#!/usr/bin/python2.4

import xml.sax
import re
import string
import copy
import sets
import sys
import datetime
import time
import codecs

# Most of this is copied from the NI resolvemembernames.py

class MemberList(xml.sax.handler.ContentHandler):

    def __init__(self):
        self.reloadXML()

    # This will return a list of member ID strings or None.  If there
    # are no matches, the list will be empty.  If we recognize a valid
    # speaker, but that person is not an MSP (e.g. The Convener,
    # Members, the Lord Advocate, etc.) then we return None.

    # (In fact, it's not at all clear that distinguishing the empty
    # list and None cases is actually useful.)

    # FIXME: use Set instead of lists

    def match_whole_speaker(self,speaker_name,speaker_date):

        #lfp = codecs.open("/var/tmp/all-names",'a','utf-8')
        #lfp.write("%s\t%s\n"%(speaker_date,speaker_name))
        #lfp.close()

        # if speaker_date:
        #     print speaker_name+" [on date "+speaker_date + "]"
        # else:
        #     print speaker_date+" [no date]"

        party = ''

        m = re.match('^(.*) \((Con|Lab|Labour|LD|SNP|SSP|Green|Ind|SSCUP|SCCUP|Sol)\s?\)(.*)$',speaker_name)
        if m:
            speaker_name = m.group(1) + m.group(3)
            party = m.group(2)

        # print "party is: "+party

        # Now we should have one of the following formats:
        # <OFFICE> (<NAME>) (<CONS>)    (one occurence)
        # <NAME> (<CONS>)
        # <OFFICE> (<NAME>)
        # <NAME> (<OFFICE>)             (also rare)
        # <NAME>

        # Names are typically fullnames: firstname + " " + lastname
        #                            or: title + " " + lastname
        #                            or: title + " " + firstname + " " + lastname

        # First, check the first part:

        m = re.search('^([^\(]*)(.*)',speaker_name)
        first_part = m.group(1).strip()
        bracketed_parts = m.group(2).strip()

        all_matching_ids = ()

        ids_from_first_part = memberList.match_string_somehow(first_part,speaker_date,party,False)
        if ids_from_first_part == None:
            return None
        else:
            if len(ids_from_first_part) == 1:
                return ids_from_first_part
            # Otherwise, we try to refine this...
            ids_so_far = ids_from_first_part

        while len(bracketed_parts) > 0:
            m = re.search('\(([^\)]*)(\)(.*)|$)',bracketed_parts)
            if not m:
                break
            bracketed_part = m.group(1).strip()
            # print "   Got bracketed part: "+bracketed_part
            ids_from_bracketed_part = memberList.match_string_somehow(bracketed_part,speaker_date,party,False)
            if ids_from_bracketed_part != None:
                if len(ids_from_bracketed_part) == 1:
                    return ids_from_bracketed_part
                elif len(ids_from_bracketed_part) == 0:
                    pass
                else:
                    if len(ids_so_far) > 0:
                        # Work out the intersection...
                        ids_so_far = filter(lambda x: x in ids_from_bracketed_part,ids_so_far)
                        if len(ids_so_far) == 1:
                            return ids_so_far
                    else:
                        ids_so_far = ids_from_bracketed_part
                # Otherwise, we try to refine this...
            else:
                return None

            if m.group(3):
                bracketed_parts = m.group(3).strip()
            else:
                bracketed_parts = ''

        return ids_so_far

    # This will return a list of member ID strings or None.  If there
    # are no matches, the list will be empty.  If we recognize a valid
    # speaker, but that person is not an MSP (e.g. The Convener,

    # Members, the Lord Advocate, etc.) then we return None.

    # (In fact, it's not at all clear that distinguishing the empty
    # list and None cases is actually useful.)

    # FIXME: use Set instead of lists

    def match_string_somehow(self,s,date,party,just_name):

        member_ids = []

        # Sometimes the names are written Lastname, FirstNames
        # (particularly in the reports of divisions.

        comma_match = re.match('^([^,]*), (.*)',s)
        if comma_match:
            rearranged = comma_match.group(2) + " " + comma_match.group(1)
            rearranged_result = self.match_string_somehow(rearranged,date,party,just_name)
            if rearranged_result != None:
                if len(rearranged_result) > 0:
                    return rearranged_result
            else:
                return None

        # ... otherwise just carry on without any rearragement.

        if not just_name:

            office_matches = self.officeslowered.get(s.lower())
            if office_matches:
                for o in office_matches:
                    if date and date < o['fromdate'] or date > o['todate']:
                        continue
                    for m in o['members']:
                        if date and date < m['fromdate'] or date > m['todate']:
                            continue
                        if m['id'] not in member_ids:
                            member_ids.append(m['id'])
                if len(member_ids) == 1:
                    return member_ids

        fullname_matches = self.fullnames.get(s)
        if fullname_matches:
            for m in fullname_matches:
                if date and date < m['fromdate'] or date > m['todate']:
                    continue
                if re.search('The Presiding Officer',s) and m['fromwhy'] != 'became_presiding_officer':
                    # There's some ambiguity about which of the
                    # presiding officers it is in this case...
                    continue
                if m['id'] not in member_ids:
                    member_ids.append(m['id'])
            if len(member_ids) == 1:
                return member_ids

        # Now check if this begins with a title:

        title_match = re.search('^(Mr|Mgr|Sir|Ms|Mrs|Miss|Lord|Dr) (.*)',s)
        if title_match:
            title = title_match.group(1)
            rest_of_name = title_match.group(2)

            if rest_of_name == 'Home Robertson':
                rest_of_name = 'John Home Robertson'

            if rest_of_name == 'John Munro' or rest_of_name == 'Munro':
                rest_of_name = 'John Farquhar Munro'

            # We should probably deal with these by using the title
            # attributes from sp-members.xml

            if rest_of_name.lower() == 'macdonald':
                if title == 'Ms':
                    rest_of_name = 'Margo MacDonald'

            if title == 'Dr' and rest_of_name == 'Jackson':
                rest_of_name = 'Sylvia Jackson'

            fullname_matches = self.fullnames.get(rest_of_name)
            if fullname_matches:
                for m in fullname_matches:
                    if date and date < m['fromdate'] or date > m['todate']:
                        continue
                    if m['id'] not in member_ids:
                        member_ids.append(m['id'])
                if len(member_ids) == 1:
                    return member_ids

            # Or if there's a single word, then this is probably just
            # a last name:

            if re.match('^[^ ]+$',rest_of_name):
                lastname_matches = self.lastnames.get(rest_of_name)
                if lastname_matches:
                    for m in lastname_matches:
                        if date and date < m['fromdate'] or date > m['todate']:
                            continue
                        if m['id'] not in member_ids:
                            member_ids.append(m['id'])
                    if len(member_ids) == 1:
                        return member_ids

        if not just_name:

            constituency_matches = self.constoidmap.get(s)
            if constituency_matches:
                for c in constituency_matches:
                    # print "       Got consituency id: "+c['id']
                    members = self.considtomembermap.get(c['id'])
                    for m in members:
                        if date and date < m['fromdate'] or date > m['todate']:
                            continue
                        if m['id'] not in member_ids:
                            member_ids.append(m['id'])
                    if len(member_ids) == 1:
                        return member_ids

        # Just return the string for people that aren't members, but
        # we know are ones we understand.

        if re.search('(Some [mM]embers|A [mM]ember|Several [mM]embers|Members)',s):
            # print "Got some general group of people..."
            return None

        if self.lawofficers.get(s.lower()):
            # print "Got some law officer..."
            return None

        return member_ids

    def reloadXML(self):
        # Could add some manually here:
        self.members = {
            # "uk.org.publicwhip/member/454" : { 'firstname':'Paul', 'lastname':'Murphy', 'title':'', 'party':'Labour' },
            # "uk.org.publicwhip/member/384" : { 'firstname':'John', 'lastname':'McFall', 'title':'', 'party':'Labour' },
        } # ID --> MLAs
        self.fullnames={} # "Firstname Lastname" --> MSPs
        self.lastnames={} # Surname --> MSPs

        # self.debatedate=None
        # self.debatenamehistory=[] # recent speakers in debate
        # self.debateofficehistory={} # recent offices ("The Deputy Prime Minister")

        self.constoidmap = {} # constituency name --> cons attributes (with date and ID)
        self.considtonamemap = {} # cons ID --> name
        self.considtomembermap = {} # cons ID --> MSPs

        self.parties = {} # party --> MLAs
        self.membertopersonmap = {} # member ID --> person ID
        self.persontomembermap = {} # person ID --> office

        # self.retitles = re.compile('^(?:Rev |Dr |Mr |Mrs |Ms |Sir |Lord )+')
        # self.rehonorifics = re.compile('(?: OBE| CBE| MP)+$')

        parser = xml.sax.make_parser()
        parser.setContentHandler(self)

        parser.parse("../../members/sp-constituencies.xml")
        parser.parse("../../members/sp-members.xml")
        parser.parse("../../members/sp-aliases.xml")

        self.loadperson = None
        parser.parse("../../members/people.xml")

        fromdate = None
        todate = None

        # These files of posts are slightly doctored versions of these
        # documents from the Scottish Parliament website after having
        # been converted to text.  This is pretty horrible, and we
        # should really keep track of these offices properly...

        #   MinistersandLawOfficersbycabinet-Session1.pdf
        #   MinistersLawOfficersMinisterialParliamentaryAidesbyCabinet-Session2.pdf
        #   ScottishMinistersandLawOfficersSession3.pdf


        posts_files = [ 'ministers-law-officers-aides-session1.txt',
                        'ministers-law-officers-aides-session2.txt',
                        'ministers-law-officers-aides-session3.txt' ]

        self.officeslowered = { }
        self.lawofficers = { }

        for pf in posts_files:
            f = codecs.open(pf,'r','utf-8')
            for line in f.readlines():
                line = line.strip()

                m_iso   = re.match('^\s*((\d{4})-(\d{2})-(\d{2}))\s+to\s+((\d{4})-(\d{2})-(\d{2}))\s*$',line)
                m_other = re.match('^.*\s+((\d+)\s+(\w+)\s+(\d{4})).*\s+((\d+)\s+(\w+)\s+(\d{4})).*$',line)

                if m_iso:
                    from_w = time.strptime(m_iso.group(1),'%Y-%m-%d')
                    to_w = time.strptime(m_iso.group(5),'%Y-%m-%d')
                    fromdate = datetime.date( from_w[0], from_w[1], from_w[2] )
                    todate = datetime.date( to_w[0], to_w[1], to_w[2] )
                elif m_other:
                    from_w = time.strptime(m_other.group(1),'%d %B %Y')
                    to_w = time.strptime(m_other.group(5),'%d %B %Y')
                    fromdate = datetime.date( from_w[0], from_w[1], from_w[2] )
                    todate = datetime.date( to_w[0], to_w[1], to_w[2] )
                elif re.match('.*\|.*\|.*',line):
                    fields = line.split('|')
                    if len(fields) != 3:
                        raise Exception, "Wrong number of fields: "+line
                    post, name, party = fields
                    name = re.sub('^Rt Hon ','',name)
                    name = re.sub('^Dr. ','',name)
                    name = re.sub(' MSP$','',name)
                    cabinet = False
                    # A theta in the post indicates it's a cabinet post.
                    # Filter that out
                    m = re.match(u"^(.*) \u0398(.*)$",post)
                    if m:
                        cabinet = True
                        # print "### Cabinet post! ###"
                        post = m.group(1) + m.group(2)
                    matches = self.fullnames[name]
                    if not matches:
                        raise "Couldn't find member: "+name
                    # Only keep matches where there's a date overlap.
                    # (There should only be one of these.)
                    matches = filter( lambda m: str(fromdate) <= m['todate'] and str(todate) >= m['fromdate'], matches)
                    if len(matches) < 1:
                        raise Exception, "No overlapping date ranges: "+str(len(matches))
                    value = { 'members': matches, 'fromdate': str(fromdate), 'todate': str(todate), 'party': party, 'cabinet': cabinet }
                    self.officeslowered.setdefault(post.lower(),[]).append(value)
                    self.officeslowered.setdefault("the "+post.lower(),[]).append(value)
                elif re.match('.*\_.*\_.*',line):
                    # In fact, the aides have never spoken in that
                    # role in the parliament so far, so ignore them
                    # too.
                    fields = line.split('_')
                    if len(fields) == 3:
                        aide_to, name, party = fields
                    elif len(fields) == 4:
                        aide_to, name, date_range, party = fields
                        m_other = re.match('^((\d+)\s+(\w+)\s+(\d{4})).*\s+((\d+)\s+(\w+)\s+(\d{4})).*$',date_range)
                        from_w = time.strptime(m_other.group(1),'%d %B %Y')
                        to_w = time.strptime(m_other.group(5),'%d %B %Y')
                        fromdate = datetime.date( from_w[0], from_w[1], from_w[2] )
                        todate = datetime.date( to_w[0], to_w[1], to_w[2] )
                    else:
                        raise Exception, "Wrong number of fields: "+line
                elif re.match('.*[^&]\#.*',line):
                    # These aren't MSPs, so just ignore them for the
                    # moment as well.
                    fields = line.split('#')
                    if len(fields) != 2:
                        raise Exception, "Wrong number of fields: "+line
                    post, name = fields
                    value = { 'name': name, 'fromdate': str(fromdate), 'todate': str(todate), 'party': party, 'cabinet': cabinet }
                    self.lawofficers.setdefault(post.lower(),[]).append(value)
                    self.lawofficers.setdefault("the "+post.lower(),[]).append(value)
                else:
                    pass

            f.close()

    def startElement(self, name, attr):

        # sp-members.xml loading
        if name == "member_sp":

            # MAKE A COPY.  (The xml documentation warns that the attr object can be
            # reused, so shouldn't be put into your structures if it's not a copy).
            attr = attr.copy()

            if self.members.get(attr["id"]):
                raise Exception, "Repeated identifier %s in members XML file" % attr["id"]
            self.members[attr["id"]] = attr

            lastname = attr["lastname"]

            # index by "Firstname Lastname" for quick lookup ...
            compoundname = attr["firstname"] + " " + lastname
            self.fullnames.setdefault(compoundname, []).append(attr)

            # add in names without the middle initial
            fnnomidinitial = re.findall('^(\S*)\s\S$', attr["firstname"])
            if fnnomidinitial:
                compoundname = fnnomidinitial[0] + " " + lastname
                self.fullnames.setdefault(compoundname, []).append(attr)

            # ... and by first initial, lastname
            if attr["firstname"]:
                compoundname = attr["firstname"][0] + " " + lastname
                self.fullnames.setdefault(compoundname, []).append(attr)

            # ... and also by "Lastname"
            self.lastnames.setdefault(lastname, []).append(attr)

            # ... and by constituency
            cons = attr["constituency"]
            consids = self.constoidmap[cons]
            consid = None
            # find the constituency id for this MSP
            for consattr in consids:
                if (consattr['fromdate'] <= attr['fromdate'] and
                    attr['fromdate'] <= attr['todate'] and
                    attr['todate'] <= consattr['todate']):
                    if consid and consid != consattr['id']:
                        raise Exception, "Two constituency ids %s %s overlap with MSP %s" % (consid, consattr['id'], attr['id'])
                    consid = consattr['id']
            if not consid:
                raise Exception, "Constituency '%s' not found" % attr["constituency"]
            # check name in members file is same as default in cons file
            backformed_cons = self.considtonamemap[consid]
            if backformed_cons != attr["constituency"]:
                raise Exception, "Constituency '%s' in members file differs from first constituency '%s' listed in cons file" % (attr["constituency"], backformed_cons)
            self.considtomembermap.setdefault(consid, []).append(attr)

            # ... and by party
            party = attr["party"]
            self.parties.setdefault(party, []).append(attr)

        # member-aliases.xml loading
        elif name == "alias":
            # search for the canonical name or the constituency name for this alias
            matches = None
            alternateisfullname = True
            if attr.has_key("fullname"):
                matches = self.fullnames.get(attr["fullname"], None)
            elif attr.has_key("lastname"):
                matches = self.lastnames.get(attr["lastname"], None)
                alternateisfullname = False
            # append every canonical match to the alternates
            for m in matches:
                newattr = {}
                newattr['id'] = m['id']
                newattr['fromwhy'] = m['fromwhy']
                newattr['towhy'] = m['towhy']
                # merge date ranges - take the smallest range covered by
                # the canonical name, and the alias's range (if it has one)
                early = max(m['fromdate'], attr.get('from', '1000-01-01'))
                late = min(m['todate'], attr.get('to', '9999-12-31'))
                # sometimes the ranges don't overlap
                if early <= late:
                    newattr['fromdate'] = early
                    newattr['todate'] = late
                    if alternateisfullname:
                        self.fullnames.setdefault(attr["alternate"], []).append(newattr)
                    else:
                        self.lastnames.setdefault(attr["alternate"], []).append(newattr)

        # people.xml loading
        elif name == "person":
            self.loadperson = attr["id"]
        elif name == "office":
            assert self.loadperson, "<office> element before <person> element"
            if attr["id"] in self.membertopersonmap:
                raise Exception, "Same office id %s appeared twice" % attr["id"]
            self.membertopersonmap[attr["id"]] = self.loadperson
            self.persontomembermap.setdefault(self.loadperson, []).append(attr["id"])

        # constituencies.xml loading
        elif name == "constituency":
            self.loadconsattr = attr
            pass
        elif name == "name":
            assert self.loadconsattr, "<name> element before <constituency> element"
            if not self.loadconsattr["id"] in self.considtonamemap:
                self.considtonamemap[self.loadconsattr["id"]] = attr["text"]
            self.constoidmap.setdefault(attr["text"], []).append(self.loadconsattr)
            nopunc = self.__strippunc(attr['text'])
            self.constoidmap.setdefault(nopunc, []).append(self.loadconsattr)

    def endElement(self, name):
        if name == "constituency":
            self.loadconsattr = None

    def __strippunc(self, cons):
        nopunc = cons.replace(',','').replace('-','').replace(' ','').lower().strip()
        return nopunc

    def membertoperson(self, memberid):
        return self.membertopersonmap[memberid]

    def list(self, date=None):
        if not date:
            date = datetime.date.today().isoformat()
        matches = self.members.values()
        ids = []
        for attr in matches:
            if 'fromdate' in attr and date >= attr["fromdate"] and date <= attr["todate"]:
                ids.append(attr["id"])
        return ids

    def list_all_dates(self):
        matches = self.members.values()
        ids = []
        for attr in matches:
            ids.append(attr["id"])
        return ids

memberList = MemberList()
