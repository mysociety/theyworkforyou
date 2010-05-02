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
import email
import time

parser = optparse.OptionParser()
parser.set_usage(''' ''')
parser.add_option('--incsv', type='string', dest="incsv", help='CSV file to use')
(options, args) = parser.parse_args()

assert options.incsv
reader = csv.reader(open(options.incsv, "rb"))
s = smtplib.SMTP('localhost')
c = 0
for row in reader:
    (name, to_email, url, seat) = row
    name = name.decode('utf-8')
    seat = seat.decode('utf-8')

    subject = "Last chance to tell local voters your views!"
    body = '''%s, 
As we enter the final few days of the campaigns
many voters are still undecided. 

Thousands of voters in %s constituency will see
the results of this MP candidate survey, which
lets you pin your political colours to the mast.

The questions are non-partisan, gathered by local
volunteers in the constituency and by famous MP
tracking website TheyWorkForYou.

It'll only take you a few minutes. Click on this
link.

%s

Best wishes,

TheyWorkForYou election team''' % (name, seat, url)

    msg = email.mime.text.MIMEText(body.encode('UTF-8'), 'plain', 'UTF-8')
    msg['Subject'] = subject
    msg['From'] = "TheyWorkForYou <election@theyworkforyou.com>"
    # code to handle accents from http://mg.pov.lt/blog/unicode-emails-in-python.html
    mail_encoded_name = str(email.Header.Header(unicode(name), 'UTF-8'))
    msg['To'] = email.utils.formataddr((mail_encoded_name, to_email))

    c = c + 1
    print str(c) + " Sending reminder email to " + name.encode("utf-8") + " " + to_email + " (" + seat.encode("utf-8") + ") " + url
    s.sendmail("election@theyworkforyou.com", [to_email], msg.as_string())

    # so we don't totally overflood
    time.sleep(0.5)

s.quit()





