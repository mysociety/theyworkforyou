#!/usr/bin/python

import xml.sax

class ConsList(xml.sax.handler.ContentHandler):
	def __init__(self):
		self.constoid = {} # stripped constituency name --> cons attributes (with date and ID)
		self.considtonames = {} # cons ID --> names
		parser = xml.sax.make_parser()
		parser.setContentHandler(self)
		parser.parse("../../../../parlparse/members/constituencies.xml")

	def strippunc(self, cons):
		nopunc = cons.replace(',','').replace("'", '').replace('-','').replace(' ','').lower().strip()
		return nopunc

	def startElement(self, name, attr):
		if name == "constituency":
			self.loadconsattr = attr
		elif name == "name":
			self.considtonames.setdefault(self.loadconsattr["id"], []).append(attr['text'])
			self.constoid.setdefault(self.strippunc(attr['text']), set()).add(self.loadconsattr)

	def canonical(self, name, date):
		id = self.find(name, str(date))
		if id not in self.considtonames:
			raise Exception, '%s %s %s' % (id, name, date)
		return self.considtonames[id][0]

	def find(self, cons, date):
		consids = self.constoid.get(self.strippunc(cons), None)
		if not consids:
			raise Exception, 'No such constituency %s' % cons
		consid = None
		for consattr in consids:
			fromdate = consattr['fromdate']
			if len(fromdate)==4: fromdate = '%s-01-01' % fromdate
			todate = consattr['todate']
			if len(todate)==4: todate = '%s-12-31' % todate
			if fromdate <= date and date <= todate:
				if consid:
					raise Exception, "Two like-named constituency ids %s %s overlap with date %s" % (consid, consattr['id'], date)
				consid = consattr['id']
		return consid

consList = ConsList()

