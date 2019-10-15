'''

Downloads larger size thumbnails
of MPs from parliament API.

requires:

everypolitician-popolo

'''

import os
import urllib
import urllib2
import csv

from popolo_data.importer import Popolo

folder = r"..\www\docs\images\mpsL"

def get_source():
	"""
	get photo index from parliament api
	"""
	url = 'https://api.parliament.uk/query/person_photo_index.csv'
	response = urllib2.urlopen(url)
	return csv.DictReader(response)

def get_id_lookup():
	"""
	create id lookup from popolo file
	convert datadotparl_id to parlparse
	"""
	people_url = "https://github.com/mysociety/parlparse/raw/master/members/people.json"
	pop = Popolo.from_url(people_url)
	count = 0
	lookup = {}
	for p in pop.persons:
		id = p.id
		datadotparl = p.identifier_value("datadotparl_id")
		if datadotparl:
			print id[-5:], datadotparl
			lookup[datadotparl] = id[-5:]
			count += 1
	print count, len(pop.persons)
	return lookup

def make_image_url(v):
	id = v[-8:]
	url = "https://api.parliament.uk/photo/{0}.jpg?crop=MCU_3:4&width=120&quality=100"
	return url.format(id)

def get_images():
	"""
	fetch image if available
	"""
	df = get_source()
	lookup = get_id_lookup()
	get_parlparse = lambda x: lookup.get(x["mnisId"], None)
	get_image_url = lambda x: make_image_url(x["image"])
	dest = os.path.join(folder)
	
	for r in df:
		parlparse_id = get_parlparse(r)
		if parlparse_id:
			print r["displayAs"]
			filename = os.path.join(dest, "{0}.jpg".format(parlparse_id))
			image_url = get_image_url(r)
			if os.path.exists(filename) is False:
				urllib.urlretrieve(image_url, filename)

if __name__ == "__main__":
	get_images()
