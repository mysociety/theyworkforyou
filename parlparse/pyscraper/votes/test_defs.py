from fd_parse import Match, DEFINE
import datetime
#from fd_core import nextdayinstance 

m2=Match('(?P<no>\d+)')
m3=Match('(?P<no>one|two)')

g2=m2.prog.groupindex
g3=m3.prog.groupindex

overlap=set(g2) & set(g3)

b=Match.join(m2, m3)
c=Match.join(m2, m3, lambda x,y: x+"|" + y)

import fd_parse

p1=fd_parse.pattern('(?P<no>one)')
p2=fd_parse.pattern('(?P<no>two)')

d=fd_parse.OR(p1, p2)

from fd_dates import *

f=futureday

p=plaindate()

s1=SEQ(p1,p2)

tenv={'today' : datetime.date.today(), 'test' : '"testvalue"', 'no' : 4}

today=datetime.date.today()

ftests=[ 'Monday next', 'tomorrow', 'On 14th June 2005']

#SET('a', '$test')('',tenv)

T=fd_parse.Template('$year and int($year) or 1900')
testdic={}

number=DEFINE('number', pattern('\d+'))

DEFINE('enprint', pattern('\d+-EN'))
DEFINE('act', pattern('[-a-z.,A-Z0-9()\s]*?'), fragment=True)
DEFINE('year', pattern('\d{4}'))

s='start%(number)end'
s1='%(number1)-%(number2)'
s2='<i>Ordered</i>, That the Explanatory Notes (relating )?to the %(act2) be printed [Bill %(enprint)].'
s3='<i>Ordered</i>, That the Explanatory Notes (relating )?to the %(act2) be printed \[Bill %(enprint)\].'

from fd_parse import sub, plaintext, prep_plaintext, tagged, tagpatterns, plaintextpar

actparse=plaintextpar('the %(act) Act %(year).')
egact='the theft act 1968'

#plaintext('<i>Ordered</i>, That the Explanatory Notes (relating )?to the %(act2) be printed [Bill %(enprint)].'

s4='Bill, as amended in the Standing Committee, to be considered to-morrow; and to be printed [Bill %(number1)].'
p4=prep_plaintext(s4)

from fd_parse import *

o=OUT('$today')
#('', tenv)

a=pattern('4')
b=pattern('5')
s=SEQ(a,b)
