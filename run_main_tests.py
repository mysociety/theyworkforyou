#!/usr/bin/python2.5

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

def add_instrumentation(www_directory):
    # Add the instrumentation:
    ssh_result = ssh("add-php-instrumentation.py "+www_directory,capture=True)
    if ssh_result.return_value != 0:
        print "stdout:\n"+ssh_result.stdout_data
        print "stderr:\n"+ssh_result.stderr_data
        raise Exception, "Instrumenting the TWFY PHP code failed."
    instrumented_files = re.split('[\r\n]+',ssh_result.stdout_data)
    # Copy over the instrument.php file:
    if 0 != scp("instrument.php",
                 www_directory+"/includes/"):
        raise Exception, "Failed to copy over the instrument.php file"
    return [ "twfy/www/"+x for x in instrumented_files if len(x.strip()) > 0 ]

def setup_coverage_directory():
    # Create a world-writable directory for coverage data:
    coverage_directory = "/home/alice/twfy-coverage/"
    if not path_exists_in_uml(coverage_directory):
        if 0 != ssh("mkdir -m 0777 "+coverage_directory):
            raise Exception, "Failed to create coverage data directory"
    # Remove any old data from that directory:
    if 0 != ssh("rm -f "+coverage_directory+"/*"):
        raise Exception, "Failed to clean the coverage data directory"

def run_main_tests():

    try:

        check_dependencies()
        setup_configuration()

        Test.instrumented_files = add_instrumentation("/data/vhost/theyworkforyou.sandbox/mysociety/twfy/www/")

        setup_coverage_directory()

        if not Test.start_all_coverage:
            Test.start_all_coverage = uml_date()

        sys.path.append('tests')
        test_basenames = [ re.sub('\\.py','',t) for t in os.listdir('tests') if re.search('\\.py$',t) ]

        for t in sorted(test_basenames):
            __import__(t)

        # More tests to write:

        # FIXME: create a new browser and try making an account:

        # FIXME: Try searching...

        # FIXME: Try adding an email alerts

        # FIXME: provoke the "send alerts" code

        # FIXME: check that an email has been received

    finally:
        Test.end_all_coverage = uml_date()

if __name__ == '__main__':
    parser = OptionParser(usage="Usage: %prog [OPTIONS]")
    parser.add_option('-o', '--output-directory', dest="output_directory",
                      help="override the default test output directory (./output/[TIMESTAMP]/)")
    options,args = parser.parse_args()
    if options.output_directory:
        output_directory = options.output_directory
    else:
        output_directory = create_output_directory()

    try:
        run_main_tests()
    except:
        handle_exception(sys.exc_info())

    output_report(instrumented_files=Test.instrumented_files)
