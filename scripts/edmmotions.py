#!/usr/bin/env python2.3
# -*- coding: latin-1 -*-
# $Id: edmmotions.py,v 1.1 2006-04-27 14:20:20 twfy-live Exp $

import xml.sax
import datetime
import sys
import urllib
import urlparse
import re
import string
import os
import time

sys.path.append("../pyscraper/")
from resolvemembernames import memberList

class edmList(xml.sax.handler.ContentHandler):
	def __init__(self):
		self.reloadXML()
	def reloadXML(self):
		self.edmlookups={}
		parser = xml.sax.make_parser()
		parser.setContentHandler(self)
		parser.parse('edm-links.xml')
	def startElement(self, name, attr):
		if name == 'memberinfo':
			self.edmlookups[attr['edm_ais_url']] = attr['id']
	def lookup(self, url):
		return self.edmlookups.get(url, None)
edmList = edmList()

edm_dir = "/home/fawkes/pwdata/edms/"
edm_index_url = "http://edm.ais.co.uk/weblink/html/motions.html/EDMI_SES=/order=1/statusdrop=2/start=%s"
edm_index_cached_url = "http://edm.ais.co.uk/cache/motions/list.1.%s.2.html"

def get_motion(session, ref):
	sn = sessions[session]
	motion = '%s%s/%s.m.html' % (edm_dir, sn, ref)
	if os.path.exists(motion):
		f = open(motion, 'r')
		content = f.read()
		f.close()
	else:
		edmurl = 'http://edm.ais.co.uk/weblink/html/motion.html/EDMI_SES=%s/ref=%s' % (session, ref)
		ur = urllib.urlopen(edmurl)
		content = ur.read()
		ur.close()
		print >> sys.stderr, "Fetching %s motion %s text page" % (sn, ref)
		m = re.search('<FRAME\s+SRC="(.*?)"\s+NAME="TEXT"', content)
		edmurl = urlparse.urljoin(edmurl, m.group(1))
		content = ''
		timeout = 10
		while timeout>0 and (content == '' or re.search('Not Found', content)):
			if re.search('Not Found', content):
				print "'Not Found' - trying again"
				time.sleep(10)
			ur = urllib.urlopen(edmurl)
			content = ur.read()
			ur.close()
			timeout -= 1
		fout = open(motion, 'w')
		fout.write(content)
		fout.close()
		time.sleep(5)
	return content

def get_printable(session, ref):
	sn = sessions[session]
	printable = '%s%s/%s.p.html' % (edm_dir, sn, ref)
	if os.path.exists(printable):
		f = open(printable, 'r')
		content = f.read()
		f.close()
	else:
		print >> sys.stderr, "Fetching %s motion %s printable page" % (sn, ref)
		ur = urllib.urlopen('http://edm.ais.co.uk/weblink/html/printable.html/ref=%s/EDMI_SES=%s' % (ref, session))
		content = ur.read()
		ur.close()
		fout = open(printable, 'w')
		fout.write(content)
		fout.close()
	return content

def get_signers(session, ref):
	sn = sessions[session]
	signers = '%s%s/%s.s.html' % (edm_dir, sn, ref)
	if os.path.exists(signers):
		f = open(signers, 'r')
		content = f.read()
		f.close()
	else:
		print >> sys.stderr, "Fetching %s motion %s signature page" % (sn, ref)
		content = ''
		timeout = 10
		while timeout>0 and (content == '' or re.search('Not Found', content)):
			if re.search('Not Found', content):
				print "'Not Found' - trying again"
				time.sleep(10)
			ur = urllib.urlopen('http://edm.ais.co.uk/weblink/html/motion_s.html/ref=%s/EDMI_SES=%s/order=1/statusdrop=2' % (ref, session))
			content = ur.read()
			ur.close()
			timeout -= 1
		fout = open(signers, 'w')
		fout.write(content)
		fout.close()
		time.sleep(5)
	return content

def get_member(memberurl, pnum, session):
	sn = sessions[session]
	member = '%s%s/%s.html' % (edm_dir, sn, pnum)
	if os.path.exists(member):
		f = open(member, 'r')
		content = f.read()
		f.close()
	else:
		print >> sys.stderr, "Having to look up %s %s" % (sn, memberurl)
		url = '%s/EDMI_SES=%s' % (memberurl, session)
		ur = urllib.urlopen(url)
		content = ur.read()
		ur.close()
		m = re.search('<FRAME\s+SRC="(.*?)"\s+NAME="CONTENT"', content)
		if m==None:
			raise Exception, "Couldn't find content frame: %s" % content
		frameurl = urlparse.urljoin(url, m.group(1))
		ur = urllib.urlopen(frameurl)
		content = ur.read()
		ur.close()
		fout = open(member, 'w')
		fout.write(content)
		fout.close()
	return content
	
fixes = [
	('VUNERABLE', 'VULNERABLE'), ('AVIATON', 'AVIATION'), ('LEASHOLD', 'LEASEHOLD'), ('WORKERS\(USDAW\)','WORKERS (USDAW)'), ('SEPERATION','SEPARATION'), ('OBECTIVES','OBJECTIVES'), (' AMD ',' AND '), ('ARTIC','ARCTIC')
]

matcher  = '<!-- \*\*\* Reference number \*\*\* -->.*?'
matcher += '<font face="arial,helvetica" size=2>(<[BI]>)?(.*?)</FONT>.*?'
matcher += '<!-- \*\*\* Motion title \*\*\* -->.*?'
matcher += '<A HREF="(.*?)" TARGET="_parent">\s*'
matcher += '<font face="arial,helvetica" size=2>(?:<[BI]>)?([^<]*?)</font></A>\s*'
matcher += '</TD>\s*<!-- \*\*\* Signatures -->.*?'
matcher += '(?:<font face="arial,helvetica" size=2>(?:<[BI]>)?(\d+) &nbsp;&nbsp;</font>\s*)?'
matcher += '</TD>\s*<!-- \*\*\* Motion date \*\*\* -->.*?'
matcher += '<font face="arial,helvetica" size=2>(?:<[BI]>)?(\d\d)\.(\d\d)\.(\d\d)</FONT>'
matcher += '(?s)'

sessions = {'05':'2005', '':'2004', '04':'2004', '03':'2003', '02':'2002', '01':'2001', '00':'2000', '99':'1999', '98':'1998', '97':'1997'}

signers = {}
edms = {}
sigs = {}
primary = {}
session = sys.argv[1]
for memberurl in edmList.edmlookups:
	pid = memberList.membertoperson(edmList.lookup(memberurl))
	m = re.search('=(.*?)SlAsHcOdEsTrInG(.*)', memberurl)
	lastname = urllib.unquote(m.group(1))
	firstname = urllib.unquote(m.group(2))
	pnum = int(re.sub('uk.org.publicwhip/person/','',pid))
#	print >> sys.stderr, "Member:%s, ID:%s, session:%s" % (memberurl,pid,sessions[session])
	content = get_member(memberurl, pnum, session)
	if re.search('no EDMs', content):
		continue;
	for fix in fixes:
		content = re.sub(fix[0], fix[1], content)
	m = re.search('ound (\d+) EDMs? signed', content)
	total = int(m.group(1))
	matches = re.findall(matcher, content)
	count = 0
	for (type, ref, url, title, num, day, month, year) in matches:
		id = "%s.%s" % (sessions[session], ref)
		title = string.capwords(title)
		url = urlparse.urljoin(memberurl, url)
		year = sessions[year]
		date = "%s-%s-%s" % (year, month, day)
		if id not in edms:
			edms[id] = {'session':sessions[session], 'ref':ref, 'title':title, 'url':url, 'num':num, 'status':'Open'}
			content = get_motion(session, ref)
#			print >> sys.stderr, "Adding EDM %s, title %s" % (ref, title)
			m = re.search('<TD>(?:<font face="arial,helvetica" size=2>|<FONT SIZE="-1"><font face="arial,helvetica" size=2><B>)\s*(.*?)(?:</font>|</B></font></FONT>)</TD>', content)
			if m:
				motiontext = m.group(1)
				edms[id]['text'] = motiontext
			else:
				m = re.search('<FONT SIZE="-1"><font face="arial,helvetica" size=2><B>The status of this EDM is (CLOSED|SUSPENDED).&nbsp;&nbsp;Reason: (.*?).</B></font>', content)
				edms[id]['status'] = string.capwords(m.group(1))
				edms[id]['closed'] = m.group(2)
		if ref not in sigs:
#			print >> sys.stderr, "Adding signatures, ref %s" % ref
			s = get_signers(session,ref)
			m = re.findall('(?:<FONT SIZE="-1">|<font face="arial,helvetica" size=2><(?:B|I)>)([^<]*?)/([^<]*?)(?:</(?:B|I)></font>|</FONT>)', s)
			pos = 0
			sigs[ref] = {}
			for (last, first) in m:
				pos += 1
				sigs[ref][(last, first)] = pos
		pos = sigs[ref][(lastname, firstname)]
		curr = edms[id]
		if curr['title']!=title or curr['url']!=url:
			print >> sys.stderr, "EDM data doesn't match: %s:%s %s:%s" % (curr['title'],title,curr['url'],url)
		if curr['num']!=num:
			if num and not curr['num']:
				edms[id]['num'] = num
			elif not num and curr['num']:
				pass
			else:
				raise Exception, "EDM number doesn't match: %s vs %s" % (curr['num'], num)
		if type=='<B>':
			type = 'Primary'
			primary[id] = 1
			if 'date' not in edms[id]:
				edms[id]['date'] = date
			else:
				if curr['date'] != edms[id]['date']:
					raise Exception, "EDM date doesn't match: %s:%s" % (curr['date'], edms[id]['date'])
		elif type=='<I>':
			type = 'Sponsor'
		else:
			type = 'Supporter'
		signers.setdefault(id,[]).append( (pid, type, date, pos) )
		count += 1
	assert total == count

keys = edms.keys()
keys.sort()
for id in keys:
	if id not in primary:
		print >> sys.stderr, "%s doesn't have a primary sponsor" % id
	print '  <edm id="%s" session="%s" ref="%s" title="%s" url="%s" num="%s" date="%s" closed="%s">' % (id, edms[id]['session'], edms[id]['ref'], edms[id]['title'], edms[id]['url'], edms[id]['num'], 'date' in edms[id] and edms[id]['date'] or 'Unknown', 'closed' in edms[id] and edms[id]['closed'] or '')
	if 'text' in edms[id]:
		print '    <text>%s</text>' % edms[id]['text']
	for s in signers[id]:
		print '    <signature id="%s" type="%s" date="%s" pos="%s" />' % (s[0], s[1], s[2], s[3])
	print '  </edm>'

assert False

matcher = '<!-- Ref -->\s*<TD WIDTH=14>[^C]*?(Closed)?[^C]*?</TD>\s*'
matcher += '<TD ALIGN="CENTER" VALIGN="TOP">\s*<font face="arial,helvetica" size=2><FONT SIZE="-1">\s*<B>(.*?)</B>\s*</FONT>\s*</TD>\s*'
matcher += '<!-- Motion Title -->\s*<TD ALIGN="LEFT" VALIGN="TOP">\s*<font face="arial,helvetica" size=2><FONT COLOR="#0000DD">\s*<A HREF="(/weblink/html/motion.html/ref=.*?)" TARGET="_top">\s*(.*?)</A>\s*</FONT>\s*</TD>\s*'
matcher += '<!-- Sponsor -->\s*<TD ALIGN="LEFT" VALIGN="TOP">\s*<A HREF="/weblink/html/member.html/mem=.*?" TARGET="_top" >\s*<font face="arial,helvetica" size=2>.*?/.*?</A>\s*</TD>\s*'
matcher += '<!-- Count of signatures -->\s*<TD ALIGN="RIGHT" VALIGN="TOP">\s*<font face="arial,helvetica" size=2><FONT SIZE="-1">(\d+)</FONT>&nbsp;&nbsp;&nbsp;\s*</TD>'

print '''<?xml version="1.0" encoding="ISO-8859-1"?>
<publicwhip>'''
start = 1
edms = 0
while (start==1 or start < edms):
	url = edm_index_cached_url % (start)
	ur = urllib.urlopen(url)
	content = ur.read()
	ur.close()
	if re.search("Not Found(?i)", content):
		url = edm_index_url % (start)
		ur = urllib.urlopen(url)
		content = ur.read()
		ur.close()
		m = re.search('<FRAME SRC="(.*?)" NAME="CONTENT"', content)
		url = urlparse.urljoin(url, m.group(1))
		ur = urllib.urlopen(url)
		content = ur.read()
		ur.close()
	if re.search("Not Found(?i)", content):
		raise Exception, "Failed to get content in url %s" % url
	if not edms:
		m = re.search('<FONT SIZE=-1>(\d+) EDMs and Amendments',content)
		edms = int(m.group(1))
	matches = re.findall(matcher, content)
	for (closed, ref, title_url, title, num) in matches:
		content = get_printable(session, ref)
		m = re.search('<TD COLSPAN="2"><font face="arial,helvetica" size=2>\s*(.*?)</TD>', content)
		motiontext = m.group(1)
		print '  <edm ref="%s" title="%s" number="%s" url="%s" closed="%s">' % (ref, title, num, title_url, closed)
		print '    <text>%s</text>' % motiontext
		print '  </edm>'
	start += 50
	assert False

sys.stdout.flush()

print '</publicwhip>'

