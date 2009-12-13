
# http://website.lineone.net/~david.beamish/peerages.htm

import re
import mx.DateTime


a = open("ghgh.html")
b = a.read()
a.close()

tab = re.search("<table><col[^>]*>([\s\S]*)</table>(?i)", b).group(1)
lins = re.findall("<tr>([\s\S]*?)</tr>(?i)", tab)
for lin in lins:
	s = re.match("""(?ix)<td[^>]*><a\sname[^>]*>
					([^<(]*)			# 1 date
					(\(\d*\s*(?:noon|a\.m\.|p\.m\.)\))?	# 2 time of day
					\s*
					(\([HIPXS:ALl]*\))? # 3 type of lord
					</a></td><td>
					([LMEVBDCP](?:ss|\.))# 4 title of lord
					(\sof)?\s?			# 5 oftitle
					<b>([^<]*?)			# 6 name of lord
					(?:\sof\s([^<]*))?</b>  # 7 ofname
					([\s\S]*?)          # 8 remainder
					(?:\((died|extinct\(\d+\)) ([^)]*)\))?  # 9, 10 death of lord
					</td>""", lin)
	if not s:
		print lin
	sdate = mx.DateTime.DateTimeFrom(s.group(1)).date
	sdeath = s.group(10) and mx.DateTime.DateTimeFrom(s.group(10)).date
	if s.group(9):
		print s.group(9)
	lordtitle = s.group(4)
	lordname = s.group(6)
	lordofname = s.group(7)
	if s.group(5):
		assert not s.group(7)
		lordname = None
		lordofname = s.group(6)
	else:
		lordname = s.group(6)
		lordofname = s.group(7)

	#if lordname != "Thatcher":
	if sdate[:4] != "1992":
		continue

	print 'title="%s" name="%s" ofname="%s" in="%s" out="%s"' % \
			(lordtitle, lordname or "", lordofname or "", sdate, sdeath or "")

#	print s.group(5), ":::", s.group(7)

"""
<tr><td bgcolor="#FFFFFF"><A NAME="18010626">
26 June 1801 (P)</A>
</td><td>
E. of <b>Wilton</b> of Wilton Castle in the County of Hereford (& V. <b>Grey de Wilton</b>) &#8211; Thomas Grey <i>Egerton</i> (1st L. Grey de Wilton) (died 23 Sep 1814)
</td></tr>
"""

