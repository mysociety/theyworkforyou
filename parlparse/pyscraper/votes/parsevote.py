#!/usr/bin/python
# vim:sw=8:ts=8:et:nowrap

# A very experimental program to parse votes and proceedings files.

# To do:

# House committees (notes below)
# Second readings
# detached [by Act] [name] paragraphs
# eating of <p>'s when shouldn't (see 2003-03-03)

import sys
import os
import os.path
import re
import fd_parse
from fd_dates import *


from fd_parse import SEQ, OR,  ANY, POSSIBLY, IF, START, END, OBJECT, NULL, OUT, DEBUG, STOP, FORCE, CALL, ATTRIBUTES, ELEMENT,  DEFINE, pattern, tagged, plaintextpar, plaintext

sys.path.append("../")
from xmlfilewrite import WriteXMLHeader
from contextexception import ContextException
from miscfuncs import toppath

splitparagraphs=ANY(
	SEQ(
		pattern('\s*(<p([^>]*?)>(<ul>)?)(?P<partext>([^<]|<i>|</i>)*)(</ul>)?(</p>)?'),
		ELEMENT('paragraph',body='$partext')
		)
	)

def namepattern(label='name'):
	return "(?P<"+label+">[-A-Za-z .']+)"


# Patterns specific to Votes and Proceedings that are used frequently

DEFINE('mp', pattern('[-A-Za-z .]+'))
DEFINE('act', pattern('[-a-z.,A-Z0-9()\s]*?'), fragment=True)
DEFINE('speaker', pattern('(Deputy )?Speaker'))
DEFINE('number', pattern('\d+'))
DEFINE('ordinal', pattern('1st|2nd|3rd|\d+th'))
DEFINE('text', pattern('[a-z .?;,()]*?'), fragment=True)

#fd_parse.standard_patterns.update(
#		{
#		'mp'	: lambda n: namepattern('mp%s' % n),
#		'act'	: lambda n: '(?P<actname%s>[-a-z.,A-Z0-9()\s]*?)' % n,
#		'speaker' : lambda n: '(?P<speaker_title%s>(Deputy )?Speaker)' %n
#		}
#	)
#

# Names may have dots and hyphens in them.

emptypar=pattern('\s*<p>\s*</p>\s*(?i)')
emptypar2=pattern('\s*<p><ul>\s*</ul></p>\s*')

actpattern='(?P<act>[-a-z.,A-Z0-9()\s]*?)' 

act=SEQ(
	pattern('\s*<p><ul>(<ul>)?(?P<shorttitle>'+actpattern+' Act)\s*(?P<year>\d+)(\.)?(</ul>)?</ul></p>'),
	ELEMENT('act',
		shorttitle='$shorttitle',
		year='$year')
	)

measurepattern=SEQ(
	pattern('\s*<p><ul>(<ul>)?(?P<shorttitle>[-a-z.,A-Z0-9()\s]*?Measure)\s*(?P<year>\d+)(\.)?</ul>(</ul>)?</p>'),
	ELEMENT('measure',
		shorttitle='$shorttitle',
		year='$year')
	)



def untagged_par():
	return SEQ(
	DEBUG('checking for untagged paragraph'),
	fd_parse.TRACE(False),
	pattern('\s*(?P<paragraph_text>(?![^<]*\[Adjourned)([^<]|<i>|</i>)+)\s*(</ul>|</p>)?'),
	START('paragraph'),
	ATTRIBUTES(type='"untagged"'),
	fd_parse.TRACE(False, envlength=512),
	OUT('$paragraph_text'),
	END('paragraph')
	)


header=SEQ(
	pattern('<pagex (?P<pagex>[\s\S]*?)/>'),
	#OUT('pagex'),
	ATTRIBUTES(groupstring='pagex'),
	pattern('\s*</td></tr>\s*</table>\s*<table width="90%"\s*cellspacing=6 cols=2 border=0>\s*<tr><td>\s*</font><p>\s*(?i)'))


# TODO -- meeting may be pursuant to an order see: 2002-03-26

meeting_time=SEQ(
	START('opening'),
	pattern('\s*(<p>1&nbsp;&nbsp;&nbsp;&nbsp;|<p(?: align=center)?>)The House met at\s*'),
#	fd_parse.TRACE(False),
	archtime,
#	fd_parse.TRACE(False),
	POSSIBLY(
		SEQ(
			pattern('(,)? pursuant to Order \['),
			plaindate(),
			pattern('\]')
			)
		),
	OR(
		pattern('\s*\.'),
		pattern('; and the Speaker Elect having taken the Chair;')
		),
	pattern('</p>'),
	END('opening')
	)


speaker_signature=SEQ(
	tagged(first='\s*',
		tags=['p','b','i','font'],
		p="(?P<speaker>[-a-zA-Z.' ]+)",
		padding='\s|&nbsp;',
		last='(<font size=3>|\s|</p>)*'),
	tagged(first='\s*',
		tags=['p','b','i','font'],
		p='(?P<title>(Deputy )?Speaker(\s*Elect)?)(&nbsp;|\s)*',
		padding='\s|&nbsp;'),
	OBJECT('speaker_signature','',speaker='$speaker')
	)

prayers=pattern('\s*(<p>)?PRAYERS.(</p>)?\s*')

paragraph_number='\s*<p>(?P<number>\d+)(&nbsp;)*'


heading=SEQ(
	pattern('\s*(<p><ul>|<p align=center>)<i>(?P<desc>([^<]|<i>|</i>)*?)(</ul>)?</p>'),
	OBJECT('heading', desc='$desc')
	)

minute_main=DEFINE('minute_main',
        pattern(paragraph_number+'(?P<minute_text>[\s\S]*?)</p>')
)

DEFINE('enprint', pattern('\d+-EN'))

en_print=SEQ(
	DEBUG('beginning en_print'),
	fd_parse.TRACE(False),
	plaintext('<i>Ordered</i>, That the Explanatory Notes (relating )?to the %(act2) be printed [Bill %(enprint)].'
		),
	ELEMENT('bill',
		attrmap={
			'billname' : '"$act2"',
			'type' : '"enprinting"',
			'enno' : '"$enprintno"'
			}
		)
	)


sub_paragraph=SEQ(
	DEBUG('sub_paragraph'),
#	fd_parse.TRACE(False, vals='p
	POSSIBLY(OR(
		en_print
		))
	)


minute_indent=SEQ(
	pattern('\s*(<p>)?<ul>(?P<paragraph_text>[\s\S]*?)(</b>)?</ul></p>'),
	START('paragraph'),
	ATTRIBUTES(type='"indent"'),
	OUT('$paragraph_text'),
	CALL('$paragraph_text', sub_paragraph),
	END('paragraph')
	)

# put back in no when you have thought about it
def paragraph_text(partype='plain', no=None):
#	if no:
#		attrmap={'no' : no}
#	else:
#		attrmap=None

	return SEQ(
		pattern('(?P<paragraph_text>([^<]|<i>|</i>)+)'),
		ELEMENT('paragraph',
			body='$paragraph_text',
                        type='%s' % repr(partype)
#			attrlit={'type' : partype},
#			attrmap=attrmap
			)
		)

def process_minute():
	def anon(s,env):

		if len(env['second'])> 0:
			pass
#			print "second reading"
		return (s,env,Success())
	return anon



minute_order=SEQ(
	pattern('\s*<ul><i>Ordered</i>(,)?(?P<paragraph_text>([^<])*)</ul></p>'),
	START('paragraph',
		attrlit={'type' : 'indent'}),
	OUT('$paragraph_text'),
	OBJECT('order', paragraph_text='$paragraph_text'),
	CALL('$paragraph_text', sub_paragraph),
	END('paragraph_text')
	)

# all p ul
# clause, ((another)? amendment proposal, (question put, division) or (question proposed - amendment by leave withdrawn) or (question - put and negatived) or (question proposed, that the amendment be made) [? after a division / deferred divisions] 'and it being ? oclock on clauses A-B', (question - put and negatived), (question put, that clause A stand part of the bill, division), clauses C to B agreed to, chairmen left chair to report, hline, dep speaker resumes ..., comittee again to-morrow

# clauses E to F agreed to, bill to be reported (dep speaker...), ord bill be read a third time ...

# A clause (....) (Name) brought up and read the first time
# Another clause

#house_committee=SEQ(
#	pattern("(?P<billname>"+actpattern+" Bill\s*\[" + ordinal(dayno) + "\s*\llotted day\],-"The House, according to Order, resolved itself into a Committee on the Bill\.</p>"),
#	tagged(first="\s*", tags=['p'],p="\(In the Committee\)")

# Note: text of the sub-paragraphs will need to be spat out.
# this is a TODO.

DEFINE('standing_committee',
	pattern('Standing Committee [A-B]'))

committee_reported=OR(
	SEQ(
		DEBUG('checking committee report'),
		fd_parse.TRACE(False, envlength=512),
		CALL('$minute_text',
			SEQ(DEBUG('inside call'),
			fd_parse.TRACE(False, envlength=512),
			plaintext(

'%(act1),-%(mp) reported from %(standing_committee), That it had gone through the %(act2), and made Amendments thereunto.',

				strings={ 'committee' : '(?P<sc>[A-B])' },
				debug=True),
			ELEMENT('bill',
				attrnames=['mp','sc'],
				attrmap={
					'type' : '"commitee_reported"',
					'billname': '"$act1"'
					}
				),
			fd_parse.TRACE(False, envlength=512)),
			passback={
				'act1' : 'billname',
				'sc' : 'sc'}), 
		DEBUG('committee_reported found'),
		fd_parse.TRACE(False),
		SEQ(
			plaintextpar(

'Bill, as amended in the Standing Committee, to be considered to-morrow; and to be printed [Bill %(number1)].'),
			ELEMENT('bill', 
				billno="$number1",
				billname="$billname",
				type="'printing'"
				),
#
# I'm not sure what follows produces the right element.
			plaintextpar(

'Minutes of Proceedings of the Committee to be printed [No. %(number2)].'),
			ELEMENT('bill',
				billno="$number1",
				billname="$billname",
				type="'printing'"
				)
			)
		)
	)

bill_second=SEQ(
	pattern('; and ordered to be read a second time '),
	futureday,
	fd_parse.TRACE(False, vals=['futureday']),
	ELEMENT('bill', type='"second reading scheduled"', date='$futuredate.isoformat()', billname='$billname')
		) 

first_reading=SEQ(
	DEBUG('checking minute bill'),
	fd_parse.TRACE(False,vals=['minute_text']),
	pattern('''(?P<billname>'''+actpattern+''' Bill),-(?P<sponsor>[-A-Za-z'. ]*?)( accordingly)?((,)? supported by (?P<supporters>[-A-Za-z'., ]*?))?(,)? presented (?P<revenue>(\(under Standing Order No\. 50 \(Procedure upon bills whose main object is to create a charge upon the public revenue\)\) )?)(a Bill to [\s\S]*?)(:)?\s*And the same was read the first time'''),
	ELEMENT('bill', type='"introduction"', 
		ord50='"$revenue"=="" and "false" or "true"',
		billname='$billname',
		sponsor='$sponsor',
		supporters='$supporters'
		),
	ELEMENT('bill', type='"first reading"', billname='$billname'),
	fd_parse.TRACE(False),
	POSSIBLY(bill_second),
	DEBUG('checked bill second'),
	fd_parse.TRACE(False),
	pattern('\s*and to be printed \[\s*Bill\s*(?P<billno>\d+)\s*\](\.)?'),
	ELEMENT('bill', 
		attrlit={'type' : 'printing'},
		attrnames=['billname', 'billno']
		),

	fd_parse.TRACE(False),
	)

second_reading1=SEQ(
	#pattern('''(?P<billname>'''+actpattern+''') Bill,-The ''' + actpattern + ''' Bill was(,)? according to Order(,)? read a second time and stood committed to a Standing Committee(\.)?'''),
	plaintext('%(act1),-The %(act2) Bill was, according to Order, read a second time and stood committed to a Standing Committee.'),
	OBJECT('second_reading','actname=$act1'),
	OBJECT('commitalto_standing','actname=$act1')
	)

# How, and in what way, is the below different?

second_reading2=SEQ(
#	pattern('''(?P<billname>'''+actpattern+''') Bill,-The ''' + actpattern + ''' Bill was(,)? according to Order(,)? read a second time and stood committed to a Standing Committee(\.)?'''),
	plaintext('%(act1),-The %(act2) was, according to Order, read a second time and stood committed to a Standing Committee.'),
	OBJECT('second_reading',actname='$act1'),
	OBJECT('commitalto_standing',actname='$act1')
	)


second_reading3=SEQ(
	DEBUG('2R3'),
	fd_parse.TRACE(False, vals=['minute_text']),
	CALL('$minute_text',
		SEQ(
			plaintext('%(act1),-The Order of the day being read, for the Second Reading of the %(act2);.'),
			ELEMENT('bill', 
				type='"order read for second reading"',
				billname='$act1'
				)
			),
		passback={'act1' : 'billname'}),
	DEBUG('2r3-2'),
	OR(minute_indent, untagged_par()),
	CALL('$paragraph_text',
		SEQ(
			fd_parse.TRACE(False, vals=['paragraph_text']),
			plaintext('<i>Ordered</i>, That the Bill be read a second time ', opttags=['i']),

			fd_parse.TRACE(False),
			futureday,
			ELEMENT('bill',
					type='"second reading scheduled"',
					billname='$billname1',
					date='$futuredate.isoformat()'
					),
			plaintext('.')
			),
		)
	)

third_reading=SEQ(
	plaintext('%(act1) [%(ordinal) allotted day],-%(act2) was, according to Order, read the third time, and passed.',
		strings={
			'ordinal' : '\d+(st|nd|rd|th)'
		}),
	ELEMENT('bill',type='"third_reading"',billname='"$act1"')
	)

explanatory_notes=SEQ(
	DEBUG('explanatory notes'),
	fd_parse.TRACE(False),
	plaintext('%(act1),-'),
	en_print
	)

bill_analysis=OR(
	first_reading,
	second_reading1,	
	second_reading2,
	third_reading,
	explanatory_notes
#	house_committee
	)


#minute_bill=SEQ(
#	fd_parse.TRACE(False),
#	pattern(paragraph_number+'(?P<billname>'+actpattern+' Bill),-(?P<sponsor>[-A-Za-z. ]*?)((,)? supported by (?P<supporters>[-A-Za-z., ]*?))?(,)? presented (a Bill to [\s\S]*?)(:)?\s*And the same was read the first time'),
#	DEBUG('matched bill'),
#	fd_parse.TRACE(False),
#	POSSIBLY(bill_second),
#	DEBUG('checked bill second'),
#	fd_parse.TRACE(False),
#	pattern('\s*and to be printed \[Bill (?P<billno>\d+)\]\.</p>'),
#	OBJECT('first_reading','','billname','sponsor','billno'), #process_minute(),
#	fd_parse.TRACE(False),
#	POSSIBLY(SEQ(
#		pattern('''\s*<p><ul><i>Ordered</i>, That the Explanatory Notes relating to the '''+actpattern+''' Bill be printed \[Bill \d+-EN\]\.\s*</ul></p>''')
#		))
#	)
#

#redundant
minute_resolution=SEQ(
	pattern('\s*<ul><i>Resolved</i>(,)?(?P<text>([^<])*)</ul></p>'),
	ELEMENT('resolution', text='$text')
	)

minute_doubleindent=SEQ(
	fd_parse.TRACE(False),
	pattern('\s*<p><ul><ul>'),
#	START('doubleindent'),
	OR(
		SEQ(
			pattern('(\s|&nbsp;)*\((?P<no>[ivxldcm]+)\)(\s|&nbsp;)*'),
#			OBJECT('number','','no'),
			paragraph_text(partype='indent2', no='"$no"')
			),
		SEQ(
			paragraph_text(partype='indent2')
			)
		),
	fd_parse.TRACE(False),
	pattern('</ul></ul></p>'),
#	END('doubleindent')
	)

minute_tripleindent=SEQ(
	fd_parse.TRACE(False),
	pattern('\s*<p><ul><ul><ul>'),
	START('tripleindent'),
	OR(
		SEQ(
			pattern('(\s|&nbsp;)*\((?P<no>[ivxldcm]+)\)(\s|&nbsp;)*'),
#			OBJECT('number','','no'),
			ELEMENT('number', no='$no'),
			paragraph_text(partype='indent3')
			),
		SEQ(
			paragraph_text(partype='indent3')
			)
		),
	fd_parse.TRACE(False),
	pattern('</ul></ul></ul></p>'),
	END('tripleindent')
	)

dateheading=SEQ(
	START('dateheading'),
	pattern('\s*<p( align=center)?>'),
	DEBUG('dateheading consumed p'),
	idate,
	DEBUG('found idate'),
	pattern('\s*</p>'),
	END('dateheading')
	)


table=SEQ(
	START('table'),
	POSSIBLY(pattern('\s*<p align=center>(<i>)?Table(</i>)?</p>(\s|<ul>)*')),
	pattern('\s*(<center>)?\s*(?P<table><table[\s\S]*?</table>)\s*(</center>)?(\s|</ul>|</p>)*'),
	#OBJECT('table_markup','table'),
	OBJECT('table_markup',''),
	END('table'),
	SET('paragraph_text', '"[Tabular data omitted]"')
	)


speaker_absence=SEQ(
	fd_parse.TRACE(False),
	pattern('''\s*(The)? Speaker's Absence'''),
	OR(
		SEQ(
			pattern(',-The House being met, and the Speaker having leave of absence pursuant to paragraph \(3\) of Standing Order No\. 3 \(Deputy Speaker\)\, ' + namepattern('deputy')+', the (?P<position>((First|Second) Deputy |)Chairman of Ways and Means), proceeded to the Table\.'),
#			OBJECT('speaker_absence','','deputy','position')
			ELEMENT('speaker_absence', deputy='$deputy', deputy_position='$position')
			),
		SEQ(
			pattern(',-<i>Resolved</i>, That the Speaker have leave of absence on '),
			futureday,
			pattern('[^<]*?\.-\(<i>'+namepattern('proposer')+'</i>\.'),
#			OBJECT('speaker_future_absence','','proposer')
			ELEMENT('speaker_future_absence', proposer='$proposer')
			)
		)
	)

oathtaking=SEQ(
	START('oathtaking'),
	fd_parse.TRACE(False),
	DEBUG('Members take the Oath'),
	pattern('\s*Members take the Oath or make Affirmation,-(Then )?the following Members took and subscribed the Oath, or made and subscribed the Affirmation required by law:'),
	DEBUG('...Members take the Oath'),
	END('oathtaking')
	)

member_single_oath=SEQ(
	pattern(u"\s*<p>\s*(?P<mp_name>[-A-Za-z'\xd6, .]+)\s*</p>"),
	fd_parse.TRACE(False),
	pattern(u"\s*<p>\s*(<i>for\s*</i>)?(?P<constituency>[-.A-Z',a-z&\xf4 ]*?)</p>"),
	ELEMENT('oath', mp_name='$mp_name', constituency='$constituency')
	) 

member_oaths=ANY(
	OR(
		pattern('\s*<p><ul></ul></p>'),
		member_single_oath
		)
	)
	

minute_plain=DEFINE('minute_plain', SEQ(
	fd_parse.TRACE(False, slength=512),
	minute_main, 
	fd_parse.TRACE(False, slength=512),
	START('minute',number='$number'),
	POSSIBLY(
		OR(
			CALL('$minute_text', speaker_absence),
			second_reading3,
			CALL('$minute_text', bill_analysis),
			SEQ(
				CALL('$minute_text', oathtaking),
				member_oaths
				),
			committee_reported
			)
		),
	DEBUG('just completed analyses'),
	fd_parse.TRACE(False, slength=512),
	ELEMENT('maintext', body='$minute_text'),
#	SET('paragraph_text','""'),
	ANY(
                OR(
		        minute_order,
		        minute_tripleindent,
	        	minute_doubleindent,
		        minute_indent,
		        dateheading,
		        SEQ(
                                untagged_par(), 
                                CALL('$paragraph_text', sub_paragraph),
		                table
        		)
                )
        ),
#	fd_parse.TRACE(False, vals=['paragraph_text']),
#	POSSIBLY(CALL('$paragraph_text', en_print)),
        END('minute')
))

vote=SEQ(
	fd_parse.TRACE(False),
	pattern('\s*<p><ul><ul>Tellers for the Ayes, '+namepattern('ayeteller1')+', '+namepattern('ayeteller2')+': (?P<ayevote>\d+)(\.)?</ul></ul></p>'),
	DEBUG('ayeteller1'),
	pattern('\s*<p><ul><ul>Tellers for the Noes, '+namepattern('noteller1')+', '+namepattern('noteller2')+'(:)?\s*(?P<novote>\d+)(\.)?</ul></ul></p>'),
	DEBUG('ayeteller2'),
	pattern('\s*<p><ul>So the Question was agreed to\.</ul></p>')
	)


bill_programme_amendment=SEQ(
	CALL('$minute_text',
		plaintext('%(act1) Bill (Programme)(No. \d+),-A Motion was made, and the Question being proposed, That the Programme Order of %(date) relating to the %(act2) Bill be varied as follows:'),
		passback={'act1' : 'billname'}
		),
	plaintextpar('%(text)And it being %(timequantum) after the commencement of proceedings on the Motion, the %(speaker) put the Question, pursuant to Standing Order No. 83A (Programme motions).\s*The House divided.'),
	ELEMENT('bill',
		attrmap={
			'billname' 	: '"$billname"',
			'type'		: '"programme"',
			'subtype'	: '"amendment"',
			'text'		: '"$text"'
			}
		),
	vote
	)


division=SEQ(
	fd_parse.TRACE(False),
	pattern('\s*<p><ul>The House divided(\.)?</ul></p>'),
	DEBUG('matched division'),
	FORCE(vote),
	ELEMENT('division', ayevote='$ayevote', novote='$novote', ayeteller1='$ayeteller1', ayeteller2='$ayeteller2', noteller1='$noteller1', noteller2='$noteller2')
	)

programme_minute=SEQ(
	START('programme_minute'),
	OR(
		SEQ(
			pattern('\s*<p><i>(?P<maintext>([^<]|<i>|</i>)*?)</p>'),
			ELEMENT('paragraph',body='$maintext')
			),
		SEQ(
			pattern('\s*<p><ul>(\d+\.|\(\d+\))\s*(?P<maintext>([^<]|<i>|</i>)*)</ul></p>'),			
			fd_parse.TRACE(False, envlength=512),
			ELEMENT('paragraph',body='$maintext')
			),
		SEQ(
			pattern('\s*<p><ul><ul>(\([a-z]\)|\d+)\s*((?P<maintext>[^<]|<i>|</i>)*)</ul></ul></p>'),
			ELEMENT('paragraph',body='$maintext')
			),
		untagged_par(),
		table
	),
	END('programme_minute')
	)

next_committee=SEQ(
	DEBUG('next committee'),
	fd_parse.TRACE(False),
	plaintextpar('Committee to-morrow.'),
	fd_parse.TRACE(False),
#	tagged(
#		first='\s*',
#		tags=['p','ul'],
#		p='Committee to-morrow.',
#		fixpunctuation=True),
	OBJECT('committee_to_morrow','')
	)

programme_order=SEQ(
	DEBUG('programme_order'),
	pattern('''\s*<p><ul><i>Ordered(,)?\s*</i>(,)?\s*That the following provisions shall apply to the '''+actpattern+''' Bill:</ul></p>'''),
	DEBUG('found ordered'),
	ANY(programme_minute)
	)	

minute_programme=SEQ(
	fd_parse.TRACE(False),
	pattern(paragraph_number+'(?P<maintext>[\s\S]*?the following (provisions|proceedings) shall apply to (proceedings on )?the (?P<bill>[\s\S]*?Bill)( \[<i>Lords</i>\])?(-|:|\s*for the purpose of[^<]*?)?)</p>'),
	fd_parse.TRACE(False),
	START('bill_programme',bill='$bill'),
	FORCE(SEQ(
		DEBUG('matched the start of a Bill programme'),
		ANY(OR(
			heading,
			programme_minute,
			)),
		POSSIBLY(division),
		POSSIBLY(programme_order),
		POSSIBLY(next_committee)
		)),
	END('bill_programme')
	)

minute_ra=SEQ(
	pattern(paragraph_number+'Royal Assent'),
	FORCE(SEQ(
		START('royal_assent'),
		fd_parse.TRACE(False),
		pattern('(,)?(-)?The (Deputy )?Speaker notified the House(,)? in accordance with the Royal Assent Act 1967(,)? That Her Majesty had signified her Royal Assent to the following Act(s)?(,)? agreed upon by both Houses((,)? and to the following Measure(s)? passed under the provisions of the Church of England \((Assembly )?Powers\) Act 1919)?(:)?</p>(?i)'),
		fd_parse.TRACE(False),
		ANY(OR(
			act,
			measurepattern,
			pattern('\s*<p><ul>(_)+</ul></p>')
			)),
		END('royal_assent')
		))
	)

detached_paragraph=DEFINE('detached_paragraph',
	SEQ(
		STOP('a detached paragraph was found.')
		)
)

minute=DEFINE('minute', 
	OR(
		detached_paragraph,
		minute_programme,
		minute_ra,
		minute_plain,
	)
)

adj_motion=SEQ(
	plaintextpar('Adjournment,-<i>Resolved</i>, That the sitting be now adjourned.-'),
#	tagged(
#		first='\s*',
#		tags=['p','ul'],
#		p='Adjournment,-<i>Resolved</i>, That the sitting be now adjourned.-',
#		fixpunctuation=True
#		),
	fd_parse.TRACE(False),
	pattern('\(<i>'+namepattern('proposer')+'</i>(\.)?\)(\s|</ul>|</p>)*'),
#	OBJECT('adj_motion','','proposer')
	ELEMENT('adj_motion', proposer='$proposer')
	)

adjournment=SEQ(
	DEBUG('attempting to match adjournment'),
	POSSIBLY(OR(
		SEQ(
		pattern('\s*And accordingly, the House, having continued to sit till'),
		fd_parse.TRACE(False),
		archtime,
		pattern('\s*(,)?\s*adjourned (till|until) to-morrow(\.)?')
		),
		tagged(
			tags=['p','ul'],
			p='And accordingly the sitting was adjourned till [^<]*(\.)?')			
	)),
	pattern('\s*(<p( align=right)?>)?\s*\[Adjourned at (?P<time>\s*(\d+(\.\d+)?\s*(a\.m\.|p\.m\.)|12\s*midnight(\.)?))\s*(</p>)?'),
	ELEMENT('adjournment',time='$time'),
	POSSIBLY(emptypar2),
	fd_parse.TRACE(False)
	)

speaker_address=pattern('\s*(<p><ul>)?Mr Speaker(,|\.)(</ul></p>)?\s*<p><ul>The Lords, authorised by virtue of Her Majesty\'s Commission, for declaring Her Royal Assent to several Acts agreed upon by both Houses(, and under the Parliament Acts 1911 and 1949)? and for proroguing the present Parliament, desire the immediate attendance of this Honourable House in the House of Peers, to hear the Commission read.</ul></p>')

royal_assent=SEQ(
	START('royal_assent'),
	pattern('\s*<p><ul>Accordingly the Speaker, with the House, went up to the House of Peers, where a Commission was read(,)? giving, declaring and notifying the Royal Assent to several Acts, and for proroguing this present Parliament.</ul></p>'),
	DEBUG('the royal assent...'),
	pattern('\s*(<p><ul>)?The Royal Assent was given to the following Acts( agreed upon by both Houses)?:(-)?(</ul></p>)?'),
	DEBUG('parsed "Accordingly the Speaker"'),
	ANY(act),
	POSSIBLY(SEQ(
		START('parlact'),
		DEBUG('attempting to match parliament act'),
		fd_parse.TRACE(False),
		pattern('\s*The Royal Assent was given to the following Act, passed under the provisions of the Parliament Acts 1911 and 1949:'),
		act,
		pattern('\s*\(The said Bill having been endorsed by the Speaker with the following Certificate:</p><p>'),
		pattern('\s*I certify, in reference to this Bill, that the provisions of section two of the Parliament Act 1911, as amended by section one of the Parliament Act 1949, have been duly complied with.</p>'),
		speaker_signature,
		pattern('\.\)\s*</p>'),
		END('parlact')
		)),
	END('royal_assent')
	)

royal_speech=SEQ(	
	DEBUG('starting the royal speech'),
	pattern('\s*<p>\s*(<ul>)?And afterwards Her Majesty\'s Most Gracious Speech was delivered to both Houses of Parliament by the Lord High Chancellor \(in pursuance of Her Majesty\'s Command\), as follows:(</ul>)?</p>'),
	DEBUG('royal speech: My Lords'),
	fd_parse.TRACE(False),
	pattern('\s*<p>\s*(<ul>)?My Lords and Members of the House of Commons(,)?(</ul>)?</p>'),
	pattern('(?P<royalspeech>[\s\S]*?)<p>\s*(<ul>)?I pray that the blessing of Almighty God may (attend you|rest upon your counsels)\.\s*(</ul>)?</p>'),
	DEBUG('end of royal speech'),
	fd_parse.TRACE(False),
	DEBUG('finished the royal speech'),
	START('royalspeech'),
	CALL('$royalspeech', splitparagraphs),
	END('royalspeech'),
#	OBJECT('royalspeech','royalspeech')
	)

words_of_prorogation=SEQ(
	pattern('\s*<p>\s*(<ul>)?After which the Lord Chancellor said:(</ul>)?</p>'),
	pattern('\s*<p>\s*(<ul>)?My Lords and Members of the House of Commons(,|:)?(</ul>)?</p>'),
	DEBUG('and now by virtue of...'),
	pattern('\s*<p>\s*(<ul>)?By virtue of Her Majesty\'s Commission which has now been read(,)? we do, in Her Majesty\'s name, and in obedience to Her Majesty\'s Commands, prorogue this Parliament to (?P<pdate1>[-a-zA-Z\s]*)(,)? to be then here holden, and this Parliament is accordingly prorogued to (?P<pdate2>[-a-zA-Z\s]*)\.(</ul>)?</p>'),
	DEBUG('parliament prorogued')
	)

prorogation=SEQ(
	pattern('\s*<p>\d+&nbsp;&nbsp;&nbsp;&nbsp;Message to attend the Lords Commissioners,-A Message from the Lords Commissioners was delivered by the Gentleman Usher of the Black Rod\.</p>'),
	FORCE(SEQ(
		DEBUG('start prorogation'),
		START('prorogation'),
		speaker_address,
		royal_assent,
		DEBUG('parsed royal assents'),
		royal_speech,
		DEBUG('parsed royal speech'),
		words_of_prorogation,
		DEBUG('parsed words of prorogation'),
		speaker_signature,
		END('prorogation')
		)),
	DEBUG('prorogation success')
	)

speaker_chair=SEQ(
	POSSIBLY(pattern('\s*<TR><TD><HR size=1></TD></TR>')),
	pattern('\s*(<tr><td><center>|<p align=center>)Mr Speaker (Elect )?will take the Chair at '),
	archtime,
	POSSIBLY(SEQ(pattern('\s*on\s*'),OR(plaindate(),futureday))),
	pattern('.(</center></td></tr>|</p>)\s*'),
	POSSIBLY(pattern('\s*<TR><TD><HR size=1></TD></TR>'))
	)

app_title=SEQ(
	DEBUG('checking for app_title'),
	fd_parse.TRACE(False),
	tagged(first='\s*',
		tags=['p','ul'],
		padding='\s',
		p='(<p align=center>)?APPENDIX\s*(?P<appno>(III|II|I|))(</p>)?(?=</)'
	),
	fd_parse.TRACE(False),
	DEBUG('starting object appendix'),
	START('appendix', appno='$appno')
	)

app_heading=heading

app_date=SEQ(
	fd_parse.TRACE(False),
	pattern('\s*<p( align=center)?>'),
	START('date_heading'),
	idate,
	pattern('\s*</p>'),
	END('date_heading')
	)


app_nopar=SEQ(
	pattern('\s*<p>(<ul>)?(<i>)?(?P<no>\d+)&nbsp;&nbsp;&nbsp;&nbsp;(</i>)?(?P<maintext>[\s\S]*?)(</ul>)?</p>'),
	ELEMENT('app_nopar',body='$maintext',no='$no')
	)

misc_par=SEQ(
	pattern('\s*<p( align=left)?>\s*(<ul>)?(?P<maintext>(?!\[W\.H\.|\[Adjourned)([^<]|<i>|</i>)+)(</ul>)?(</p>)?'),
	OBJECT('miscpar',maintext='$maintext')
	)

app_par=misc_par

app_subheading=SEQ(
	pattern('\s*<p( align=left)?><i>(?P<maintext>([^<]|<i>|</i>)*)</p>'),
	OBJECT('app_subhead',maintext='$maintext')
	)

app_nosubpar=SEQ(
	pattern('\s*<p><ul>\(\d+\)([^<]|<i>|</i>)*</ul></p>')
	)

#date accidently put in a separate paragraph

app_date_sep=pattern('\s*<p><ul>dated ([^<]|<i>|</i>)*?\[[a-zA-Z ]*\].</ul></p>')

attr_sep=pattern('\s*<p><ul>\[by Act\]\s*\[[\s\S]*?\].</ul></p>')

appendix=SEQ(
	app_title,
	DEBUG('after app_title'),
	fd_parse.TRACE(False), 
	ANY(SEQ(fd_parse.TRACE(False),OR(
		app_nopar,
		minute_doubleindent,
		app_date,
		app_heading,
		app_subheading,
		app_nosubpar,
		app_date_sep,
		attr_sep,
		app_par,
		OR(emptypar, emptypar2),
		untagged_par(),
		))),
	END('appendix'),
	DEBUG('ended appendix')
	)

westminsterhall=SEQ(
	START('westminsterhall'),
	POSSIBLY(pattern('\s*<hr width=90%>')),
	pattern('\s*(<p>\s*</p>|<p>\s*<p>)?\s*<p( align=center)?>\s*\[\s*W.H.(,)?\s*No(\.)?\s*(?P<no>\d+)\s*\]\s*</p>'),
	tagged(
		tags=['p','font','b'],
		first='\s*',
		padding='<p>',
		p='Minutes of Proceedings of the Sitting in Westminster Hall',
		last='(<font size=3>)?(</p>)?'
		),
	POSSIBLY(SEQ(
		pattern('(\s|<p>|<b>)*\[pursuant to the Order of '),
		plaindate(),
		pattern('\](|</font>|</b>)*</p>'),
		)),
	pattern('\s*(<p( align=center)?>)?(<font size=3>)?The sitting (commenced|began) at (?i)'),
	archtime,
	pattern('.(</b>)?</p>'),
	DEBUG('remaining westminster hall'),
	ANY(SEQ(fd_parse.TRACE(False),
		OR(
			adj_motion,
			pattern('\s*<p><ul><ul>([^<])*?</ul></ul></p>'),
			misc_par,
			untagged_par()
			)), 
		until=adjournment),
	fd_parse.TRACE(False),
	speaker_signature,
	END('westminsterhall'))	

chairmens_panel=SEQ(
	tagged(
		first='\s*',
		tags=['p','b'],
		p='''CHAIRMEN'S PANEL'''
		),
	DEBUG('chairmen\'s panel...'),
	pattern('''\s*<p>(<ul>)?In pursuance of Standing Order No\. 4 \(Chairmen's Panel\)(,)? the Speaker (has )?nominated '''),
	OR(
		pattern(u'''([-A-Za-z \xd6.']+?, )*?([-A-Za-z \xd6.']+?) and ([-A-Za-z \xd6.']+? )to be members of the Chairmen's Panel during this Session(\.)?</p>'''),
		pattern(u'''([-A-Za-z \xd6.']+? )to be a member of the Chairmen's Panel during this Session( of Parliament)?(\.)?(</ul>)?</p>''')
		),
	OBJECT('chairmens_panel','')
	)

#TODO: this is absorbed into appendices on some occasions, need to stop that
#happening, eg 2001-10-18. It can preceed westminsterhall.

certificate=SEQ(
	POSSIBLY(pattern('\s*<p( align=center)?>_+</p>')),
	tagged(
		first='\s*',
		padding='\s',
		tags=['p','b','ul'],
		p='''THE SPEAKER'S CERTIFICATE'''
		),
	tagged(
		first='\s*',
		tags=['p','ul'],
		p='''\s*The Speaker certified that the (?P<billname>[-a-z.,A-Z0-9()\s]*?Bill) is a Money Bill within the meaning of the Parliament Act 1911(\.)?'''
		),
	OBJECT('money_bill_certificate',billname='$billname'),
	POSSIBLY(pattern('\s*<p( align=center)?>_+</p>')),
	)

endpattern=pattern('\s*</td></tr>\s*</table>\s*')

O13notice=SEQ(
	START('O13notice'),
	pattern('\s*<p align=center>_______________</p>'),
	pattern('\s*<p><ul><i>Notice given by the Speaker, pursuant to Standing Order No. 13 \(Earlier meeting of House in certain circumstances\):</i></ul></p>'),
	ANY(
		SEQ(
			DEBUG('misc paragraphs'),
			pattern('\s*<p><ul>(?P<text>[\s\S]*?)</ul></p>'),
			OBJECT('par',body='$text')
		)
	),
	speaker_signature,
	POSSIBLY(pattern('\s*</table>(?i)')),
	POSSIBLY(pattern('\s*<p align=center>_________________________________________</p></center>')),
	pattern('\s*(<p>|<tr><td>)<FONT size=\+2><B><CENTER>(?P<date>[\s\S]*?)</B></CENTER></FONT>\s*(</p>|</td></tr>)(?i)'),
	pattern('\s*<TABLE WIDTH="90%" CELLSPACING=6 COLS=2 BORDER=0>\s*<TR><TD>(?i)'),
	END('O13notice')
	)

corrigenda=SEQ(
	POSSIBLY(pattern('\s*<hr[^>]*>')),
	pattern('(<p( align=center)?>|\s)*CORRIGEND(A|UM)</p>\s*'),
	START('corrigenda'),
	ANY(
		OR(
			pattern('\s*<p><ul>[\s\S]*?</ul></p>'),
			pattern('\s*<ul>[\s\S]*?</ul>'),
			untagged_par()
			)
	),
	END('corrigenda')
	)

memorandum=SEQ(
	pattern('\s*<p( align=center)?>MEMORANDUM</p>'),	
	START('memorandum'),
	POSSIBLY(
		SEQ(
			pattern('\s*<p align=center>(?P<text>[^<]*?)</p>'),
			OBJECT('heading',text='$text')
			)
		),
	POSSIBLY(
		SEQ(
			pattern('\s*<p>(?P<text>[^<]*?)</p>'),
			OBJECT('paragraph', text='$text')
			)
		),	
	END('memorandum')
	)




footnote=SEQ(
	pattern('\s*Votes and Proceedings:'),
	plaindate(),
	pattern('\s*No. \d+')
	)

page=SEQ(
	START('page',date='$date'),
	header, 
	POSSIBLY(pattern('\s*<p><b>Votes and Proceedings</b></p>')),
	POSSIBLY(O13notice), #O13notice,
	meeting_time,
	#prayers,
## prayers don't necessary come first (though they usually do)
	ANY(
		SEQ(fd_parse.TRACE(False),OR(prayers,minute)),
		until=prorogation, 
		otherwise=SEQ(adjournment, speaker_signature, speaker_chair)), 
	DEBUG('now check for appendices'),
	ANY(appendix),
	POSSIBLY(chairmens_panel),
	#chairmens_panel,
	POSSIBLY(certificate), 
	#certificate,
	POSSIBLY(westminsterhall), 
	#westminsterhall,
	POSSIBLY(corrigenda), 
	#corrigenda,
	POSSIBLY(memorandum),
	POSSIBLY(footnote),
	POSSIBLY(pattern('(\s|<p>|</p>)*')),
	DEBUG('endpattern'),
	endpattern,
	END('page')
	)

votedir=os.path.join(toppath, 'cmpages/votes')

def getstring(date):
	name='votes%s.html' % date
	f=open(votedir+'/'+name) # do not do this
	s=f.read()
        return s

def parsevote(date):
        s=getstring(date)
        return parsevotetext(s, date)

def stringprep(s):
        s=s.replace('\x99','&#214;')
	s=s.decode('latin-1')

	# I am not sure what the <legal 1> tags are for. At present
	# it seems safe to remove them.

	s=s.replace('<legal 1>','<p><ul>')
	s=re.sub('<br><br>(?i)','</p><p>',s)
	s=re.sub('<br>(?i)','</p>',s)
	s=s.replace('&#151;','-')

	s=s.replace('</i></b><b><i><i><b>','') # has no useful effect
	s=s.replace('</i></b></i></b>','</i></b>')
	s=re.sub('alaign=center(?i)','align=center',s)
	s=re.sub('<i>\s*</i>','',s)
	s=re.sub('</p>\s*</ul>','</p>\n',s)
        return s

#	return page(s,{'today': '"%s"' % date, 'date':date})
def parsevotetext(s, date):
        s=stringprep(s)
	return page(s,{'today': fromiso(date), 'date':date})

if __name__ == '__main__':

	date=sys.argv[1]
	(s,env,result)=parsevote(date)
	
	
	if result.success:
	
		#output='''<?xml version="1.0" encoding="ISO-8859-1"?>'''+result.text()
		xml=result.delta.apply(None)
		output=xml.toprettyxml(encoding="ISO-8859-1")
		print output

		outdir='votes'
		outfile=os.path.join('votes','votes%s.xml' % date)
		fout=open(outfile,'w')
		fout.write(output)
	else:
		print result
		print "----"
		print s[:128]
		sys.exit(1)

# For calling from lazyrunall.py
def RunVotesFilters(fout, text, sdate, sdatever):
        (s,env,result)=parsevotetext(text, sdate)

        if result.success:
		result.delta.apply(None).writexml(fout, encoding="ISO-8859-1")
#                WriteXMLHeader(fout)
#                fout.write(result.text())  
        else:
                raise ContextException("Failed to parse vote\n%s\n%s" % (result, s[:128]))




