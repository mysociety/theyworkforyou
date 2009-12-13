#!/usr/bin/python

import urllib, re
base = 'http://politics.guardian.co.uk'

out = open('../rawdata/mpinfo/guardian-mpsurls2005.txt', 'w')
for i in range(-272, -266):
	url = '%s/person/browse/mps/az/0,,%d,00.html' % (base, i)
	fp = urllib.urlopen(url)
	index = fp.read()
	fp.close()
	m = re.findall('<a href="(/person/0[^"]*)">(.*?), (.*?)</a>', index)
	for match in m:
		url = '%s%s' % (base, match[0])
		name = '%s %s' % (match[2], match[1])
		fp = urllib.urlopen(url)
		person = fp.read()
		fp.close()
		cons = re.search('Member of Parliament for <a href="(/hoc/constituency/[^"]*)">(.*?)</a>', person)
		consurl, consname = cons.groups()
		consurl = '%s%s' % (base, consurl)
		out.write('%s\t%s\t%s\t%s\n' % (name, consname, url, consurl))

