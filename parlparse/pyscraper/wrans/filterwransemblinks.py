import sys
import re
import os
import string
import cStringIO

import mx.DateTime

import miscfuncs

# output to check for undetected member names
import miscfuncs
toppath = miscfuncs.toppath

seelines = open(os.path.join(miscfuncs.tmppath, 'emblinks.txt'), "w")

# this detects the domain
reglinkdomt = '(?:\.or[gq]|\.com|[\.\s]uk|\.tv|\.net|\.gov|\.int|\.info|\.it|\.ch|\.es|\.mz|\.lu|\.fr|\.dk|\.mil|\.eu)(?!\w)'
reglinkdomf = 'http://(?:(?:www.)?defraweb|nswebcopy|\d+\.\d+\.\d+\.\d+)|www.defraweb'
reglinkdom = '(?:http ?:? ?/{1,3}(?:www)?|www)(?:(?:[^/:;,?=()<>"\'@](?!www\.))*?(?:%s))+|%s' % (reglinkdomt, reglinkdomf)

# this detects the middle section of a url between the slashes.
reglinkmid = '(?:/(?:(?:[^/:;,?="<()]|&#\d+;|&amp;)(?!www\.))+)*/'

# this detects the tail section of a url trailing a slash
# XXX: This seems very odd way of matching URIs
#reglinktail = '[^./:;,]*(?:\.\s?(?:s?html?|pdf|xls|(?:asp|php|cfm(?:\?[^\s.]+)?)))|\w*'
regqs = '(?:\?\s?\w+=[\w/]+(?:&\w+=[\w/%]*)*)'
regasptype = '(?:asp|nsf|php|cfm|gif|jpg|jpeg|png)%s?' % regqs
reglinktail = '(?:[^./:;,?=]|&#\d+;)*(?:\.?\s?(?:s?html?|xls|doc|pdf(?:\?Open ?Element)?|%s|%s))|(?:[\w-]|&#\d+;)*' % (regasptype, regqs)


rreglink = '(?:(?:%s)(?:(?:%s)(?:%s)?)?)' % (reglinkdom, reglinkmid, reglinktail)
reglink = '((%s)(?:(%s)(%s)?)?)(?i)' % (reglinkdom, reglinkmid, reglinktail)
relink = re.compile(reglink)

rregemail = '\w+@\w+(?:\.\w+)*'


rehtlink = re.compile('(%s)(?:(%s)(%s)?)?(?i)' % (reglinkdom, reglinkmid, reglinktail))


def ConstructHTTPlink(qstrdom, qstrmid, qstrtail):
	qstrdom = re.sub('^http|[:/\s]', '', qstrdom)
	if not qstrmid:
		qstrmid = ''
	if not qstrtail:
		qstrtail = ''

        qstrdom = re.sub('\n', '', qstrdom)
        qstrmid = re.sub('\n', '', qstrmid)
        qstrtail = re.sub('\n', '', qstrtail)

	if not re.match('[\w\-.]*$', qstrdom):
		print ' bad domain -- ' + qstrdom

	if qstrmid:
		qstrmid = re.sub(' ', '', qstrmid)
		qstrmid = re.sub('&#15[01];', '-', qstrmid)
		qstrmid = re.sub('&#0?95;', '_', qstrmid)
		if re.search('&#\d+;', qstrmid):
			print ' undemangled href symbol ' + qstrmid
		qstrmid = qstrmid.replace('&amp;', '&') # XXX
		qstrmid = re.sub('&', '&amp;', qstrmid)
	if not re.match('[\w\-/.+;&%]*$', qstrmid):
		#print ' bad midd -- ' + qstrmid
                pass

	if qstrtail:
		qstrtail = re.sub(' ', '', qstrtail)
		qstrtail = re.sub('&#15[01];', '-', qstrtail)
		qstrtail = re.sub('&#0?95;', '_', qstrtail)
		qstrtail = re.sub('&#0?35;', '#', qstrtail)
		if re.search('&#\d+;', qstrtail):
			print ' undemangled href symbol ' + qstrtail
		qstrtail = re.sub('&', '&amp;', qstrtail)
	if not re.match('[\w\-./\?&=%;~#]*$', qstrtail):
		print ' bad tail -- ' + qstrtail


	qstrlink = 'http://%s%s%s' % (qstrdom, qstrmid, qstrtail)
	return qstrlink


def ExtractHTTPlink(stex, qs):
	qlink = relink.search(stex)

	# no link found.  output if there should be.
	if not qlink:
		if re.search('www|http|ftp(?i)', stex):
			seelines.write(qs.sdate)
			seelines.write('\t')
			seelines.write(' --failed to find link-- ' + stex + '\n')
			print ' --failed to find link-- ' + stex
		return (None,None,None)


	qspan = qlink.span(1)
	qstr = re.sub(' ', '', qlink.group(1))


	qstrlink = ConstructHTTPlink(qlink.group(2), qlink.group(3), qlink.group(4))
	qtags = ( '<a href="%s">' % qstrlink, '</a>' )

	# write out debug stuff
	qplpch = [ ]
	slo = qspan[0] - 10
	shi = qspan[1] + 20
	if slo < 0:
		slo = 0
	if shi > len(stex):
		shi = len(stex)
	qplpch.append(stex[slo:qspan[0]])
	qplpch.append('(' + qlink.group(2) + ')')

	if qlink.group(3):
		qplpch.append('(' + qlink.group(3) + ')')
	if qlink.group(4):
		qplpch.append('(' + qlink.group(4) + ')')

	qplpch.append(stex[qspan[1]:shi])
	#print ' **** ' + string.join(qplpch)
	seelines.write(qs.sdate)
	seelines.write('\t')
	map(seelines.write, qplpch)
	seelines.write('\n')


	return (qspan,qstr,qtags)


#############
# main type call which generates a file of all the links in the file on disk
if __name__ == '__main__':
	pwcmdirs = os.path.join(toppath, "cmpages")
	pwcmdirin = os.path.join(pwcmdirs, 'wrans')

	lkpages = os.path.join(toppath, "anslinks.html")
	flkpages = open(lkpages, "w")
	flkpages.write('<html>\n<body>\n')

	relinkspl = re.compile('<pagex? url=\"(.*?)"/>|%s' % reglink)
	nlinks = 0

	fdirin = os.listdir(pwcmdirin)
	fdirin.sort()
	fdirin.reverse()
	for fin in fdirin:
		sdate = re.search('\d{4}-\d{2}-\d{2}', fin).group(0)
		#if sdate > '2000-11-07':
		#	continue
		flkpages.write('<html>\n<body>\n')
		jfin = os.path.join(pwcmdirin, fin)
		ofin = open(jfin)
		text = ofin.read()
		ofin.close()

		pageurl = 'nothing'
		for ref in relinkspl.findall(text):
			if ref[0]:
				if not pageurl:
					flkpages.write('</ul>\n')
				pageurl = ref[0]
				continue

			# write out the pageurl
			if pageurl:
				pn = re.search('(\d{1,3})\.htm$', pageurl).group(1)
				flkpages.write('<h2><a href="%s">Written Answers %s page %s</a></h2>\n' % (pageurl, sdate, pn))
				flkpages.write('<ul>\n')
				pageurl = ''

				nlinks = nlinks + 1
				if (nlinks % 20) == 0:
					print sdate

			qstrlink = ConstructHTTPlink(ref[2], ref[3], ref[4])
			flkpages.write('\t<li><a href="%s">%s</a></li>\n' % (qstrlink, ref[1]))
		if not pageurl:
			flkpages.write('</ul>\n')

	flkpages.write('</body>\n</html>\n')
	flkpages.close()

