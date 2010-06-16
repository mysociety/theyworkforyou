#!/usr/bin/python2.6

# Use this like:
#
#  find-last-email.py alice-09396128@localhost.localdomain 'http://.*/A/[^ $]*'
#
# ... to return the last of any matches of that regular expression in
# emails to the email address.

import sys
import mailbox
import re

if len(sys.argv) != 3:
    print >> sys.stderr, "Usage: find-last-email.py [RECIPIENT] [REGEXP]"
    sys.exit(1)

recipient_address = sys.argv[1]
recipient_address_regexp = re.compile(re.escape(recipient_address))

message_regexp_string = sys.argv[2]
message_regexp = re.compile(message_regexp_string)

fp = open('/var/spool/mail/alice')
mb = mailbox.PortableUnixMailbox(fp)

last_match = None

for m in mb:
    current_recipient = m.get('To')
    if recipient_address_regexp.search(current_recipient):
        for line in m.fp:
            line = line.strip()
            current_match = message_regexp.search(line)
            if current_match:
                last_match = current_match

if last_match:
    print last_match.group(0)
    sys.exit(0)
else:
    sys.exit(2)
