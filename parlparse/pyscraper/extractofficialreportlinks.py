#! /usr/bin/python2.4
import sys
import re
import string

import mx.DateTime

rexoffrep = '<i>official(?:\s|</?i>)*report,?</i>(?i)'
rexdate = '(\d+\s+\S+(?:\s+\d+)?)'
rexcolnum = 'column[s.]*?\s+(\d+[ws]*|[wa]*\d+)'
rexhonmem = '(to the hon.*?\(.*?\))'

rex1 = '%s[.,]?\s+\(?%s,?\s+%s\)?(?i)' % (rexdate, rexoffrep, rexcolnum)
rex2 = '%s[.,]?\s+%s,\s+%s,?\s+%s(?i)' % (rexdate, rexhonmem, rexoffrep, rexcolnum)

rexirefer = '(i (?:therefore |would )?refer)\s+(.*?%s.*?)$(?i)' % rexoffrep
rextoans = 'to (?:the|my) answers?'
#(.*?\s+given|gave)\s+(.*?%s.*?)' % rexoffrep

def IdentCodes(lline, i, n):
	#print '%d/%d:  %s' % (i, n, lline)

	# 1:  I regret that it has not been possible to provide an answer before Prorogation, I will write to the hon. Member and place a copy of the letter in the Library.
	# 1:  I will write to my hon. Friend and place a copy of my letter in the Library.
	# 1:  I will write to the hon. Member and place a copy of my letter in the Library.

	return lline


def Sx(sm, st, text):
	while 1:
		m = re.search(sm, text)
		if not m:
			return text

		text = text[:m.span(0)[0]] + st + text[m.span(0)[1]:]


def ExtractOfficialReportLinks(text):
	return []

	tx = text
	tx = Sx(' hon\. ', ' honourable ', tx)
	tx = Sx(' Member ', ' member ', tx)

	honconst = 'the honourable [Mm]embers? for .*?\(.*?\)|my (?:[Rr]ight,? )?honourable [Ff]riend the [Mm]ember for .*?\(.*?\)|the Minister for the Cabinet Office \(.*?\)'
	#honconst = '\([A-Z][a-z][a-zA-Z. ]{3,50}\)'
	sh = re.findall(honconst, tx)
	for lsh in sh:
		ssh = re.findall(honconst, lsh[1:])
		if ssh:
			print '  subtracting  ',
			sh = ssh
		print sh[0]

	honconst = '\([A-Z][a-z][a-zA-Z. ]{3,50}\)'
	sh = re.findall(honconst, tx)
	for lsh in sh:
		print '   ' + lsh


	tx = Sx(' honourable [Gg]entleman ', ' honourable member ', tx)
	tx = Sx(' honourable [Ff]riend ', ' honourable member ', tx)
	tx = Sx(' right honourable ', ' honourable ', tx)

	sb = re.findall('the honourable member', tx)
	#if sb:
	#	print sb[0],
	res = []
	return res

	if not re.search(rexoffrep, text):
		return res

	words = re.findall('(\S+)\s*', text)
	if len(words) < 20:
		print words
	return res

	# dig out the the rest of the date.
	# 10 June 2003, <i>Official Report,</i> column 771W
	ho = re.findall(rex1, text)
	for hho in ho:
		colno = hho[1]
		date = mx.DateTime.DateTimeFrom(hho[0]).date
		res.append((date, colno, 'someone', 'someone'))
	return res

	ho.extend(re.findall(rex2, text))
	if ho:
		for hho in ho:
			print hho
	else:
		print text
		#sys.exit()

	# dig out the full statement if possible.

	#I refer my hon. Friend to the written answer which I gave to the hon. Member for Eltham (Clive Efford) on 15 September 2003, <i>Official Report,</i> column 559W.

	# separate out the clauses of the sentence
	ir = re.findall(rexirefer, text)
	if not ir:
		return
	otext = text
	text = ir[0][1]

	# transformal grammar
	text = re.sub('hon[.](?i)', 'honourable', text)
	text = re.sub('Member', 'member', text)
	text = re.sub('my honourable friend(?i)', 'the honourable member', text)
	text = re.sub('honourable (?:lady|gentleman)(?i)', 'honourable member', text)

	text = re.sub('written ', '', text)
	text = re.sub('reply', 'answer', text)
	text = re.sub('Parliamentary Statement', 'answer', text)
	text = re.sub('statement', 'answer', text)
	text = re.sub('response', 'answer', text)

	text = re.sub('answer made', 'answer given', text)
	text = re.sub('member the answer', 'member to the answer', text)
	text = re.sub('to my answer of', 'to the answer I gave on', text)
	text = re.sub('to my answer', 'to the answer given by me', text)
	text = re.sub('(?:which|that) I gave', 'I gave', text)
	text = re.sub('I gave him', 'I gave to him', text)
	text = re.sub('I gave the', 'I gave to the', text)
	text = re.sub('I gave', 'given by me', text)
	text = re.sub('answer (my .*?) gave', 'answer given by \\1', text)
	text = re.sub('to this house on', 'on', text)

	# transform the given clause
	text = re.sub('given to (.*?) by (.*?) on', 'given by \\2 to \\1 on', text)
	text = re.sub('given on', 'given by someone on', text)
	text = re.sub('(given by .*?) on', '\\1 to someone on', text)
	text = re.sub('given (to .*? on)', 'given by someone \\1', text)

	#print text
	#return

	#print '\t' + text

	# filter the text down
	rexfilt = '^(^the honourable member to the answers?) given (by .*?) (to .*?) on (.*?%s.*?)$(?i)' % rexoffrep
	rm = re.findall(rexfilt, text)
	if not rm:
		return

	print rm[0][1:3]

	#sys.exit()


#ss = s.split('\n')



def Munge(text):
	st = re.split('([,.]|\s+)', text)
	tx = ''
	for ss in st:
		if re.search('\S', ss):
			tx = tx + ':' + string.strip(ss) + ':'
	#print re.sub('::', ' ', tx)

	tx = Sx('.*?::I::refer:', ':I::refer:', tx)
	tx = Sx(':I::would::refer:', ':I::refer:', tx)

	tx = Sx(':<i>Official::Report</i>:', ':OffReport:', tx)
	tx = Sx(':<i>Official::Report::,::</i>:', ':OffReport:', tx)
	tx = Sx(':hon::\.:', ':honourable:', tx)
	tx = Sx(':the::honourable::[Mm]ember:', ':MP:', tx)
	tx = Sx(':the::honourable::[Gg]entleman:', ':MP:', tx)
	tx = Sx(':right::honourable:', ':honourable:', tx)
	tx = Sx(':my::honourable::[Ff]riend:', ':MP:', tx)

	tx = Sx(':[Ww]ritten::[Aa]nswer:', ':reply:', tx)
	tx = Sx(':answers?:', ':reply:', tx)
	tx = Sx(':reply:', ':statement:', tx)
	tx = Sx(':Parliamentary::Statement:', ':statement:', tx)
	tx = Sx(':[Ww]ritten::[Mm]inisterial::[Ws]tatement:', ':statement:', tx)
	tx = Sx(':[Ww]ritten::[Ws]tatement:', ':statement:', tx)

	tx = Sx(':my::statement:', ':the::statement::given::by::me:', tx)
	tx = Sx(':I::provided:', ':I::gave:', tx)
	tx = Sx(':I::gave:', ':given::by::me:', tx)
	tx = Sx(':gave::to:', ':gave:', tx)

	tx = Sx(':to::this::[Hh]ouse:', '', tx)

	tx = Sx(':MP::for:.*?\(.*?\):', ':MP:', tx)
	tx = Sx(':MP::the:.*?\(.*?\):', ':MP:', tx)
	tx = Sx(':MP::the::Member::for::.*?::OffReport:', ':MP::OffReport:', tx)

	tx = Sx(':o[nf]::\d+::[^:]*::\d+:', ':DATE:', tx)
	tx = Sx(':o[nf]::\w+::\d+::[^:]*::\d+:', ':DATE:', tx)
	tx = Sx(':o[nf]::\d+::[^:]*:', ':DATE:', tx)
	tx = Sx(':columns:', ':column:', tx)
	tx = Sx(':column::\.:', ':column:', tx)
	tx = Sx(':column::\d+[^:]*?[WS]*?:', ':COLNUM:', tx)
	tx = Sx(':COLNUM::and::\d+[^:]*?[WS]*?:', ':COLNUM:', tx)

	tx = Sx(':DATE::to::MP:', ':to::MP::DATE:', tx)

	tx = Sx(':DATE::,:', ':DATE:', tx)
	tx = Sx(':OffReport::,:', ':OffReport:', tx)

	tx = Sx(':DATE::OffReport::COLNUM:', ':OFFREP:', tx)

	tx = Sx(':OFFREP::to::MP:', ':to::MP::OFFREP:', tx)

	tx = Sx(':OFFREP::[.,]::.*?:$', ':OFFREP::.:', tx)

	print re.sub('::', ' ', tx)



#for s in ss:
#	Munge(s)
