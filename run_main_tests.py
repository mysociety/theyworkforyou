#!/usr/bin/python2.5

from common import *
from subprocess import call, check_call, Popen
import time
import re
import sys
from optparse import OptionParser
from BeautifulSoup import BeautifulSoup
from browser import fake_browser
import cgi

def run_main_tests(output_directory):
    check_dependencies()
    setup_configuration()

    start_all_coverage = uml_date()

    # Fetch the main page:


    # Look for class="error"
    # Look for failures to parse with BeautifulSoup


    run_http_test(output_directory,
                  "/msps/",
                  test_name="Fetching basic MSPs page",
                  test_short_name="basic-MSPs")

    run_http_test(output_directory,
                  "/mp/gordon_brown/kirkcaldy_and_cowdenbeath",
                  test_name="Fetching Gordon Brown's page",
                  test_short_name="gordon-brown")

    end_all_coverage = uml_date()

    # ========================================================================
    # Generate the coverage report:

    output_filename_all_coverage = os.path.join(output_directory,"coverage")

    coverage_data = coverage_data_between(start_all_coverage,end_all_coverage)
    fp = open(output_filename_all_coverage,"w")
    fp.write(coverage_data)
    fp.close()

    used_source_directory = os.path.join(output_directory,"mysociety")

    check_call(["mkdir","-p",used_source_directory])

    rsync_from_guest("/data/vhost/theyworkforyou.sandbox/mysociety/twfy/",
                     os.path.join(used_source_directory,"twfy"),
                     user="alice",
                     verbose=False)

    rsync_from_guest("/data/vhost/theyworkforyou.sandbox/mysociety/phplib/",
                     os.path.join(used_source_directory,"phplib"),
                     user="alice",
                     verbose=False)

    report_index_filename = os.path.join(output_directory,"report.html")
    fp = open(report_index_filename,"w")

    # Generate complete coverage report:
    coverage_report_leafname = "coverage-report"
    generate_coverage("/data/vhost/theyworkforyou.sandbox/mysociety/",
                      output_filename_all_coverage,
                      os.path.join(output_directory,coverage_report_leafname),
                      used_source_directory)

    fp.write('''<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<head>
<title>They Work For You Test Reports</title>
<style type="text/css">
%s
</style>
</head>
<body style="background-color: #ffffff">
<h2>They Work For You Test Reports</h2>
<p><a href="%s/coverage.html">Code coverage report for all tests.</a>
</p>
''' % (standard_css(),coverage_report_leafname))

    for t in all_tests:
        print "=============="
        print str(t)

        passed_colour = "#96ff81"
        failed_colour = "#ff8181"

        background_colour = passed_colour

        fp.write("<div class=\"test\" style=\"background-color: %s\">\n"%(passed_colour,))
        fp.write("<h3>%s</h3>\n" % (t.test_name,))
        fp.write("<h4>%s</h4>\n" % (t.get_id_and_short_name(),))
        fp.write("<pre>\n")
        fp.write(cgi.escape(file_to_string(os.path.join(t.test_output_directory,"info"))))
        fp.write("</pre>\n")
        if t.test_type == TEST_HTTP:
            # Generate coverage information:
            coverage_data_file = os.path.join(t.test_output_directory,"coverage")
            coverage_report_directory = os.path.join(t.test_output_directory,coverage_report_leafname)
            print "Using parameters:"
            print "coverage_data_file: "+coverage_data_file
            print "coverage_report_directory: "+coverage_report_directory
            print "used_source_directory: "+used_source_directory
            print "t.test_output_directory is: "+t.test_output_directory
            generate_coverage("/data/vhost/theyworkforyou.sandbox/mysociety/",
                              coverage_data_file,
                              coverage_report_directory,
                              used_source_directory)
            relative_url = os.path.join(os.path.join(t.get_id_and_short_name(),coverage_report_leafname),"coverage.html")
            fp.write("<p><a href=\"%s\">Code coverage for this test.</a></p>\n" % (relative_url,))
            if t.render and t.full_image_filename:
                # fp.write("<div style=\"float: right\">")
                fp.write("<div>")
                relative_full_image_filename = re.sub(re.escape(output_directory),'',t.full_image_filename)
                relative_thumbnail_image_filename = re.sub(re.escape(output_directory),'',t.thumbnail_image_filename)
                fp.write("<a href=\"%s\"><img src=\"%s\"></a>" % (relative_full_image_filename,relative_thumbnail_image_filename))
                fp.write("</div>")
        elif t.test_type == TEST_SSH:
            for s in ("stdout","stderr"):
                fp.write("<h4>%s</h4>" % (s,))
                fp.write("<div class=\"stdout_stderr\"><pre>")
                fp.write(cgi.escape(file_to_string(os.path.join(t.test_output_directory,s))))
                fp.write("</pre></div>")
        fp.write("</div>\n")

    fp.write('''</table>
</body>
</html>''')
    fp.close()

if __name__ == '__main__':
    parser = OptionParser(usage="Usage: %prog [OPTIONS]")
    parser.add_option('-o', '--output-directory', dest="output_directory",
                      help="override the default test output directory (./output/[TIMESTAMP]/)")
    options,args = parser.parse_args()
    if options.output_directory:
        output_directory = options.output_directory
    else:
        output_directory = create_output_directory() 

    check_call(["mkdir","-p",output_directory])

    run_main_tests(output_directory)
