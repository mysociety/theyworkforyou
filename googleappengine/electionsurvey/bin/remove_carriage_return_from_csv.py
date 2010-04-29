#!/usr/bin/python -u

# Collapses white space to single spaces in a CSV file's rows.

# Nasty script to recover from me screwing up

import csv
import sys
import re

reader = csv.reader(sys.stdin)
writer = csv.writer(sys.stdout)
for row in reader:
    row = [ re.sub(',?\s*\n\s*', ', ', col) for col in row ]
    row = [ re.sub('\s+', ' ', col) for col in row ]
    writer.writerow(row)

