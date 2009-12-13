import datetime
import re
import string


class Env:
	def __init__(self, D):
		self.dict=D

	def __getitem__(self, s):
		if self.dict.has_key(s):
			return repr(self.dict[s])
		else:
			return 'None'

class Template(string.Template):
	def __init__(self, template):
		string.Template.__init__(self, template)
		self.template=template

	def substitute(self,D):
		return string.Template.substitute(self, Env(D))

	def __str__(self):
		return repr(self.template)
	
	def __repr__(self):
		return "Template(%s)" % repr(self.template)

# Date functions

# This should really be a part of the datetime module.

def fromiso(isodate):
	'''Converts a date in datetime format into an iso date string.

	Curiously this function does not exist in the datetime module'''

	mobj=re.match('(\d{4})-(\d{2})-(\d{2})', isodate)
	if mobj:
		g=mobj.groups()
		return datetime.date(int(g[0]), int(g[1]), int(g[2]))
	else:
		raise Exception, "Ill formatted isodate (%s)" % isodate


def nextday(date):
	'''Returns the next day from and to datetime format.'''

	return date+datetime.timedelta(1)

def nextdayinstance(today, futureday):
	'''Returns the next instance of a day of the week from today.

	For example, on Tuesday 14th June "Monday next" becomes 20th June'''

	return today + datetime.timedelta(7+(futureday-today.weekday()))


def daynum(daystring):
	'''Returns the ISO weekday of a string.'''

	return (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'].index(daystring))

def monthnum(monthstring):
	return [None,'January','February','March','April','May','June','July','August','September','October','November','December'].index(monthstring)

def cardinalnum(cardinalstring):
	if re.match('one|two|three|four|five|six(?!ty)|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen', cardinalstring):
		return ['zero','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'].index(cardinalstring)

	mobj=re.match('twenty|thirty|forty|fifty|sixty|seventy|eighty|ninety', cardinalstring)
	if mobj:
		tens=['twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'].index(mobj.group())+2
		if not mobj.end()==len(cardinalstring):
			return tens*10+cardinalnum(cardinalstring[mobj.end()+1:])
		else:
			return tens*10
	
