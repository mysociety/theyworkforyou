#!/usr/bin/python2.5

import sys
import os
import re
from subprocess import check_call

def usage_and_exit():
    print "Usage: add-php-instrumentation.py [DIRECTORY]"
    sys.exit(1)

if not len(sys.argv) == 2:
    usage_and_exit()

start_directory = sys.argv[1]

if not os.path.exists(start_directory):
    print "The directory '%s' does not exist." % (start_directory,)
    sys.exit(2)

os.chdir(start_directory)

for (dirpath, dirnames, filenames) in os.walk("."):
    for filename in filenames:
        if not re.search('(?i)\.php$',filename):
            continue
        if filename == 'instrument.php':
            continue
        full_relative_filename = os.path.join(dirpath,filename)
        full_relative_filename = re.sub('^\.\/','',full_relative_filename)
        slashes = len(re.findall('/',full_relative_filename))
        prefix = slashes * "../"
        extra_line = 'include_once dirname(__FILE__).'
        extra_line += '"/'+prefix+'includes/instrument.php";'
        backup = full_relative_filename + ".backup"
        if not os.path.exists(backup):
            check_call(["cp",full_relative_filename,backup])
        # Copy the file, but if it looks like a standard PHP file
        # insert the instrumentation line as the second line.
        fp = open(backup)
        ofp = open(full_relative_filename,"w")
        file_rewritten = False
        first_line = True
        for line in fp:
            if first_line:
                if re.search('^\s*<\?(php)?\s*$',line):
                    ofp.write(line)
                    ofp.write(extra_line+"\n")
                    file_rewritten = True
                else:
                    ofp.write(line)
                first_line = False
            else:
                ofp.write(line)
        print full_relative_filename
