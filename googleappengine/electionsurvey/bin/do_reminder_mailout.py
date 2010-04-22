#!/usr/bin/python2.5 -u
# coding=utf-8

#
# do-reminder_mailout.py:
# Actually send reminder
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import csv
import optparse
import smtplib
import email.utils
import email.mime.text
import time

parser = optparse.OptionParser()
parser.set_usage(''' ''')
parser.add_option('--incsv', type='string', dest="incsv", help='CSV file to use')
(options, args) = parser.parse_args()

assert options.incsv
reader = csv.reader(open(options.incsv, "rb"))
s = smtplib.SMTP('localhost')
for row in reader:
    (name, to_email, url, seat) = row
    name = name.decode('utf-8')
    seat = seat.decode('utf-8')

    subject = "Local voters ask for your views on local and national issues"
    body = '''%s, 

Thousands of voters in %s constituency will see
the results of this MP candidate survey.

The questions are non-partisan, gathered by local
volunteers in the constituency and by famous MP
tracking website TheyWorkForYou.

It'll only take you a few minutes. Click on this
link, and then fill in the multi-choice questions.

%s

Best wishes,

TheyWorkForYou election team''' % (name, seat, url)

    msg = email.mime.text.MIMEText(body)
    msg['Subject'] = subject
    msg['From'] = "TheyWorkForYou <election@theyworkforyou.com>"
    msg['To'] = email.utils.formataddr((name, to_email))

    print "Sending reminder email to " + to_email + " seat: " + seat.encode("utf-8") + " name: " + name.encode("utf-8")
    s.sendmail("election@theyworkforyou.com", [to_email], msg.as_string())

    # so we don't totally overflood
    time.sleep(0.1)

s.quit()





