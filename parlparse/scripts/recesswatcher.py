#!/usr/bin/env python2.4
# vim:sw=4:ts=4:et:nowrap

# Extact list of dates of recesses of parliament, and write a message out on
# the day of a recess start or end.  So I know they are happening when called
# from a cron job.  Writes out to a parl-recesses.txt file which we can load
# from elsewhere (PHP).  To be run every day.

import urllib
import re
import mx.DateTime
import datetime
import os
import csv
import smtplib
import sys

recess_file = os.path.expanduser('~/parldata/parl-recesses.txt')
recess_file_new = recess_file + ".new"

toaddrs = [ "parlparse@seagrass.goatchurch.org.uk" ] # @seagrass

today = datetime.date.today().isoformat()

# This is the new location, but the script is currently broken, and unneeded, aww
url = "http://www.parliament.uk/what_s_on/recess.cfm"

ur = urllib.urlopen(url)
co = ur.read()
ur.close()

def domail(subject, msg):
	msg = "From: The Recess Gods <francis@flourish.org>\n" +  \
		  "To: " + ", ".join(toaddrs) + "\n" + \
		  "Subject: " + subject + "\n\n" +  \
		  msg + url + "\n"
	server = smtplib.SMTP('localhost')
	server.sendmail("francis@flourish.org", toaddrs, msg)
	server.quit()

# Matches this kind of table cell:
#     <td class="editonprotabletext">^M
#     <p><font size="2">18 December 2003</font></p>^M
#     </td>^M

cells = re.findall('<td class="editonprotabletext"[^>]*>\s*' +
                   '<p[^>]*>(?:<font size="2">)?(?:<i>)?' +
                   '([^<]*?)' +
                   '(?:</i>)?(?:</font>)?</p>' +
                   '\s*</td>', co)

# Within table of dates, have 3 columns
if len(cells) % 3 <> 0:
    print cells
    raise Exception, "Expect multiple of 3 table cells"

# Load them in and match the dates
dates = []
last_finish = 1000-01-01
cells = [ x.decode('utf-8').strip() for x in cells ]
while cells:
    (name, start, finish) = cells[0:3]
    del cells[0:3]
    if not name or start == 'to be confirmed': continue
    # print "%s: %s to %s" % (name, start, finish)
    start = (mx.DateTime.strptime(start, "%e %b %Y")+1).date
    finish = (mx.DateTime.strptime(finish, "%e %b %Y")-1).date
    assert start > last_finish
    assert finish > start
    dates.append((name, start, finish)) 
    if start == today:
		domail(name + " starts", "%s of parliament starts today %s, ends %s\n" % (name, start, finish))
    if finish == today:
        domail(name + " ends", "%s of parliament ends today %s\n" % (name, finish))

# "dates" now contains a list of all the periods of recess

enddates = map(lambda x: max(x[1], x[2]), dates)
max_date = reduce(max, enddates)
# print "max_date", max_date
if max_date < today:
	print "Parliamentary recess updater in possible trouble"
	print "Unknown when next recess is - check it isn't published elsewhere"
	print ""
	print "Today is %s, last recess ended %s" % (today, max_date)
    print "Source of data: %s" % (url)

csv.writer(open(recess_file_new, "w")).writerows(dates)
os.rename(recess_file_new, recess_file)

