#! /usr/bin/python2.4

import xml.sax
import re
import string
import copy
import sets
import sys
import datetime
from contextexception import ContextException

class MemberList(xml.sax.handler.ContentHandler):
	deputy_speaker = None

	def __init__(self):
		self.reloadXML()

	def reloadXML(self):
		self.members = {
			"uk.org.publicwhip/member/454" : { 'firstname':'Paul', 'lastname':'Murphy', 'title':'', 'party':'Labour' },
			"uk.org.publicwhip/member/384" : { 'firstname':'John', 'lastname':'McFall', 'title':'', 'party':'Labour' },
		} # ID --> MLAs
		self.fullnames={} # "Firstname Lastname" --> MLAs
		self.lastnames={} # Surname --> MLAs

		self.debatedate=None
		self.debatenamehistory=[] # recent speakers in debate
		self.debateofficehistory={} # recent offices ("The Deputy Prime Minister")

		self.constoidmap = {} # constituency name --> cons attributes (with date and ID)
		self.considtonamemap = {} # cons ID --> name
		self.considtomembermap = {} # cons ID --> MLAs

		self.parties = {} # party --> MLAs
		self.membertopersonmap = {} # member ID --> person ID
		self.persontomembermap = {} # person ID --> office

		self.retitles = re.compile('^(?:Rev |Dr |Mr |Mrs |Ms |Miss |Sir |Lord )+')
		self.rehonorifics = re.compile('(?: OBE| CBE| MP)+$')

		parser = xml.sax.make_parser()
		parser.setContentHandler(self)
		self.loadconsattr = None
		parser.parse("../members/constituencies.xml")
		parser.parse("../members/ni-members.xml")
		self.loadperson = None
		parser.parse("../members/people.xml")
		parser.parse("../members/ni-aliases.xml")

	def startElement(self, name, attr):
		# all-members.xml loading
		if name == "member_ni":

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
			# find the constituency id for this MLA
			for consattr in consids:
				if (consattr['fromdate'] <= attr['fromdate'] and
					attr['fromdate'] <= attr['todate'] and
					attr['todate'] <= consattr['todate']):
					if consid and consid != consattr['id']:
						raise Exception, "Two constituency ids %s %s overlap with MLA %s" % (consid, consattr['id'], attr['id'])
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

		# people.xml loading
		elif name == "person":
			self.loadperson = attr["id"]
		elif name == "office":
			assert self.loadperson, "<office> element before <person> element"
			if attr["id"] in self.membertopersonmap:
				raise Exception, "Same office id %s appeared twice" % attr["id"]
			self.membertopersonmap[attr["id"]] = self.loadperson
			self.persontomembermap.setdefault(self.loadperson, []).append(attr["id"])

	def endElement(self, name):
		if name == "constituency":
			self.loadconsattr = None

	def partylist(self):
		return self.parties.keys()

	def list(self, date=None):
		if not date:
			date = datetime.date.today().isoformat()
		matches = self.members.values()
		ids = []
		for attr in matches:
			if 'fromdate' in attr and date >= attr["fromdate"] and date <= attr["todate"]:
				ids.append(attr["id"])
		return ids

	# useful to have this function out there
	def striptitles(self, text):
		text = text.replace("&rsquo;", "'")
		text = text.replace("&nbsp;", " ")
		(text, titletotal) = self.retitles.subn("", text)
		text = self.rehonorifics.sub("", text)
		return text.strip(), titletotal

	def __strippunc(self, cons):
		nopunc = cons.replace(',','').replace('-','').replace(' ','').lower().strip()
		return nopunc

	# date can be none, will give more matches
	def fullnametoids(self, tinput, date):
		# Special case gender uniques
		if tinput == 'Mrs Bell': tinput = 'Mrs E Bell'

		text, titletotal = self.striptitles(tinput)

		# Special case for non-MLAs
		if text == 'P Murphy': return ["uk.org.publicwhip/member/454"]
		if text == 'McFall': return ["uk.org.publicwhip/member/384"]

		# Find unique identifier for member
		ids = sets.Set()
		matches = []
		matches.extend(self.fullnames.get(text, []))
		if not matches and titletotal > 0:
			matches = self.lastnames.get(text, None)

		# If a speaker, then match against the special speaker parties
		if text == "Speaker" or text == "The Speaker":
			matches.extend(self.parties.get("Speaker", []))
		if not matches and (text == 'Deputy Speaker' or text == 'Madam Deputy Speaker' or text =='The Deputy Speaker'):
			if not self.deputy_speaker:
				raise ContextException, 'Deputy speaker speaking, but do not know who it is'
			return self.fullnametoids(self.deputy_speaker, date)

		if matches:
			for attr in matches:
				if (date == None) or (date >= attr["fromdate"] and date <= attr["todate"]):
					ids.add(attr["id"])
		return ids

	def setDeputy(self, deputy):
		if deputy == 'Mr Wilson':
			deputy = 'Mr J Wilson'
		self.deputy_speaker = deputy

	def match(self, input, date):
		# Clear name history if date change
		if self.debatedate != date:
			self.debatedate = date
			self.cleardebatehistory()
		speakeroffice = ''
		office = None
		input = re.sub(' \(Designate\)', '', input)
		match = re.match('(.*) \((.*?)\)\s*$', input)
		if match:
			office = match.group(1)
			speakeroffice = ' speakeroffice="%s"' % office
			input = match.group(2)
		ids = self.fullnametoids(input, date)
		if len(ids) == 0 and match:
			office = match.group(2)
			input = match.group(1)
			speakeroffice = ' speakeroffice="%s"' % office
			ids = self.fullnametoids(input, date)

		officeids = self.debateofficehistory.get(input, None)
		if officeids and len(ids) == 0:
			ids = officeids
		if office:
			self.debateofficehistory.setdefault(office, sets.Set()).union_update(ids)

		if len(ids) == 0:
			if not re.search('Some Members|A Member|Several Members|Members', input):
				raise ContextException, "No matches %s" % (input)
			return None, 'speakerid="unknown" error="No match" speakername="%s"' % (input)
		if len(ids) > 1 and 'uk.org.publicwhip/member/90355' in ids:
			# Special case for 8th May, when Mr Hay becomes Speaker
			if input == 'Mr Hay':
				ids.remove('uk.org.publicwhip/member/90355')
			elif input == 'Mr Speaker':
				ids.remove('uk.org.publicwhip/member/90287')
			else:
				raise ContextException, 'Problem with Mr Hay!'
		elif len(ids) > 1:
			names = ""
			for id in ids:
				names += id + " " + self.members[id]["firstname"] + " " + self.members[id]["lastname"] + " (" + self.members[id]["constituency"] + ") "
			raise ContextException, "Multiple matches %s, possibles are %s" % (input, names)
			return None, 'speakerid="unknown" error="Matched multiple times" speakername="%s"' % (input)
		for id in ids:
			pass
		remadename = self.members[id]["lastname"]
		if self.members[id]["firstname"]:
			remadename = self.members[id]["firstname"] + " " + remadename
		if self.members[id]["title"]:
			remadename = self.members[id]["title"] + " " + remadename
		if self.members[id]["party"] == "Speaker" and re.search("Speaker", input):
			remadename = input
		return id, 'speakerid="%s" speakername="%s"%s' % (id, remadename, speakeroffice)

	def cleardebatehistory(self):
		self.debatenamehistory = []
		self.debateofficehistory = {}

	def getmember(self, memberid):
		return self.members[memberid]

	def membertoperson(self, memberid):
		return self.membertopersonmap[memberid]

memberList = MemberList()
