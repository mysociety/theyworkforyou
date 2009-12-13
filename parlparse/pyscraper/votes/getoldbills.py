import os
import urllib2

billdir='oldbills'
base='http://www.publications.parliament.uk/pa'

def doubleyear(year):
	nextyear='%s' % (year +1)
	return '%s%s' % (year, nextyear[2:])

def scrapebill(house, session, billno):
	filename='bill-%s-%s-%s' % (house, session, billno)
	filepath=os.path.join(billdir, filename)

	if os.path.exists(filepath):
		return True
	
	try:
		href='%s/cm%s/cmbills/%03i/%s%03i.htm' % (
			base,
			doubleyear(session),
			billno,
			session+1,
			billno)

		print href
		url=urllib2.urlopen(href)

	except urllib2.HTTPError:
		return False

	outfile=open(filepath, 'w')
	s=url.read()
	outfile.write(s)
	return True
		

for house in ['cm','ld']:
	for session in [2001,2002,2003,2004,2005]:
		for printno in range(1, 151):
			t=scrapebill(house,session,printno)

