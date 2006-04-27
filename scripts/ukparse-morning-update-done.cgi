#!/bin/sh

# Called as a CGI script to notify that new data is
# available from ukparse.

at NOW >/dev/null 2>/dev/null <<EOF
/home/fawkes/fawkes/scripts/morningupdate
EOF

cat <<EOF
Content-Type: text/plain

TheyWorkForYou morning update job scheduled.
EOF

