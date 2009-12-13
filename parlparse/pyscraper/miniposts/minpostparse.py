# vim:sw=8:ts=8:et:nowrap

# to do:
# Fill in the 2003-2004 gap


import os
import datetime
import re
import sys
import urllib
import string
import tempfile
import xml.sax
import shutil

import miscfuncs
import difflib
import mx.DateTime
from resolvemembernames import memberList
from resolvelordsnames import lordsList

from xmlfilewrite import WriteXMLHeader

toppath = miscfuncs.toppath
pwcmdirs = miscfuncs.pwcmdirs
chggdir = os.path.join(pwcmdirs, "chgpages")
chgtmp = tempfile.mktemp(".xml", "pw-chgtemp-", miscfuncs.tmppath)

membersdir = os.path.normpath(os.path.abspath(os.path.join("..", "members")))
ministersxml = os.path.join(membersdir, "ministers.xml")
peoplexml = os.path.join(membersdir, "people.xml") # generated from ministers.xml by personsets.py
   # used in


uniqgovposns = ["Prime Minister",
				"Chancellor of the Exchequer",
				"Lord Steward",
				"Treasurer of Her Majesty's Household",
				"Chancellor of the Duchy of Lancaster",
				"President of the Council",
                                "Lord President of the Council",
				"Parliamentary Secretary to the Treasury",
				"Second Church Estates Commissioner",
				"Chief Secretary",
				"Advocate General for Scotland",
				"Deputy Chief Whip (House of Lords)",
				"Vice Chamberlain",
				"Attorney General",
				"Chief Whip (House of Lords)",
				"Lord Privy Seal",
				"Solicitor General",
				"Economic Secretary",
				"Financial Secretary",
				"Lord Chamberlain",
				"Comptroller",
				"Deputy Prime Minister",
				"Paymaster General",
				"Master of the Horse",
                                "Lord Chancellor",
                                "Deputy Leader of the House of Lords",
				]

govposns = ["Secretary of State",
			"Minister without Portfolio",
			"Minister of State",
			"Parliamentary Secretary",
			"Parliamentary Under-Secretary",
			"Parliamentary Under-Secretary of State",
			"Assistant Whip",
			"Lords Commissioner",
			"Lords in Waiting",
			"Baronesses in Waiting",
			 ]

govdepts = ["Department of Health",
			"HM Treasury",
			"HM Household",
			"Home Office",
			"Cabinet Office",
			"Privy Council Office",
                                "Ministry of Defence",
                                "Department for Environment, Food and Rural Affairs",
                                "Department for Energy and Climate Change", # DEFECATE
                                        "Department of Energy and Climate Change",
                                "Department for International Development",
                                "Department for Culture, Media & Sport",
                                        "Department for Culture, Media and Sport",
                                "Department for Constitutional Affairs",
                                "Department for Education and Skills",
                                        "Department for Children, Schools and Families",
                                        "Department for Innovation, Universities and Skills",
                                "Office of the Deputy Prime Minister",
                                "Deputy Prime Minister",
                                "Department for Transport",
                                "Department for Work and Pensions",
                                "Northern Ireland Office",
                                "Law Officers' Department",
                                "Law Officers",
                                "Attorney General's Office",
                                "Office of the Advocate General for Scotland",
                                "Department of Trade and Industry",
                                        "Department for Business, Enterprise & Regulatory Reform",
                                        "Department for Business, Innovation & Skills",
                                        "Department for Business, Innovation and Skills",
                                "House of Commons",
                                "House of Lords",
                                "Foreign & Commonwealth Office",
                                        "Foreign and Commonwealth Office",
                                "Government Equalities Office",

                                "Office of the Secretary of State for Wales",
                                "Department for Productivity, Energy and Industry",
                                "Scotland Office",
                                "Wales Office",
                                "Department for Communities and Local Government",
                                "Ministry of Justice",
                                "No Department",
                                "Regional Affairs",
                                ]


ppsdepts = govdepts + [ "Minister Without Portfolio",
				"Minister without Portfolio",
				"Minister without Portfolio and Party Chair",
				"Prime Minister",
				"Prime Minister's Office",
				"Leader of the House of Commons",
                                "Leader of the House of Lords",
				]
ppsnondepts = [ "HM Official Opposition", "Leader of the Opposition" ]

import newlabministers2003_10_15
from newlabministers2003_10_15 import opendate

renampos = re.compile("""<td>\s*<(?:b|strong)>
        ([^,]*),	# last name
        \s*
        ([^<\(]*?)	# first name
        \s*
        (?:\(([^)]*)\))? # constituency
        </(?:b|strong)></td><td>
        ([^,<]*)	# position
        (?:,\s*([^<]*))? # department
        (?:</td>)?\s*$(?i)""",
        re.X)

bigarray = {}
# bigarray2 = {}

def ApplyPatches(filein, fileout, patchfile):
	shutil.copyfile(filein, fileout)
	status = os.system("patch --quiet %s <%s" % (fileout, patchfile))
	if status == 0:
		return True
	print "blanking out failed patch %s" % patchfile
	print "---- This should not happen, therefore assert!"
	assert False

# do the xml thing
def WriteXML(moffice, fout):
        fout.write('<moffice id="%s" name="%s"' % (moffice.moffid, moffice.fullname))  # should be cleaning up here, but aren't
        if moffice.matchid:
                fout.write(' matchid="%s"' % moffice.matchid)

        # more runtime cleaning up of the xml rather than using a proper function
        fout.write(' dept="%s" position="%s"' % (moffice.dept.replace("&", "&amp;"), moffice.pos.replace('&', '&amp;')))
        if moffice.responsibility:
                fout.write(' responsibility="%s"' % re.sub("&", "&amp;", moffice.responsibility))

        fout.write(' fromdate="%s"' % moffice.sdatestart)

        if moffice.bopen:
                fout.write(' todate="%s"' % "9999-12-31")
        else:
                fout.write(' todate="%s"' % moffice.sdateend)

        fout.write(' source="%s">' % moffice.sourcedoc)
        fout.write('</moffice>\n')




class protooffice:
	def __init__(self):
		pass

	def SelCteeproto(self, lsdatet, name, cons, committee):
		self.sdatet = lsdatet
		self.sourcedoc = "chgpages/selctee"
		self.dept = committee
		if not re.search("Committee", committee):
			self.dept += " Committee"
		self.pos = ""
		self.responsibility = ""
		if re.search("\(Chairman\)", name):
			self.pos = "Chairman"
		name = re.sub(" \(Chairman\)?$", "", name)

		self.fullname = re.sub("^Mrs? ", "", name).strip()
		# Why doesn't this work with an accent?

		if re.match("Si..?n Simon$", self.fullname):
			self.fullname = "Sion Simon"
		if re.match("Si..?n C\. James$", self.fullname):
			self.fullname = "Sian C James"
		if re.match("Lembit ..?pik$", self.fullname):
			self.fullname = "Lembit Opik"
#		if re.match("Anne Picking$", self.fullname):
#			self.fullname = "Anne Moffat"
                self.cons = re.sub("&amp;", "&", cons)
                # Or this?
                if re.match("Ynys M(..?|&#244;)n", cons):
                        self.cons = "Ynys Mon"

	def PPSproto(self, lsdatet, name, master, dept):
		self.sdatet = lsdatet
		self.sourcedoc = "chgpages/privsec"

                name = re.sub('\s+', ' ', name)
		nameMatch = re.match('(.*?)(?:\s*\(([^)]*)\))?\s*$', name)
		self.fullname = nameMatch.group(1).strip()
		self.fullname = re.sub("^Mr?s? ", "", self.fullname)
		if re.match("Si..?n Simon$", self.fullname):
			self.fullname = "Sion Simon"
		if re.match("Si..?n C\. ?James$", self.fullname):
			self.fullname = "Sian C James"
                self.cons = None
                if nameMatch.group(2):
		        self.cons = re.sub("&amp;", "&", nameMatch.group(2))
                if self.fullname == 'Angela E. Smith':
                        self.cons = 'Basildon'

		# map down to the department for this record
		self.pos = "PPS"
		master = re.sub('\s+,', ',', master)
		self.responsibility = master
		if dept == "Prime Minister":
			dept = "Prime Minister's Office"
		self.dept = dept


	def GovPostproto(self, lsdatet, e, deptno):  # department number to extract multiple departments
		self.sdatet = lsdatet
		self.sourcedoc = "chgpages/govposts"

		nampos = renampos.match(e)
		if not nampos:
			raise Exception, "renampos didn't match: '%s'" % e
		self.lasname = nampos.group(1)
		self.froname = nampos.group(2)
		self.cons = nampos.group(3)
		if self.cons == 'MP for Worcester':
			self.cons = None # Can only be one

		self.froname = re.sub("^Rt Hon\s+|^Mrs?\s+", "", self.froname)
		self.froname = re.sub("\s+(?:QC|[GKDCOM]BE)?$", "", self.froname)
		self.lasname = re.sub("\s+(?:QC|[GKDCOM]BE)?$", "", self.lasname)
		self.fullname = "%s %s" % (self.froname, self.lasname)

		# sometimes a bracket of constituency gets through, when the name hasn't been reversed
		mbrackinfullname = re.search("([^\(]*?)\s*\(([^\)]*)\)$", self.fullname)
		if mbrackinfullname:
			self.fullname = mbrackinfullname.group(1)
			assert not self.cons
			self.cons = mbrackinfullname.group(2)

		if re.match("Si..?n Simon$", self.fullname):
			self.fullname = "Sion Simon"

		# special Gareth Thomas match
		if self.fullname == "Gareth Thomas" and (
                (self.sdatet[0] >= '2004-04-16' and self.sdatet[0] <=
                '2004-09-20') or (self.sdatet[0] >= '2005-05-17')):
			self.cons = "Harrow West"

		if self.cons == "Worcs.":
                        self.cons = None # helps make the stick-chain work

		if self.fullname == "Lord Bach of Lutterworth":
			self.fullname = "Lord Bach"

		# special matches for people who were listed before they became Lords
		if self.fullname == "Andrew Adonis" and self.sdatet[0][:7] == "2005-05":
			self.fullname = "Lord Adonis"
                if self.fullname == 'Stephen Carter':
                        self.fullname = 'Lord Carter of Barnes'
                if self.fullname == 'Peter Mandelson':
                        self.fullname = 'Lord Mandelson'
                if self.fullname == 'Paul Myners':
                        self.fullname = 'Lord Myners'

                if self.fullname == 'Admiral Sir Alan West':
                        self.fullname = 'Lord West of Spithead'
                if self.fullname == 'Sir Mark Malloch Brown':
                        self.fullname = 'Lord Malloch-Brown'
                if self.fullname == 'Sir Digby Jones' or self.fullname == 'Digby, Lord Jones of Birmingham':
                        self.fullname = 'Lord Jones of Birmingham'
                if self.fullname == 'Professor Sir Ara Darzi':
                        self.fullname = 'Lord Darzi of Denham'
                if self.fullname == 'Shriti Vadera':
                        self.fullname = 'Baroness Vadera'
                if self.fullname == 'Glenys Kinnock':
                        self.fullname = 'Baroness Kinnock of Holyhead'

                # Okay, name done, let's move on to position

		pos = nampos.group(4).strip()
                pos = re.sub("\s+", " ", pos)
		dept = (nampos.group(5) or "No Department").strip()
                dept = re.sub("\s+", " ", dept)
                dept = dept.replace('&amp;', '&')
		responsibility = ""
		if self.sdatet[0] in bigarray and self.fullname in bigarray[self.sdatet[0]]:
			responsibility = bigarray[self.sdatet[0]][self.fullname]

		# change of wording in 2004-11
		if re.match("Leader of the House of Commons", dept):
			dept = dept.replace("Leader of the House of Commons", "House of Commons")
		# change of wording in 2006-04
		if pos == "Lord Commissioner":
			pos = "Lords Commissioner"

		pos = re.sub(' \(Cabinet\)', '', pos)

		# separate out the departments if more than one
		if dept not in govdepts:
			self.depts = None

			# go through and try to match <dept> + " and "
			for gd in govdepts:
				dept0 = dept[:len(gd)]
				if (gd == dept0) and (dept[len(gd):len(gd) + 5] == " and "):
					dept1 = dept[len(gd) + 5:]

					# we're trying to split these strings up, but it's pretty rigid
					if dept1 in govdepts:
						self.depts = [ (pos, dept0), (pos, dept1) ]
						break
                                        # The below is only for Deputy Leader of the House of Lords currently, as they're also MoS in DefECate
					if dept1 in uniqgovposns and dept1 == 'Deputy Leader of the House of Lords':
						self.depts = [ (pos, dept0), (dept1, 'House of Lords') ]
						break
					pd1 = re.match("([^,]+)(?:,| in the)\s*(.+)$", dept1)
					if pd1 and pd1.group(2) in govdepts:
						self.depts = [ (pos, dept0), (pd1.group(1), pd1.group(2)) ]
						break
					pd1 = re.match("([^,]+) and (.+)$", dept1)
					if pd1 and pd1.group(2) in govdepts:
						self.depts = [ (pos, dept0), (pos, pd1.group(1)), (pos, pd1.group(2)) ]
						break
					print "Attempted match on", dept0

			if not self.depts:
				print "\n***No match for department: '%s'\n" % dept

		else:
			self.depts = [ (pos, dept) ]


		# map down to the department for this record
		self.pos = self.depts[deptno][0]
		self.responsibility = responsibility
		self.dept = self.depts[deptno][1]


	def OffOppproto(self, lsdatet, name, pos, dept, responsibility, sourcedoc):
		self.sdatet = lsdatet
		self.sourcedoc = sourcedoc
		name = re.sub("^Rt Hon\s+|^Mrs?\s+", "", name)
		name = re.sub("\s+(?:QC|[GKDCOM]BE)?$", "", name)
		self.fullname = name
                self.cons = None
		self.depts = [ (pos, dept) ]
		self.pos = pos.replace('&amp;', '&')
		self.dept = dept.replace('&amp;', '&') # XXX
		self.responsibility = responsibility.replace('&amp;', '&')

        def __str__(self):
                return '%s, %s, %s, %s, %s, %s, %s, %s, %s' % (
                        self.fullname, self.pos, self.dept, self.responsibility, self.sdatet, self.sourcedoc,
                        hasattr(self, 'sdateend') and self.sdateend or '', hasattr(self, 'stimeend') and self.stimeend or '',
                        hasattr(self, 'bopen') and self.bopen or ''
                )

	# turns the protooffice into a part of a chain
	def SetChainFront(self, fn, bfrontopen):
		if bfrontopen:
			(self.sdatestart, self.stimestart) = (opendate, None)
		else:
			(self.sdatestart, self.stimestart) = self.sdatet

		(self.sdateend, self.stimeend) = self.sdatet
		self.fn = fn
		self.bopen = True

	def SetChainBack(self, sdatet):
		self.sdatet = sdatet
		(self.sdateend, self.stimeend) = self.sdatet  # when we close it, it brings it up to the day the file changed
		self.bopen = False

	# this helps us chain the offices
	def StickChain(self, nextrec, fn):
		if (self.sdateend, self.stimeend) >= nextrec.sdatet:
			print "\n\n *** datetime not incrementing\n\n"
			print self.sdateend, self.stimeend, nextrec.sdatet
			print fn
			assert False
		assert self.bopen

		if (self.fullname, self.dept, self.pos, self.responsibility) == (nextrec.fullname, nextrec.dept, nextrec.pos, nextrec.responsibility):
			consCheckA = self.cons
			if consCheckA:
				consCheckA = memberList.canonicalcons(consCheckA, self.sdateend)
			consCheckB = nextrec.cons
			if consCheckB:
				consCheckB = memberList.canonicalcons(consCheckB, nextrec.sdatet[0])
			if consCheckA != consCheckB:
				raise Exception, "Mismatched cons name %s %s" % (self.cons, nextrec.cons)
			(self.sdateend, self.stimeend) = nextrec.sdatet
			self.fn = fn
			return True
		return False


def SpecMins(regex, fr, sdate):
        a = re.findall(regex, fr)
        for i in a:
                specpost = i[0]
                if len(i)==3:
                        specname = i[2]
                        if i[1]:
                                specpost = "%s; %s" % (specpost, i[1])
                else:
                        specname = i[1]
                specname = re.sub("^\s+", "", specname)
                specname = re.sub("\s+$", "", specname)
                nremadename = specname
                nremadename = re.sub("^Rt Hon ", "", nremadename)
                if not re.search("Duke |Lord |Baroness ", specname):
                        nremadename = re.sub("\s+MP$", "", nremadename)
                        nremadename = re.sub("^Mrs?\s+", "", nremadename)
                        nremadename = re.sub(" [GKDCOM]BE$", "", nremadename)
                bigarray.setdefault(sdate, {})
                if specpost == "Universitites":
                        specpost = "Universities"
                bigarray[sdate][nremadename] = specpost


def ParseSelCteePage(fr, gp):
        m = re.search('selctee(\d+)_(.*?)\.html', gp)
        (num, filedate) = m.groups()
        num = int(num)

        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

        if num <=76:
                frupdated = re.search('<td class="lastupdated">\s*Updated (.*?)(?:&nbsp;| )(.*?)\s*</td>', fr)
                lsudate = re.match("(\d\d)/(\d\d)/(\d\d\d\d)$", frupdated.group(1))
                if lsudate:
                    sudate = "%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
                else:
                    lsudate = re.match("(\d\d)/(\d\d)/(\d\d)$", frupdated.group(1))
                    y2k = int(lsudate.group(3)) < 50 and "20" or "19"
                    sudate = "%s%s-%s-%s" % (y2k, lsudate.group(3), lsudate.group(2), lsudate.group(1))
        elif num <= 133:
        	frdate = re.search("Select Committee\s+Membership at\s+(.*?)\s*<(?i)", fr)
                sudate = mx.DateTime.DateTimeFrom(frdate.group(1)).date
        else:
                frdate = re.search(">This list was last updated on\s+<(?:b|strong)>\s*(.*?)\s*<", fr)
                sudate = mx.DateTime.DateTimeFrom(frdate.group(1)).date

        sdate = sudate
        res = [ ]

        committees = re.findall("<a\s+href=(?:'|\")(?:http://hcl2\.hclibrary/sections/hcio/mmc/selcom\.asp)?#\d+(?:'|\")>(.*?)</a></I>", fr, re.I | re.S)
        committees = map(lambda x: re.sub("\s+", " ", x).replace("&amp;", "&"), committees)
        found = { }

        # Dupe causes issues
        fr = re.sub('(<tr><td>Martin Salter</td><td>Reading West</td><td>Labour</td></tr>){2}', r'\1', fr)

        fr = re.sub('</tr>\s*<td', '</tr><tr><td', fr)
        # XXX: This is slow, speed it up!
        list = re.findall("<tr>\s*<td (?:colspan='3' bgcolor='#F1ECE4'|bgcolor=#f1ece4 colSpan=3)(?: height=\"\d+\")?>(?:<(?:b|strong)>)?<font size=\+1>(?:<(?:b|strong)>)?(?:<I>)?<A\s+NAME='?\d+'?></a>\s*([^<]*?)\s*(?:</(?:b|strong)>)?</font>.*?</tr>\s*((?:<tr>\s*<td(?: height=\"19\")?>.*?</td>\s*<td(?: height=\"19\")?>.*?</td>\s*<td(?: height=\"19\")?>.*?</td>\s*</tr>\s*|<tr><td colspan='3'><(?:b|strong)>Appointed[^<]*?</(?:b|strong)></td></tr>\s*|<tr><td colspan=2>.*?</td><td>.*?</td></tr>\s*)+)<tr>\s*<td colspan='?3'?(?: height=\"19\")?>(?:&nbsp;?|\xc2\xa0)</td>\s*</tr>", fr, re.I | re.S)
        for committee in list:
                cteename = re.sub("\s+", " ", committee[0]).replace("&amp;", "&")
                members = committee[1]
                if cteename not in committees:
                        pass # print "Committee title not in list: ", cteename
                else:
                        found[cteename] = 1
                for member in re.findall("<tr>\s*<td(?: height=\"19\")?>\s*(.*?)\s*</td>\s*<td(?: height=\"19\")?>\s*(.*?)\s*</td>\s*<td(?: height=\"19\")?>\s*(.*?)\s*</td>\s*</tr>(?i)", members):
                        name = member[0]
                        const = member[1]
                        party = member[2]
                        ec = protooffice()
                        ec.SelCteeproto((sdate, stime), name, const, cteename)
                        res.append(ec)
                if num>=181 and num<=185 and cteename=='Communities and Local Government':
                        ec = protooffice()
                        ec.SelCteeproto((sdate, stime), 'Mr Greg Hands', 'Hammersmith & Fulham', 'Communities and Local Government')
                        res.append(ec)
        for i in committees:
                if i not in found:
                        print "Argh:", gp, i

        return (sdate, stime), res

def ParseGovPostsPage(fr, gp):
        m = re.search('govposts(\d+)_(\d+-\d+-\d+)', gp)
        (num, filedate) = m.groups()
        num = int(num)

        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

        if (num >= 36 and num <= 38) or (num >= 106 and num <= 110) or num == 141 or num == 176:
                return "SKIPTHIS", None # Reshuffling
        elif num == 39:
                sdate = "2006-05-08" # Moved back to date of reshuffle
        elif num == 111:
                sdate = '2007-06-28' # Moved back to date of reshuffle
        elif num <= 67:
                frupdated = re.search('<td class="lastupdated">\s*Updated (.*?)(?:&nbsp;| )(.*?)\s*</td>', fr)
                if not frupdated:
                    print "Failed to find lastupdated on:", gp
                lsudate = re.match("(\d\d)/(\d\d)/(\d\d\d\d)$", frupdated.group(1))
                if lsudate:
                    sdate = "%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
                else:
                    lsudate = re.match("(\d\d)/(\d\d)/(\d\d)$", frupdated.group(1))
                    y2k = int(lsudate.group(3)) < 50 and "20" or "19"  # I don't think our records go back far enough to merit this!
                    sdate = "%s%s-%s-%s" % (y2k, lsudate.group(3), lsudate.group(2), lsudate.group(1))
        elif num <= 97:
                frdate = re.search(">Her Majesty's Government at\s+(.*?)\s*<", fr)
                sdate = mx.DateTime.DateTimeFrom(frdate.group(1)).date
        else:
                frdate = re.search(">This list was last updated on\s+<(?:b|strong)>\s*(.*?)\s+<", fr)
                sdate = mx.DateTime.DateTimeFrom(frdate.group(1)).date

        # extract special Ministers of State and PUSes
        namebit = "<td valign='TOP'>(.*?)(?:\s+\[.*?\])?</td>"
        alsobit = "(?:[-\s]+\(?also .*?\)?|[;:] (Minister for .*?))?"
        SpecMins("<TR><td width='400'><b>Minister of State \((.*?)\)</b></td>%s" % namebit, fr, sdate)
        SpecMins("<TR><td width='400'>- Mini?ster of State \(([^)]*?)\)%s</TD>%s" % (alsobit, namebit), fr, sdate)
        SpecMins("<tr><td>- Minister of State \((.*?)\)?%s</td>%s" % (alsobit, namebit), fr, sdate)
        SpecMins("<TR><td width='400'>- Minister (?:of State )?for (.*?)%s</TD>%s" % (alsobit, namebit), fr, sdate)
        SpecMins("<tr><td>- Minister for (.*?)</td>%s" % namebit, fr, sdate)
        SpecMins("<TR><td width='400'><B>Minister of (.*?)</B>%s" % namebit, fr, sdate)
        SpecMins("<TR><td width='400'>- Parliamentary Under-Secretary (?:of state )?(?:for )?\(?(.*?)\)?%s</TD>%s(?i)" % (alsobit, namebit), fr, sdate)

        # Fixes
        if num>=169:
                fr = re.sub('Parliamentary Under-Secretary and Department for Culture, Media & Sport', 'Parliamentary Under-Secretary, Department for Culture, Media & Sport', fr)
        if num>=177:
                fr = re.sub('Foreign, Foreign', 'Foreign', fr)
        if num>=197:
                fr = re.sub('Parliamentary Under Secretary', 'Parliamentary Under-Secretary', fr)
                fr = re.sub('Culture,\s+Media and Sports', 'Culture, Media & Sport', fr)

	# extract the alphabetical list
        Malphl = re.search("ALPHABETICAL LIST OF HM GOVERNMENT([\s\S]*?)</table>", fr)
        if not Malphl:
                print "ALPHABETICAL LIST not found in file:" , gp
        alphl = Malphl.group(1)
	lst = re.split("</?tr>(?i)", alphl)

	# match the name form on each entry
	#<TD><B>Abercorn, Duke of</B></TD><TD>Lord Steward, HM Household</TD>

	res = [ ]

	luniqgov = uniqgovposns[:]
	for e1 in lst:
		e = e1.strip()
		if re.match("(?:<[^<]*>|\s)*$", e):
			continue

		# multiple entry of departments (simple inefficient method)
		for deptno in range(3):  # at most 3 offices at a time, we'll handle
			ec = protooffice()
			ec.GovPostproto((sdate, stime), e, deptno)

			# prove we've got all the posts
			if ec.pos not in govposns:
				if ec.pos in luniqgov:
					luniqgov.remove(ec.pos)
				else:
					print "Unaccounted govt position", ec.pos

			res.append(ec)

			if len(ec.depts) == deptno + 1:
				break

	return (sdate, stime), res

#	<td class="lastupdated">
#		Updated 16/12/04&nbsp;16:31
#	</td>

def ParsePrivSecPage(fr, gp):
        m = re.search('privsec(\d+)_(.*?)\.html', gp)
        (num, filedate) = m.groups()
        num = int(num)

        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

        if num == 17:
                sdate = '2006-01-13'
        elif num == 25:
                sdate = '2006-05-08' # Reshuffle
        elif num == 30:
                sdate = '2006-06-22'
        elif num == 41:
                sdate = '2006-09-06'
        elif num == 43 or num == 44:
                return "SKIPTHIS", None
        elif num == 63:
                return "SKIPTHIS", None # Brown shuffle
        elif num == 64:
                sdate = '2007-06-28' # Brown shuffle
        elif num >= 102:
                return ('2009-01-16', stime), []
        elif num <= 48:
                frupdated = re.search('<td class="lastupdated">\s*Updated (.*?)(?:&nbsp;| )(.*?)\s*</td>', fr)
                if not frupdated:
                        print "failed to find lastupdated in", gp
                lsudate = re.match("(\d\d)/(\d\d)/(\d\d\d\d)$", frupdated.group(1))
                if lsudate:
                    sudate = "%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
                else:
                    lsudate = re.match("(\d\d)/(\d\d)/(\d\d)$", frupdated.group(1))
	            y2k = int(lsudate.group(3)) < 50 and "20" or "19"
	            sudate = "%s%s-%s-%s" % (y2k, lsudate.group(3), lsudate.group(2), lsudate.group(1))

	        sdate = sudate
        elif num <= 57:
	        sdate = filedate
        else:
                frdate = re.search(">This list was last updated on\s+<(?:b|strong)>\s*(.*?)\s*<(?i)", fr)
                msdate = mx.DateTime.DateTimeFrom(frdate.group(1)).date
                sdate = msdate

	res = [ ]
        if num < 96:
            start = '<font[^>]*><b>Attorney-General see </b>\s*Law\s+Officers Department</font>'
        else:
            start = '<b>(?:<font[^>]*>)?(?:<a name="Department"></a>)?HM Government(?:</font>)?</b>'
        Mppstext = re.search('(?i)<tr>\s*<td[^>]*>%s</td>\s*</tr>([\s\S]*?)</table>' % start, fr)

        # skip over a holding page that says the PPSs are not sorted out right after the reshuffle
        if re.search('Following the reshuffle', fr):
                assert not Mppstext
                return "SKIPTHIS", None

        if not Mppstext:
                print gp
                #print fr
        ppstext = Mppstext.group(1)
	ppslst = re.split("</?tr>(?i)", ppstext)

	# match the name form on each entry
	#<TD><B>Abercorn, Duke of</B></TD><TD>Lord Steward, HM Household</TD>

	luniqgov = uniqgovposns[:]
	deptname = None
	ministername = None
	for e1 in ppslst:
		e = e1.strip()
		if re.match("(?:<[^<]*>|\s|&nbsp;)*$", e):
			continue
		deptMatch = re.match('\s*<td[^>]*>(?:<font[^>]*>|<b>){2,}([^<]*)(?:(?:</b>|</font>){2,}</td>)?\s*$(?i)', e1)
		if deptMatch:
			deptname = re.sub("&amp;", "&", deptMatch.group(1))  # carry forward department name
			deptname = re.sub("\s+", " ", deptname).strip()
                        deptname = re.sub(" \(Team PPSs?\)", "", deptname)
			continue
		nameMatch = re.match("\s*<td[^>]*>\s*([^<]*)</td>\s*<td[^>]*>\s*([^<]*)(?:</td>)?\s*$(?i)", e1)
		if nameMatch.group(1) and nameMatch.group(1) != '&nbsp;': 
			ministername = nameMatch.group(1)  # carry forward minister name (when more than one PPS)
			if ministername == 'Rt Hon Lord Rooker , Minister of State' or \
			    ministername == 'Rt Hon Lord Rooker of Perry Bar , Minister of State':
                                ministername = 'Rt Hon Lord Rooker, Minister of State'

		if re.search('vacant(?i)', nameMatch.group(2)) or re.match('&nbsp;$', nameMatch.group(2)):
			continue

                # Special case, though presumably lots of other PPSs are wrong too :-/
                if re.match('Ms Barbara Keeley', nameMatch.group(2)) and num>=87:
                        continue

		if deptname == "Law Officers Department":
			deptname = "Law Officers' Department"

		if deptname in ppsdepts:
			ec = protooffice()
			ec.PPSproto((sdate, stime), nameMatch.group(2), ministername, deptname)
			res.append(ec)
		else:
			if deptname not in ppsnondepts:
				print "unknown department/post", deptname
				assert False

	return (sdate, stime), res


def titleish(s):
        s = s.title().replace('&Amp;', '&amp;').replace(' And ',' and ').replace(' Of ', ' of ').replace(' The ',' the ').replace('Pps','PPS').replace(' For ',' for ').replace("'S", "'s").strip()
        return s

def ParseOffOppPage(fr, gp):
        m = re.search('offoppose(\d+)_(\d+-\d+-\d+)', gp)
        (num, filedate) = m.groups()
        num = int(num)

        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

        if num == 37 or num == 79:
                return "SKIPTHIS", None # Reshuffling
        #elif num == 38:
        #        sdate = "2005-12-09" # Moved back to date of reshuffle
        #elif num == 80:
        #        sdate = "2007-07-03" # Moved back to date of reshuffle
        elif num <= 64:
                frupdated = re.search('<td class="lastupdated">\s*Updated (.*?)(?:&nbsp;| )(.*?)\s*</td>', fr)
                if not frupdated:
                    print "Failed to find lastupdated on:", gp
                lsudate = re.match("(\d\d)/(\d\d)/(\d\d\d\d)$", frupdated.group(1))
                if lsudate:
                    sdate = "%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
                else:
                    lsudate = re.match("(\d\d)/(\d\d)/(\d\d)$", frupdated.group(1))
                    y2k = int(lsudate.group(3)) < 50 and "20" or "19"  # I don't think our records go back far enough to merit this!
                    sdate = "%s%s-%s-%s" % (y2k, lsudate.group(3), lsudate.group(2), lsudate.group(1))
        elif num <= 75:
                sdate = filedate
        else:
                frdate = re.search(">This list was last updated on\s+<(?:b|strong)>\s*(.*?)\s+<", fr)
                if not frdate:
                        print num, filedate
                        sys.exit()
                sdate = mx.DateTime.DateTimeFrom(frdate.group(1)).date

	# extract the alphabetical list
        if num <= 97:
                table = re.search("(?s)>HER MAJESTY&#39;S OFFICIAL OPPOSITION<(.*?)</table>", fr)
        else:
                table = re.search("(?si)<(?:font|strong)[^>]*>\s*Her Majesty's\s+Official Opposition<(.*?)</table>", fr)
	list = re.split("</?tr>(?i)", table.group(1))

	res = [ ]
        pos = ''
        dept = ''
        inothermins = False
	for row in list:
                cells = re.search('<td colspan="2" bgcolor="#F1ECE4"><strong>(.*?)</strong></td>(?i)', row)
                if cells:
                        dept = cells.group(1)
                        inothermins = False
                        continue

                cells = re.search('<td[^>]*>\s*(.*?)\s*</td>\s*(?:<td[^>]*>\s*(.*?)\s*</td>\s*)?<td[^>]*>\s*(.*?)\s*</td>(?si)', row)
                if not cells:
                        continue
                j = cells.group(1)
                name = cells.group(3)

                responsibility = ''

                if j and j != '&nbsp;':
                        if re.match('\(Also in', j):
                                continue
                        j = re.sub('(?i) \((Lords|Commons)\)', '', j)
                        if (not name or name == '&nbsp;') and not re.search('Shadow Ministers', j):
                                dept = titleish(re.sub('</?(font|b|strong)[^>]*>(?i)', '', j))
                                if re.match('Opposition Whip', dept):
                                        dept = 'Whips'
                                inothermins = False
                                continue
                        j = re.sub('<br>', ' ', j)
                        j = re.sub('</?font[^>]*>', '', j)
                        j = re.sub('&nbsp;|\s+', ' ', j)
                        j = titleish(re.sub('</?(b|strong)>(?i)', '', j))
                        if j=='Whips': j = 'Whip'
                        resp = re.match('(?:- )?Shadow Minister (?:for |\()(.*)', j)
                        if resp and inothermins:
                                responsibility = re.sub('\)$', '', resp.group(1))
                        elif re.match('(Other)?\s*Shadow Ministers?\s*(\(|$)', j):
                                pos = 'Shadow Minister'
                                inothermins = True
                        else:
                                pos = j
                                inothermins = False

                if not name or name == '&nbsp;' or re.search('vacant(?i)', name):
                        continue

                name = re.sub("\s+\((until|also|as from) .*?\)", '', name)
                name = re.sub('\s+', ' ', re.sub('</?b>', '', name.replace('&nbsp;', ' ')))
                name = re.sub('</?font[^>]*>\s*', '', name)
                name = re.sub('Rt Hon the |Professor the |The ', '', name)

                # Don't care about non MP/Lord
                if name == 'Michael Bates' or re.search('Kulveer Ranger', name):
                        continue

                names = re.split('\s*<br>\s*(?i)', name)
                names = [ re.sub('\s*(\*|\*\*|#)$', '', n) for n in names ]

                for name in names:
                        # Done here instead of alias because two Baroness Morrises
                        if name == 'Baroness Morris':
                                name = 'Baroness Morris of Bolton'
		        if re.search('Sayeeda Warsi', name):
                                name = 'Baroness Warsi'
        
		        ec = protooffice()
        		ec.OffOppproto((sdate, stime), name, pos, dept, responsibility, "chgpages/offoppose")
        		res.append(ec)

	return (sdate, stime), res

def ParseNewLibDemPage(fr, gp):
        m = re.search('libdem(\d+)_(\d+-\d+-\d+)', gp)
        (num, filedate) = m.groups()
        num = int(num)
        sdate = filedate
        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

	# extract the alphabetical list
        name = None
        pos = None
        res = []
        rows = re.split('<br/>\s*', fr)
        for row in rows:
                m = re.match('Name: (.*)', row)
                if m:
                        if name and pos:
		                ec = protooffice()
        		        ec.OffOppproto((sdate, stime), name, pos, '', '', "chgpages/libdem") # Just pos should be enough
        		        res.append(ec)
                        name = m.group(1)
                        pos = ''
                        continue
                m = re.match('Role: (.*)', row)
                if m:
                        pos = m.group(1)
        if name and pos:
	        ec = protooffice()
                ec.OffOppproto((sdate, stime), name, pos, '', '', "chgpages/libdem") # Just pos should be enough
                res.append(ec)
	return (sdate, stime), res

def ParseLibDemPage(fr, gp):
        m = re.search('libdem(\d+)_(\d+-\d+-\d+)', gp)
        (num, filedate) = m.groups()
        num = int(num)

        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

        if num <= 45 or num == 58 or num == 59:
                frupdated = re.search('<td class="lastupdated">\s*Updated (.*?)(?:&nbsp;| )(.*?)\s*</td>', fr)
                if not frupdated:
                    print "Failed to find lastupdated on:", gp
                lsudate = re.match("(\d\d)/(\d\d)/(\d\d\d\d)$", frupdated.group(1))
                if lsudate:
                    sdate = "%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
                else:
                    lsudate = re.match("(\d\d)/(\d\d)/(\d\d)$", frupdated.group(1))
                    y2k = int(lsudate.group(3)) < 50 and "20" or "19"  # I don't think our records go back far enough to merit this!
                    sdate = "%s%s-%s-%s" % (y2k, lsudate.group(3), lsudate.group(2), lsudate.group(1))
        elif num <= 54:
                sdate = filedate
        elif num > 82:
                return ParseNewLibDemPage(fr, gp)
        else:
                frdate = re.search(">Th(is|e) list (below )?was last updated\s+on\s+<b>\s*(.*?)\s+<", fr)
                if not frdate:
                        print "A problem was found with", num, filedate
                        sys.exit()
                sdate = mx.DateTime.DateTimeFrom(frdate.group(3)).date

	# extract the alphabetical list
        table = re.search("(?s)>LIBERAL DEMOCRAT PARLIAMENTARY\s+SPOKES(?:MEN|PERSONS)<(.*?)</table>", fr)
	list = re.split("</?tr>(?i)", table.group(1))

	res = [ ]
        pos = ''
        dept = ''
        inothermins = False
	for row in list:
                cells = re.search('<td[^>]*>\s*(.*?)\s*</td>\s*(?:<td[^>]*>\s*(.*?)\s*</td>\s*)?<td[^>]*>\s*(.*?)\s*</td>(?si)', row)
                if not cells:
                        continue
                j = cells.group(1)
                name = cells.group(3)

                responsibility = ''

                if j and j != '&nbsp;':
                        if re.match('\(Also in', j):
                                continue
                        j = re.sub('<br>|&nbsp;', ' ', j)
                        j = re.sub('\s+', ' ', j)
                        j = re.sub('</?(font|em|span)[^>]*>', '', j)
                        boldhead = False
                        if re.search('<b>', j):
                                boldhead = True
                        j = titleish(re.sub('</?b>', '', j))

                        # Department headings in a line on their own, couple of exceptions
                        if (not name or name == '&nbsp;') and not re.search('Shadow Ministers', j) \
                            and not re.search('Spokespersons? In the Lords', j):
                                dept = j
                                if re.match('Whips \((Commons|Lords)\)', dept):
                                        dept = ''
                                inothermins = False
                                continue
                        j = j.replace('Whips', 'Whip')
                        resp = re.match('Shadow Minister for (.*)', j)
                        if resp:
                                responsibility = resp.group(1)
                                pos = 'Shadow Minister'
                        elif re.match('\s*Shadow Ministers?\s*(\(|$)', j):
                                pos = 'Shadow Minister'
                                inothermins = True
                        elif re.match('\s*Spokespersons? In the Lords$', j):
                                pos = 'Spokesperson in the Lords'
                                inothermins = True
                        elif inothermins and not boldhead:
                                responsibility = j
                        else:
                                pos = j
                                inothermins = False

                if not name or name == '&nbsp;' or re.search('vacant(?i)', name) or re.search('to be confirmed(?i)', name):
                        continue

                name = re.sub("\s*\((until|also|as from) .*?\)", '', name)
                name = re.sub('\s+', ' ', re.sub('</?(b|font|span)[^>]*>', '', name.replace('&nbsp;', ' '))).strip()
                name = re.sub('Rt Hon the |Professor the |The ', '', name)

                if name == 'Lord Garden KCB' and num>67: # He died
                        continue

                if re.match('Lord Dholakia &amp;\s+Lord Wallace of Saltaire \(shared position\)', name):
                        name = 'Lord Dholakia<br>Lord Wallace of Saltaire'
                names = re.split('\s*<br>\s*(?i)', name)
                names = [ re.sub('^#', '', re.sub('\s*(\*|\*\*|#)+$', '', n)) for n in names ]

                for name in names:
                        # Done here instead of alias because two Baroness Morrises
                        if name == 'Baroness Morris':
                                name = 'Baroness Morris of Bolton'
		        if re.search('Sayeeda Warsi', name):
                                name = 'Baroness Warsi'
        
		        ec = protooffice()
        		ec.OffOppproto((sdate, stime), name, pos, dept, responsibility, "chgpages/libdem")
        		res.append(ec)

	return (sdate, stime), res


def ParsePlaidSNPPage(fr, gp):
        m = re.search('plaidsnp(\d+)_(\d+-\d+-\d+)', gp)
        (num, filedate) = m.groups()
        num = int(num)

        stime = '%02d:%02d' % (num/60, num%60) # Will break at 86,400 :)

        if num == 8:
                return "SKIPTHIS", None # Just shows constituencies
        elif num <= 28:
                frupdated = re.search('<td class="lastupdated">\s*Updated (.*?)(?:&nbsp;| )(.*?)\s*</td>', fr)
                if not frupdated:
                    print "Failed to find lastupdated on:", gp
                lsudate = re.match("(\d\d)/(\d\d)/(\d\d\d\d)$", frupdated.group(1))
                if lsudate:
                    sdate = "%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
                else:
                    lsudate = re.match("(\d\d)/(\d\d)/(\d\d)$", frupdated.group(1))
                    sdate = "20%s-%s-%s" % (lsudate.group(3), lsudate.group(2), lsudate.group(1))
        else:
                frdate = re.search(">This list was last updated on\s+<(?:b|strong)>\s*(.*?)\s+<(?i)", fr)
                if not frdate:
                        print "A problem was found with", num, filedate
                        sys.exit()
                sdate = mx.DateTime.DateTimeFrom(frdate.group(1)).date

	# extract the alphabetical list
        table = re.search('(?is)<(?:b|strong)>Plaid Cymru</(?:b|strong)>(.*?)</table>', fr).group(1)
	res = [ ]

        whips = re.findall('(?s)Joint Chief Whips are\s+(.*?) and (.*?)\.?\s*<', table)
        for entry in whips:
                for name in entry[0], entry[1]:
        	        name = re.sub(' MP$', '', re.sub('\s+', ' ', name))
        	        ec = protooffice()
                        ec.OffOppproto((sdate, stime), name, 'Chief Whip', '', '', "chgpages/plaidsnp")
                        res.append(ec)

        list = re.findall('(?i)<tr>\s*<td>([^<]*?)(?:</td>)?\s*<td>([^<]*?)(?:</td></tr>|(?=<tr>))', table)
        for row in list:
                resps = re.sub('\s+', ' ', re.sub('&nbsp;', ' ', row[0])).strip()
                name = row[1].strip()
                name = re.sub(' MP$', '', re.sub('\s+', ' ', name))
                if not name or name == '&nbsp;':
                        continue
                ec = protooffice()
                if re.match('Parliamentary Leader', resps):
                        ec.OffOppproto((sdate, stime), name, resps, '', '', 'chgpages/plaidsnp')
                else:
                        ec.OffOppproto((sdate, stime), name, 'Spokesperson', '', resps, 'chgpages/plaidsnp')
                res.append(ec)

	return (sdate, stime), res

def ParseDUPPage(fr, gp):
        fp = open(membersdir + '../rawdata/dup_parl.bsv')
        stime = 0
        res = []
        for line in fp:
                if re.match('\d{4}-\d\d-\d\d', line):
                        sdate = line.strip()
                        stime = stime + 1
                        continue
                position, name = line.split('|')
                ec = protooffice()
                ec.OffOppproto((sdate, stime), name, position, '', '', 'chgpages/dup')
                res.append(ec)
        fp.close()
        return (sdate, stime), res

# this goes through all the files and chains positions together
def ParseChggdir(chgdirname, ParsePage, bfrontopenchains):
	fchgdir = os.path.join(chggdir, chgdirname)

	gps = os.listdir(fchgdir)
	gps = [ x for x in gps if re.match(".*\.html$", x) ]
	gps.sort() # important to do in order of date

	chainprotos = [ ]
	sdatetlist = [ ]
	sdatetprev = ("1997-05-01", "")
	for gp in gps:
                filename = gp
		patchfile = '%s/patches/chgpages/%s/%s.patch' % (toppath, chgdirname, gp)
		if os.path.isfile(patchfile):
			patchtempfilename = tempfile.mktemp("", "min-applypatchtemp-", '%s/tmp/' % toppath)
			ApplyPatches(os.path.join(fchgdir, filename), patchtempfilename, patchfile)
			filename = patchtempfilename
		f = open(os.path.join(fchgdir, filename))
		fr = f.read()
		f.close()

		# get the protooffices from this file
                fr = fr.replace('\xc2\xa0', '&nbsp;')
		sdatet, proff = ParsePage(fr, os.path.join(fchgdir, gp))
		if sdatet == "SKIPTHIS":
			continue

		# all PPSs and committee memberships get cancelled when cross the general election.
		if (chgdirname == 'privsec' or chgdirname == 'selctee') and sdatet[0] > "2005-05-01" and sdatetprev[0] < "2005-05-01":
			genelectioncuttoff = ("2005-04-11", "00:01")
			#print "genelectioncuttoffgenelectioncuttoff", chgdirname

			# close the chains that have not been stuck
			for chainproto in chainprotos:
				if chainproto.bopen:
					chainproto.SetChainBack(genelectioncuttoff)


		# stick any chains we can
		proffnew = [ ]
		lsxfromincomplete = ((not chainprotos) and ' fromdateincomplete="yes"') or ''
		for prof in proff:
			bstuck = False
			for chainproto in chainprotos:
				if chainproto.bopen and (chainproto.fn != gp) and chainproto.StickChain(prof, gp):
					assert not bstuck
					bstuck = True
			if not bstuck:
				proffnew.append(prof)

		# close the chains that have not been stuck
		for chainproto in chainprotos:
			if chainproto.bopen and (chainproto.fn != gp):
				chainproto.SetChainBack(sdatet)
				#print "closing", chainproto.lasname, chainproto.sdatet

		# append on the new chains
		bfrontopen = bfrontopenchains and not chainprotos
		for prof in proffnew:
			prof.SetChainFront(gp, bfrontopen)
			chainprotos.append(prof)

		# list of all the times scraping has been made
		sdatetlist.append((sdatet[0], chgdirname))

		sdatetprev = sdatet

	# no need to close off the running cases with year 9999, because it's done in the writexml
	return chainprotos, sdatetlist

# endeavour to get an id into all the names
def SetNameMatch(cp, cpsdates, mpidmap):
	cp.matchid = ""

	# don't match names that are in the lords
        if cp.fullname == 'Dame Marion Roe DBE':
                cp.fullname = 'Marion Roe'
        if cp.fullname == 'Jamie, Earl of Mar and Kellie':
                cp.fullname = 'Earl of Mar and Kellie'
	if not re.search("Duke |Lord |Baroness |Dame |^Earl |Viscount ", cp.fullname):
		fullname = cp.fullname
		cons = cp.cons
                if fullname == "Michael Foster" and not cons:
                        if cpsdates[0] in ["2006-05-08", "2006-05-09", "2006-05-10", "2006-05-11", "2008-10-06"]:
                                cons = "Worcester"   # this Michael Foster had been a PPS
                        else:
                                print cpsdates[0]; assert False  # double check we still have the right Michael Foster

                if fullname == "Rt Hon Michael Ancram, Earl of QC" or fullname == "Rt Hon Michael Ancram, Earl of, QC":
                        fullname = "Michael Ancram"
                if fullname == "Hon Nicholas Nicholas Soames":
                        fullname = "Nicholas Soames"
		cp.matchid, cp.remadename, cp.remadecons = memberList.matchfullnamecons(fullname, cons, cpsdates[0])
		if not cp.matchid:
                        print cpsdates[0]
			print (cp.matchid, cp.remadename, cp.remadecons)
			print cpsdates
			raise Exception, 'No match: ' + fullname + " : " + (cons or "[nocons]") + "\nOrig:" + cp.fullname
	else:
		cp.remadename = cp.fullname
		cp.remadename = re.sub("^Rt Hon ", "", cp.remadename) # XXX Think this gets removed in about 3 places now!
		cp.remadename = re.sub(" Kt ", " ", cp.remadename)
		cp.remadename = re.sub(" [GKDCOM]BE$", "", cp.remadename)
		cp.remadecons = ""
		date = cpsdates[0]

                # People being made ministers before they're Lorded. Tsch.
		# Manual fixes for old date stuff. Hmm.
                if cp.remadename == 'Lord Myners' and date < '2008-10-21':
                        date = '2008-10-21'
                if cp.remadename == 'Lord Mandelson' and date<'2008-10-13':
                        date = '2008-10-13'
                if cp.remadename == 'Lord Carter of Barnes' and date<'2008-10-16':
                        date = '2008-10-16'
                if cp.remadename == 'Lord Darzi of Denham' and date<'2007-07-19':
                        date = '2007-07-19'
                if cp.remadename == 'Lord Malloch-Brown' and date<'2007-07-09':
                        date = '2007-07-09'
                if cp.remadename == 'Lord West of Spithead' and date<='2007-07-09':
                        date = '2007-07-09'
                if cp.remadename == 'Lord Jones of Birmingham' and date<='2007-07-10':
                        date = '2007-07-10'
		if cp.remadename == 'Lord Adonis' and date<'2005-05-23':
			date = '2005-05-23'
		if cp.remadename == 'Baroness Clark of Calton' and date=='2005-06-28':
			date = '2005-07-13'
		if (cp.remadename == 'Baroness Morgan of Huyton' or cp.remadename == 'Lord Rooker') and date=='2001-06-11':
			date = '2001-06-21'
		if cp.remadename == 'Lord Grocott' and date=='2001-06-12':
			date = '2001-07-03'
                if (re.match('Baroness Warsi', cp.remadename) or cp.remadename == 'Dame Pauline Neville-Jones') and date < '2007-10-15':
                        date = '2007-10-15'
                if cp.remadename == 'Baroness Vadera' and date<'2007-07-11':
                        date = '2007-07-11'
                if cp.remadename == 'Baroness Kinnock of Holyhead' and date<'2009-06-30':
                        date = '2009-06-30'

		if cp.remadename == 'Lord Davidson of Glen Cova':
			cp.remadename = 'Lord Davidson of Glen Clova'
		if cp.remadename == 'Lord Rooker of Perry Bar':
			cp.remadename = 'Lord Rooker'

		bnonlords = cp.remadename in ['Duke of Abercorn', 'Lord Vestey']
		if not bnonlords:
			fullname = cp.remadename
			cp.matchid = lordsList.GetLordIDfname(cp.remadename, None, date) # loffice isn't used?

	# make the structure we will sort by.  Now uses the personids from people.xml (slightly backward.  It means for running from scratch you should execute personsets.py, this operation, and personsets.py again)
	if cp.matchid in mpidmap:
		cp.sortobj = (mpidmap[cp.matchid], cpsdates[0])
                cp.cmpobj = mpidmap[cp.matchid]
	else:
		if not bnonlords:
			print "mpid of", cp.remadename, "not found in people.xml; please run personsets.py and this command again"
		cp.sortobj = (re.sub("(.*) (\S+)$", "\\2 \\1", cp.remadename), cp.remadecons, cpsdates[0])
		cp.cmpobj = (re.sub("(.*) (\S+)$", "\\2 \\1", cp.remadename), cp.remadecons)



# indentify open for gluing
def GlueGapDataSetGaptonewlabministers2003(mofficegroup):
	# find the open dates at the two ends
	opendatefront = [ ]
	opendateback = [ ]

	for i in range(len(mofficegroup)):
		if mofficegroup[i][1].sdateend == opendate:
			opendateback.append(i)
		if mofficegroup[i][1].sdatestart == opendate:
			opendatefront.append(i)

	# nothing there
	if not opendateback and not opendatefront:
		return

	# glue the facets together
	for iopendateback in range(len(mofficegroup) - 1, -1, -1):
		if mofficegroup[iopendateback][1].sdateend == opendate:
			iopendatefrontm = None
			for iopendatefront in range(len(mofficegroup)):
				if (mofficegroup[iopendatefront][1].sdatestart == opendate and
					mofficegroup[iopendateback][1].pos == mofficegroup[iopendatefront][1].pos and
					mofficegroup[iopendateback][1].dept == mofficegroup[iopendatefront][1].dept):
					iopendatefrontm = iopendatefront

			if iopendatefrontm == None:
				rp = mofficegroup[iopendateback]
				print "%s\tpos='%s'\tdept='%s'" % (rp[1].remadename, rp[1].pos, rp[1].dept)
			assert iopendatefrontm != None

			# glue the two things together
			mofficegroup[iopendatefrontm][1].sdatestart = mofficegroup[iopendateback][1].sdatestart
			mofficegroup[iopendatefrontm][1].stimestart = None
			mofficegroup[iopendatefrontm][1].sourcedoc = mofficegroup[iopendateback][1].sourcedoc + " " + mofficegroup[iopendatefrontm][1].sourcedoc
			del mofficegroup[iopendateback]

	# check all linked up
	for iopendatefront in range(len(mofficegroup)):
		assert not (mofficegroup[iopendatefront][1].sdatestart == opendate)
	#	rp = mofficegroup[iopendatefront]
	#	print "\t%s\tpos='%s'\tdept='%s'" % (rp[1].remadename, rp[1].pos, rp[1].dept)



def CheckPPStoMinisterpromotions(mofficegroup):
	# now sneak in a test that MPs always get promoted from PPS to ministerialships
	ppsdatesend = [ ]
	ministerialdatesstart = [ ]
	committeegovlist = [ ]
	for rp in mofficegroup:
		if rp[1].pos == "PPS":
			committeegovlist.append((rp[1].sdatestart, "govpost", rp[1]))
			if rp[1].dept != "Prime Minister's Office":
				ppsdatesend.append(rp[1].sdateend)
		elif rp[1].pos == "Chairman":
			if rp[1].dept != "Modernisation of the House of Commons Committee":
				committeegovlist.append((rp[1].sdatestart, "committee", rp[1]))
		elif rp[1].pos == "":
			pass # okay to be an ordinary member and a gov position
			#committeegovlist.append((rp[1].sdatestart, "committee", rp[1]))
		else:  # ministerial position
			if rp[1].pos != "Second Church Estates Commissioner":
				committeegovlist.append((rp[1].sdatestart, "govpost", rp[1]))
			if rp[1].pos != "Assistant Whip":
				ministerialdatesstart.append(rp[1].sdatestart)

	# check we always go from PPS to ministerial position
	#if ppsdatesend and ministerialdatesstart:
	#	if max(ppsdatesend) > min(ministerialdatesstart):
	#		if mofficegroup[0][1].fullname not in ["Paddy Tipping"]:
	#			print "New demotion to PPS for: ", mofficegroup[0][1].fullname

	# check that goverment positions don't overlap committee positions
	committeegovlist.sort()
	ioverlaps = 0
	for i in range(len(committeegovlist)):
		j = i + 1
		while j < len(committeegovlist) and committeegovlist[i][2].sdateend > committeegovlist[j][2].sdatestart:
			if (committeegovlist[i][1] == "govpost") != (committeegovlist[j][1] == "govpost"):
				ioverlaps += 1
			j += 1
	#if ioverlaps:
	#	print "Overlapping government and committee posts for: ", mofficegroup[0][1].fullname

class LoadMPIDmapping(xml.sax.handler.ContentHandler):
	def __init__(self):
		self.mpidmap = {}
		self.in_person = None
		parser = xml.sax.make_parser()
		parser.setContentHandler(self)
		parser.parse(peoplexml)
	def startElement(self, name, attr):
		if name == "person":
			assert not self.in_person
			self.in_person = attr["id"]
		elif name == "office":
			assert attr["id"] not in self.mpidmap
			self.mpidmap[attr["id"]] = self.in_person
	def endElement(self, name):
		if name == "person":
			self.in_person = None


# main function that sticks it together
def ParseGovPosts():

	# get from our two sources (which unfortunately don't overlap, so they can't be merged)
	# I believe our gap from 2003-10-15 to 2004-06-06 is complete, though there is a terrible gap in the PPSs
	porres = newlabministers2003_10_15.ParseOldRecords()
	cpres, sdatetlist = ParseChggdir("govposts", ParseGovPostsPage, True)

	# parliamentary private secs
        cpressec, sdatelistsec = ParseChggdir("privsec", ParsePrivSecPage, False)

	# parliamentary Select Committees
	cpresselctee, sdatelistselctee = ParseChggdir("selctee", ParseSelCteePage, False)

        # Official Oppositions
        cpresopp, sdatelistoff = ParseChggdir('offoppose', ParseOffOppPage, False)
        cpreslibdem, sdatelistlibdem = ParseChggdir('libdem', ParseLibDemPage, False)
        cpresplaidsnp, sdatelistplaidsnp = ParseChggdir('plaidsnp', ParsePlaidSNPPage, False)
        cpresdup, sdatelistdup = [], []
        #cpresdup, sdatelistdup = ParseChggdir('dup', ParseDUPPage, False)

	mpidmap = LoadMPIDmapping().mpidmap

	# allocate ids and merge lists
	rpcp = []

	# run through the office in the documented file
	moffidn = 1;
	for po in porres:
		cpsdates = [po.sdatestart, po.sdateend]
		if cpsdates[1] == opendate:
			cpsdates[1] = newlabministers2003_10_15.dateofinfo

		SetNameMatch(po, cpsdates, mpidmap)
		po.moffid = "uk.org.publicwhip/moffice/%d" % moffidn
                po.sortobj = (po.sortobj, po.moffid)
		rpcp.append((po.sortobj, po, po.cmpobj))
		moffidn += 1

	# run through the offices in the new code
	assert moffidn < 1000
	moffidn = 1000
	for cp in cpres:
                if cp.fullname in []: # ignore until they're introduced as Lords
                        continue

		cpsdates = [cp.sdatestart, cp.sdateend]
		if cpsdates[0] == opendate:
			cpsdates[0] = sdatetlist[0][0]

		SetNameMatch(cp, cpsdates, mpidmap)
		cp.moffid = "uk.org.publicwhip/moffice/%d" % moffidn
                cp.sortobj = (cp.sortobj, cp.moffid)
		rpcp.append((cp.sortobj, cp, cp.cmpobj))
		moffidn += 1

	# private secretaries, select committees, official opposition
	for cpm in cpressec, cpresselctee, cpresopp, cpreslibdem, cpresplaidsnp, cpresdup:
                for cp in cpm:
	        	cpsdates = [cp.sdatestart, cp.sdateend]
		        SetNameMatch(cp, cpsdates, mpidmap)
        		cp.moffid = "uk.org.publicwhip/moffice/%d" % moffidn
                        cp.sortobj = (cp.sortobj, cp.moffid)
        		rpcp.append((cp.sortobj, cp, cp.cmpobj))
        		moffidn += 1

	# bring same to same places
	# the sort object is by name, constituency, dateobject
	rpcp.sort()
	# (there was a gluing loop here, but it was wrong thing to do, since it disrupted gluing of datasets-- failures shouldn't be happening to here)

	# now we batch them up into the person groups to make it visible
	# and facilitate the once-only gluing of the two documents (newlabministers records and webpage scrapings) together
	# this is a conservative grouping.  It may fail to group people which should be grouped,
	# but this gets sorted out in the personsets.py
	mofficegroups = [ ]
	prevrpm = None
	for rp in rpcp:
		if rp:
			if not prevrpm or prevrpm[2] != rp[2]:
				mofficegroups.append([ ])
			mofficegroups[-1].append(rp)
			prevrpm = rp


	# now look for open ends
	for mofficegroup in mofficegroups:
		GlueGapDataSetGaptonewlabministers2003(mofficegroup)
		CheckPPStoMinisterpromotions(mofficegroup)

	fout = open(chgtmp, "w")
	WriteXMLHeader(fout)
	fout.write("\n<!-- ministerofficegroup is just for readability.  Actual grouping is done in personsets.py -->\n\n")
	fout.write("<publicwhip>\n")

	fout.write("\n")
	for listofdates in sdatetlist, sdatelistsec, sdatelistselctee, sdatelistoff, sdatelistlibdem, sdatelistplaidsnp, sdatelistdup:
                for lsdatet in listofdates:
		        fout.write('<chgpageupdates date="%s" chgtype="%s"/>\n' % lsdatet)


	# output the file, a tag round the groups of offices which form a single person
	# (could sort them by last name as well)
	for mofficegroup in mofficegroups:
		fout.write('\n<ministerofficegroup>\n')
		for rp in mofficegroup:
			WriteXML(rp[1], fout)
		fout.write("</ministerofficegroup>\n")

	fout.write("</publicwhip>\n\n")
	fout.close();

	# we get the members directory and overwrite the file that's there
	# (in future we'll have to load and check match it)

	#print "Over-writing %s;\nDon't forget to check it in" % ministersxml
	if os.path.isfile(ministersxml):
		os.remove(ministersxml)
	os.rename(chgtmp, ministersxml)


