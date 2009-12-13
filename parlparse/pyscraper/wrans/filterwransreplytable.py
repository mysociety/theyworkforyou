# vim:sw=8:ts=8:et:nowrap

import sys
import re
import string
import cStringIO

import mx.DateTime

from miscfuncs import FixHTMLEntities
from miscfuncs import FixHTMLEntitiesL
from contextexception import ContextException

regtablejunk = '</?font[^>]*>|</?p>|\n(?i)'



recolsplit = re.compile('(<t[dh][^>]*>[\s\S]*?(?:</t[dh]>|(?=<t[dh][^>]*>)))(?i)')
recolmatch = re.compile('<t[dh](?: class[ =]"tabletext")?(?: align=\"?(center|right)\"?)?(?: colspan=\"?(\d+)\"?)?(?: rowspan=\"?(\d+)\"?)?(?: align=\"?(center|right)\"?)?(?: valign=top)?(?: colspan=\"?(\d+)\"?)?(?: NOWRAP)?>\s*([\s\S]*?)\s*(?:</t[dh]>)?$(?i)')
def ParseRow(srow, hdcode, stampur):
	# build up the list of entries for this row
	Lscols = [ '\t\t<tr> ' ]
	for spcol in recolsplit.split(srow):
		col = recolmatch.match(spcol)
		if col:
			colspan = ''
                        rowspan = ''
			if col.group(2):
				colspan = ' colspan="%s"' % col.group(2)
			if col.group(5):
				colspan = ' colspan="%s"' % col.group(5)
			if col.group(3):
				rowspan = ' rowspan="%s"' % col.group(3)
			talign = ''
			if col.group(1):
				talign = ' align="%s"' % col.group(1)
			if col.group(4):
				talign = ' align="%s"' % col.group(4)
			Lscols.append('<%s%s%s%s>' % (hdcode, colspan, rowspan, talign))

			coltext = re.sub('\n', ' ', col.group(6))
			coltext = re.sub('</?font[^>]*>|</?p[^>]*>|</?center>|</?B>|</?ul>(?i)', '', coltext)
			coltext = re.sub('^(?:<br>|\s)(?i)', '', coltext)
			coltext = re.sub('(?:<br>|\s)$(?i)', '', coltext)
			content = FixHTMLEntitiesL(coltext, '', stampurl=stampur)
			Lscols.extend(content)
			Lscols.append('</%s> ' % hdcode)

		# check that the outside text contains nothing but bogus close column tags
		elif not re.match('(?:</t[dh]>|</font>|\s)*$(?i)', spcol):
			print "spcol:", spcol
			print "srow:", srow
			print "srowsplit:", recolsplit.split(srow)
			raise ContextException("non column text", stamp=stampur, fragment=srow)
	Lscols.append('</tr>')
	return string.join(Lscols, '')


# replies can have tables
def ParseTable(lstable, stampur):
	# remove the table bracketing
	stable = re.match('<table[^>]*>\s*([\s\S]*?)\s*</table>$(?i)', lstable)
	if not stable:
		raise ContextException('Missing </table> somewhere...', stamp=stampur, fragment=stable)
	stable = stable.group(1)
	if re.search('<table[^>]*>|</table>(?i)', stable):
		print lstable
		raise Exception, 'Double <table> start tag in table parse chunk'

	# break into rows, making sure we can deal with non-closed <tr> symbols
	sprows = re.split('(<tr[^>]*>[\s\S]*?(?:</tr>|(?=<tr[^>]*>)))(?i)', stable)

	# build the rows
	stitle = ''
	srows = []
	for sprow in sprows:
		trg = re.match('<tr[^>]*>([\s\S]*?)(?:</tr>)?$(?i)', sprow)

		if trg:
			srows.append(trg.group(1))

		elif re.search('\S', sprow):
			if (not srows) and (not stitle):
				stitle = sprow
			elif not re.match('(?:</t[dhr]>|</font>|</?tbody>|</?thead>|\s)*$(?i)', sprow):
				raise ContextException("non-row text", stamp=stampur, fragment=sprow)


	# take out tags round the title; they're always out of order
        #print "stitle ", stitle
	stitle = string.strip(re.sub('</?font[^>]*>|</?p>|</?i>|<br>|<tbody>|</?thead>|&nbsp;(?i)', '', stitle))
	ctitle = ''
	if stitle:
		ts = re.match('(?:\s|<b>|<center>)+([\s\S]*?)(?:</b>|</center>)+\s*([\s\S]*?)\s*$(?i)', stitle)
		if not ts:
			raise ContextException(' non-standard table title: %s ' % stitle, stamp=stampur, fragment=stitle)
		Lstitle = [ '\t<caption>' ]
		Lstitle.append(FixHTMLEntities(ts.group(1), '</?font[^>]*>|</?p>|\n(?i)', stampurl=stampur))
		if ts.group(2):
			Lstitle.append(' -- ')
			Lstitle.append(FixHTMLEntities(ts.group(2), '</?font[^>]*>|</?p>|\n(?i)', stampurl=stampur))
		Lstitle.append('</caption>')
		ctitle = string.join(Lstitle, '')

	# split into header and body
	for ih in range(len(srows)):
		if re.search('<td[^>]*>(?i)', srows[ih]):
			break

	# construct the text for writing the table
	res = [ '<table>' ]
	if ctitle:
		res.append(ctitle)

	if ih > 0:
		res.append('\t<thead>')
		for srow in srows[:ih]:
			res.append(ParseRow(srow, 'th', stampur))
		res.append('\t</thead>')

	res.append('\t<tbody>')
	for srow in srows[ih:]:
		res.append(ParseRow(srow, 'td', stampur))
	res.append('\t</tbody>')

	res.append('</table>')

	return res


