#!/usr/bin/python

from common import *
from testing import *
from subprocess import call, check_call, Popen
import time
import re
import sys
from optparse import OptionParser
from BeautifulSoup import BeautifulSoup
from browser import *
import cgi

if len(sys.argv) != 2:
    print >> sys.stderr, "Usage: ./run_one_test 100_postcode.py"

check_dependencies()
setup_configuration()

test_to_run = os.path.basename(sys.argv[1])

print "running test", test_to_run

sys.path.append('tests')

create_output_directory()

__import__(test_to_run)
