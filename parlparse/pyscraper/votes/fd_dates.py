import fd_parse
import datetime
import re
from fd_core import fromiso, nextday, nextdayinstance, daynum, monthnum, cardinalnum

from fd_parse import SEQ, OR,  ANY, POSSIBLY, IF, START, END, ELEMENT, NULL, OUT, DEBUG, STOP, FORCE, CALL, SET, DEFINE, pattern, tagged

# Time handling

# English numbers up to 12, and up to 60

engnumber12s='one|two|three|four|five|six(?!ty)|seven|eight|nine|ten|eleven|twelve'
engnumber12p='(%s)' % engnumber12s

engnumber60p='(' + engnumber12s + '|thirteen|fourteen|fifteen|sixteen|seventeen|eighteen|nineteen|((twenty|thirty|forty|fifty)(-(one|two|three|four|five|six|seven|eight|nine))?))'

# half an hour
# an hour and a half
# three and a half hours

# three hours and 27 minutes
timequantump='(an hour and a (half|quarter)|((a quarter|three quarters) of|half) an hour|%(engnumber60p)s minutes|%(engnumber12p)s (and a half hours|hours( and %(engnumber60p)s minutes)?))' % {'engnumber12p' : engnumber12p, 'engnumber60p' : engnumber60p}

DEFINE('timequantum', pattern(timequantump))

# English times, eg "three minutes to four o'clock"

archtimep='(twelve (noon|midnight)|(a quarter past|half-past|a quarter to|'+engnumber60p+' minutes (to|past)|)\s*' + engnumber12p + '(\s*o\'\s*clock))?'

archtime=DEFINE('archtime',
	SEQ(
		pattern('\s*(?P<archtime>'+archtimep+')(?i)'),
		ELEMENT('time', archtime='$archtime')
		)
	)

# Date handling

monthnamep='(January|February|March|April|May|June|July|August|September|October|November|December)'
daynamep='(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)'
ordinalp='(st|nd|rd|th)'
datep='\d+' + ordinalp + '\s+' + monthnamep + '\s+\d+(?i)'

dayname=pattern('\s*(?P<dayname>' + daynamep + ')\s*')

monthname=pattern('\s*(?P<monthname>'+ monthnamep+ ')\s*')

year=pattern('(?P<year>\d{4})\s*')


dayordinal=pattern('\s*(?P<day>\d+)'+ordinalp+'\s*')

def plaindate(today='$today', rtn='date'):
	return SEQ(
		POSSIBLY(dayname), 
		dayordinal, 
		monthname,
#		SET('year', 'str(%s.year)' % today),
		POSSIBLY(year),
		SET('year', '$year and $year or str(%s.year)' % today),
		SET(rtn, 'datetime.date(int($year), monthnum($monthname), int($day))')
		)

DEFINE('date', plaindate())

'''today is a string in ISO format, eg "2001-02-04" the return
value is a date format.

At the moment futureday gets dates in the following year, where
no year is given, wrong. TODO.'''

futureday=DEFINE('futureday',
	SEQ(OR(
		SEQ(
			dayname,pattern('\s*next\s*'),
			SET('futuredate', 'nextdayinstance($today, daynum($dayname))')
			),
		SEQ(
			pattern('to(-)?morrow'),
			SET('futuredate', 'nextday($today)')
			),
		SEQ(
			pattern('on '),
			plaindate(),
			SET('futuredate', '$date')
			)
		))
	)

# Dates with idiosyncratic italics

idate=DEFINE('idate',
	SEQ(
		pattern('\s*<i>\s*'),
		dayname,
		DEBUG('got dayname'),
		fd_parse.TRACE(False),
		POSSIBLY(pattern('\s*</i>\s*')),
		fd_parse.TRACE(False),
		OR(
			pattern('\s*(?P<dayno>\d+)(st|nd|rd|th)\s*<i>\s*'),
			pattern('\s*(?P<dayno>\d+)(<i>)?(st|nd|rd|th)\s*')
		),
		fd_parse.TRACE(False),
		DEBUG('got dayordinal'),
		fd_parse.TRACE(False),
		monthname,
		DEBUG('got monthname'),
		OR(
			SEQ(pattern('\s*</i>\s*'),year),
			SEQ(year,pattern('\s*</i>\s*'))
			),
		ELEMENT('date',
			dayname="$dayname",
			monthname="$monthname",
			year="$year",
			dayno="$dayno")
		)
	)

