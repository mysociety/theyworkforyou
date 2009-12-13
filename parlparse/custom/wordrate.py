import sys
import os.path
import re
import string


# returns a dict of date, list of files
xmldir = "C:/publicwhip/pwdata/scrapedxml"
xmldir = ""
xmldir = "/home/fawkes/pwdata/scrapedxml"
typefiles = ("debates", )
typefiles = ("debates", "westminhall", "wms")
def FindAllFiles():
	fildays = {}
	for subtyp in typefiles:
		dxmldir = os.path.join(xmldir, subtyp)
		dfils = os.listdir(dxmldir)
		for dfil in dfils:
			h = re.search("(\d{4}-\d{2}-\d{2}).*\.xml$", dfil)
			if h:
				fildays.setdefault(h.group(1), []).append(os.path.join(dxmldir, dfil))

	# separate the days into month batches
	daykeys = fildays.keys()
	daykeys.sort()
	monthlists = []
	for daykey in daykeys:
		if (not monthlists) or (monthlists[-1][-1][:7] != daykey[:7]):
			monthlists.append([daykey])
		else:
			monthlists[-1].append(daykey)

	print "number of monthlists", len(monthlists)
	#monthlists = monthlists[0:3]
	return fildays, monthlists

# the object we use to search the countries
def MakeCountriesSearch(lcountries):
	clist = [ ]
	for c in re.split("\n", lcountries):
		lc = string.strip(c)
		if lc:
			clist.append(lc)
	print "number of countries", len(clist)
	#clist = clist[30:120]

	rece = re.compile(string.join("|"))
	reclist = map(re.compile, clist)
	return rece, reclist, clist


# different ways of summing the lists
def SBool(r):
	return (r and 1 or 0)
def SumBool(s, r):
	return s + SBool(r)
def SumBLists(countthis, recsg):
	if not countthis:
		return map(SBool, recsg)
	return map(SumBool, countthis, recsg)

def Sum(s, r):
	return s + r
def SumLists(countthis, recsg):
	if not countthis:
		return recsg
	return map(Sum, countthis, recsg)
def SumZero(recsg):
	return [ 0 for r in recsg ]

# do all the things of a paragraph
def MakeCountFile(rece, reclist, clist, fils):
	countperpara = None
	numparas = 0
	countperspee = None
	numspees = 0
	for fil in fils:
		fin = open(fil)
		countthisspee = None
		for para in fin.readlines():
			if re.match("\s*<(?:p|\S*heading|speech|ques|reply)", para):
				# don't count speech titles as paragraphs
				if re.match("\s*<p", para):
					numparas += 1
				# search in this paragraph
				recs = rece.search(para)
				if recs:
					lpara = para[recs.start(0):]  # discard the string before the match place
					# search each country in the paragraph
					rec = [ (r.search(lpara) and 1 or 0) for r in reclist ]
					countthisspee = SumLists(countthisspee, rec)

			if re.match("\s*</(\S*heading|speech|reply)", para):  # run ques and reply together
				if countthisspee:
					countperpara = SumLists(countperpara, countthisspee)
					countperspee = SumBLists(countperspee, countthisspee)
				countthisspee = None
				numspees += 1
		fin.close()
		print fil, numparas, numspees

	if not countperspee:
		countperspee = SumZero(clist)
	return numparas, countperpara, numspees, countperspee



# do everything
def RunAll(lcountries):
	fildays, monthlists = FindAllFiles()

	rece, reclist, clist = MakeCountriesSearch(lcountries)
	fout = open("mocount.csv", "w")
	fout.write("Number of speeches each country is mentioned in Hansard, in: %s\n" % string.join(typefiles, ", "))
	fout.write("month, total number of days, dates, total number of speeches, total number of paragraphs")
	for c in clist:
		fout.write(", ")
		fout.write(c)
	fout.write("\n")

	for monthlist in monthlists:
		print "***** monthlist *****", monthlist[0][:7]
		tnumparas, tcountperpara, tnumspees, tcountperspee = 0, None, 0, None
		for day in monthlist:
			numparas, countperpara, numspees, countperspee = MakeCountFile(rece, reclist, clist, fildays[day])

			tnumparas += numparas
			tcountperpara = SumLists(tcountperpara, countperpara)
			tnumspees += numspees
			tcountperspee = SumLists(tcountperspee, countperspee)

		fout.write("%s, %d, %s, %d, %d" % (monthlist[0][:7], len(monthlist), string.join(monthlist, " "), tnumspees, tnumparas))
		for c in tcountperspee:
			fout.write(", %d" % c)
		fout.write("\n")
		fout.flush()

	fout.close()



countries = """
Afghanistan
Albania
Algeria
Andorra
Angola
Antigua and Barbuda|Antigua
Argentina
Armenia
Australia
Austria
Azerbaijan
Bahamas
Bahrain
Bangladesh
Barbados
Belarus|Byelorussia
Belgium
Belize
Benin
Bhutan
Bolivia
Bosnia and Herzegovina|Bosnia
Botswana
Brazil
Brunei
Bulgaria
Burkina Faso
Burundi
Cambodia
Cameroon
Canada
Cape Verde
Central African Republic
Chad
Chile
China
Colombia
Comoros
Congo-Brazzaville|Congo Brazzaville|Republic of Congo
Costa Rica
Cote d'Ivoire|Ivory Coast
Croatia
Cuba
Cyprus
Czech Republic
Democratic People's Republic of Korea|North Korea
Democratic Republic of the Congo|DRC|Zaire|Democratic Republic of Congo
Denmark
Djibouti
Dominica
Dominican Republic
Ecuador
Egypt
El Salvador
Equatorial Guinea
Eritrea
Estonia
Ethiopia
Fiji
Finland
France
Gabon
Gambia
Georgia
Germany
Ghana
Greece
Grenada
Guatemala
Guinea
Guinea-Bissau
Guyana
Haiti
Honduras
Hungary
Iceland
India
Indonesia
Iran
Iraq
Ireland
Israel
Italy
Jamaica
Japan
Jordan
Kazakhstan
Kenya
Kiribati
Kuwait
Kyrgyzstan
Lao People's Democratic Republic|Laos
Latvia
Lebanon
Lesotho
Liberia
Libyan Arab Jamahiriya|Libya
Liechtenstein
Lithuania
Luxembourg
Macedonia|FYROM
Madagascar
Malawi
Malaysia
Maldives
Mali
Malta
Marshall Islands
Mauritania
Mauritius
Mexico
Micronesia
Monaco
Mongolia
Morocco
Mozambique
Myanmar|Burma
Namibia
Nauru
Nepal
Netherlands
New Zealand
Nicaragua
Niger
Nigeria
Norway
Oman
Pakistan
Palau
Panama
Papua New Guinea
Paraguay
Peru
Philippines
Poland
Portugal
Qatar
Republic of Korea|South Korea
Moldova
Romania
Russian Federation|Russia
Rwanda
Saint Lucia
Saint Vincent and the Grenadines
Samoa
San Marino
Sao Tome and Principe
Saudi Arabia
Senegal
Serbia|Montenegro
Seychelles
Sierra Leone
Singapore
Slovakia
Slovenia
Solomon Islands
Somalia
South Africa
Spain
Sri Lanka
Sudan
Suriname
Swaziland
Sweden
Switzerland
Syrian Arab Republic|Syria
Tajikistan
Thailand
Timor-Leste|East Timor
Togo
Tonga
Trinidad and Tobago
Tunisia
Turkey
Turkmenistan
Tuvalu
Uganda
Ukraine
United Arab Emirates|UAE
United Kingdom|UK
England
Wales
Scotland
Northern Ireland
United Republic of Tanzania|Tanzania
United States of America|US|USA
Uruguay
Uzbekistan
Vanuatu
Venezuela
Viet Nam|Vietnam
Yemen
Zambia
Zimbabwe
Anguilla
Ascension
Bermuda
British Antarctic Territory
British Indian Overseas Territory|BIOT|Diego Garcia|Chagos Islands
Cayman Islands
Falkland Islands
Gibraltar
Montserrat
Pitcairn|Henderson|Ducie|Oeno
Saint Christopher and Nevis|Saint Kitts and Nevis
South Georgia and South Sandwich Islands
St Helena|Tristan De Cunha
Turks and Caicos Islands
Holy See|Vatican
Tibet
Hong Kong
Macau|Macao
Western Sahara
Kashmir
Kosovo
North Cyprus
Palestine
Taiwan|Formosa
Chechnya
"""

RunAll(countries)

